<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Демо-наполнение сайта государственного агентства: главная, проекты, медиа,
 * новости, документы, вакансии, тендеры, руководство, формы и типовые страницы.
 * Идемпотентно — записи создаются только если их ещё нет (по slug/url), а уже
 * настроенные страницы и меню не изменяются.
 */
final class DemoSeeder
{
    private const DEMO_VERSION = '2026.07-v3';

    /** @return array<string,int> счётчики добавленного по разделам */
    public static function run(PDO $pdo): array
    {
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $c = [
                'assets' => 0,
                'home' => 0,
                'news' => 0,
                'documenty' => 0,
                'vakansii' => 0,
                'tendery' => 0,
                'meropriyatiya' => 0,
                'projects' => 0,
                'albums' => 0,
                'videos' => 0,
                'forms' => 0,
                'team' => 0,
                'pages' => 0,
                'menu' => 0,
            ];

            self::seedAssets($pdo, $c);
            self::seedNews($pdo, $c);
            self::seedEntries($pdo, $c);
            self::seedProjects($pdo, $c);
            self::seedMedia($pdo, $c);
            self::seedForms($pdo, $c);
            self::seedTeam($pdo, $c);
            self::seedHome($pdo, $c);
            self::seedPages($pdo, $c);
            self::seedMenu($pdo, $c);
            self::storeVersion($pdo);

            if ($ownsTransaction) {
                $pdo->commit();
            }

            return $c;
        } catch (\Throwable $e) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Выполняет полный сброс разделов (очистку контента) и загрузку эталонного комплекта «с чистого листа».
     * @return array<string,int>
     */
    public static function resetAndRun(PDO $pdo): array
    {
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        // Дочерние таблицы идут раньше родительских. Это позволяет выполнить
        // сброс без отключения FOREIGN_KEY_CHECKS и не скрывать реальные ошибки.
        $tables = [
            'news_poll_votes',
            'news_polls',
            'news_images',
            'news_translations',
            'social_posts',
            'news_views',
            'news',
            'block_revisions',
            'blocks',
            'page_translations',
            'pages',
            'content_entry_translations',
            'content_entries',
            'project_images',
            'project_fields',
            'project_translations',
            'projects',
            'photo_album_images',
            'photo_album_translations',
            'photo_albums',
            'video_translations',
            'videos',
            'form_submissions',
            'forms',
            'team_member_translations',
            'team_members',
            'menu_items',
            'content_revisions',
        ];

        try {
            foreach ($tables as $t) {
                if (self::tableExists($pdo, $t)) {
                    $pdo->exec('DELETE FROM `' . $t . '`');
                }
            }

            // Медиабиблиотеку пользователя не очищаем целиком: удаляем только
            // записи файлов, которыми управляет этот демо-комплект.
            if (self::tableExists($pdo, 'files')) {
                $assetDir = \dirname(__DIR__, 2) . '/database/demo_assets';
                $demoNames = array_map('basename', glob($assetDir . '/*.jpg') ?: []);
                if ($demoNames !== []) {
                    $placeholders = implode(',', array_fill(0, count($demoNames), '?'));
                    $deleteDemoFiles = $pdo->prepare(
                        "DELETE FROM files WHERE stored_name IN ({$placeholders})"
                    );
                    $deleteDemoFiles->execute($demoNames);
                }
            }

            $result = self::run($pdo);
            $issues = self::verify($pdo);
            if ($issues !== []) {
                throw new \RuntimeException(
                    'Проверка демо-данных не пройдена: ' . implode('; ', $issues)
                );
            }
            if ($ownsTransaction) {
                $pdo->commit();
            }

            return $result;
        } catch (\Throwable $e) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Проверяет полноту и внутреннюю согласованность эталонного демо-комплекта.
     *
     * @return list<string>
     */
    public static function verify(PDO $pdo): array
    {
        $issues = [];
        $minimums = [
            'news' => 7,
            'news_translations' => 6,
            'news_images' => 4,
            'news_polls' => 1,
            'pages' => 12,
            'blocks' => 40,
            'projects' => 4,
            'project_images' => 8,
            'project_fields' => 16,
            'project_translations' => 4,
            'content_entries' => 14,
            'content_entry_translations' => 14,
            'photo_albums' => 3,
            'photo_album_images' => 12,
            'photo_album_translations' => 3,
            'videos' => 3,
            'video_translations' => 3,
            'forms' => 3,
            'team_members' => 4,
            'team_member_translations' => 4,
            'menu_items' => 40,
        ];

        foreach ($minimums as $table => $minimum) {
            if (!self::tableExists($pdo, $table)) {
                $issues[] = "отсутствует таблица {$table}";
                continue;
            }
            $count = (int) $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
            if ($count < $minimum) {
                $issues[] = "{$table}: {$count}, ожидалось не менее {$minimum}";
            }
        }

        if (self::tableExists($pdo, 'pages')) {
            $homeCount = (int) $pdo->query('SELECT COUNT(*) FROM pages WHERE is_home = 1')->fetchColumn();
            if ($homeCount !== 1) {
                $issues[] = "главных страниц: {$homeCount}, ожидалась 1";
            }

            $unlinkedPages = (int) $pdo->query(
                'SELECT COUNT(*)
                 FROM pages p
                 LEFT JOIN pages root
                   ON root.id = p.translation_group_id
                  AND root.deleted_at IS NULL
                 WHERE p.deleted_at IS NULL
                   AND (
                       p.translation_group_id IS NULL
                       OR p.translation_group_id = 0
                       OR root.id IS NULL
                   )'
            )->fetchColumn();
            if ($unlinkedPages > 0) {
                $issues[] = "страницы без корректной группы переводов: {$unlinkedPages}";
            }

            $parentedCount = (int) $pdo->query(
                'SELECT COUNT(*) FROM pages WHERE parent_id IS NOT NULL AND deleted_at IS NULL'
            )->fetchColumn();
            if ($parentedCount < 8) {
                $issues[] = "иерархия страниц: {$parentedCount} связей, ожидалось не менее 8";
            }

            $parentedPages = $pdo->query(
                'SELECT id, parent_id FROM pages WHERE parent_id IS NOT NULL AND deleted_at IS NULL'
            )->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($parentedPages as $parentedPage) {
                $hierarchyError = \App\Models\Page::validateParent(
                    (int) $parentedPage['parent_id'],
                    (int) $parentedPage['id']
                );
                if ($hierarchyError !== null) {
                    $issues[] = 'страница #' . (int) $parentedPage['id'] . ': ' . $hierarchyError;
                }
            }
        }

        if (self::tableExists($pdo, 'blocks')) {
            $knownTypes = BlockTypeRegistry::types();
            $actualTypes = $pdo->query('SELECT DISTINCT type FROM blocks')->fetchAll(PDO::FETCH_COLUMN) ?: [];
            foreach ($actualTypes as $type) {
                if (!in_array((string) $type, $knownTypes, true)) {
                    $issues[] = "неизвестный тип блока {$type}";
                }
            }

            $mixedPageBlocks = (int) $pdo->query(
                'SELECT COUNT(*)
                 FROM blocks b
                 INNER JOIN pages p ON p.id = b.page_id
                 WHERE p.deleted_at IS NULL
                   AND b.lang <> \'\'
                   AND b.lang <> p.lang'
            )->fetchColumn();
            if ($mixedPageBlocks > 0) {
                $issues[] = "блоки привязаны к странице другого языка: {$mixedPageBlocks}";
            }

            $homeStacks = $pdo->query(
                'SELECT b.lang, COUNT(*) AS total
                 FROM blocks b
                 INNER JOIN pages p ON p.id = b.page_id
                 WHERE p.slug = \'home\'
                   AND p.deleted_at IS NULL
                   AND b.lang = p.lang
                   AND b.parent_block_id IS NULL
                 GROUP BY b.lang'
            )->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
            foreach (['ru', 'uz'] as $lang) {
                if ((int) ($homeStacks[$lang] ?? 0) < 6) {
                    $issues[] = "главная {$lang}: неполный стек блоков";
                }
            }
        }

        if (self::tableExists($pdo, 'menu_items') && self::tableExists($pdo, 'pages')) {
            $brokenTargets = (int) $pdo->query(
                "SELECT COUNT(*)
                 FROM menu_items mi
                 LEFT JOIN pages p
                   ON mi.url_type = 'page'
                  AND p.slug = mi.url_value
                  AND p.lang = mi.lang
                  AND p.status = 'published'
                  AND p.deleted_at IS NULL
                 WHERE mi.url_type = 'page' AND p.id IS NULL"
            )->fetchColumn();
            if ($brokenTargets > 0) {
                $issues[] = "пункты меню с отсутствующими страницами: {$brokenTargets}";
            }

            foreach (['ru', 'uz'] as $lang) {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM menu_items WHERE lang = :lang');
                $stmt->execute([':lang' => $lang]);
                if ((int) $stmt->fetchColumn() < 20) {
                    $issues[] = "меню {$lang}: недостаточно пунктов";
                }
            }
        }

        if (self::tableExists($pdo, 'content_entries')) {
            $stmt = $pdo->query('SELECT id, data FROM content_entries');
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $entry) {
                json_decode((string) $entry['data'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $issues[] = 'некорректный JSON content_entries #' . (int) $entry['id'];
                }
            }
        }

        $assetSource = \dirname(__DIR__, 2) . '/database/demo_assets';
        foreach (glob($assetSource . '/*.jpg') ?: [] as $source) {
            $target = self::uploadsDir() . '/' . basename($source);
            if (!is_file($target) || hash_file('sha256', $source) !== hash_file('sha256', $target)) {
                $issues[] = 'демо-медиа не синхронизировано: ' . basename($source);
            }
        }

        return $issues;
    }

    /** Абсолютный путь каталога публичных загрузок. */
    private static function uploadsDir(): string
    {
        $dir = (string) Config::get('paths.public_uploads', '');
        return $dir !== '' ? rtrim($dir, '/') : \dirname(__DIR__, 2) . '/public/uploads/public';
    }

    /**
     * Копирует демо-изображения из database/demo_assets в каталог публичных
     * загрузок и регистрирует их в медиабиблиотеке (таблица files). Нужно,
     * чтобы демо-главная и карточки показывали реальные картинки после чистой
     * установки (сами загрузки в репозиторий не входят).
     */
    private static function seedAssets(PDO $pdo, array &$c): void
    {
        $src = \dirname(__DIR__, 2) . '/database/demo_assets';
        $dest = self::uploadsDir();
        if (!is_dir($src)) {
            return;
        }
        if (!is_dir($dest) && !mkdir($dest, 0775, true) && !is_dir($dest)) {
            throw new \RuntimeException('Не удалось создать каталог демо-медиа: ' . $dest);
        }

        $hasFiles = self::tableExists($pdo, 'files');
        $fileIns = $hasFiles ? $pdo->prepare(
            "INSERT INTO files (original_name, stored_name, mime_type, size, access_type, uploaded_by, created_at)
             SELECT :n, :s, 'image/jpeg', :sz, 'public', NULL, NOW()
             FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM files WHERE stored_name = :s2)"
        ) : null;

        foreach (glob($src . '/*.jpg') ?: [] as $file) {
            $name = basename($file);
            $target = $dest . '/' . $name;
            $needsCopy = !is_file($target)
                || hash_file('sha256', $file) !== hash_file('sha256', $target);
            if ($needsCopy) {
                if (!copy($file, $target)) {
                    throw new \RuntimeException('Не удалось обновить демо-медиа: ' . $name);
                }
                $c['assets']++;
            }
            if ($fileIns !== null) {
                $fileIns->execute([':n' => $name, ':s' => $name, ':sz' => (int) @filesize($file), ':s2' => $name]);
            }
        }
    }

