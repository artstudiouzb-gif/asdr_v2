<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\AdminListQuery;
use App\Core\AppUrl;
use App\Core\Cache;
use App\Core\ConcurrencyException;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Core\ImageField;
use App\Core\Locale;
use App\Core\RateLimiter;
use App\Core\Slug;
use App\Core\TextProcessor;
use App\Core\View;
use App\Models\Language;
use App\Models\News;
use App\Models\NewsTranslation;
use App\Models\ContentRevision;
use App\Models\Setting;

final class NewsController
{
    public function index(): void
    {
        Auth::requireLogin();
        $filters = AdminListQuery::normalize(
            $_GET,
            ['newest', 'oldest', 'title_asc', 'title_desc', 'published_desc'],
            'newest',
            true
        );
        $total = News::adminCount($filters);
        [$filters, $pages] = AdminListQuery::fitPage($filters, $total);
        View::render('admin/news/index', [
            'items' => News::adminList($filters),
            'filters' => $filters,
            'filterParams' => AdminListQuery::urlParams($filters),
            'total' => $total,
            'pages' => $pages,
            'langCounts' => News::langCounts(),
        ]);
    }

    public function duplicate(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();
        $newId = News::duplicate((int) $params['id']);
        if ($newId === null) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }
        Flash::success('Новость дублирована как черновик.');
        header('Location: /admin/news/' . $newId . '/edit');
        exit;
    }

    public function create(): void
    {
        Auth::requireLogin();
        View::render('admin/news/form', ['news' => null, 'translations' => [], 'error' => null]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        [$data, $error] = $this->collectInput(null);

        if ($error !== null) {
            View::render('admin/news/form', ['news' => $data, 'translations' => [], 'error' => $error]);
            return;
        }

        $extras = $this->collectExtras();
        [$id, $purgeAfterCommit] = Database::transaction(function (\PDO $_pdo) use ($data, $extras): array {
            $id = News::create($data);
            News::updateExtras($id, $extras);
            $this->saveTranslations($id);
            $purge = $this->handleGallery($id);
            $this->handlePollAndTimeline($id);

            return [$id, $purge];
        });
        if ($purgeAfterCommit !== []) {
            \App\Core\MediaCleaner::purgeUnreferenced($purgeAfterCommit);
        }
        Cache::forgetPrefix('page:');

        // Публикация в соцсети выполняется ТОЛЬКО при явном подтверждении админом (флажок publish_to_social).
        if ($data['status'] === 'published') {
            if (!empty($_POST['publish_to_social'])) {
                \App\Core\SocialSettings::enqueueForNews($id);
            }
            if (\App\Core\WebPush::isEnabled()) {
                \App\Models\WebPushSubscription::enqueueNews($id);
            }
            $this->dispatchNewsPublished($id, $data);
        }

        Flash::success('Новость создана.');
        header('Location: /admin/news/' . $id . '/edit?draft_saved=news%3Anew');
        exit;
    }

    public function edit(array $params): void
    {
        Auth::requireLogin();
        $news = News::findById((int) $params['id']);
        if (!$news) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $activeLang = (string) ($_GET['lang'] ?? Language::defaultCode());
        if (!Language::isActive($activeLang)) {
            $activeLang = Language::defaultCode();
        }

        View::render('admin/news/form', [
            'news' => $news,
            'translations' => NewsTranslation::forNews((int) $news['id']),
            'gallery' => \App\Models\NewsImage::forNews((int) $news['id']),
            'activeLang' => $activeLang,
            'error' => null,
        ]);
    }

    public function createTranslation(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();
        $id = (int) $params['id'];
        $targetLang = trim((string) ($_POST['target_lang'] ?? ''));
        if ($targetLang === '' || !Language::isActive($targetLang)) {
            Flash::error('Указан некорректный язык перевода.');
            header('Location: /admin/news/' . $id . '/edit');
            exit;
        }

        try {
            $newId = \App\Core\TranslationGroupHelper::createTranslation('news', $id, $targetLang);
            Flash::success("Создан отдельный пост перевода новости на язык «{$targetLang}».");
            header('Location: /admin/news/' . $newId . '/edit');
            exit;
        } catch (\Throwable $e) {
            Flash::error('Не удалось создать перевод: ' . $e->getMessage());
            header('Location: /admin/news/' . $id . '/edit');
            exit;
        }
    }

    /**
     * Предпросмотр новости до публикации (группа 5.2): рендер как на сайте,
     * но только для авторизованных, с noindex и вне кэша/sitemap.
     */
    public function preview(array $params): void
    {
        Auth::requireLogin();
        $news = News::findById((int) $params['id']);
        if (!$news) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $lang = (string) ($_GET['lang'] ?? Language::defaultCode());
        if (!Language::isActive($lang)) {
            $lang = Language::defaultCode();
        }
        $news = News::localize($news, $lang);

        View::render('site/news_show', [
            'news' => $news,
            'gallery' => \App\Models\NewsImage::forNews((int) $news['id']),
            'robotsNoindex' => true,
            'previewNotice' => true,
        ]);
    }

    public function update(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $id = (int) $params['id'];
        $news = News::findById($id);
        if (!$news) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $activeLang = (string) ($_POST['active_lang_tab'] ?? $_GET['lang'] ?? Language::defaultCode());
        if (!Language::isActive($activeLang)) {
            $activeLang = Language::defaultCode();
        }

        $expectedLockVersion = (int) ($_POST['expected_lock_version'] ?? 0);
        if (!ContentRevision::isFresh('news', $id, (string) ($_POST['expected_updated_at'] ?? ''), $expectedLockVersion)) {
            View::render('admin/news/form', [
                'news' => $news,
                'translations' => NewsTranslation::forNews($id),
                'gallery' => \App\Models\NewsImage::forNews($id),
                'activeLang' => $activeLang,
                'error' => 'Новость уже была изменена в другой вкладке или другим пользователем. Текущие данные перезагружены; восстановите локальный черновик и проверьте изменения.',
            ]);
            return;
        }

        [$data, $error] = $this->collectInput($id, $news);

        if ($error !== null) {
            View::render('admin/news/form', [
                'news' => array_merge($news, $data),
                'translations' => NewsTranslation::forNews($id),
                'activeLang' => $activeLang,
                'error' => $error,
            ]);
            return;
        }

        $wasPublished = ($news['status'] ?? '') === 'published';
        $extras = $this->collectExtras();
        $expectedVersion = (int) ($_POST['expected_lock_version'] ?? 0);
        try {
            $purgeAfterCommit = Database::transaction(function (\PDO $_pdo) use ($id, $data, $extras, $expectedVersion): array {
                ContentRevision::capture('news', $id, Auth::id());
                News::update($id, $data, $expectedVersion);
                News::updateExtras($id, $extras);
                $this->saveTranslations($id);
                $this->handlePollAndTimeline($id);

                return $this->handleGallery($id);
            });
        } catch (ConcurrencyException) {
            $news = News::findById($id) ?? $news;
            View::render('admin/news/form', [
                'news' => $news,
                'translations' => NewsTranslation::forNews($id),
                'gallery' => \App\Models\NewsImage::forNews($id),
                'activeLang' => $activeLang,
                'error' => 'Новость уже была изменена в другой вкладке или другим пользователем. Текущие данные перезагружены; восстановите локальный черновик и проверьте изменения.',
            ]);
            return;
        }
        if ($purgeAfterCommit !== []) {
            \App\Core\MediaCleaner::purgeUnreferenced($purgeAfterCommit);
        }
        Cache::forgetPrefix('page:');

        // Публикация в соцсети выполняется ТОЛЬКО при явном подтверждении админом (флажок publish_to_social).
        if ($data['status'] === 'published') {
            if (!empty($_POST['publish_to_social'])) {
                \App\Core\SocialSettings::enqueueForNews($id);
            }
            if (!$wasPublished) {
                if (\App\Core\WebPush::isEnabled()) {
                    \App\Models\WebPushSubscription::enqueueNews($id);
                }
                $this->dispatchNewsPublished($id, $data);
            }
        }

        $langParam = $activeLang !== '' ? '&lang=' . urlencode($activeLang) : '';
        Flash::success('Новость обновлена.');
        header('Location: /admin/news/' . $id . '/edit?draft_saved=news%3A' . $id . $langParam);
        exit;
    }

    /** Отправляет событие news.published в исходящие вебхуки (задача 136). */
    private function dispatchNewsPublished(int $id, array $data): void
    {
        $base = AppUrl::base();
        \App\Core\WebhookDispatcher::dispatch('news.published', [
            'id' => $id,
            'title' => (string) ($data['title'] ?? ''),
            'slug' => (string) ($data['slug'] ?? ''),
            'lang' => (string) ($data['lang'] ?? Language::defaultCode()),
            'url' => $base . Locale::url(
                'news/' . rawurlencode((string) ($data['slug'] ?? '')),
                (string) ($data['lang'] ?? Language::defaultCode())
            ),
        ]);
    }

    /** Ручная постановка новости в очередь публикации во все готовые сети. */
    public function pushSocial(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $news = News::findById((int) $params['id']);
        if (!$news) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        // Кнопка конкретной сети передаёт network; пусто — во все готовые.
        $only = trim((string) ($_POST['network'] ?? ''));
        $only = in_array($only, \App\Core\SocialPublisher::NETWORKS, true) ? $only : null;

        // Кнопка публикации в админке — осознанное действие человека, поэтому
        // публикуем и повторно. Автопубликация при сохранении новости (выше по
        // коду) остаётся идемпотентной, иначе правки плодили бы посты.
        $count = \App\Core\SocialSettings::enqueueForNews((int) $news['id'], $only, true);
        if ($count <= 0) {
            Flash::error($only !== null
                ? 'Сеть не настроена. Проверьте её в разделе «Соцсети».'
                : 'Нет настроенных соцсетей. Включите их в разделе «Соцсети».');
        } else {
            // Пытаемся отправить сразу. Что не ушло — остаётся в очереди,
            // и воркер дошлёт по расписанию.
            $res = \App\Core\SocialSettings::dispatchPendingForNews((int) $news['id'], $only);
            if (!empty($res['busy'])) {
                Flash::success("Поставлено в очередь публикации: {$count}. Воркер уже занят и отправит её по расписанию.");
            } elseif ($res['sent'] > 0 && $res['failed'] === 0) {
                Flash::success("Опубликовано в соцсети: {$res['sent']}.");
            } elseif ($res['sent'] > 0) {
                Flash::success("Опубликовано: {$res['sent']}, не удалось: {$res['failed']} — оставлено в очереди. " . implode('; ', $res['errors']));
            } elseif ($res['failed'] > 0) {
                Flash::error('Не удалось опубликовать: ' . implode('; ', $res['errors']) . '. Оставлено в очереди — воркер повторит.');
            } else {
                Flash::success("Поставлено в очередь публикации: {$count}. Отправка — по расписанию воркера.");
            }
        }
        // Из списка возвращаемся в список (с теми же фильтрами), из формы — в форму.
        if (($_POST['from'] ?? '') === 'list') {
            $query = trim((string) ($_POST['return_query'] ?? ''));
            header('Location: /admin/news' . ($query !== '' ? '?' . $query : ''));
            exit;
        }
        $activeLang = (string) ($_POST['active_lang_tab'] ?? $_GET['lang'] ?? '');
        $langParam = $activeLang !== '' ? '?lang=' . urlencode($activeLang) : '';
        header('Location: /admin/news/' . (int) $news['id'] . '/edit' . $langParam);
        exit;
    }

    private function parseDocs(array $rawDocs): array
    {
        $safeUrl = static function (string $u): string {
            return ($u !== '' && \App\Core\UrlGuard::isSafeLink($u)) ? $u : '';
        };
        $docs = [];
        foreach ($rawDocs as $doc) {
            $title = trim((string) ($doc['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $docs[] = [
                'title' => $title,
                'meta' => trim((string) ($doc['meta'] ?? '')),
                'url' => $safeUrl(trim((string) ($doc['url'] ?? ''))),
            ];
        }
        return $docs;
    }

    private function parseTimeline(string $raw): array
    {
        $events = [];
        if (trim($raw) !== '') {
            foreach (explode("\n", $raw) as $line) {
                $parts = array_map('trim', explode('|', $line));
                if (count($parts) >= 2 && $parts[1] !== '') {
                    $events[] = [
                        'date' => $parts[0] !== '' ? $parts[0] : null,
                        'title' => $parts[1],
                        'text' => $parts[2] ?? null,
                    ];
                }
            }
        }
        return $events;
    }

    private function parsePollOptions(mixed $raw): array
    {
        if (is_array($raw)) {
            return array_values(array_filter(array_map('trim', $raw), static fn (string $v): bool => $v !== ''));
        }
        $str = trim((string) $raw);
        if ($str === '') {
            return [];
        }
        return array_values(array_filter(array_map('trim', (array) preg_split('/[\r\n,]+/u', $str)), static fn (string $v): bool => $v !== ''));
    }

    /**
     * Сохраняет переводы для всех НЕ-дефолтных активных языков из полей
     * translations[<lang>][...].
     */
    private function saveTranslations(int $newsId): void
    {
        $defaultCode = Language::defaultCode();
        $activeLang = (string) ($_POST['active_lang_tab'] ?? $_GET['lang'] ?? '');
        $input = (array) ($_POST['translations'] ?? []);

        // Если форма отправлена с отдельной языковой страницы альтернативного языка
        if ($activeLang !== '' && $activeLang !== $defaultCode) {
            $t = $_POST;
            $docs = $this->parseDocs((array) ($t['docs'] ?? []));
            $timeline = $this->parseTimeline((string) ($t['timeline_raw'] ?? ''));
            $pollQuestion = trim((string) ($t['poll_question'] ?? ''));
            $pollOptions = $this->parsePollOptions($t['poll_options'] ?? '');

            NewsTranslation::upsert($newsId, $activeLang, [
                'title' => trim((string) ($t['title'] ?? '')),
                'badge' => trim((string) ($t['badge'] ?? '')),
                'excerpt' => trim((string) ($t['excerpt'] ?? '')),
                'content' => TextProcessor::process((string) ($t['content'] ?? ''), $activeLang),
                'hashtags' => trim((string) ($t['hashtags'] ?? '')),
                'key_points' => trim((string) ($t['key_points'] ?? '')),
                'event_meta' => trim((string) ($t['event_meta'] ?? '')),
                'docs' => $docs,
                'timeline_json' => $timeline,
                'poll_question' => $pollQuestion,
                'poll_options_json' => $pollOptions,
                'meta_title' => trim((string) ($t['meta_title'] ?? '')),
                'meta_description' => trim((string) ($t['meta_description'] ?? '')),
            ]);
        }

        foreach (Language::active() as $lang) {
            $code = (string) $lang['code'];
            if ($code === $defaultCode || ($activeLang !== '' && $code === $activeLang)) {
                continue;
            }
            if (!isset($input[$code])) {
                continue;
            }
            $t = (array) $input[$code];

            $docs = $this->parseDocs((array) ($t['docs'] ?? []));
            $timeline = $this->parseTimeline((string) ($t['timeline_raw'] ?? ''));
            $pollQuestion = trim((string) ($t['poll_question'] ?? ''));
            $pollOptions = $this->parsePollOptions($t['poll_options'] ?? '');

            NewsTranslation::upsert($newsId, $code, [
                'title' => trim((string) ($t['title'] ?? '')),
                'badge' => trim((string) ($t['badge'] ?? '')),
                'excerpt' => trim((string) ($t['excerpt'] ?? '')),
                'content' => TextProcessor::process((string) ($t['content'] ?? ''), $code),
                'hashtags' => trim((string) ($t['hashtags'] ?? '')),
                'key_points' => trim((string) ($t['key_points'] ?? '')),
                'event_meta' => trim((string) ($t['event_meta'] ?? '')),
                'docs' => $docs,
                'timeline_json' => $timeline,
                'poll_question' => $pollQuestion,
                'poll_options_json' => $pollOptions,
                'meta_title' => trim((string) ($t['meta_title'] ?? '')),
                'meta_description' => trim((string) ($t['meta_description'] ?? '')),
            ]);
        }
    }

    public function destroy(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        News::delete((int) $params['id']);
        Flash::success('Новость удалена.');
        header('Location: ' . AdminListQuery::returnPath('/admin/news', $_POST['return_query'] ?? ''));
        exit;
    }

    /**
     * @return array{0: array, 1: string|null}
     */
    /** Поля детальной страницы (эскиз): бейдж, тезисы, мероприятие, документы. */
    private function collectExtras(): array
    {
        return [
            'badge' => trim((string) ($_POST['badge'] ?? '')),
            'key_points' => trim((string) ($_POST['key_points'] ?? '')),
            'event_meta' => trim((string) ($_POST['event_meta'] ?? '')),
            'docs' => $this->parseDocs((array) ($_POST['docs'] ?? [])),
            'source_note' => trim((string) ($_POST['source_note'] ?? '')),
        ];
    }

    private function collectInput(?int $id, ?array $existing = null): array
    {
        $title = trim((string) ($_POST['title'] ?? ''));
        $slugInput = trim((string) ($_POST['slug'] ?? ''));
        $lang = (string) ($_POST['lang'] ?? $_GET['lang'] ?? Language::defaultCode());
        if (!Language::isActive($lang)) {
            $lang = Language::defaultCode();
        }
        $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
        // WYSIWYG-контент прогоняем через типограф/санитайзер (задача 75).
        $content = TextProcessor::process((string) ($_POST['content'] ?? ''), $lang);
        $metaTitle = trim((string) ($_POST['meta_title'] ?? ''));
        $metaDescription = trim((string) ($_POST['meta_description'] ?? ''));
        $status = (isset($_POST['publish_action']) || ($_POST['status'] ?? 'draft') === 'published') ? 'published' : 'draft';
        $publishedAtInput = trim((string) ($_POST['published_at'] ?? ''));

        $videoUrl = trim((string) ($_POST['video_url'] ?? ''));
        $audioUrl = trim((string) ($_POST['audio_url'] ?? ''));
        $audioTitle = trim((string) ($_POST['audio_title'] ?? ''));
        $hashtags = trim((string) ($_POST['hashtags'] ?? ''));
        $layoutType = in_array($_POST['layout_type'] ?? 'standard', News::LAYOUTS, true) ? (string) $_POST['layout_type'] : 'standard';
        $sidebarLayout = in_array($_POST['sidebar_layout'] ?? 'right_sidebar', ['no_sidebar', 'left_sidebar', 'right_sidebar'], true) ? (string) $_POST['sidebar_layout'] : 'right_sidebar';
        $focalX = self::clampPercent($_POST['focal_x'] ?? null);
        $focalY = self::clampPercent($_POST['focal_y'] ?? null);

        if ($title === '') {
            return [['title' => $title, 'slug' => $slugInput, 'excerpt' => $excerpt, 'content' => $content, 'status' => $status, 'sidebar_layout' => $sidebarLayout], 'Укажите заголовок новости.'];
        }

        if ($videoUrl !== '' && !\App\Core\Video::isYoutube($videoUrl)) {
            return [
                ['title' => $title, 'slug' => $slugInput, 'excerpt' => $excerpt, 'content' => $content, 'status' => $status, 'video_url' => $videoUrl, 'layout_type' => $layoutType, 'sidebar_layout' => $sidebarLayout],
                'Ссылка на видео должна быть YouTube-адресом (youtube.com/watch, youtu.be и т.п.).',
            ];
        }
        if ($audioUrl !== ''
            && (str_starts_with($audioUrl, '//') || !\App\Core\UrlGuard::isSafeMedia($audioUrl))) {
            return [
                ['title' => $title, 'slug' => $slugInput, 'excerpt' => $excerpt, 'content' => $content, 'status' => $status, 'audio_url' => $audioUrl, 'layout_type' => $layoutType, 'sidebar_layout' => $sidebarLayout],
                'Ссылка на аудио должна быть локальным путём или HTTP(S)-адресом.',
            ];
        }

        $rawSlug = $slugInput !== '' ? $slugInput : $title;
        $slug = Slug::unique(
            $rawSlug,
            static fn (string $candidate, ?int $excludeId): bool => News::slugExists($candidate, $excludeId, $lang),
            $id
        );

        $publishedAt = date('Y-m-d H:i:s');
        if ($publishedAtInput !== '') {
            $publishedAtDate = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $publishedAtInput);
            $publishedAtErrors = \DateTimeImmutable::getLastErrors();
            if ($publishedAtDate === false
                || ($publishedAtErrors !== false
                    && ($publishedAtErrors['warning_count'] > 0 || $publishedAtErrors['error_count'] > 0))) {
                return [
                    ['title' => $title, 'slug' => $slugInput, 'excerpt' => $excerpt, 'content' => $content, 'status' => $status, 'published_at' => $publishedAtInput, 'layout_type' => $layoutType, 'sidebar_layout' => $sidebarLayout],
                    'Укажите корректную дату и время публикации.',
                ];
            }
            $publishedAt = $publishedAtDate->format('Y-m-d H:i:s');
        }

        $image = ImageField::resolve('image_file', 'image_url', $existing['image'] ?? null, Auth::id());

        $timelineRaw = trim((string) ($_POST['timeline_raw'] ?? ''));
        $timelineEvents = [];
        if ($timelineRaw !== '') {
            foreach (explode("\n", $timelineRaw) as $line) {
                $parts = array_map('trim', explode('|', $line));
                if (count($parts) >= 2) {
                    $timelineEvents[] = [
                        'date' => $parts[0],
                        'title' => $parts[1],
                        'text' => $parts[2] ?? '',
                    ];
                }
            }
        }
        $timelineJson = $timelineEvents !== [] ? json_encode($timelineEvents, JSON_UNESCAPED_UNICODE) : null;

        $data = [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $excerpt !== '' ? $excerpt : null,
            'content' => $content,
            'image' => $image,
            'video_url' => $videoUrl !== '' ? $videoUrl : null,
            'audio_url' => $audioUrl !== '' ? $audioUrl : null,
            'audio_title' => $audioTitle !== '' ? $audioTitle : null,
            'hashtags' => $hashtags !== '' ? $hashtags : null,
            'timeline_json' => $timelineJson,
            'layout_type' => $layoutType,
            'sidebar_layout' => $sidebarLayout,
            'focal_x' => $focalX,
            'focal_y' => $focalY,
            'meta_title' => $metaTitle !== '' ? $metaTitle : null,
            'meta_description' => $metaDescription !== '' ? $metaDescription : null,
            'status' => $status,
            'published_at' => $publishedAt,
            'author_id' => Auth::id(),
            'lang' => $lang,
        ];

        return [$data, null];
    }

    private static function clampPercent(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $n = (int) $value;
        return max(0, min(100, $n));
    }

    /**
     * Обрабатывает галерею новости: удаление отмеченных фото, правку alt/порядка/
     * фокальной точки существующих и загрузку новых файлов (news_gallery[]).
     */
    /** @return list<string> пути, которые можно удалить только после COMMIT */
    private function handleGallery(int $newsId): array
    {
        $purgeAfterCommit = [];
        // 1. Правки/удаления существующих.
        foreach ((array) ($_POST['gallery'] ?? []) as $imgId => $meta) {
            $imgId = (int) $imgId;
            if ($imgId <= 0) {
                continue;
            }
            if (!empty($meta['delete'])) {
                $path = \App\Models\NewsImage::delete($imgId, $newsId);
                if ($path !== null) {
                    $purgeAfterCommit[] = $path;
                }
                continue;
            }
            \App\Models\NewsImage::updateMeta(
                $imgId,
                $newsId,
                trim((string) ($meta['alt'] ?? '')) ?: null,
                (int) ($meta['sort'] ?? 0),
                self::clampPercent($meta['focal_x'] ?? null),
                self::clampPercent($meta['focal_y'] ?? null)
            );
        }

        // 2. Новые загрузки (множественный input news_gallery[]).
        if (empty($_FILES['news_gallery']) || !is_array($_FILES['news_gallery']['name'] ?? null)) {
            return $purgeAfterCommit;
        }

        $existing = \App\Models\NewsImage::forNews($newsId);
        $sort = count($existing);

        $names = $_FILES['news_gallery']['name'];
        foreach ($names as $i => $name) {
            if (($_FILES['news_gallery']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }
            $single = [
                'name' => $_FILES['news_gallery']['name'][$i],
                'type' => $_FILES['news_gallery']['type'][$i],
                'tmp_name' => $_FILES['news_gallery']['tmp_name'][$i],
                'error' => $_FILES['news_gallery']['error'][$i],
                'size' => $_FILES['news_gallery']['size'][$i],
            ];
            try {
                $file = \App\Core\Uploader::store($single, 'public', Auth::id());
                \App\Models\NewsImage::create($newsId, \App\Models\FileEntry::publicUrl($file), null, $sort++);
            } catch (\Throwable $e) {
                Flash::error('Не удалось загрузить фото галереи: ' . $e->getMessage());
            }
        }

        return $purgeAfterCommit;
    }

    private function handlePollAndTimeline(int $newsId): void
    {
        $pollQuestion = trim((string) ($_POST['poll_question'] ?? ''));
        $pollOptionsRaw = trim((string) ($_POST['poll_options'] ?? ''));
        $pollOptions = array_values(array_filter(array_map('trim', (array) preg_split('/[\r\n,]+/u', $pollOptionsRaw)), static fn ($v) => $v !== ''));
        \App\Models\NewsPoll::saveForNews($newsId, $pollQuestion, $pollOptions);
    }

    public function generateAiSummary(): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();
        header('Content-Type: application/json; charset=utf-8');

        $limitKey = (string) Auth::id() . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        if (!RateLimiter::throttle('admin_ai_summary', $limitKey, 10, 10, false)) {
            http_response_code(429);
            header('Retry-After: 600');
            echo json_encode(['ok' => false, 'error' => 'Слишком много запросов. Повторите позже.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $content = trim((string) ($_POST['content'] ?? ''));
        $title = trim((string) ($_POST['title'] ?? ''));

        \App\Models\ErrorLog::record(
            'INFO',
            'ИИ-Аннотация: Запрос получен. Заголовок: "' . mb_substr($title, 0, 60) . '...", Длина текста: ' . mb_strlen($content) . ' симв.',
            __FILE__,
            __LINE__
        );

        if ($content === '' && $title === '') {
            \App\Models\ErrorLog::record('WARNING', 'ИИ-Аннотация: Заголовок и текст новости пустые.', __FILE__, __LINE__);
            echo json_encode(['ok' => false, 'error' => 'Текст новости и заголовок пустые.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $cleanText = strip_tags($content);
        $cleanText = html_entity_decode($cleanText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $cleanText = (string) preg_replace('/\s+/u', ' ', $cleanText);

        // Если задан ключ API Google Gemini — используем ИИ Gemini
        $apiKey = Setting::get('ai_api_key', '');

        if ($apiKey !== '') {
            $models = ['gemini-2.0-flash', 'gemini-1.5-flash', 'gemini-1.5-pro'];
            $prompt = "Проанализируй текст новости. Сделай краткое аннотированное резюме на том же языке новости (1-2 предложения, максимум 250 символов) и выдели 5 тематических хештегов (#тег1 #тег2).\n\nЗаголовок: {$title}\nТекст: {$cleanText}\n\nВерни ответ строго в формате JSON без разметки markdown:\n{\"excerpt\": \"...\", \"hashtags\": \"...\"}";

            foreach ($models as $model) {
                foreach (['v1beta', 'v1'] as $apiVersion) {
                    $url = 'https://generativelanguage.googleapis.com/' . $apiVersion . '/models/' . $model . ':generateContent?key=' . rawurlencode($apiKey);
                    $res = \App\Core\Http::postJson($url, [
                        'contents' => [
                            ['parts' => [['text' => $prompt]]]
                        ]
                    ], [], 15);

                    if ($res['status'] === 200 && !empty($res['body'])) {
                        $geminiData = json_decode((string) $res['body'], true);
                        $rawResponse = (string) ($geminiData['candidates'][0]['content']['parts'][0]['text'] ?? '');
                        
                        $cleanJson = preg_replace('/```(?:json)?/i', '', $rawResponse);
                        $cleanJson = str_replace('```', '', (string) $cleanJson);
                        $cleanJson = trim($cleanJson);

                        if (preg_match('/\{[\s\S]*\}/', $cleanJson, $match)) {
                            $parsed = json_decode($match[0], true);
                            if (is_array($parsed) && (!empty($parsed['excerpt']) || !empty($parsed['hashtags']))) {
                                \App\Models\ErrorLog::record(
                                    'INFO',
                                    'ИИ-Аннотация: Успешно сгенерировано через Gemini API (' . $model . '). Резюме: ' . mb_strlen((string) ($parsed['excerpt'] ?? '')) . ' симв.',
                                    __FILE__,
                                    __LINE__
                                );

                                echo json_encode([
                                    'ok' => true,
                                    'excerpt' => trim((string) ($parsed['excerpt'] ?? '')),
                                    'hashtags' => trim((string) ($parsed['hashtags'] ?? '')),
                                ], JSON_UNESCAPED_UNICODE);
                                return;
                            }
                        }
                    } else {
                        $errObj = json_decode((string) ($res['body'] ?? ''), true);
                        $errMsg = $errObj['error']['message'] ?? ($res['error'] ?: ('HTTP Code ' . $res['status']));
                        \App\Models\ErrorLog::record(
                            'WARNING',
                            'ИИ-Аннотация [Gemini API ' . $apiVersion . ' Error] ' . $model . ': ' . $errMsg,
                            __FILE__,
                            __LINE__
                        );
                    }
                }
            }
        } else {
            \App\Models\ErrorLog::record('INFO', 'ИИ-Аннотация: Ключ Gemini API не задан. Используется локальный генератор NLP.', __FILE__, __LINE__);
        }

        // Локальный алгоритм-фолбэк (NLP)
        $sentences = array_values(array_filter(array_map('trim', (array) preg_split('/(?<=[.!?])\s+/u', $cleanText)), static fn ($s) => mb_strlen($s) > 15));
        $excerpt = implode(' ', array_slice($sentences, 0, 2));
        if (mb_strlen($excerpt) > 280) {
            $excerpt = mb_substr($excerpt, 0, 277) . '...';
        }

        $words = (array) preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($title . ' ' . $cleanText));
        $stopwords = ['и', 'в', 'на', 'с', 'по', 'для', 'о', 'об', 'из', 'к', 'от', 'за', 'при', 'до', 'без', 'что', 'как', 'это', 'был', 'была', 'были', 'быть', 'будет', 'года', 'году', 'узбекистан', 'ташкент'];
        $freq = [];
        foreach ($words as $w) {
            $w = (string) $w;
            if (mb_strlen($w) >= 4 && !in_array($w, $stopwords, true)) {
                $freq[$w] = ($freq[$w] ?? 0) + 1;
            }
        }
        arsort($freq);
        $topWords = array_slice(array_keys($freq), 0, 5);
        $hashtags = implode(' ', array_map(static fn ($w) => '#' . $w, $topWords));

        \App\Models\ErrorLog::record('INFO', 'ИИ-Аннотация: Сгенерировано локальным NLP-алгоритмом.', __FILE__, __LINE__);

        echo json_encode([
            'ok' => true,
            'excerpt' => $excerpt,
            'hashtags' => $hashtags,
        ], JSON_UNESCAPED_UNICODE);
    }
}
