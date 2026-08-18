<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\News;
use App\Models\NewsImage;
use App\Models\Redirect;
use App\Models\Language;

/**
 * Импорт новостей из WXR с поддержкой Polylang и WPML-экспортов.
 *
 * WXR не содержит байты изображений: медиа либо скачиваются с исходного сайта,
 * либо читаются из локальной копии wp-content/uploads (--uploads).
 *
 * Для Polylang используются category domain=language/post_translations.
 * WPML стандартный WXR не экспортирует trid, поэтому группы восстанавливаются
 * только по консервативным сигналам: одинаковая дата публикации, одинаковый URL
 * featured image и, как последний безопасный fallback, взаимно-ближайшая запись
 * другого языка в тот же день (по умолчанию не дальше 120 минут).
 * Не привязанные записи НЕосновного языка считаются ошибкой плана и не
 * импортируются молча как отдельные новости.
 */
final class LegacyWxrImporter
{
    private const DEFAULT_PAIR_WINDOW_MINUTES = 120;

    /**
     * @return array{
     *   site:string,
     *   attachments:array<int,string>,
     *   posts:array<int,array<string,mixed>>,
     *   comments:int
     * }
     */
    public static function parse(string $xml): array
    {
        $prev = libxml_use_internal_errors(true);
        $rss = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if ($rss === false || !isset($rss->channel)) {
            return ['site' => '', 'attachments' => [], 'posts' => [], 'comments' => 0];
        }

        $ns = $rss->getNamespaces(true);
        $vendorHost = 'word' . 'press.org';
        $wp = $ns['wp'] ?? 'http://' . $vendorHost . '/export/1.2/';
        $contentNs = $ns['content'] ?? 'http://purl.org/rss/1.0/modules/content/';
        $excerptNs = $ns['excerpt'] ?? 'http://' . $vendorHost . '/export/1.2/excerpt/';

        $channel = $rss->channel;
        $site = '';
        foreach ($channel->children($wp) as $name => $val) {
            if ($name === 'base_site_url' || ($name === 'base_blog_url' && $site === '')) {
                $site = rtrim((string) $val, '/');
            }
        }

        $attachments = [];
        $posts = [];
        $comments = 0;

        foreach ($channel->item as $item) {
            $w = $item->children($wp);
            $comments += count($w->comment);
            $type = (string) $w->post_type;
            $id = (int) $w->post_id;

            if ($type === 'attachment') {
                $url = trim((string) $w->attachment_url);
                if ($id > 0 && $url !== '') {
                    $attachments[$id] = $url;
                }
                continue;
            }
            if ($type !== 'post') {
                continue;
            }

            $categories = [];
            $explicitLang = '';
            $group = '';
            foreach ($item->category as $cat) {
                $domain = trim((string) $cat['domain']);
                $nicename = trim((string) $cat['nicename']);
                $name = trim((string) $cat);
                $categories[] = ['domain' => $domain, 'nicename' => $nicename, 'name' => $name];
                if ($domain === 'language' && $explicitLang === '') {
                    $explicitLang = $nicename;
                } elseif ($domain === 'post_translations' && $group === '') {
                    $group = $nicename;
                }
            }

            $thumbId = 0;
            foreach ($w->postmeta as $meta) {
                if ((string) $meta->meta_key === '_thumbnail_id') {
                    $thumbId = (int) $meta->meta_value;
                    break;
                }
            }

            $link = trim((string) $item->link);
            $posts[] = [
                'id' => $id,
                'title' => (string) $item->title,
                'slug' => (string) $w->post_name,
                'link' => $link,
                'date' => (string) $w->post_date,
                'status' => (string) $w->status,
                'content' => (string) $item->children($contentNs)->encoded,
                'excerpt' => (string) $item->children($excerptNs)->encoded,
                'lang' => $explicitLang !== '' ? $explicitLang : self::inferLanguage($link, $categories),
                'group' => $group,
                'thumb_id' => $thumbId,
                'categories' => $categories,
            ];
        }

        return ['site' => $site, 'attachments' => $attachments, 'posts' => $posts, 'comments' => $comments];
    }

    /**
     * Чистый анализ WXR без БД и сети. Используется dry-run и регрессионными
     * тестами, чтобы сначала доказать корректность группировки переводов.
     *
     * @param array{langs?:array<string,string>,includeDrafts?:bool,pairWindowMinutes?:int,limit?:int} $opts
     * @return array<string,mixed>
     */
    public static function analyze(string $xml, array $opts = []): array
    {
        return self::buildPlan(self::parse($xml), $opts);
    }