    /**
     * Демо-главная по эскизу: hero, счётчики, направления, проекты, новости и
     * медиа. Блоки берутся из фикстуры database/demo_assets/home_blocks.json.
     * Идемпотентно: страница создаётся при отсутствии, блоки — только если
     * главная ещё пуста.
     */
    private static function seedHome(PDO $pdo, array &$c): void
    {
        $fixtureDir = \dirname(__DIR__, 2) . '/database/demo_assets';
        $fixtureFiles = [
            'ru' => $fixtureDir . '/home_blocks.json',
            'uz' => $fixtureDir . '/home_blocks_uz.json',
        ];
        $localizedBlocks = [];
        foreach ($fixtureFiles as $lang => $fixture) {
            if (!is_file($fixture)) {
                continue;
            }
            $blocks = json_decode((string) file_get_contents($fixture), true);
            if (is_array($blocks) && $blocks !== []) {
                $localizedBlocks[$lang] = $blocks;
            }
        }
        if ($localizedBlocks === []) {
            return;
        }

        // Главная хранится так же, как остальные переводы: отдельная запись на
        // каждый язык и общая translation_group_id. is_home остаётся только у
        // основной записи, а языковые версии распознаются по общей группе/slug.
        $findDefaultHome = $pdo->prepare(
            "SELECT id, slug, translation_group_id
             FROM pages
             WHERE lang = 'ru'
               AND (is_home = 1 OR slug = 'home')
               AND deleted_at IS NULL
             ORDER BY is_home DESC, id ASC
             LIMIT 1"
        );
        $findDefaultHome->execute();
        $defaultHome = $findDefaultHome->fetch(PDO::FETCH_ASSOC);
        if (!$defaultHome) {
            $insertDefaultHome = $pdo->prepare(
                "INSERT INTO pages
                    (title, slug, meta_title, meta_description, `lead`, status, is_home,
                     layout_type, transparent_header, lang, translation_group_id, created_at)
                 SELECT :title, 'home', :meta_title, :meta_description, :lead, 'published', 1,
                        'no_sidebar', 1, 'ru', NULL, NOW()
                 FROM DUAL
                 WHERE NOT EXISTS (
                     SELECT 1 FROM pages WHERE slug = 'home' AND lang = 'ru'
                 )"
            );
            $insertDefaultHome->execute([
                ':title' => 'Главная',
                ':meta_title' => 'Агентство стратегического развития и реформ',
                ':meta_description' => 'Стратегические инициативы, проекты, новости и аналитические материалы Агентства.',
                ':lead' => 'Стратегия. Реформы. Развитие.',
            ]);
            $c['pages'] += $insertDefaultHome->rowCount();
            $findDefaultHome->execute();
            $defaultHome = $findDefaultHome->fetch(PDO::FETCH_ASSOC);
        }
        if (!$defaultHome) {
            return;
        }

        $homeId = (int) $defaultHome['id'];
        $homeSlug = (string) ($defaultHome['slug'] ?: 'home');
        $pdo->prepare(
            'UPDATE pages
             SET translation_group_id = id
             WHERE id = :id
               AND (translation_group_id IS NULL OR translation_group_id = 0)'
        )->execute([':id' => $homeId]);

        $homeIds = ['ru' => $homeId];
        if (isset($localizedBlocks['uz'])) {
            $findUzHome = $pdo->prepare(
                "SELECT id
                 FROM pages
                 WHERE lang = 'uz'
                   AND deleted_at IS NULL
                   AND (translation_group_id = :group_id OR slug = :slug)
                 ORDER BY (translation_group_id = :group_id_order) DESC, id ASC
                 LIMIT 1"
            );
            $findUzHome->execute([
                ':group_id' => $homeId,
                ':slug' => $homeSlug,
                ':group_id_order' => $homeId,
            ]);
            $uzHomeId = $findUzHome->fetchColumn();
            if ($uzHomeId === false) {
                $insertUzHome = $pdo->prepare(
                    "INSERT INTO pages
                        (title, slug, meta_title, meta_description, `lead`, status, is_home,
                         layout_type, transparent_header, lang, translation_group_id, created_at)
                     SELECT :title, :slug, :meta_title, :meta_description, :lead, 'published', 0,
                            'no_sidebar', 1, 'uz', :group_id, NOW()
                     FROM DUAL
                     WHERE NOT EXISTS (
                         SELECT 1 FROM pages WHERE slug = :slug_check AND lang = 'uz'
                     )"
                );
                $insertUzHome->execute([
                    ':title' => 'Bosh sahifa',
                    ':slug' => $homeSlug,
                    ':meta_title' => 'Strategik rivojlanish va islohotlar agentligi',
                    ':meta_description' => 'Agentlikning strategik tashabbuslari, loyihalari, yangiliklari va tahliliy materiallari.',
                    ':lead' => 'Strategiya. Islohotlar. Taraqqiyot.',
                    ':group_id' => $homeId,
                    ':slug_check' => $homeSlug,
                ]);
                $c['pages'] += $insertUzHome->rowCount();
                $findUzHome->execute([
                    ':group_id' => $homeId,
                    ':slug' => $homeSlug,
                    ':group_id_order' => $homeId,
                ]);
                $uzHomeId = $findUzHome->fetchColumn();
            }
            if ($uzHomeId !== false) {
                $uzHomeId = (int) $uzHomeId;
                $pdo->prepare(
                    'UPDATE pages
                     SET translation_group_id = :group_id
                     WHERE id = :id
                       AND (translation_group_id IS NULL OR translation_group_id = 0 OR translation_group_id = id)'
                )->execute([':group_id' => $homeId, ':id' => $uzHomeId]);
                $homeIds['uz'] = $uzHomeId;
            }
        }

        $ins = $pdo->prepare(
            'INSERT INTO blocks (page_id, lang, type, title, data, sort_order, is_active, created_at)
             VALUES (:pid, :lang, :ty, :ti, :d, :so, 1, NOW())'
        );
        foreach ($localizedBlocks as $lang => $blocks) {
            $localizedHomeId = $homeIds[$lang] ?? null;
            if ($localizedHomeId === null) {
                continue;
            }

            // Не перезаписываем редакторский контент. Исключение — нетронутая
            // стартовая RU-главная из schema.sql, которую демо-комплект заменяет.
            $countStmt = $pdo->prepare('SELECT COUNT(*) FROM blocks WHERE page_id = :page_id');
            $countStmt->execute([':page_id' => $localizedHomeId]);
            $count = (int) $countStmt->fetchColumn();
            if ($count > 0) {
                if ($lang !== 'ru' || !self::isUntouchedStarterHome($pdo, $localizedHomeId)) {
                    continue;
                }
                $deleteStarter = $pdo->prepare('DELETE FROM blocks WHERE page_id = :page_id');
                $deleteStarter->execute([':page_id' => $localizedHomeId]);
            }

            // Демо-главной нужна прозрачная шапка поверх hero.
            $pdo->prepare('UPDATE pages SET transparent_header = 1, layout_type = ? WHERE id = ?')
                ->execute(['no_sidebar', $localizedHomeId]);

            $order = 1;
            foreach ($blocks as $b) {
                if (!isset($b['type'])) {
                    continue;
                }
                $ins->execute([
                    ':pid' => $localizedHomeId,
                    ':lang' => $lang,
                    ':ty' => (string) $b['type'],
                    ':ti' => (string) ($b['title'] ?? ''),
                    ':d' => json_encode($b['data'] ?? [], JSON_UNESCAPED_UNICODE),
                    ':so' => $order++,
                ]);
                $c['home']++;
            }
        }

        if (isset($localizedBlocks['uz']) && self::tableExists($pdo, 'page_translations')) {
            $translation = $pdo->prepare(
                "INSERT INTO page_translations (page_id, lang, title, meta_title, meta_description, `lead`)
                 VALUES (:page_id, 'uz', :title, :meta_title, :meta_description, :lead)
                 ON DUPLICATE KEY UPDATE
                    title = VALUES(title),
                    meta_title = VALUES(meta_title),
                    meta_description = VALUES(meta_description),
                    `lead` = VALUES(`lead`)"
            );
            $translation->execute([
                ':page_id' => $homeId,
                ':title' => 'Bosh sahifa',
                ':meta_title' => 'Strategik rivojlanish va islohotlar agentligi',
                ':meta_description' => 'Agentlikning strategik tashabbuslari, loyihalari, yangiliklari va tahliliy materiallari.',
                ':lead' => 'Strategiya. Islohotlar. Taraqqiyot.',
            ]);
        }
    }

    private static function seedNews(PDO $pdo, array &$c): void
    {
        self::seedFlagshipNews($pdo, $c);

        $news = [
            [
                'title' => 'Представлена цифровая платформа мониторинга реформ',
                'slug' => 'platforma-monitoringa-reform',
                'excerpt' => 'Новая платформа объединяет ключевые показатели Стратегии «Узбекистан–2030» и позволяет отслеживать достижение результатов.',
                'badge' => 'Цифровизация',
                'image' => '/uploads/public/demo-agency-hero.jpg',
                'hashtags' => '#Узбекистан2030 #цифровизация #реформы',
                'layout' => 'standard',
                'uz_title' => 'Islohotlarni monitoring qilish raqamli platformasi taqdim etildi',
                'uz_excerpt' => 'Yangi platforma «O‘zbekiston–2030» strategiyasining asosiy ko‘rsatkichlarini birlashtiradi va natijalar ijrosini kuzatish imkonini beradi.',
                'uz_badge' => 'Raqamlashtirish',
            ],
            [
                'title' => 'Обсуждены приоритеты устойчивого регионального развития',
                'slug' => 'regionalnoe-razvitie-prioritety',
                'excerpt' => 'Эксперты и представители регионов рассмотрели проекты инфраструктуры, занятости и развития человеческого капитала.',
                'badge' => 'Региональное развитие',
                'image' => '/uploads/public/demo-urban-development.jpg',
                'hashtags' => '#регионы #инфраструктура #развитие',
                'layout' => 'side_image',
                'uz_title' => 'Hududlarni barqaror rivojlantirish ustuvor yo‘nalishlari muhokama qilindi',
                'uz_excerpt' => 'Ekspertlar va hududlar vakillari infratuzilma, bandlik va inson kapitalini rivojlantirish loyihalarini ko‘rib chiqdilar.',
                'uz_badge' => 'Hududiy rivojlanish',
            ],
            [
                'title' => 'Опубликован аналитический обзор социально-экономической динамики',
                'slug' => 'analiticheskiy-obzor-dinamiki',
                'excerpt' => 'Обзор содержит ключевые тенденции, сценарные оценки и рекомендации для дальнейшего повышения устойчивости экономики.',
                'badge' => 'Аналитика',
                'image' => '/uploads/public/demo-strategy-meeting.jpg',
                'hashtags' => '#аналитика #экономика #прогноз',
                'layout' => 'premium',
                'uz_title' => 'Ijtimoiy-iqtisodiy dinamika bo‘yicha tahliliy sharh e’lon qilindi',
                'uz_excerpt' => 'Sharh asosiy tendensiyalar, ssenariy baholari va iqtisodiyot barqarorligini oshirish bo‘yicha tavsiyalarni qamrab oladi.',
                'uz_badge' => 'Tahlil',
            ],
            [
                'title' => 'Расширяется портфель проектов зелёной экономики',
                'slug' => 'portfel-zelenoy-ekonomiki',
                'excerpt' => 'В портфель включены инициативы в сфере возобновляемой энергетики, энергоэффективности и устойчивой инфраструктуры.',
                'badge' => 'Зелёная экономика',
                'image' => '/uploads/public/demo-green-energy.jpg',
                'hashtags' => '#зелёнаяэкономика #энергетика #ESG',
                'layout' => 'gallery',
                'uz_title' => 'Yashil iqtisodiyot loyihalari portfeli kengaymoqda',
                'uz_excerpt' => 'Portfelga qayta tiklanuvchi energiya, energiya samaradorligi va barqaror infratuzilma tashabbuslari kiritildi.',
                'uz_badge' => 'Yashil iqtisodiyot',
            ],
            [
                'title' => 'Открыт приём заявок в экспертный кадровый резерв',
                'slug' => 'ekspertnyy-kadrovyy-rezerv',
                'excerpt' => 'К участию приглашаются специалисты в области стратегического планирования, анализа данных и управления проектами.',
                'badge' => 'Карьера',
                'image' => '/uploads/public/hero-demo-g2.jpg',
                'hashtags' => '#карьера #эксперты #вакансии',
                'layout' => 'standard',
                'uz_title' => 'Ekspert kadrlar zaxirasiga arizalar qabul qilinmoqda',
                'uz_excerpt' => 'Strategik rejalashtirish, ma’lumotlar tahlili va loyihalarni boshqarish sohasidagi mutaxassislar taklif etiladi.',
                'uz_badge' => 'Karyera',
            ],
        ];
        $ins = $pdo->prepare(
            "INSERT INTO news (title, slug, excerpt, badge, content, image, hashtags, layout_type, sidebar_layout, meta_title, meta_description, status, published_at, lang, created_at)
             SELECT :t, :s, :e, :b, :co, :img, :hashtags, :layout, 'right_sidebar', :mt, :md, 'published', NOW() - INTERVAL :d DAY, 'ru', NOW()
             FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM news WHERE slug = :s2)"
        );
        foreach ($news as $i => $n) {
            $content = '<p><strong>' . htmlspecialchars($n['excerpt'], ENT_QUOTES) . '</strong></p>'
                . '<p>Материал демонстрирует полноценную публикацию: структурированный текст, тематическую обложку, метаданные и связанную узбекскую версию.</p>'
                . '<h3>Основные направления</h3><ul><li>измеримые цели и показатели;</li><li>межведомственная координация;</li><li>открытость результатов для общества.</li></ul>';
            $ins->execute([
                ':t' => $n['title'],
                ':s' => $n['slug'],
                ':e' => $n['excerpt'],
                ':b' => $n['badge'],
                ':co' => $content,
                ':img' => $n['image'],
                ':hashtags' => $n['hashtags'],
                ':layout' => $n['layout'],
                ':mt' => $n['title'] . ' — Агентство',
                ':md' => $n['excerpt'],
                ':d' => $i + 2,
                ':s2' => $n['slug'],
            ]);
            $c['news'] += $ins->rowCount();

            if (self::tableExists($pdo, 'news_translations')) {
                $idStmt = $pdo->prepare("SELECT id FROM news WHERE slug = :slug AND lang = 'ru' LIMIT 1");
                $idStmt->execute([':slug' => $n['slug']]);
                $newsId = $idStmt->fetchColumn();
                if ($newsId !== false) {
                    $trans = $pdo->prepare(
                        "INSERT INTO news_translations
                            (news_id, lang, title, badge, excerpt, content, hashtags, meta_title, meta_description)
                         SELECT :nid, 'uz', :t, :b, :e, :co, :hashtags, :mt, :md
                         FROM DUAL
                         WHERE NOT EXISTS (
                             SELECT 1 FROM news_translations WHERE news_id = :nid2 AND lang = 'uz'
                         )"
                    );
                    $uzContent = '<p><strong>' . htmlspecialchars($n['uz_excerpt'], ENT_QUOTES) . '</strong></p>'
                        . '<p>Material to‘liq nashr imkoniyatlarini namoyish etadi: tuzilgan matn, mavzuli muqova, metadata va ikki tilli kontent.</p>';
                    $trans->execute([
                        ':nid' => (int) $newsId,
                        ':t' => $n['uz_title'],
                        ':b' => $n['uz_badge'],
                        ':e' => $n['uz_excerpt'],
                        ':co' => $uzContent,
                        ':hashtags' => '#O‘zbekiston2030 #islohotlar',
                        ':mt' => $n['uz_title'] . ' — Agentlik',
                        ':md' => $n['uz_excerpt'],
                        ':nid2' => (int) $newsId,
                    ]);
                }
            }
        }
    }

    /**
     * Флагманская демо-новость в «премиум»-макете (по эскизу детальной
     * страницы): бейдж, ключевые тезисы, карточка мероприятия, цитата,
     * документы и фотогалерея. Показывает редактору все возможности
     * медиа-движка новостей сразу после установки.
     */
    private static function seedFlagshipNews(PDO $pdo, array &$c): void
    {
        $slug = 'zasedanie-strategiya-2030';
        $docs = [
            ['title' => 'Пресс-релиз по итогам заседания', 'meta' => 'PDF · 245 КБ', 'url' => '/catalog/documenty'],
            ['title' => 'Презентация: ход реализации Стратегии', 'meta' => 'PDF · 1,2 МБ', 'url' => '/catalog/documenty'],
        ];
        $timeline = [
            ['date' => '09:30', 'title' => 'Открытие заседания', 'text' => 'Представлена повестка и ожидаемые результаты.'],
            ['date' => '10:15', 'title' => 'Отчёты по направлениям', 'text' => 'Рассмотрена динамика ключевых показателей.'],
            ['date' => '12:00', 'title' => 'Приняты решения', 'text' => 'Определены ответственные исполнители и контрольные сроки.'],
        ];
        $content = '<p><strong>В Агентстве стратегического развития и реформ Республики Узбекистан состоялось расширенное заседание, посвящённое вопросам реализации Стратегии «Узбекистан–2030».</strong></p>'
            . '<p>В заседании приняли участие руководители профильных министерств и ведомств, представители регионов и эксперты. Участники обсудили ход выполнения ключевых инициатив, определили приоритеты на предстоящий период и утвердили конкретные меры по их реализации.</p>'
            . '<blockquote><p>Наша задача — обеспечить эффективную реализацию всех намеченных инициатив и достичь конкретных результатов, которые ощутит каждый гражданин нашей страны.</p><cite>Директор Агентства</cite></blockquote>'
            . '<h3>Основные вопросы повестки</h3>'
            . '<ul><li>Реализация приоритетных направлений Стратегии «Узбекистан–2030»</li>'
            . '<li>Развитие «зелёной» экономики и энергетики</li>'
            . '<li>Инвестиционная и промышленная политика</li>'
            . '<li>Развитие образования, науки и инноваций</li>'
            . '<li>Цифровая трансформация и электронное правительство</li></ul>'
            . '<p>По итогам заседания ответственным ведомствам и регионам даны поручения по ускорению реализации проектов и обеспечению своевременного достижения ключевых показателей.</p>';

        $ins = $pdo->prepare(
            "INSERT INTO news (title, slug, excerpt, badge, content, image, hashtags, key_points, event_meta, timeline_json, docs, source_note, views, layout_type, sidebar_layout, meta_title, meta_description, status, published_at, lang, created_at)
             SELECT :t, :s, :e, :b, :co, :img, :hashtags, :kp, :em, :timeline, :dc, :sn, 1284, 'premium', 'right_sidebar', :mt, :md, 'published', NOW() - INTERVAL 1 DAY, 'ru', NOW()
             FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM news WHERE slug = :s2)"
        );
        $ins->execute([
            ':t' => 'Заседание по вопросам реализации Стратегии «Узбекистан–2030»',
            ':s' => $slug,
            ':e' => 'Обсуждены ключевые приоритеты и ход реализации стратегических инициатив, направленных на устойчивое развитие страны и повышение благосостояния населения.',
            ':b' => 'Мероприятие',
            ':co' => $content,
            ':img' => '/uploads/public/demo-strategy-meeting.jpg',
            ':hashtags' => '#Узбекистан2030 #стратегия #реформы #развитие',
            ':kp' => "Рассмотрены приоритетные направления Стратегии «Узбекистан–2030»\nПроанализирован прогресс реализации ключевых инициатив\nУтверждены дальнейшие шаги и ответственные исполнители\nОсобое внимание уделено инвестициям, инновациям и человеческому капиталу",
            ':em' => "Дата: 20 мая 2026 года\nФормат: расширенное заседание\nУчастники: министерства, ведомства, регионы",
            ':timeline' => json_encode($timeline, JSON_UNESCAPED_UNICODE),
            ':dc' => json_encode($docs, JSON_UNESCAPED_UNICODE),
            ':sn' => 'Подготовлено пресс-службой Агентства',
            ':mt' => 'Реализация Стратегии «Узбекистан–2030» — итоги заседания',
            ':md' => 'Ключевые решения расширенного заседания по реализации Стратегии «Узбекистан–2030».',
            ':s2' => $slug,
        ]);
        $c['news'] += $ins->rowCount();

        // Фотогалерея новости — если таблица есть и запись только что создана.
        $newsId = $pdo->prepare('SELECT id FROM news WHERE slug = :s LIMIT 1');
        $newsId->execute([':s' => $slug]);
        $nid = $newsId->fetchColumn();
        if ($nid !== false) {
            $uzDocs = [
                ['title' => 'Yig‘ilish yakunlari bo‘yicha press-reliz', 'meta' => 'PDF · 245 KB', 'url' => '/catalog/documenty'],
                ['title' => 'Taqdimot: Strategiyani amalga oshirish borishi', 'meta' => 'PDF · 1,2 MB', 'url' => '/catalog/documenty'],
            ];
            $uzContent = '<p><strong>O‘zbekiston Respublikasi Strategik rejalashtirish va islohotlar agentligida «O‘zbekiston–2030» Strategiyasini amalga oshirish masalalariga bag‘ishlangan kengaytirilgan yig‘ilish bo‘lib o‘tdi.</strong></p>'
                . '<p>Yig‘ilishda tegishli vazirlik va idoralar rahbarlari, hududlar vakillari hamda ekspertlar ishtirok etdilar. Ishtirokchilar ustuvor tashabbuslarning bajarilish borishini muhokama qildilar.</p>'
                . '<blockquote><p>Vazifamiz — barcha belgilangan tashabbuslarning samarali amalga oshirilishini ta’minlash va har bir fuqaro sezadigan aniq natijalarga erishishdir.</p><cite>Agentlik direktori</cite></blockquote>';

            if (self::tableExists($pdo, 'news_translations')) {

                $transIns = $pdo->prepare(
                    'INSERT INTO news_translations (news_id, lang, title, badge, excerpt, content, key_points, event_meta, docs, poll_question, poll_options_json)
                     SELECT :nid, "uz", :t, :b, :e, :co, :kp, :em, :dc, :pq, :po
                     FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM news_translations WHERE news_id = :nid2 AND lang = "uz")'
                );
                $transIns->execute([
                    ':nid' => (int) $nid,
                    ':t' => '«O‘zbekiston–2030» Strategiyasini amalga oshirish masalalari bo‘yicha yig‘ilish',
                    ':b' => 'Tadbirlar',
                    ':e' => 'Mamlakatni barqaror rivojlantirish va aholi farovonligini oshirishga qaratilgan strategik tashabbuslarni amalga oshirish borishi muhokama qilindi.',
                    ':co' => $uzContent,
                    ':kp' => "«O‘zbekiston–2030» Strategiyasining ustuvor yo‘nalishlari ko‘rib chiqildi\nAsosiy tashabbuslar ijrosi tahlil qilindi\nKelgusi qadamlar va mas’ul ijrochilar tasdiqlandi",
                    ':em' => "Sana: 20-may 2026-yil\nShakl: kengaytirilgan yig‘ilish\nIshtirokchilar: vazirliklar, idoralar, hududlar",
                    ':dc' => json_encode($uzDocs, JSON_UNESCAPED_UNICODE),
                    ':pq' => 'Strategiya ijrosidagi ustuvor yo‘nalishni qo‘llab-quvvatlaysizmi?',
                    ':po' => json_encode(['Ha, to‘liq', 'Qisman', 'Qo‘shimcha takliflarim bor'], JSON_UNESCAPED_UNICODE),
                    ':nid2' => (int) $nid,
                ]);
            }

            $cols = $pdo->query("SHOW COLUMNS FROM news")->fetchAll(PDO::FETCH_COLUMN) ?: [];
            if (in_array('translation_group_id', $cols, true)) {
                $existUz = $pdo->prepare("SELECT id FROM news WHERE translation_group_id = :gid AND lang = 'uz' LIMIT 1");
                $existUz->execute([':gid' => (int) $nid]);
                if ($existUz->fetchColumn() === false) {
                    $uzIns = $pdo->prepare(
                        "INSERT INTO news (title, slug, excerpt, badge, content, image, key_points, event_meta, docs, status, published_at, lang, translation_group_id, created_at)
                         VALUES (:t, 'strategiya-uzbekistan-2030-uz', :e, :b, :c, '/uploads/public/demo-strategy-meeting.jpg', :kp, :em, :dc, 'published', NOW(), 'uz', :gid, NOW())"
                    );
                    $uzIns->execute([
                        ':t' => '«O‘zbekiston–2030» Strategiyasini amalga oshirish masalalari bo‘yicha yig‘ilish',
                        ':e' => 'Mamlakatni barqaror rivojlantirish va aholi farovonligini oshirishga qaratilgan strategik tashabbuslarni amalga oshirish borishi muhokama qilindi.',
                        ':b' => 'Tadbirlar',
                        ':c' => $uzContent,
                        ':kp' => "«O‘zbekiston–2030» Strategiyasining ustuvor yo‘nalishlari ko‘rib chiqildi\nAsosiy tashabbuslar ijrosi tahlil qilindi\nKelgusi qadamlar va mas’ul ijrochilar tasdiqlandi",
                        ':em' => "Sana: 20-may 2026-yil\nShakl: kengaytirilgan yig‘ilish\nIshtirokchilar: vazirliklar, idoralar, hududlar",
                        ':dc' => json_encode($uzDocs, JSON_UNESCAPED_UNICODE),
                        ':gid' => (int) $nid,
                    ]);
                }
            }

            if (self::tableExists($pdo, 'news_images')) {
                $imgIns = $pdo->prepare(
                    'INSERT INTO news_images (news_id, path, alt_text, sort_order, created_at)
                     SELECT :nid, :p, :a, :o, NOW()
                     FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM news_images WHERE news_id = :nid2 AND path = :p2)'
                );
                $gallery = [
                    '/uploads/public/demo-strategy-meeting.jpg',
                    '/uploads/public/demo-agency-hero.jpg',
                    '/uploads/public/demo-urban-development.jpg',
                    '/uploads/public/demo-green-energy.jpg',
                ];
                foreach ($gallery as $i => $path) {
                    $imgIns->execute([':nid' => (int) $nid, ':p' => $path, ':a' => 'Заседание по Стратегии «Узбекистан–2030»', ':o' => $i, ':nid2' => (int) $nid, ':p2' => $path]);
                }
            }

            if (self::tableExists($pdo, 'news_polls')) {
                $pollIns = $pdo->prepare(
                    "INSERT INTO news_polls (news_id, question, options_json, created_at)
                     SELECT :nid, :question, :options, NOW()
                     FROM DUAL
                     WHERE NOT EXISTS (SELECT 1 FROM news_polls WHERE news_id = :nid2)"
                );
                $pollIns->execute([
                    ':nid' => (int) $nid,
                    ':question' => 'Какое направление Стратегии наиболее важно для устойчивого развития?',
                    ':options' => json_encode(
                        ['Человеческий капитал', 'Экономический рост', 'Зелёная экономика', 'Цифровая трансформация'],
                        JSON_UNESCAPED_UNICODE
                    ),
                    ':nid2' => (int) $nid,
                ]);
            }
        }
    }

    private static function seedEntries(PDO $pdo, array &$c): void
    {
        $ins = $pdo->prepare(
            "INSERT INTO content_entries (type_id, title, slug, status, data, created_at)
             SELECT :tid, :t, :s, 'published', :d, NOW()
             FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM content_entries WHERE type_id = :tid2 AND slug = :s2)"
        );
        $byType = [
            'documenty' => [
                ['Методология мониторинга Стратегии «Узбекистан–2030»', 'metodologiya-monitoringa-2030', ['doc_number' => 'ММ-2030', 'doc_date' => '2026-07-15', 'category' => 'Методология', 'summary' => 'Единые подходы к оценке достижения целей, индикаторов и результатов реформ.', 'file' => '/catalog/documenty/metodologiya-monitoringa-2030']],
                ['Аналитический отчёт о ходе структурных реформ', 'otchet-strukturnye-reformy', ['doc_number' => 'АО-07/26', 'doc_date' => '2026-07-01', 'category' => 'Аналитические отчёты', 'summary' => 'Результаты мониторинга структурных преобразований и рекомендации по дальнейшим шагам.', 'file' => '/catalog/documenty/otchet-strukturnye-reformy']],
                ['Регламент межведомственной координации', 'reglament-koordinacii', ['doc_number' => 'РК-12', 'doc_date' => '2026-06-20', 'category' => 'Регламенты', 'summary' => 'Порядок обмена данными, согласования решений и контроля исполнения.', 'file' => '/catalog/documenty/reglament-koordinacii']],
                ['Обзор международного опыта стратегического планирования', 'obzor-mezhdunarodnogo-opyta', ['doc_number' => 'ОМО-04', 'doc_date' => '2026-06-05', 'category' => 'Обзоры', 'summary' => 'Сравнение современных моделей стратегического управления и оценки реформ.', 'file' => '/catalog/documenty/obzor-mezhdunarodnogo-opyta']],
            ],
            'vakansii' => [
                ['Ведущий специалист отдела ИТ', 'vedushchiy-it', ['department' => 'Отдел информационных технологий', 'salary' => 'по договорённости', 'deadline' => '2026-08-31', 'requirements' => 'Высшее образование, опыт от 3 лет, знание PHP/MySQL.', 'duties' => 'Сопровождение и развитие информационных систем.']],
                ['Юрисконсульт', 'yuriskonsult', ['department' => 'Юридический отдел', 'salary' => 'от 8 000 000 сум', 'deadline' => '2026-08-20', 'requirements' => 'Высшее юридическое образование, опыт от 2 лет.', 'duties' => 'Правовое сопровождение деятельности организации.']],
                ['Специалист по кадрам', 'specialist-kadry', ['department' => 'Отдел кадров', 'salary' => 'от 6 000 000 сум', 'deadline' => '2026-09-10', 'requirements' => 'Опыт кадрового делопроизводства.', 'duties' => 'Ведение кадрового учёта и документации.']],
                ['Пресс-секретарь', 'press-sekretar', ['department' => 'Пресс-служба', 'salary' => 'по итогам собеседования', 'deadline' => '2026-08-05', 'requirements' => 'Опыт в СМИ или PR, грамотная речь.', 'duties' => 'Взаимодействие со СМИ, ведение новостей сайта.']],
            ],
            'tendery' => [
                ['Развитие аналитической платформы мониторинга', 'platforma-monitoringa-zakupka', ['tender_number' => 'T-2026-031', 'budget' => 'по итогам конкурса', 'start_date' => '2026-07-10', 'deadline' => '2026-08-20', 'summary' => 'Разработка модулей визуализации показателей и межведомственного обмена данными.', 'file' => '/catalog/tendery/platforma-monitoringa-zakupka']],
                ['Исследование социально-экономической динамики регионов', 'issledovanie-regionov', ['tender_number' => 'T-2026-034', 'budget' => 'по итогам конкурса', 'start_date' => '2026-07-18', 'deadline' => '2026-09-01', 'summary' => 'Комплексное исследование факторов роста и качества жизни в регионах.', 'file' => '/catalog/tendery/issledovanie-regionov']],
                ['Организация международного экспертного форума', 'ekspertnyy-forum', ['tender_number' => 'T-2026-038', 'budget' => 'по итогам конкурса', 'start_date' => '2026-07-25', 'deadline' => '2026-09-15', 'summary' => 'Организационное и техническое сопровождение экспертного форума.', 'file' => '/catalog/tendery/ekspertnyy-forum']],
            ],
            'meropriyatiya' => [
                ['Открытая презентация системы мониторинга Стратегии', 'prezentaciya-monitoringa-strategii', ['event_date' => '2026-09-12', 'event_time' => '10:00', 'location' => 'Ташкент, конференц-зал Агентства', 'summary' => 'Презентация цифровой системы мониторинга целей и показателей Стратегии «Узбекистан–2030».']],
                ['Экспертный диалог по региональному развитию', 'dialog-regionalnoe-razvitie', ['event_date' => '2026-10-03', 'event_time' => '15:00', 'location' => 'Гибридный формат', 'summary' => 'Обсуждение новых подходов к развитию регионов и оценке качества государственных программ.']],
                ['Форум стратегических инициатив', 'forum-strategicheskih-iniciativ', ['event_date' => '2026-11-18', 'event_time' => '09:30', 'location' => 'Ташкент', 'summary' => 'Площадка для обмена опытом между государственными органами, экспертами и международными партнёрами.']],
            ],
        ];
        $translations = [
            'documenty' => [
                'metodologiya-monitoringa-2030' => ['«O‘zbekiston–2030» strategiyasini monitoring qilish metodologiyasi', ['category' => 'Metodologiya', 'summary' => 'Islohotlar maqsadlari, indikatorlari va natijalarini baholashga yagona yondashuvlar.']],
                'otchet-strukturnye-reformy' => ['Tarkibiy islohotlarning borishi bo‘yicha tahliliy hisobot', ['category' => 'Tahliliy hisobotlar', 'summary' => 'Tarkibiy o‘zgarishlar monitoringi natijalari va keyingi qadamlar bo‘yicha tavsiyalar.']],
                'reglament-koordinacii' => ['Idoralararo muvofiqlashtirish reglamenti', ['category' => 'Reglamentlar', 'summary' => 'Ma’lumot almashish, qarorlarni kelishish va ijroni nazorat qilish tartibi.']],
                'obzor-mezhdunarodnogo-opyta' => ['Strategik rejalashtirish bo‘yicha xalqaro tajriba sharhi', ['category' => 'Sharhlar', 'summary' => 'Strategik boshqaruv va islohotlarni baholashning zamonaviy modellarini taqqoslash.']],
            ],
            'vakansii' => [
                'vedushchiy-it' => ['IT bo‘limining yetakchi mutaxassisi', ['department' => 'Axborot texnologiyalari bo‘limi', 'requirements' => 'Oliy ma’lumot, kamida 3 yillik tajriba, PHP/MySQL bilimlari.', 'duties' => 'Axborot tizimlarini qo‘llab-quvvatlash va rivojlantirish.']],
                'yuriskonsult' => ['Yuriskonsult', ['department' => 'Yuridik bo‘lim', 'requirements' => 'Oliy yuridik ma’lumot va kamida 2 yillik tajriba.', 'duties' => 'Agentlik faoliyatini huquqiy qo‘llab-quvvatlash.']],
                'specialist-kadry' => ['Kadrlar bo‘yicha mutaxassis', ['department' => 'Inson resurslari bo‘limi', 'requirements' => 'Kadrlar ish yurituvi bo‘yicha tajriba.', 'duties' => 'Kadrlar hisobi va hujjatlarini yuritish.']],
                'press-sekretar' => ['Matbuot kotibi', ['department' => 'Matbuot xizmati', 'requirements' => 'OAV yoki PR sohasida tajriba, savodli nutq.', 'duties' => 'OAV bilan hamkorlik va sayt yangiliklarini yuritish.']],
            ],
            'tendery' => [
                'platforma-monitoringa-zakupka' => ['Monitoring tahliliy platformasini rivojlantirish', ['summary' => 'Ko‘rsatkichlarni vizuallashtirish va idoralararo ma’lumot almashish modullarini ishlab chiqish.']],
                'issledovanie-regionov' => ['Hududlarning ijtimoiy-iqtisodiy dinamikasini o‘rganish', ['summary' => 'Hududlarda o‘sish omillari va hayot sifatini kompleks o‘rganish.']],
                'ekspertnyy-forum' => ['Xalqaro ekspert forumini tashkil etish', ['summary' => 'Ekspert forumini tashkiliy va texnik jihatdan qo‘llab-quvvatlash.']],
            ],
            'meropriyatiya' => [
                'prezentaciya-monitoringa-strategii' => ['Strategiyani monitoring qilish tizimining ochiq taqdimoti', ['location' => 'Toshkent, Agentlik konferensiya zali', 'summary' => '«O‘zbekiston–2030» strategiyasi maqsad va ko‘rsatkichlarini monitoring qilish raqamli tizimi taqdimoti.']],
                'dialog-regionalnoe-razvitie' => ['Hududiy rivojlanish bo‘yicha ekspert muloqoti', ['location' => 'Gibrid shakl', 'summary' => 'Hududlarni rivojlantirish va davlat dasturlari sifatini baholashning yangi yondashuvlari muhokamasi.']],
                'forum-strategicheskih-iniciativ' => ['Strategik tashabbuslar forumi', ['location' => 'Toshkent', 'summary' => 'Davlat organlari, ekspertlar va xalqaro hamkorlar o‘rtasida tajriba almashish maydoni.']],
            ],
        ];
        foreach ($byType as $slug => $rows) {
            $tid = self::typeId($pdo, $slug);
            if ($tid === null) {
                continue;
            }
            foreach ($rows as $r) {
                $ins->execute([':tid' => $tid, ':t' => $r[0], ':s' => $r[1], ':d' => json_encode($r[2], JSON_UNESCAPED_UNICODE), ':tid2' => $tid, ':s2' => $r[1]]);
                $c[$slug] += $ins->rowCount();

                if (self::tableExists($pdo, 'content_entry_translations')) {
                    $entryStmt = $pdo->prepare(
                        'SELECT id FROM content_entries WHERE type_id = :type_id AND slug = :slug LIMIT 1'
                    );
                    $entryStmt->execute([':type_id' => $tid, ':slug' => $r[1]]);
                    $entryId = $entryStmt->fetchColumn();
                    $translation = $translations[$slug][$r[1]] ?? null;
                    if ($entryId !== false && is_array($translation)) {
                        $translatedData = array_replace($r[2], $translation[1] ?? []);
                        $transIns = $pdo->prepare(
                            "INSERT INTO content_entry_translations (entry_id, lang, title, data)
                             SELECT :entry_id, 'uz', :title, :data
                             FROM DUAL
                             WHERE NOT EXISTS (
                                 SELECT 1 FROM content_entry_translations
                                 WHERE entry_id = :entry_id2 AND lang = 'uz'
                             )"
                        );
                        $transIns->execute([
                            ':entry_id' => (int) $entryId,
                            ':title' => (string) $translation[0],
                            ':data' => json_encode($translatedData, JSON_UNESCAPED_UNICODE),
                            ':entry_id2' => (int) $entryId,
                        ]);
                    }
                }
            }
        }
    }

    private static function seedProjects(PDO $pdo, array &$c): void
    {
        if (!self::tableExists($pdo, 'projects')) {
            return;
        }

        $projects = [
            ['cifrovaya-transformaciya', 'Цифровая трансформация и развитие инноваций', '/uploads/public/hero-demo-g3.jpg', '<h2>О проекте</h2><p>Проект объединяет цифровизацию государственных услуг, развитие инфраструктуры данных и внедрение современных инструментов мониторинга реформ.</p><h3>Ключевые результаты</h3><ul><li>единая система показателей реализации стратегии;</li><li>межведомственный обмен данными;</li><li>сокращение сроков предоставления государственных услуг.</li></ul><blockquote><p>Цифровые решения должны давать измеримый результат для граждан и бизнеса.</p></blockquote>'],
            ['transportnaya-infrastruktura', 'Развитие транспортной и логистической инфраструктуры', '/uploads/public/hero-demo-g2.jpg', '<h2>О проекте</h2><p>Комплексная программа развития транспортных коридоров, городской мобильности и современной логистической инфраструктуры регионов.</p><h3>План реализации</h3><ol><li>анализ транспортных потоков;</li><li>подготовка приоритетных проектов;</li><li>поэтапная реализация и публичный мониторинг.</li></ol>'],
            ['zelenaya-energetika', 'Увеличение доли возобновляемых источников энергии', '/uploads/public/hero-demo-g4.jpg', '<h2>О проекте</h2><p>Инициатива направлена на повышение энергоэффективности, развитие солнечной и ветровой генерации и рациональное использование природных ресурсов.</p><table><thead><tr><th>Направление</th><th>Цель</th></tr></thead><tbody><tr><td>Возобновляемая энергетика</td><td>Новые генерирующие мощности</td></tr><tr><td>Энергоэффективность</td><td>Снижение удельного потребления</td></tr></tbody></table>'],
            ['investicii-prioritetnye-otrasli', 'Привлечение инвестиций в приоритетные отрасли', '/uploads/public/hero-demo.jpg', '<h2>О проекте</h2><p>Формирование прозрачного портфеля инвестиционных предложений и сопровождение проектов, создающих рабочие места и устойчивую добавленную стоимость.</p><h3>Приоритеты</h3><ul><li>промышленная кооперация;</li><li>региональные инвестиционные программы;</li><li>международное партнёрство.</li></ul>'],
        ];
        $projectImages = [
            'cifrovaya-transformaciya' => '/uploads/public/demo-agency-hero.jpg',
            'transportnaya-infrastruktura' => '/uploads/public/demo-urban-development.jpg',
            'zelenaya-energetika' => '/uploads/public/demo-green-energy.jpg',
            'investicii-prioritetnye-otrasli' => '/uploads/public/demo-strategy-meeting.jpg',
        ];
        $ins = $pdo->prepare(
            "INSERT INTO projects (title, slug, description, cover_image, status, is_featured, sort_order, created_at)
             SELECT :t, :s, :d, :i, 'published', 1, :o, NOW()
             FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM projects WHERE slug = :s2)"
        );
        foreach ($projects as $i => $project) {
            $ins->execute([':t' => $project[1], ':s' => $project[0], ':d' => $project[3], ':i' => $projectImages[$project[0]] ?? $project[2], ':o' => $i, ':s2' => $project[0]]);
            $c['projects'] += $ins->rowCount();

            $pidStmt = $pdo->prepare('SELECT id FROM projects WHERE slug = :s LIMIT 1');
            $pidStmt->execute([':s' => $project[0]]);
            $projectId = $pidStmt->fetchColumn();
            if ($projectId === false) {
                continue;
            }

            if (self::tableExists($pdo, 'project_images')) {
                $imageIns = $pdo->prepare(
                    "INSERT INTO project_images (project_id, file_path, caption, sort_order)
                     SELECT :pid, :path, :caption, :sort
                     FROM DUAL
                     WHERE NOT EXISTS (
                         SELECT 1 FROM project_images WHERE project_id = :pid2 AND file_path = :path2
                     )"
                );
                $gallery = array_values(array_unique([
                    $projectImages[$project[0]] ?? $project[2],
                    '/uploads/public/demo-strategy-meeting.jpg',
                    '/uploads/public/demo-urban-development.jpg',
                ]));
                foreach ($gallery as $sort => $path) {
                    $imageIns->execute([
                        ':pid' => (int) $projectId,
                        ':path' => $path,
                        ':caption' => $project[1],
                        ':sort' => $sort,
                        ':pid2' => (int) $projectId,
                        ':path2' => $path,
                    ]);
                }
            }

            if (self::tableExists($pdo, 'project_fields')) {
                $fieldIns = $pdo->prepare(
                    "INSERT INTO project_fields (project_id, field_key, field_value, sort_order)
                     SELECT :pid, :field_key, :field_value, :sort
                     FROM DUAL
                     WHERE NOT EXISTS (
                         SELECT 1 FROM project_fields WHERE project_id = :pid2 AND field_key = :field_key2
                     )"
                );
                $fields = [
                    'Статус' => 'В реализации',
                    'Период' => '2026–2030',
                    'Уровень' => $i % 2 === 0 ? 'Национальный' : 'Межрегиональный',
                    'Ключевой результат' => 'Измеримый вклад в достижение целей Стратегии «Узбекистан–2030»',
                ];
                $fieldOrder = 0;
                foreach ($fields as $fieldKey => $value) {
                    $fieldIns->execute([
                        ':pid' => (int) $projectId,
                        ':field_key' => $fieldKey,
                        ':field_value' => $value,
                        ':sort' => $fieldOrder++,
                        ':pid2' => (int) $projectId,
                        ':field_key2' => $fieldKey,
                    ]);
                }
            }
        }

        if (self::tableExists($pdo, 'project_translations')) {
            $transIns = $pdo->prepare(
                'INSERT INTO project_translations (project_id, lang, title, description)
                 SELECT :pid, "uz", :t, :d
                 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM project_translations WHERE project_id = :pid2 AND lang = "uz")'
            );
            $uzProjects = [
                'cifrovaya-transformaciya' => ['Raqamli transformatsiya va innovatsiyalarni rivojlantirish', '<h2>Loyiha haqida</h2><p>Loyiha davlat xizmatlarini raqamlashtirish va ma’lumotlar infratuzilmasini rivojlantirishni birlashtiradi.</p>'],
                'transportnaya-infrastruktura' => ['Transport va logistika infratuzilmasini rivojlantirish', '<h2>Loyiha haqida</h2><p>Transport yo‘laklari va shahar mobilligini rivojlantirish bo‘yicha majmuaviy dastur.</p>'],
                'zelenaya-energetika' => ['Qayta tiklanuvchi energiya manbalari ulushini oshirish', '<h2>Loyiha haqida</h2><p>Quyosh va shamol energiyasini rivojlantirish hamda energiya samaradorligini oshirish tashabbusi.</p>'],
                'investicii-prioritetnye-otrasli' => ['Ustuvor tarmoqlarga investitsiyalarni jalb qilish', '<h2>Loyiha haqida</h2><p>Shaffof investitsiya takliflari portfelini shakllantirish va loyihalarni kuzatib borish.</p>'],
            ];
            foreach ($uzProjects as $slug => $data) {
                $pidStmt = $pdo->prepare('SELECT id FROM projects WHERE slug = :s LIMIT 1');
                $pidStmt->execute([':s' => $slug]);
                $pid = $pidStmt->fetchColumn();
                if ($pid !== false) {
                    $transIns->execute([':pid' => (int) $pid, ':t' => $data[0], ':d' => $data[1], ':pid2' => (int) $pid]);
                }
            }
        }
    }

    private static function seedMedia(PDO $pdo, array &$c): void
    {
        if (self::tableExists($pdo, 'photo_albums')) {
            $albums = [
                ['strategiya-2030-v-deystvii', 'Стратегия «Узбекистан–2030» в действии', 'Рабочие заседания, презентации и обсуждение приоритетных реформ.', '/uploads/public/demo-strategy-meeting.jpg', '«O‘zbekiston–2030» strategiyasi amalda', 'Ishchi yig‘ilishlar, taqdimotlar va ustuvor islohotlar muhokamasi.'],
                ['regionalnoe-razvitie', 'Развитие регионов Узбекистана', 'Проекты инфраструктуры и новые точки экономического роста.', '/uploads/public/demo-urban-development.jpg', 'O‘zbekiston hududlarini rivojlantirish', 'Infratuzilma loyihalari va iqtisodiy o‘sishning yangi nuqtalari.'],
                ['zelenaya-transformaciya', 'Зелёная трансформация', 'Энергетика, устойчивые города и экологические инициативы.', '/uploads/public/demo-green-energy.jpg', 'Yashil transformatsiya', 'Energetika, barqaror shaharlar va ekologik tashabbuslar.'],
            ];
            $albumIns = $pdo->prepare(
                "INSERT INTO photo_albums (title, slug, description, cover_url, is_published, is_featured, created_at)
                 SELECT :t, :s, :d, :c, 1, 1, NOW()
                 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM photo_albums WHERE slug = :s2)"
            );
            foreach ($albums as $album) {
                $albumIns->execute([':t' => $album[1], ':s' => $album[0], ':d' => $album[2], ':c' => $album[3], ':s2' => $album[0]]);
                $c['albums'] += $albumIns->rowCount();
                if (self::tableExists($pdo, 'photo_album_images')) {
                    $albumIdStmt = $pdo->prepare('SELECT id FROM photo_albums WHERE slug = :slug LIMIT 1');
                    $albumIdStmt->execute([':slug' => $album[0]]);
                    $albumId = $albumIdStmt->fetchColumn();
                    if ($albumId !== false) {
                        $imageIns = $pdo->prepare(
                            'INSERT INTO photo_album_images (album_id, image_url, caption, sort_order, created_at)
                             SELECT :aid, :url, :caption, :ord, NOW()
                             FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM photo_album_images WHERE album_id = :aid2 AND image_url = :url2)'
                        );
                        foreach (['/uploads/public/demo-strategy-meeting.jpg', '/uploads/public/demo-urban-development.jpg', '/uploads/public/demo-agency-hero.jpg', '/uploads/public/demo-green-energy.jpg'] as $order => $url) {
                            $imageIns->execute([':aid' => (int) $albumId, ':url' => $url, ':caption' => $album[1], ':ord' => $order, ':aid2' => (int) $albumId, ':url2' => $url]);
                        }

                        if (self::tableExists($pdo, 'photo_album_translations')) {
                            $translationIns = $pdo->prepare(
                                "INSERT INTO photo_album_translations (album_id, lang, title, description)
                                 SELECT :album_id, 'uz', :title, :description
                                 FROM DUAL
                                 WHERE NOT EXISTS (
                                     SELECT 1 FROM photo_album_translations
                                     WHERE album_id = :album_id2 AND lang = 'uz'
                                 )"
                            );
                            $translationIns->execute([
                                ':album_id' => (int) $albumId,
                                ':title' => $album[4],
                                ':description' => $album[5],
                                ':album_id2' => (int) $albumId,
                            ]);
                        }
                    }
                }
            }
        }

        if (self::tableExists($pdo, 'videos')) {
            $videos = [
                ['uzbekistan-2030-klyuchevye-celi', 'Узбекистан–2030: ключевые цели и приоритеты', '/uploads/public/demo-strategy-meeting.jpg', '02:35', 'O‘zbekiston–2030: asosiy maqsad va ustuvor yo‘nalishlar'],
                ['zelenaya-ekonomika', 'Переход к «зелёной» экономике', '/uploads/public/demo-green-energy.jpg', '03:12', 'Yashil iqtisodiyotga o‘tish'],
                ['cifrovye-gosuslugi', 'Цифровая трансформация государственных услуг', '/uploads/public/demo-agency-hero.jpg', '02:08', 'Davlat xizmatlarining raqamli transformatsiyasi'],
            ];
            $videoIns = $pdo->prepare(
                "INSERT INTO videos (title, slug, description, cover_url, video_url, duration, is_published, is_featured, sort_order, created_at)
                 SELECT :t, :s, :d, :c, '/press-centr', :du, 1, 1, :o, NOW()
                 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM videos WHERE slug = :s2)"
            );
            foreach ($videos as $i => $video) {
                $videoIns->execute([':t' => $video[1], ':s' => $video[0], ':d' => 'Информационный видеоматериал Агентства.', ':c' => $video[2], ':du' => $video[3], ':o' => $i, ':s2' => $video[0]]);
                $c['videos'] += $videoIns->rowCount();

                if (self::tableExists($pdo, 'video_translations')) {
                    $videoIdStmt = $pdo->prepare('SELECT id FROM videos WHERE slug = :slug LIMIT 1');
                    $videoIdStmt->execute([':slug' => $video[0]]);
                    $videoId = $videoIdStmt->fetchColumn();
                    if ($videoId !== false) {
                        $videoTranslationIns = $pdo->prepare(
                            "INSERT INTO video_translations (video_id, lang, title, description)
                             SELECT :video_id, 'uz', :title, :description
                             FROM DUAL
                             WHERE NOT EXISTS (
                                 SELECT 1 FROM video_translations
                                 WHERE video_id = :video_id2 AND lang = 'uz'
                             )"
                        );
                        $videoTranslationIns->execute([
                            ':video_id' => (int) $videoId,
                            ':title' => $video[4],
                            ':description' => 'Agentlikning axborot videomateriali.',
                            ':video_id2' => (int) $videoId,
                        ]);
                    }
                }
            }
        }
    }

    private static function seedForms(PDO $pdo, array &$c): void
    {
        if (!self::tableExists($pdo, 'forms')) {
            return;
        }
        $forms = [
            [
                'Обращение в Агентство',
                'public-appeal',
                [
                    ['name' => 'name', 'label' => 'Ваше имя', 'type' => 'text', 'required' => true],
                    ['name' => 'email', 'label' => 'E-mail', 'type' => 'email', 'required' => true],
                    ['name' => 'phone', 'label' => 'Телефон', 'type' => 'tel', 'required' => false],
                    ['name' => 'topic', 'label' => 'Тема обращения', 'type' => 'select', 'options' => 'Общий вопрос,Предложение,Запрос информации,Запись на приём', 'required' => true],
                    ['name' => 'message', 'label' => 'Сообщение', 'type' => 'textarea', 'required' => true],
                    ['name' => 'consent', 'label' => 'Согласен на обработку предоставленных данных', 'type' => 'checkbox', 'required' => true],
                ],
                'Спасибо! Ваше обращение зарегистрировано.',
            ],
            [
                'Регистрация на мероприятие',
                'event-registration',
                [
                    ['name' => 'name', 'label' => 'Ф.И.О.', 'type' => 'text', 'required' => true],
                    ['name' => 'organization', 'label' => 'Организация', 'type' => 'text', 'required' => true],
                    ['name' => 'email', 'label' => 'E-mail', 'type' => 'email', 'required' => true],
                    ['name' => 'participation', 'label' => 'Формат участия', 'type' => 'radio', 'options' => 'Очно,Онлайн', 'required' => true],
                    ['name' => 'topics', 'label' => 'Интересующие направления', 'type' => 'checkbox_group', 'options' => 'Стратегическое планирование,Региональное развитие,Зелёная экономика,Цифровизация', 'required' => false],
                    ['name' => 'event_date', 'label' => 'Предпочтительная дата', 'type' => 'date', 'required' => false],
                ],
                'Регистрация принята. Подтверждение будет направлено на указанный e-mail.',
            ],
            [
                'Заявка в экспертный резерв',
                'expert-pool',
                [
                    ['name' => 'name', 'label' => 'Ф.И.О.', 'type' => 'text', 'required' => true],
                    ['name' => 'email', 'label' => 'E-mail', 'type' => 'email', 'required' => true],
                    ['name' => 'specialization', 'label' => 'Специализация', 'type' => 'select', 'options' => 'Экономика,Аналитика данных,Управление проектами,Международное сотрудничество', 'required' => true],
                    ['name' => 'experience', 'label' => 'Кратко опишите опыт', 'type' => 'textarea', 'required' => true],
                    ['name' => 'resume', 'label' => 'Резюме', 'type' => 'file', 'required' => true],
                    ['name' => 'consent', 'label' => 'Подтверждаю достоверность сведений', 'type' => 'checkbox', 'required' => true],
                ],
                'Заявка принята и направлена на рассмотрение.',
            ],
        ];
        $ins = $pdo->prepare(
            "INSERT INTO forms (name, slug, fields_json, success_message, created_at)
             SELECT :n, :s, :f, :m, NOW()
             FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM forms WHERE slug = :s2)"
        );
        foreach ($forms as $form) {
            $ins->execute([
                ':n' => $form[0],
                ':s' => $form[1],
                ':f' => json_encode($form[2], JSON_UNESCAPED_UNICODE),
                ':m' => $form[3],
                ':s2' => $form[1],
            ]);
            $c['forms'] += $ins->rowCount();
        }
    }

    private static function seedTeam(PDO $pdo, array &$c): void
    {
        if (!self::tableExists($pdo, 'team_members')) {
            return;
        }
        $team = [
            ['Нуриддинов Шерзод Бахтиярович', 'Директор', 'Nuriddinov Sherzod Baxtiyarovich', 'Direktor'],
            ['Юлдашева Нилуфар Азизовна', 'Заместитель директора', 'Yuldasheva Nilufar Azizovna', 'Direktor o‘rinbosari'],
            ['Каримов Бехзод Шухратович', 'Руководитель направления стратегического анализа', 'Karimov Behzod Shuhratovich', 'Strategik tahlil yo‘nalishi rahbari'],
            ['Исмоилова Дилноза Фарходовна', 'Руководитель пресс-службы', 'Ismoilova Dilnoza Farhodovna', 'Matbuot xizmati rahbari'],
        ];
        $ins = $pdo->prepare(
            "INSERT INTO team_members (name, position, status, sort_order, created_at)
             SELECT :n, :p, 'published', :o, NOW()
             FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM team_members WHERE name = :n2)"
        );
        foreach ($team as $i => $t) {
            $ins->execute([':n' => $t[0], ':p' => $t[1], ':o' => $i, ':n2' => $t[0]]);
            $c['team'] += $ins->rowCount();

            if (self::tableExists($pdo, 'team_member_translations')) {
                $memberStmt = $pdo->prepare('SELECT id FROM team_members WHERE name = :name LIMIT 1');
                $memberStmt->execute([':name' => $t[0]]);
                $memberId = $memberStmt->fetchColumn();
                if ($memberId !== false) {
                    $translationIns = $pdo->prepare(
                        "INSERT INTO team_member_translations (member_id, lang, name, position)
                         SELECT :member_id, 'uz', :name, :position
                         FROM DUAL
                         WHERE NOT EXISTS (
                             SELECT 1 FROM team_member_translations
                             WHERE member_id = :member_id2 AND lang = 'uz'
                         )"
                    );
                    $translationIns->execute([
                        ':member_id' => (int) $memberId,
                        ':name' => $t[2],
                        ':position' => $t[3],
                        ':member_id2' => (int) $memberId,
                    ]);
                }
            }
        }
    }

    private static function seedPages(PDO $pdo, array &$c): void
    {
        // Страницы с переводами для 'ru' и 'uz'
        $pages = [
            'o-nas' => [
                'ru' => [
                    'title' => 'Об организации',
                    'blocks' => [
                        ['text', 'О нас', [
                            'title' => 'Правовой статус и законодательная основа деятельности',
                            'content' => '<h3>Законодательная основа и правовой статус</h3><p>Деятельность Агентства стратегического планирования и развития осуществляется в строгом соответствии с Конституцией Республики Узбекистан, законами Республики Узбекистан, а также нормативно-правовыми актами Президента Республики Узбекистан, нацеленными на модернизацию системы государственного управления.</p><div class="gov-card u-inline-d65cd622b8"><h4 class="u-inline-14ed279e35">Ключевые нормативно-правовые акты Агентства:</h4><ul class="u-inline-f07251c396"><li><strong>Указ Президента Республики Узбекистан № УП-201 от 30 октября 2025 года</strong> «О мерах по внедрению системы стратегического планирования и развития» — заложил основу для системного прогнозирования развития отраслей и регионов страны.</li><li><strong>Постановление Президента Республики Узбекистан № ПП-394 от 29 декабря 2025 года</strong> «О мерах по организации и эффективному налаживанию системы стратегического планирования и развития на основе новых подходов» — регламентирует структуру, организацию деятельности, полномочия и порядок взаимодействия Агентства с другими министерствами и ведомствами.</li><li><strong>Указ Президента Республики Узбекистан № УП-21 от 16 февраля 2026 года</strong> «О дополнительных мерах по последовательному продолжению реформ и выведению их на новый этап в рамках приоритетных направлений развития страны до 2030 года» — определяет ключевые KPI развития отраслей и координирующую роль Агентства в мониторинге реализации реформ до 2030 года.</li></ul></div><h3>Основные задачи и функции</h3><p>В соответствии с Указами Президента, на Агентство возложены следующие стратегические задачи:</p><ul><li><strong>Стратегическое планирование:</strong> Координация разработки и мониторинга реализации долгосрочных стратегий развития отраслей экономики и регионов.</li><li><strong>Оценка реформ (KPI до 2030 года):</strong> Разработка и внедрение системы ключевых показателей эффективности (KPI) для оценки хода реформ на основе Указа Президента № УП-21.</li><li><strong>Анализ и прогнозирование:</strong> Мониторинг макроэкономических показателей, выявление системных проблем и барьеров на пути реформ с подготовкой аналитических отчетов руководству страны.</li><li><strong>Методологическое руководство:</strong> Внедрение новых подходов и передовых международных стандартов стратегического планирования в деятельность органов исполнительной власти.</li></ul><h3>Регламент и прозрачность деятельности</h3><p>В соответствии с Законом РУз «Об открытости деятельности органов государственной власти и управления», Агентство обеспечивает полную прозрачность процессов разработки и мониторинга стратегий развития. Вся информация о ходе выполнения приоритетных направлений до 2030 года регулярно публикуется на нашем портале для ознакомления граждан, инвесторов и внутренних партнеров.</p>'
                        ]]
                    ]
                ],
                'uz' => [
                    'title' => 'Tashkilot haqida',
                    'blocks' => [
                        ['text', 'Tashkilot haqida', [
                            'title' => 'Huquqiy maqom va faoliyatning qonuniy asoslari',
                            'content' => '<h3>Qonunchilik asosi va huquqiy maqomi</h3><p>Strategik rejalashtirish va rivojlanish agentligi faoliyati O‘zbekiston Respublikasi Konstitutsiyasi, O‘zbekiston Respublikasi qonunlari, shuningdek, davlat boshqaruvi tizimini modernizatsiya qilishga qaratilgan O‘zbekiston Respublikasi Prezidentining normativ-huquqiy hujjatlariga qat’iy muvofiq ravishda amalga oshiriladi.</p><div class="gov-card u-inline-d65cd622b8"><h4 class="u-inline-14ed279e35">Agentlik faoliyatining asosiy normativ-huquqiy hujjatlari:</h4><ul class="u-inline-f07251c396"><li><strong>O‘zbekiston Respublikasi Prezidentining 2025-yil 30-oktabrdagi PF-201-son Farmoni</strong> «Strategik rejalashtirish va rivojlanish tizimini joriy etish bo‘yicha tashkiliy chora-tadbirlar to‘g‘risida» — tarmoqlar va hududlarni rivojlantirishni tizimli prognozlashtirish asosi etib belgilandi.</li><li><strong>O‘zbekiston Respublikasi Prezidentining 2025-yil 29-dekabrdagi PQ-394-son qarori</strong> «Strategik rejalashtirish va rivojlanish tizimini yangicha yondashuvlar asosida tashkil etish va samarali yo‘lga qo‘yish chora-tadbirlari to‘g‘risida» — Agentlikning tuzilmasini, faoliyatini tashkil etishni, vakolatlari va boshqa vazirliklar hamda idoralar bilan hamkorlik qilish tartibini belgilaydi.</li><li><strong>O‘zbekiston Respublikasi Prezidentining 2026-yil 16-fevraldagi PF-21-son Farmoni</strong> «Mamlakat taraqqiyotining 2030-yilgacha mo‘ljallangan ustuvor yo‘nalishlari doirasida islohotlarni izchil davom ettirish va yangi bosqichga olib chiqishning qo‘shimcha chora-tadbirlari to‘g‘risida» — islohotlar ijrosini monitoring qilishda Agentlikning muvofiqlashtiruvchi rolini hamda 2030-yilgacha bo‘lgan asosiy KPI ko‘rsatkichlarini belgilaydi.</li></ul></div><h3>Asosiy vazifalar va funksiyalar</h3><p>Prezident Farmonlariga muvofiq, Agentlik zimmasiga quyidagi strategik vazifalar yuklatilgan:</p><ul><li><strong>Strategik rejalashtirish:</strong> Iqtisodiyot tarmoqlari va hududlarni rivojlantirishning uzoq muddatli strategiyalarini ishlab chiqish va amalga oshirilishini muvofiqlashtirish.</li><li><strong>Islohotlarni baholash (2030-yilgacha bo‘lgan KPI):</strong> PF-21-son Farmoni asosida islohotlar ijrosini baholash uchun samaradorlik ko‘rsatkichlari (KPI) tizimini ishlab chiqish va joriy etish.</li><li><strong>Tahlil va prognozlash:</strong> Makroiqtisodiy ko‘rsatkichlarni monitoring qilish, tizimli muammolar va to‘siqlarni aniqlash, tahliliy hisobotlarni davlat rahbariyatiga taqdim etish.</li><li><strong>Metodologik rahbarlik:</strong> Davlat ijro etuvchi hokimiyat organlari faoliyatiga strategik rejalashtirishning yangi yondashuvlari va ilg‘or xalqaro standartlarini joriy etish.</li></ul><h3>Faoliyat reglamenti va shaffoflik</h3><p>O‘zbekiston Respublikasining «Davlat hokimiyati va boshqaruvi organlari faoliyatining ochiqligi to‘g‘risida»gi Qonuniga muvofiq, Agentlik rivojlanish strategiyalarini ishlab chiqish va monitoring qilish jarayonlarining to‘liq shaffofligini ta’minlaydi. 2030-yilgacha bo‘lgan ustuvor yo‘nalishlar ijrosi to‘g‘risidagi ma’lumotlar fuqarolar, investorlar va xalqaro hamkorlar uchun muntazam ravishda portalimizda e’lon qilib boriladi.</p>'
                        ]]
                    ]
                ]
            ],
            'rukovodstvo' => [
                'ru' => [
                    'title' => 'Руководство',
                    'blocks' => [
                        ['text', 'Введение', ['title' => 'Руководство', 'content' => '<p>Руководящий состав организации.</p>']],
                        ['team_list', 'Команда', ['title' => 'Руководящий состав', 'limit' => 0]],
                        ['cta_band', 'Директор', ['title' => 'Директор Агентства', 'text' => 'Биография, приоритеты работы и публикации руководителя.', 'button_text' => 'Страница директора', 'button_url' => '/direktor', 'bg_color' => '#072b61', 'text_color' => '#ffffff']]
                    ]
                ],
                'uz' => [
                    'title' => 'Rahbariyat',
                    'blocks' => [
                        ['text', 'Kirish', ['title' => 'Rahbariyat', 'content' => '<p>Tashkilotning rahbariyat tarkibi.</p>']],
                        ['team_list', 'Jamoa', ['title' => 'Rahbariyat tarkibi', 'limit' => 0]]
                    ]
                ]
            ],
            'struktura' => [
                'ru' => [
                    'title' => 'Структура',
                    'blocks' => [
                        ['org_structure', 'Оргсхема', [
                            'title' => 'Организационная структура',
                            'head_title' => 'Директор',
                            'head_name' => 'Нуриддинов Шерзод Бахтиярович',
                            'head_url' => '/direktor',
                            'side_items' => "Координационный совет\nСоветник директора",
                            'branches' => [
                                ['title' => 'Первый заместитель директора', 'name' => '', 'units' => "Отдел стратегического планирования\nОтдел макроэкономического анализа\nОтдел мониторинга реформ\nПроектный офис по развитию секторов экономики"],
                                ['title' => 'Заместитель директора', 'name' => '', 'units' => "Отдел регионального развития\nОтдел инвестиционной политики\nПроектный офис по развитию территорий\nОтдел международного сотрудничества"],
                                ['title' => 'Заместитель директора', 'name' => '', 'units' => "Юридический отдел\nОтдел управления персоналом\nОтдел финансов и бухгалтерского учёта\nПресс-служба и связи с общественностью\nОтдел цифровизации и информационных технологий"],
                            ],
                            'footnote' => 'Структура утверждена в установленном порядке и может уточняться при изменении задач Агентства.',
                        ]]
                    ]
                ],
                'uz' => [
                    'title' => 'Tuzilma',
                    'blocks' => [
                        ['org_structure', 'Tuzilma sxemasi', [
                            'title' => 'Tashkiliy tuzilma',
                            'head_title' => 'Direktor',
                            'head_name' => 'Nuriddinov Sherzod Baxtiyarovich',
                            'head_url' => '/direktor',
                            'side_items' => "Muvofiqlashtiruvchi kengash\nDirektor maslahatchisi",
                            'branches' => [
                                ['title' => 'Direktorning birinchi o‘rinbosari', 'name' => '', 'units' => "Strategik rejalashtirish bo‘limi\nMakroiqtisodiy tahlil bo‘limi\nIslohotlar monitoringi bo‘limi\nIqtisodiyot tarmoqlarini rivojlantirish loyihaviy ofisi"],
                                ['title' => 'Direktor o‘rinbosari', 'name' => '', 'units' => "Hududiy rivojlanish bo‘limi\nInvestitsiya siyosati bo‘limi\nHududlarni rivojlantirish loyihaviy ofisi\nXalqaro hamkorlik bo‘limi"],
                                ['title' => 'Direktor o‘rinbosari', 'name' => '', 'units' => "Yuridik bo‘lim\nPersonalni boshqarish bo‘limi\nMoliya va buxgalteriya hisobi bo‘limi\nMatbuot xizmati va jamoatchilik bilan aloqalar\nRaqamlashtirish va axborot texnologiyalari bo‘limi"],
                            ],
                            'footnote' => 'Tuzilma belgilangan tartibda tasdiqlangan bo‘lib, Agentlik vazifalari o‘zgarganda aniqlashtirilishi mumkin.',
                        ]]
                    ]
                ]
            ],
            'antikorrupciya' => [
                'ru' => [
                    'title' => 'Противодействие коррупции',
                    'blocks' => [
                        ['text', 'Антикоррупция', ['title' => 'Противодействие коррупции', 'content' => '<p>Организация проводит последовательную антикоррупционную политику. Ознакомиться с нормативными документами можно в разделе «Документы».</p><p>Сообщить о фактах коррупции можно через форму обратной связи.</p>']]
                    ]
                ],
                'uz' => [
                    'title' => 'Korrupsiyaga qarshi kurash',
                    'blocks' => [
                        ['text', 'Korrupsiyaga qarshi kurashish', ['title' => 'Korrupsiyaga qarshi kurashish', 'content' => '<p>Tashkilotda korrupsiyaga qarshi kurashish bo‘yicha tizimli siyosat yuritiladi. Normativ hujjatlar bilan «Hujjatlar» bo‘limida tanishishingiz mumkin.</p><p>Korrupsiya holatlari haqida xabar berish uchun qayta aloqa shaklidan foydalanishingiz mumkin.</p>']]
                    ]
                ]
            ]
        ];

        $prototypeFixture = \dirname(__DIR__, 2) . '/database/demo_assets/prototype_pages.json';
        if (is_file($prototypeFixture)) {
            $prototypePages = json_decode((string) file_get_contents($prototypeFixture), true);
            if (is_array($prototypePages)) {
                $assetAliases = [
                    '/uploads/public/hero-demo.jpg' => '/uploads/public/demo-strategy-meeting.jpg',
                    '/uploads/public/hero-demo-g2.jpg' => '/uploads/public/demo-urban-development.jpg',
                    '/uploads/public/hero-demo-g3.jpg' => '/uploads/public/demo-agency-hero.jpg',
                    '/uploads/public/hero-demo-g4.jpg' => '/uploads/public/demo-green-energy.jpg',
                ];
                array_walk_recursive($prototypePages, static function (&$value) use ($assetAliases): void {
                    if (is_string($value) && isset($assetAliases[$value])) {
                        $value = $assetAliases[$value];
                    }
                });
                $pages = array_replace($pages, $prototypePages);
            }
        }

        $pageIns = $pdo->prepare(
            "INSERT INTO pages
                (title, slug, meta_title, meta_description, `lead`, status, is_home,
                 layout_type, lang, translation_group_id, created_at)
             SELECT :t, :s, :mt, :md, :lead, 'published', 0,
                    'no_sidebar', :lang, :group_id, NOW()
             FROM DUAL
             WHERE NOT EXISTS (
                 SELECT 1 FROM pages WHERE slug = :s2 AND lang = :lang2
             )"
        );

        $transIns = $pdo->prepare(
            "INSERT INTO page_translations (page_id, lang, title, meta_title, meta_description, `lead`)
             SELECT :pid, :lang, :title, :meta_title, :meta_description, :lead
             FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM page_translations WHERE page_id = :pid2 AND lang = :lang2)"
        );

        $blockIns = $pdo->prepare(
            'INSERT INTO blocks (page_id, lang, type, title, data, sort_order, is_active, created_at)
             VALUES (:pid, :lang, :ty, :ti, :d, :so, 1, NOW())'
        );

        $createdPageSlugs = [];
        foreach ($pages as $slug => $langData) {
            if (!is_array($langData)) {
                continue;
            }
            $ruData = $langData['ru'] ?? null;
            if (!is_array($ruData)) {
                continue;
            }

            // Основная версия создаётся первой и становится корнем группы.
            // Благодаря UNIQUE(slug, lang) языковые версии используют один URL,
            // но в редакторе являются самостоятельными страницами.
            $orderedLangData = ['ru' => $ruData] + $langData;
            $groupId = null;
            foreach ($orderedLangData as $lang => $data) {
                if (!is_array($data)) {
                    continue;
                }
                $lang = (string) $lang;
                $title = (string) ($data['title'] ?? '');
                $blocks = $data['blocks'] ?? [];
                $metaTitle = $title . ($lang === 'uz'
                    ? ' — Strategik rivojlanish va islohotlar agentligi'
                    : ' — Агентство стратегического развития и реформ');
                $metaDescription = $lang === 'uz'
                    ? '«' . $title . '» bo‘limining rasmiy ma’lumotlari.'
                    : 'Официальная информация раздела «' . $title . '».';
                $lead = $lang === 'uz'
                    ? 'Agentlikning strategik tashabbuslari, natijalari va dolzarb materiallari.'
                    : 'Стратегические инициативы, результаты и актуальные материалы Агентства.';

                if ($lang !== 'ru' && $groupId === null) {
                    continue;
                }
                $pageIns->execute([
                    ':t' => $title,
                    ':s' => $slug,
                    ':mt' => $metaTitle,
                    ':md' => $metaDescription,
                    ':lead' => $lead,
                    ':lang' => $lang,
                    ':group_id' => $lang === 'ru' ? null : $groupId,
                    ':s2' => $slug,
                    ':lang2' => $lang,
                ]);
                $pageCreated = $pageIns->rowCount();
                $c['pages'] += $pageCreated;
                if ($pageCreated > 0) {
                    $createdPageSlugs[] = (string) $slug;
                }

                $pid = self::pageIdForLang($pdo, (string) $slug, $lang);
                if ($pid === null) {
                    continue;
                }
                if ($lang === 'ru') {
                    $pdo->prepare(
                        'UPDATE pages
                         SET translation_group_id = id
                         WHERE id = :id
                           AND (translation_group_id IS NULL OR translation_group_id = 0)'
                    )->execute([':id' => $pid]);
                    $groupStmt = $pdo->prepare(
                        'SELECT translation_group_id FROM pages WHERE id = :id LIMIT 1'
                    );
                    $groupStmt->execute([':id' => $pid]);
                    $storedGroupId = $groupStmt->fetchColumn();
                    $groupId = $storedGroupId !== false && (int) $storedGroupId > 0
                        ? (int) $storedGroupId
                        : $pid;
                } else {
                    $pdo->prepare(
                        'UPDATE pages
                         SET translation_group_id = :group_id
                         WHERE id = :id
                           AND (translation_group_id IS NULL OR translation_group_id = 0 OR translation_group_id = id)'
                    )->execute([':group_id' => $groupId, ':id' => $pid]);
                }

                // Сохраняем старую таблицу метаданных как совместимый fallback.
                $transIns->execute([
                    ':pid' => $groupId,
                    ':lang' => $lang,
                    ':title' => $title,
                    ':meta_title' => $metaTitle,
                    ':meta_description' => $metaDescription,
                    ':lead' => $lead,
                    ':pid2' => $groupId,
                    ':lang2' => $lang
                ]);

                $hasBlocksStmt = $pdo->prepare(
                    'SELECT COUNT(*) FROM blocks WHERE page_id = :page_id'
                );
                $hasBlocksStmt->execute([':page_id' => $pid]);
                $hasBlocks = (int) $hasBlocksStmt->fetchColumn() > 0;
                if (!$hasBlocks && is_array($blocks)) {
                    $order = 1;
                    // Оформление секций (фоны, отступы, появление) — по общим
                    // правилам ритма, чтобы демо-страницы не были плоскими.
                    // Тексты блоков при этом свои: демо-контент конкретного
                    // ведомства ценнее заготовок из готовых сборок.
                    $valid = array_values(array_filter(
                        $blocks,
                        static fn ($b): bool => is_array($b) && count($b) >= 3
                    ));
                    $looks = \App\Core\PagePresets::rhythmFor(
                        array_map(static fn (array $b): string => (string) $b[0], $valid)
                    );
                    $lookIndex = 0;
                    foreach ($blocks as $block) {
                        if (!is_array($block) || count($block) < 3) {
                            continue;
                        }
                        $type = (string) ($block[0] ?? '');
                        $btitle = (string) ($block[1] ?? '');
                        $blockData = $block[2] ?? [];
                        if ($type === 'form' && is_array($blockData) && !empty($blockData['form_slug'])) {
                            $formStmt = $pdo->prepare('SELECT id FROM forms WHERE slug = :slug LIMIT 1');
                            $formStmt->execute([':slug' => (string) $blockData['form_slug']]);
                            $formId = $formStmt->fetchColumn();
                            $blockData['form_id'] = $formId !== false ? (int) $formId : null;
                            unset($blockData['form_slug']);
                        }

                        // Своё оформление блока (если задано в демо-данных)
                        // важнее автоматического — не перетираем.
                        if (is_array($blockData)) {
                            $blockData += $looks[$lookIndex] ?? [];
                        }
                        $lookIndex++;

                        $blockIns->execute([
                            ':pid' => $pid,
                            ':lang' => $lang,
                            ':ty' => $type,
                            ':ti' => $btitle,
                            ':d' => json_encode($blockData, JSON_UNESCAPED_UNICODE),
                            ':so' => $order++
                        ]);
                    }
                }
            }
        }

        // Иерархия влияет на дерево и хлебные крошки, но не меняет URL.
        // При обычном идемпотентном запуске не переназначаем родителя у
        // существующих редакционных страниц — связываем только что созданные.
        $hierarchy = [
            'rukovodstvo' => 'o-nas',
            'struktura' => 'o-nas',
            'direktor' => 'rukovodstvo',
            'antikorrupciya' => 'o-nas',
            'strategiya-2030' => 'napravleniya',
            'ustoychivyy-ekonomicheskiy-rost' => 'napravleniya',
            'analitika' => 'napravleniya',
            'meropriyatiya' => 'press-centr',
        ];
        $setParent = $pdo->prepare(
            'UPDATE pages child
             INNER JOIN pages parent ON parent.slug = :parent_slug AND parent.lang = child.lang
             SET child.parent_id = parent.id
             WHERE child.slug = :child_slug
               AND child.parent_id IS NULL
               AND child.deleted_at IS NULL
               AND parent.deleted_at IS NULL'
        );
        foreach ($hierarchy as $childSlug => $parentSlug) {
            if (!in_array($childSlug, $createdPageSlugs, true)) {
                continue;
            }
            $setParent->execute([
                ':child_slug' => $childSlug,
                ':parent_slug' => $parentSlug,
            ]);
        }
    }

    private static function seedMenu(PDO $pdo, array &$c): void
    {
        if (!self::tableExists($pdo, 'menu_items')) {
            return;
        }
        if ((int) $pdo->query('SELECT COUNT(*) FROM menu_items')->fetchColumn() > 0) {
            return;
        }
        $menus = [
            'ru' => [
                ['title' => 'Агентство', 'type' => 'page', 'value' => 'o-nas', 'mega' => 3, 'children' => [
                    ['Об агентстве', 'page', 'o-nas'],
                    ['Руководство', 'page', 'rukovodstvo'],
                    ['Структура', 'page', 'struktura'],
                    ['Директор', 'page', 'direktor'],
                    ['Противодействие коррупции', 'page', 'antikorrupciya'],
                ]],
                ['title' => 'Деятельность', 'type' => 'page', 'value' => 'napravleniya', 'mega' => 3, 'children' => [
                    ['Стратегия «Узбекистан–2030»', 'page', 'strategiya-2030', '2030'],
                    ['Приоритетные направления', 'page', 'napravleniya'],
                    ['Устойчивый экономический рост', 'page', 'ustoychivyy-ekonomicheskiy-rost'],
                    ['Проекты и инициативы', 'custom', '/projects'],
                    ['Аналитика', 'page', 'analitika'],
                ]],
                ['title' => 'Пресс-центр', 'type' => 'page', 'value' => 'press-centr', 'mega' => 2, 'children' => [
                    ['Новости', 'news_index', ''],
                    ['Мероприятия', 'page', 'meropriyatiya'],
                    ['Фотоальбомы', 'custom', '/albums'],
                    ['Видеоматериалы', 'custom', '/videos'],
                ]],
                ['title' => 'Открытые данные', 'type' => 'custom', 'value' => '/catalog/documenty', 'mega' => 2, 'children' => [
                    ['Документы', 'custom', '/catalog/documenty'],
                    ['Тендеры', 'custom', '/catalog/tendery'],
                    ['Вакансии', 'custom', '/catalog/vakansii'],
                ]],
                ['title' => 'Контакты', 'type' => 'page', 'value' => 'kontakty', 'mega' => 0, 'children' => []],
            ],
            'uz' => [
                ['title' => 'Agentlik', 'type' => 'page', 'value' => 'o-nas', 'mega' => 3, 'children' => [
                    ['Agentlik haqida', 'page', 'o-nas'],
                    ['Rahbariyat', 'page', 'rukovodstvo'],
                    ['Tuzilma', 'page', 'struktura'],
                    ['Direktor', 'page', 'direktor'],
                    ['Korrupsiyaga qarshi kurash', 'page', 'antikorrupciya'],
                ]],
                ['title' => 'Faoliyat', 'type' => 'page', 'value' => 'napravleniya', 'mega' => 3, 'children' => [
                    ['«O‘zbekiston–2030» strategiyasi', 'page', 'strategiya-2030', '2030'],
                    ['Ustuvor yo‘nalishlar', 'page', 'napravleniya'],
                    ['Barqaror iqtisodiy o‘sish', 'page', 'ustoychivyy-ekonomicheskiy-rost'],
                    ['Loyihalar va tashabbuslar', 'custom', '/projects'],
                    ['Tahlil', 'page', 'analitika'],
                ]],
                ['title' => 'Matbuot markazi', 'type' => 'page', 'value' => 'press-centr', 'mega' => 2, 'children' => [
                    ['Yangiliklar', 'news_index', ''],
                    ['Tadbirlar', 'page', 'meropriyatiya'],
                    ['Fotoalbomlar', 'custom', '/albums'],
                    ['Videomateriallar', 'custom', '/videos'],
                ]],
                ['title' => 'Ochiq ma’lumotlar', 'type' => 'custom', 'value' => '/catalog/documenty', 'mega' => 2, 'children' => [
                    ['Hujjatlar', 'custom', '/catalog/documenty'],
                    ['Tenderlar', 'custom', '/catalog/tendery'],
                    ['Bo‘sh ish o‘rinlari', 'custom', '/catalog/vakansii'],
                ]],
                ['title' => 'Aloqa', 'type' => 'page', 'value' => 'kontakty', 'mega' => 0, 'children' => []],
            ],
        ];
        $langs = $pdo->query('SELECT code FROM languages WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll(PDO::FETCH_COLUMN) ?: [];
        if ($langs === []) {
            return;
        }
        $ins = $pdo->prepare(
            'INSERT INTO menu_items
                (lang, title, badge_text, badge_color, badge_pos, url_type, url_value, parent_id, mega_columns, sort_order, is_active, created_at)
             VALUES
                (:lang, :title, :badge, :badge_color, :badge_pos, :url_type, :url_value, :parent_id, :mega, :sort_order, 1, NOW())'
        );
        foreach ($langs as $lang) {
            $lang = (string) $lang;
            $items = $menus[$lang] ?? $menus['ru'];
            foreach ($items as $i => $item) {
                $ins->execute([
                    ':lang' => $lang,
                    ':title' => $item['title'],
                    ':badge' => null,
                    ':badge_color' => 'blue',
                    ':badge_pos' => 'right',
                    ':url_type' => $item['type'],
                    ':url_value' => $item['value'],
                    ':parent_id' => null,
                    ':mega' => $item['mega'],
                    ':sort_order' => $i,
                ]);
                $c['menu'] += $ins->rowCount();
                $parentId = (int) $pdo->lastInsertId();
                foreach ($item['children'] as $childOrder => $child) {
                    $ins->execute([
                        ':lang' => $lang,
                        ':title' => $child[0],
                        ':badge' => $child[3] ?? null,
                        ':badge_color' => isset($child[3]) ? 'blue' : null,
                        ':badge_pos' => 'right',
                        ':url_type' => $child[1],
                        ':url_value' => $child[2],
                        ':parent_id' => $parentId,
                        ':mega' => 0,
                        ':sort_order' => $childOrder,
                    ]);
                    $c['menu'] += $ins->rowCount();
                }
            }
        }
    }

    private static function typeId(PDO $pdo, string $slug): ?int
    {
        $stmt = $pdo->prepare('SELECT id FROM content_types WHERE slug = :s LIMIT 1');
        $stmt->execute([':s' => $slug]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    private static function tableExists(PDO $pdo, string $table): bool
    {
        $driver = strtolower((string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
        if ($driver === 'sqlite') {
            $stmt = $pdo->prepare(
                "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = :name LIMIT 1"
            );
            $stmt->execute([':name' => $table]);

            return (bool) $stmt->fetchColumn();
        }

        $stmt = $pdo->prepare(
            'SELECT 1
             FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = :name
             LIMIT 1'
        );
        $stmt->execute([':name' => $table]);

        return (bool) $stmt->fetchColumn();
    }

    private static function storeVersion(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'settings')) {
            return;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO settings (`key`, `value`, updated_at)
             VALUES ('demo_data_version', :version, NOW())
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = NOW()"
        );
        $stmt->execute([':version' => self::DEMO_VERSION]);
    }

    private static function isUntouchedStarterHome(PDO $pdo, int $pageId): bool
    {
        $stmt = $pdo->prepare(
            'SELECT id, type, title, data FROM blocks
             WHERE page_id = :page_id AND parent_block_id IS NULL ORDER BY sort_order, id'
        );
        $stmt->execute([':page_id' => $pageId]);
        $blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $expected = [
            ['cta', 'Hero', ['title' => 'Официальный сайт организации', 'text' => 'Актуальная информация, документы, новости и услуги в одном месте.', 'button_text' => 'Последние новости', 'button_url' => '/news', '_spacing' => 'max']],
            ['columns', 'Быстрые ссылки', ['columns' => 3, 'gap' => 'medium', '_spacing' => 'premium']],
            ['news_latest', 'Последние новости', ['title' => 'Последние новости', 'limit' => 3, '_spacing' => 'premium']],
        ];
        if (count($blocks) !== count($expected)) {
            return false;
        }
        foreach ($blocks as $i => $block) {
            $data = json_decode((string) ($block['data'] ?? ''), true);
            if (($block['type'] ?? '') !== $expected[$i][0]
                || ($block['title'] ?? '') !== $expected[$i][1]
                || $data !== $expected[$i][2]) {
                return false;
            }
        }

        $columnsId = (int) ($blocks[1]['id'] ?? 0);
        $childStmt = $pdo->prepare('SELECT data FROM blocks WHERE parent_block_id = :id ORDER BY column_index, sort_order, id');
        $childStmt->execute([':id' => $columnsId]);
        $children = $childStmt->fetchAll(PDO::FETCH_COLUMN);
        $expectedUrls = ['/catalog/documenty', '/catalog/vakansii', '/catalog/tendery'];
        if (count($children) !== count($expectedUrls)) {
            return false;
        }
        foreach ($children as $i => $json) {
            $data = json_decode((string) $json, true);
            if (!is_array($data) || ($data['button_url'] ?? '') !== $expectedUrls[$i]) {
                return false;
            }
        }

        return true;
    }

    private static function pageIdForLang(PDO $pdo, string $slug, string $lang): ?int
    {
        $stmt = $pdo->prepare(
            'SELECT id
             FROM pages
             WHERE slug = :slug AND lang = :lang AND deleted_at IS NULL
             ORDER BY id ASC
             LIMIT 1'
        );
        $stmt->execute([':slug' => $slug, ':lang' => $lang]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    private static function defaultLang(PDO $pdo): string
    {
        try {
            $code = $pdo->query('SELECT code FROM languages WHERE is_default = 1 LIMIT 1')->fetchColumn();
            if ($code !== false && $code !== '') {
                return (string) $code;
            }
        } catch (\Throwable $e) {
            // таблица языков может отсутствовать в минимальной установке
        }

        return 'ru';
    }
}