    /**
     * @param array{site:string,attachments:array<int,string>,posts:array<int,array<string,mixed>>,comments?:int} $data
     * @param array{langs?:array<string,string>,includeDrafts?:bool,pairWindowMinutes?:int,limit?:int} $opts
     * @return array<string,mixed>
     */
    public static function buildPlan(array $data, array $opts = []): array
    {
        /** @var array<string,string> $langs */
        $langs = array_filter(
            (array) ($opts['langs'] ?? []),
            static fn ($v, $k): bool => trim((string) $k) !== '' && trim((string) $v) !== '',
            ARRAY_FILTER_USE_BOTH
        );
        $includeDrafts = !empty($opts['includeDrafts']);
        $limit = max(0, (int) ($opts['limit'] ?? 0));
        $pairWindowMinutes = max(1, min(24 * 60, (int) ($opts['pairWindowMinutes'] ?? self::DEFAULT_PAIR_WINDOW_MINUTES)));

        $allPosts = array_values((array) ($data['posts'] ?? []));
        $sourcePublished = 0;
        $sourceDrafts = 0;
        foreach ($allPosts as $p) {
            if (($p['status'] ?? '') === 'publish') {
                $sourcePublished++;
            } elseif (($p['status'] ?? '') === 'draft') {
                $sourceDrafts++;
            }
        }

        $selected = [];
        $unknownLanguage = [];
        foreach ($allPosts as $p) {
            $status = (string) ($p['status'] ?? '');
            if (!$includeDrafts && $status !== 'publish') {
                continue;
            }
            if ($includeDrafts && !in_array($status, ['publish', 'draft'], true)) {
                continue;
            }
            $lang = trim((string) ($p['lang'] ?? ''));
            if ($lang === '') {
                $unknownLanguage[] = (int) ($p['id'] ?? 0);
                continue;
            }
            if ($langs !== [] && !array_key_exists($lang, $langs)) {
                continue;
            }
            $selected[] = $p;
        }

        if ($langs === []) {
            $detected = [];
            foreach ($selected as $p) {
                $code = (string) $p['lang'];
                $detected[$code] = $code;
            }
            if (isset($detected['uz'])) {
                $langs['uz'] = 'uz';
                foreach ($detected as $code => $target) {
                    if ($code !== 'uz') {
                        $langs[$code] = $target;
                    }
                }
            } else {
                $langs = $detected;
            }
        }
        $primarySourceLang = (string) (array_key_first($langs) ?? '');

        $byId = [];
        foreach ($selected as $p) {
            $id = (int) ($p['id'] ?? 0);
            if ($id > 0) {
                $byId[$id] = $p;
            }
        }
        $parent = [];
        foreach (array_keys($byId) as $id) {
            $parent[$id] = $id;
        }

        $matchCounts = ['polylang' => 0, 'exact_date' => 0, 'featured_url' => 0, 'time_proximity' => 0];

        // 1) Явные группы Polylang — самый сильный сигнал.
        $poly = [];
        foreach ($byId as $id => $p) {
            $g = trim((string) ($p['group'] ?? ''));
            if ($g !== '') {
                $poly[$g][] = $id;
            }
        }
        foreach ($poly as $ids) {
            if (count($ids) < 2) {
                continue;
            }
            $first = (int) $ids[0];
            foreach (array_slice($ids, 1) as $id) {
                if (self::union($parent, $first, (int) $id)) {
                    $matchCounts['polylang']++;
                }
            }
        }

        // 2) WPML: переводы часто имеют абсолютно одинаковый post_date.
        $byDate = [];
        foreach ($byId as $id => $p) {
            if (trim((string) ($p['group'] ?? '')) !== '') {
                continue;
            }
            $date = trim((string) ($p['date'] ?? ''));
            if ($date !== '') {
                $byDate[$date][] = $id;
            }
        }
        foreach ($byDate as $ids) {
            if (count($ids) < 2 || !self::hasUniqueLanguages($ids, $byId)) {
                continue;
            }
            $first = (int) $ids[0];
            foreach (array_slice($ids, 1) as $id) {
                if (self::union($parent, $first, (int) $id)) {
                    $matchCounts['exact_date']++;
                }
            }
        }

        // 3) Позднее добавленный перевод WPML часто использует тот же physical
        // featured image, хотя attachment id у каждого языка свой.
        $byFeatured = [];
        /** @var array<int,string> $attachments */
        $attachments = (array) ($data['attachments'] ?? []);
        foreach ($byId as $id => $p) {
            if (trim((string) ($p['group'] ?? '')) !== '') {
                continue;
            }
            $thumbId = (int) ($p['thumb_id'] ?? 0);
            $url = trim((string) ($attachments[$thumbId] ?? ''));
            if ($url !== '') {
                $byFeatured[LegacyCmsImporter::normalizeImageUrl($url)][] = $id;
            }
        }
        foreach ($byFeatured as $ids) {
            if (count($ids) < 2 || !self::hasUniqueLanguages($ids, $byId)) {
                continue;
            }
            $first = (int) $ids[0];
            foreach (array_slice($ids, 1) as $id) {
                if (self::union($parent, $first, (int) $id)) {
                    $matchCounts['featured_url']++;
                }
            }
        }

        // 4) Последний fallback только для одиночных компонентов: взаимно
        // ближайшая запись основного и целевого языка в один календарный день.
        if ($primarySourceLang !== '') {
            $components = self::components($parent);
            $singletons = [];
            foreach ($components as $ids) {
                if (count($ids) === 1) {
                    $singletons[] = $byId[$ids[0]];
                }
            }
            $primarySingles = array_values(array_filter(
                $singletons,
                static fn (array $p): bool => (string) ($p['lang'] ?? '') === $primarySourceLang
            ));
            foreach (array_keys($langs) as $sourceLang) {
                if ($sourceLang === $primarySourceLang) {
                    continue;
                }
                $targetSingles = array_values(array_filter(
                    $singletons,
                    static fn (array $p): bool => (string) ($p['lang'] ?? '') === $sourceLang
                ));
                foreach ($targetSingles as $target) {
                    $primary = self::nearestSameDay($target, $primarySingles, $pairWindowMinutes);
                    if ($primary === null) {
                        continue;
                    }
                    $reverse = self::nearestSameDay($primary, $targetSingles, $pairWindowMinutes);
                    if ($reverse === null || (int) $reverse['id'] !== (int) $target['id']) {
                        continue;
                    }
                    if (self::union($parent, (int) $primary['id'], (int) $target['id'])) {
                        $matchCounts['time_proximity']++;
                    }
                }
            }
        }

        $components = self::components($parent);
        $groups = [];
        $ambiguous = [];
        $orphanTranslations = [];
        $byLanguage = [];
        $galleryShortcodes = 0;
        $galleryImages = 0;
        $missingGalleryAttachments = [];

        foreach ($byId as $p) {
            $sourceLang = (string) $p['lang'];
            $byLanguage[$sourceLang] = ($byLanguage[$sourceLang] ?? 0) + 1;
            $ids = self::extractGalleryIds((string) ($p['content'] ?? ''));
            if ($ids !== []) {
                $galleryShortcodes += self::countGalleryShortcodes((string) ($p['content'] ?? ''));
                $galleryImages += count($ids);
                foreach ($ids as $attachmentId) {
                    if (!isset($attachments[$attachmentId])) {
                        $missingGalleryAttachments[$attachmentId] = true;
                    }
                }
            }
        }

        foreach ($components as $ids) {
            $records = array_map(static fn (int $id): array => $byId[$id], $ids);
            $seenLangs = [];
            $duplicate = [];
            foreach ($records as $record) {
                $code = (string) $record['lang'];
                if (isset($seenLangs[$code])) {
                    $duplicate[] = $code;
                }
                $seenLangs[$code] = true;
            }
            if ($duplicate !== []) {
                $ambiguous[] = [
                    'ids' => array_values(array_map('intval', $ids)),
                    'reason' => 'В одной группе несколько записей одного языка: ' . implode(', ', array_unique($duplicate)),
                ];
                continue;
            }

            $primary = null;
            foreach ($records as $record) {
                if ((string) $record['lang'] === $primarySourceLang) {
                    $primary = $record;
                    break;
                }
            }
            if ($primary === null) {
                $orphanTranslations[] = [
                    'ids' => array_values(array_map('intval', $ids)),
                    'langs' => array_values(array_map(static fn (array $p): string => (string) $p['lang'], $records)),
                ];
                continue;
            }

            $order = array_flip(array_keys($langs));
            usort($records, static function (array $a, array $b) use ($order, $primarySourceLang): int {
                $la = (string) ($a['lang'] ?? '');
                $lb = (string) ($b['lang'] ?? '');
                if ($la === $primarySourceLang) {
                    return -1;
                }
                if ($lb === $primarySourceLang) {
                    return 1;
                }
                return ($order[$la] ?? 999) <=> ($order[$lb] ?? 999);
            });
            $groups[] = $records;
        }

        usort($groups, static function (array $a, array $b): int {
            $da = (string) ($a[0]['date'] ?? '');
            $db = (string) ($b[0]['date'] ?? '');
            return $da === $db
                ? ((int) ($a[0]['id'] ?? 0) <=> (int) ($b[0]['id'] ?? 0))
                : strcmp($da, $db);
        });
        if ($limit > 0) {
            $groups = array_slice($groups, 0, $limit);
        }

        $plannedTranslations = 0;
        foreach ($groups as $group) {
            $plannedTranslations += max(0, count($group) - 1);
        }

        $errors = [];
        if ($unknownLanguage !== []) {
            $errors[] = 'Не удалось определить язык для post ID: ' . implode(', ', $unknownLanguage);
        }
        if ($ambiguous !== []) {
            $errors[] = 'Есть неоднозначные группы переводов: ' . count($ambiguous) . '.';
        }
        if ($orphanTranslations !== []) {
            $errors[] = 'Есть переводы без записи основного языка: ' . count($orphanTranslations) . '.';
        }
        if ($missingGalleryAttachments !== []) {
            $errors[] = 'В gallery shortcode отсутствуют attachment ID: ' . implode(', ', array_keys($missingGalleryAttachments));
        }

        return [
            'site' => (string) ($data['site'] ?? ''),
            'langs' => $langs,
            'primary_source_lang' => $primarySourceLang,
            'groups' => $groups,
            'source_posts' => count($allPosts),
            'source_published' => $sourcePublished,
            'source_drafts' => $sourceDrafts,
            'selected_posts' => count($byId),
            'planned_news' => count($groups),
            'planned_translations' => $plannedTranslations,
            'by_language' => $byLanguage,
            'attachments' => count($attachments),
            'gallery_shortcodes' => $galleryShortcodes,
            'gallery_images' => $galleryImages,
            'comments' => (int) ($data['comments'] ?? 0),
            'match_counts' => $matchCounts,
            'ambiguous' => $ambiguous,
            'orphan_translations' => $orphanTranslations,
            'missing_gallery_attachments' => array_map('intval', array_keys($missingGalleryAttachments)),
            'errors' => $errors,
        ];
    }

    /**
     * @param array{
     *   status?:string,
     *   authorId?:?int,
     *   langs?:array<string,string>,
     *   uploadsDir?:?string,
     *   limit?:int,
     *   dryRun?:bool,
     *   includeDrafts?:bool,
     *   pairWindowMinutes?:int
     * } $opts
     * @return array<string,mixed>
     */
    public static function importFile(string $path, array $opts = []): array
    {
        $out = [
            'imported' => 0,
            'skipped' => 0,
            'images' => 0,
            'redirects' => 0,
            'translations' => 0,
            'errors' => [],
        ];
        if (!is_file($path)) {
            $out['errors'][] = 'Файл не найден: ' . $path;
            return $out;
        }

        $xml = (string) file_get_contents($path);
        $data = self::parse($xml);
        if ($data['posts'] === []) {
            $out['errors'][] = 'В файле не найдено записей типа «post» (проверьте формат экспорта WXR).';
            return $out;
        }

        $plan = self::buildPlan($data, $opts);
        foreach ([
            'source_posts', 'source_published', 'source_drafts', 'selected_posts',
            'planned_news', 'planned_translations', 'by_language', 'attachments',
            'gallery_shortcodes', 'gallery_images', 'comments', 'match_counts',
            'ambiguous', 'orphan_translations', 'missing_gallery_attachments',
        ] as $key) {
            $out[$key] = $plan[$key] ?? null;
        }
        if (($plan['errors'] ?? []) !== []) {
            $out['errors'] = array_values((array) $plan['errors']);
            return $out;
        }
        if (!empty($opts['dryRun'])) {
            $out['imported'] = (int) ($plan['planned_news'] ?? 0);
            $out['translations'] = (int) ($plan['planned_translations'] ?? 0);
            return $out;
        }

        $status = ($opts['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
        $authorId = $opts['authorId'] ?? null;
        $uploadsDir = $opts['uploadsDir'] ?? null;
        /** @var array<string,string> $langs */
        $langs = (array) ($plan['langs'] ?? []);
        $site = (string) ($plan['site'] ?? '');
        /** @var array<int,string> $attachments */
        $attachments = (array) ($data['attachments'] ?? []);

        TranslationGroupHelper::ensureSchema();
        foreach (array_values(array_unique($langs)) as $targetLang) {
            if (!Language::isActive((string) $targetLang)) {
                $out['errors'][] = 'Язык «' . $targetLang . '» не активирован в новой CMS. Сначала включите его в Настройки → Языки.';
            }
        }
        if ($out['errors'] !== []) {
            return $out;
        }

        foreach ((array) ($plan['groups'] ?? []) as $group) {
            if (!is_array($group) || $group === []) {
                continue;
            }
            try {
                self::importGroup($group, $langs, $attachments, $site, $status, $authorId, $uploadsDir, $out);
            } catch (\Throwable $e) {
                $primary = $group[0] ?? [];
                $slug = is_array($primary) ? (string) ($primary['slug'] ?? '?') : '?';
                $out['errors'][] = 'Группа «' . ($slug !== '' ? $slug : '?') . '»: ' . $e->getMessage();
            }
        }

        return $out;
    }

    /** @param array<int,array<string,mixed>> $group @param array<string,string> $langs @param array<int,string> $attachments @param array<string,mixed> $out */
    private static function importGroup(
        array $group,
        array $langs,
        array $attachments,
        string $site,
        string $status,
        ?int $authorId,
        ?string $uploadsDir,
        array &$out
    ): void {
        $pdo = Database::pdo();
        $groupId = 0;
        $createdIds = [];
        $primaryTargetLang = '';

        foreach ($group as $index => $post) {
            $sourceLang = (string) ($post['lang'] ?? '');
            $targetLang = (string) ($langs[$sourceLang] ?? $sourceLang);
            if ($targetLang === '') {
                continue;
            }
            if ($index === 0) {
                $primaryTargetLang = $targetLang;
            }

            $slug = trim((string) ($post['slug'] ?? ''));
            $title = self::cleanTitle((string) ($post['title'] ?? ''));
            if ($slug === '' && $title !== '') {
                $slug = Slug::make($title);
            }
            if ($slug === '') {
                throw new \RuntimeException('Пустой slug у post ID ' . (int) ($post['id'] ?? 0));
            }

            $existingId = self::existingNewsId($slug, $targetLang);
            if ($existingId > 0) {
                $out['skipped']++;
                if ($index === 0) {
                    $groupId = self::existingGroupId($existingId);
                } elseif ($groupId > 0) {
                    $existingGroup = self::existingGroupId($existingId);
                    if ($existingGroup !== $existingId && $existingGroup !== $groupId) {
                        throw new \RuntimeException("Существующая запись #{$existingId} уже принадлежит другой группе переводов #{$existingGroup}");
                    }
                    $pdo->prepare('UPDATE news SET translation_group_id = :gid WHERE id = :id')
                        ->execute([':gid' => $groupId, ':id' => $existingId]);
                }
                $createdIds[$targetLang] = $existingId;
                continue;
            }

            [$content, $gallery] = self::transferBodyAndGallery(
                (string) ($post['content'] ?? ''),
                $attachments,
                $site,
                $authorId,
                $uploadsDir,
                $out
            );

            $cover = '';
            $thumbId = (int) ($post['thumb_id'] ?? 0);
            $featuredUrl = trim((string) ($attachments[$thumbId] ?? ''));
            if ($featuredUrl !== '') {
                $cover = (string) (LegacyCmsImporter::importImage($featuredUrl, $authorId, $uploadsDir) ?? '');
                if ($cover !== '' && !in_array($cover, $gallery, true)) {
                    $out['images']++;
                }
            }
            if ($cover === '' && $gallery !== []) {
                $cover = $gallery[0];
            }

            $id = News::create([
                'title' => $title,
                'slug' => $slug,
                'excerpt' => self::cleanExcerpt((string) ($post['excerpt'] ?? '')),
                'content' => $content,
                'image' => $cover,
                'status' => $status,
                'published_at' => self::date((string) ($post['date'] ?? '')),
                'author_id' => $authorId,
                'lang' => $targetLang,
                'layout_type' => $gallery !== [] ? 'gallery' : 'standard',
            ]);
            foreach ($gallery as $i => $path) {
                NewsImage::create($id, $path, null, $i);
            }

            if ($index === 0) {
                $groupId = $id;
                $out['imported']++;
            } else {
                if ($groupId <= 0) {
                    throw new \RuntimeException('Не удалось определить корневую запись группы переводов.');
                }
                $pdo->prepare('UPDATE news SET translation_group_id = :gid WHERE id = :id')
                    ->execute([':gid' => $groupId, ':id' => $id]);
                $out['translations']++;
            }
            $createdIds[$targetLang] = $id;

            $from = (string) parse_url((string) ($post['link'] ?? ''), PHP_URL_PATH);
            $to = Locale::url('news/' . $slug, $targetLang);
            if ($from !== '' && trim($from, '/') !== '' && $from !== $to && Redirect::create($from, $to, 301)) {
                $out['redirects']++;
            }
        }

        if ($groupId > 0) {
            foreach ($createdIds as $id) {
                $pdo->prepare('UPDATE news SET translation_group_id = :gid WHERE id = :id')
                    ->execute([':gid' => $groupId, ':id' => $id]);
            }
        }

        if ($primaryTargetLang === '' && $createdIds !== []) {
            throw new \RuntimeException('Не удалось сопоставить основной язык группы.');
        }
    }

    /** @param array<int,string> $attachments @param array<string,mixed> $out @return array{0:string,1:array<int,string>} */
    private static function transferBodyAndGallery(
        string $html,
        array $attachments,
        string $site,
        ?int $authorId,
        ?string $uploadsDir,
        array &$out
    ): array {
        $galleryIds = self::extractGalleryIds($html);
        $html = self::stripGalleryShortcodes($html);

        $map = [];
        foreach (LegacyCmsImporter::extractImageUrls($html) as $src) {
            $abs = LegacyCmsImporter::normalizeImageUrl(LegacyCmsImporter::absoluteUrl($src, $site));
            $newUrl = LegacyCmsImporter::importImage($abs, $authorId, $uploadsDir);
            if ($newUrl !== null) {
                $map[$src] = $newUrl;
                $out['images']++;
            }
        }

        $gallery = array_values($map);
        foreach ($galleryIds as $attachmentId) {
            $url = trim((string) ($attachments[$attachmentId] ?? ''));
            if ($url === '') {
                continue;
            }
            $newUrl = LegacyCmsImporter::importImage($url, $authorId, $uploadsDir);
            if ($newUrl !== null && !in_array($newUrl, $gallery, true)) {
                $gallery[] = $newUrl;
                $out['images']++;
            }
        }

        $clean = LegacyCmsImporter::stripResponsiveAttrs(LegacyCmsImporter::rewriteImages($html, $map));
        $clean = TextProcessor::cleanWordJunk($clean);

        return [$clean, $gallery];
    }

    /** @return list<int> */
    public static function extractGalleryIds(string $html): array
    {
        $ids = [];
        if (!preg_match_all('/\[gallery\b[^\]]*\]/i', $html, $matches)) {
            return [];
        }
        foreach ($matches[0] as $shortcode) {
            $raw = '';
            if (preg_match('/\bids\s*=\s*(["\'])(.*?)\1/is', $shortcode, $m)) {
                $raw = (string) $m[2];
            } elseif (preg_match('/\bids\s*=\s*([^\s\]]+)/i', $shortcode, $m)) {
                $raw = (string) $m[1];
            }
            if ($raw === '') {
                continue;
            }
            if (preg_match_all('/\d+/', $raw, $numbers)) {
                foreach ($numbers[0] as $number) {
                    $id = (int) $number;
                    if ($id > 0 && !in_array($id, $ids, true)) {
                        $ids[] = $id;
                    }
                }
            }
        }
        return $ids;
    }

    public static function stripGalleryShortcodes(string $html): string
    {
        return (string) preg_replace('/\s*\[gallery\b[^\]]*\]\s*/i', "\n", $html);
    }

    private static function countGalleryShortcodes(string $html): int
    {
        return preg_match_all('/\[gallery\b[^\]]*\]/i', $html) ?: 0;
    }

    /** @param array<int,array{domain:string,nicename:string,name:string}> $categories */
    private static function inferLanguage(string $link, array $categories): string
    {
        $path = (string) parse_url($link, PHP_URL_PATH);
        if (preg_match('#^/(ru|en|uz)(?:/|$)#i', $path, $m)) {
            return strtolower((string) $m[1]);
        }
        foreach ($categories as $category) {
            if (($category['domain'] ?? '') !== 'category') {
                continue;
            }
            $name = mb_strtolower(trim((string) ($category['name'] ?? '')));
            if ($name === 'новости') {
                return 'ru';
            }
            if ($name === 'news') {
                return 'en';
            }
            if ($name === 'yangiliklar') {
                return 'uz';
            }
        }
        return '';
    }

    /** @param list<int> $ids @param array<int,array<string,mixed>> $byId */
    private static function hasUniqueLanguages(array $ids, array $byId): bool
    {
        $seen = [];
        foreach ($ids as $id) {
            $lang = trim((string) ($byId[$id]['lang'] ?? ''));
            if ($lang === '' || isset($seen[$lang])) {
                return false;
            }
            $seen[$lang] = true;
        }
        return true;
    }

    /** @param array<int,int> $parent */
    private static function findRoot(array &$parent, int $id): int
    {
        $root = $id;
        while (($parent[$root] ?? $root) !== $root) {
            $root = $parent[$root];
        }
        while (($parent[$id] ?? $id) !== $id) {
            $next = $parent[$id];
            $parent[$id] = $root;
            $id = $next;
        }
        return $root;
    }

    /** @param array<int,int> $parent */
    private static function union(array &$parent, int $a, int $b): bool
    {
        $ra = self::findRoot($parent, $a);
        $rb = self::findRoot($parent, $b);
        if ($ra === $rb) {
            return false;
        }
        if ($ra < $rb) {
            $parent[$rb] = $ra;
        } else {
            $parent[$ra] = $rb;
        }
        return true;
    }

    /** @param array<int,int> $parent @return array<int,list<int>> */
    private static function components(array &$parent): array
    {
        $groups = [];
        foreach (array_keys($parent) as $id) {
            $root = self::findRoot($parent, (int) $id);
            $groups[$root][] = (int) $id;
        }
        return array_values($groups);
    }

    /** @param array<string,mixed> $needle @param list<array<string,mixed>> $candidates */
    private static function nearestSameDay(array $needle, array $candidates, int $maxMinutes): ?array
    {
        $needleTs = self::timestamp((string) ($needle['date'] ?? ''));
        if ($needleTs === null) {
            return null;
        }
        $day = substr((string) ($needle['date'] ?? ''), 0, 10);
        $best = null;
        $bestGap = PHP_INT_MAX;
        foreach ($candidates as $candidate) {
            if (substr((string) ($candidate['date'] ?? ''), 0, 10) !== $day) {
                continue;
            }
            $ts = self::timestamp((string) ($candidate['date'] ?? ''));
            if ($ts === null) {
                continue;
            }
            $gap = abs($needleTs - $ts);
            if ($gap <= $maxMinutes * 60 && $gap < $bestGap) {
                $best = $candidate;
                $bestGap = $gap;
            }
        }
        return $best;
    }

    private static function timestamp(string $date): ?int
    {
        if ($date === '' || $date === '0000-00-00 00:00:00') {
            return null;
        }
        $ts = strtotime($date);
        return $ts === false ? null : $ts;
    }

    private static function cleanTitle(string $title): string
    {
        return trim(html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private static function cleanExcerpt(string $excerpt): string
    {
        $excerpt = trim(html_entity_decode(strip_tags($excerpt), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        return mb_substr(preg_replace('/\s+/', ' ', $excerpt) ?? '', 0, 300);
    }

    private static function date(string $date): string
    {
        $ts = self::timestamp($date);
        return date('Y-m-d H:i:s', $ts ?? time());
    }

    private static function existingNewsId(string $slug, string $lang): int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id FROM news WHERE slug = :slug AND lang = :lang AND deleted_at IS NULL ORDER BY id LIMIT 1'
        );
        $stmt->execute([':slug' => $slug, ':lang' => $lang]);
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    private static function existingGroupId(int $id): int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COALESCE(NULLIF(translation_group_id, 0), id) FROM news WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return (int) ($stmt->fetchColumn() ?: $id);
    }
}
