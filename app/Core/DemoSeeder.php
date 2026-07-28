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
    /** @return array<string,int> счётчики добавленного по разделам */
    public static function run(PDO $pdo): array
    {
        $c = ['assets' => 0, 'home' => 0, 'news' => 0, 'documenty' => 0, 'vakansii' => 0, 'tendery' => 0, 'projects' => 0, 'albums' => 0, 'videos' => 0, 'forms' => 0, 'team' => 0, 'pages' => 0, 'menu' => 0];

        self::seedAssets($pdo, $c);
        self::seedNews($pdo, $c);
        self::seedEntries($pdo, $c);
        self::seedProjects($pdo, $c);
        self::seedMedia($pdo, $c);
        self::seedForms($pdo, $c);
        self::seedTeam($pdo, $c);
        self::seedHome($pdo, $c);
        self::seedPages($pdo, $c);
        return $c;
    }

    /**
     * Выполняет полный сброс разделов (очистку контента) и загрузку эталонного комплекта «с чистого листа».
     * @return array<string,int>
     */
    public static function resetAndRun(PDO $pdo): array
    {
        $tables = [
            'blocks',
            'page_translations',
            'pages',
            'news_images',
            'news_translations',
            'news',
            'content_entries',
            'project_translations',
            'projects',
            'photo_album_images',
            'photo_albums',
            'videos',
            'forms',
            'team_members',
            'menu_items',
        ];

        $driver = strtolower((string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
        if ($driver === 'mysql') {
            try { $pdo->exec('SET FOREIGN_KEY_CHECKS = 0'); } catch (\Throwable) {}
        }

        foreach ($tables as $t) {
            try {
                $hasTable = $driver === 'sqlite'
                    ? (bool) $pdo->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='{$t}'")->fetchColumn()
                    : (bool) $pdo->query("SHOW TABLES LIKE '{$t}'")->fetchColumn();
                if ($hasTable) {
                    $pdo->exec('DELETE FROM `' . $t . '`');
                }
            } catch (\Throwable) {}
        }

        if ($driver === 'mysql') {
            try { $pdo->exec('SET FOREIGN_KEY_CHECKS = 1'); } catch (\Throwable) {}
        }

        return self::run($pdo);
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
        if (!is_dir($dest)) {
            @mkdir($dest, 0775, true);
        }

        $hasFiles = (bool) $pdo->query("SHOW TABLES LIKE 'files'")->fetchColumn();
        $fileIns = $hasFiles ? $pdo->prepare(
            "INSERT INTO files (original_name, stored_name, mime_type, size, access_type, uploaded_by, created_at)
             SELECT :n, :s, 'image/jpeg', :sz, 'public', NULL, NOW()
             FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM files WHERE stored_name = :s2)"
        ) : null;

        foreach (glob($src . '/*.jpg') ?: [] as $file) {
            $name = basename($file);
            $target = $dest . '/' . $name;
            if (!is_file($target)) {
                @copy($file, $target);
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
        $fixture = \dirname(__DIR__, 2) . '/database/demo_assets/home_blocks.json';
        if (!is_file($fixture)) {
            return;
        }
        $blocks = json_decode((string) file_get_contents($fixture), true);
        if (!is_array($blocks) || $blocks === []) {
            return;
        }

        // Есть ли уже главная страница сайта?
        $homeId = $pdo->query('SELECT id FROM pages WHERE is_home = 1 LIMIT 1')->fetchColumn();
        if ($homeId === false) {
            // Не переносим главную у существующего сайта — создаём, только если её нет.
            $pdo->prepare(
                "INSERT INTO pages (title, slug, status, is_home, layout_type, transparent_header, created_at)
                 SELECT 'Главная', 'home', 'published', 1, 'no_sidebar', 1, NOW()
                 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = 'home')"
            )->execute();
            $homeId = $pdo->query('SELECT id FROM pages WHERE is_home = 1 LIMIT 1')->fetchColumn();
            if ($homeId === false) {
                $homeId = $pdo->query("SELECT id FROM pages WHERE slug = 'home' LIMIT 1")->fetchColumn();
            }
            $c['pages'] += $homeId !== false ? 1 : 0;
        }
        if ($homeId === false) {
            return;
        }
        $homeId = (int) $homeId;

        // Наполняем демо-главную, только если она пуста или содержит нетронутую
        // «стартовую» вёрстку из schema.sql (cta+columns+news_latest). Если
        // редактор уже менял главную — не трогаем, чтобы не стереть работу.
        $count = (int) $pdo->query('SELECT COUNT(*) FROM blocks WHERE page_id = ' . $homeId)->fetchColumn();
        if ($count > 0) {
            if (!self::isUntouchedStarterHome($pdo, $homeId)) {
                return;
            }
            $pdo->exec('DELETE FROM blocks WHERE page_id = ' . $homeId);
        }

        // Демо-главной нужна прозрачная шапка поверх hero.
        $pdo->prepare('UPDATE pages SET transparent_header = 1, layout_type = ? WHERE id = ?')
            ->execute(['no_sidebar', $homeId]);

        $lang = self::defaultLang($pdo);
        $ins = $pdo->prepare(
            'INSERT INTO blocks (page_id, lang, type, title, data, sort_order, is_active, created_at)
             VALUES (:pid, :lang, :ty, :ti, :d, :so, 1, NOW())'
        );
        $order = 1;
        foreach ($blocks as $b) {
            if (!isset($b['type'])) {
                continue;
            }
            $ins->execute([
                ':pid' => $homeId,
                ':lang' => $lang,
                ':ty' => (string) $b['type'],
                ':ti' => (string) ($b['title'] ?? ''),
                ':d' => json_encode($b['data'] ?? [], JSON_UNESCAPED_UNICODE),
                ':so' => $order++,
            ]);
        }
        $c['home'] = $order - 1;
    }

    private static function seedNews(PDO $pdo, array &$c): void
    {
        self::seedFlagshipNews($pdo, $c);

        $news = [
            ['Запуск обновлённого официального портала', 'zapusk-portala', 'Представлен новый сайт организации с современным дизайном, удобной навигацией и версией для слабовидящих.'],
            ['График приёма граждан на квартал', 'grafik-priema', 'Опубликовано расписание личного приёма граждан руководством организации.'],
            ['Итоги деятельности за год', 'itogi-goda', 'Подведены основные итоги работы и ключевые показатели за отчётный период.'],
            ['Расширен перечень электронных услуг', 'elektronnye-uslugi', 'Теперь больше документов можно получить онлайн без личного визита.'],
            ['Объявлен новый набор специалистов', 'nabor-specialistov', 'Открыты вакансии в нескольких подразделениях. Подробности — в разделе «Вакансии».'],
        ];
        // Обложки берём из демо-изображений (регистрируются в seedAssets).
        $covers = ['/uploads/public/hero-demo.jpg', '/uploads/public/hero-demo-g2.jpg', '/uploads/public/hero-demo-g3.jpg', '/uploads/public/hero-demo-g4.jpg'];
        $ins = $pdo->prepare(
            "INSERT INTO news (title, slug, excerpt, content, image, status, published_at, created_at)
             SELECT :t, :s, :e, :co, :img, 'published', NOW() - INTERVAL :d DAY, NOW()
             FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM news WHERE slug = :s2)"
        );
        foreach ($news as $i => $n) {
            $ins->execute([':t' => $n[0], ':s' => $n[1], ':e' => $n[2], ':co' => '<p>' . $n[2] . '</p><p>Полный текст материала.</p>', ':img' => $covers[$i % count($covers)], ':d' => $i * 2, ':s2' => $n[1]]);
            $c['news'] += $ins->rowCount();
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
            "INSERT INTO news (title, slug, excerpt, badge, content, image, key_points, event_meta, docs, source_note, layout_type, status, published_at, created_at)
             SELECT :t, :s, :e, :b, :co, :img, :kp, :em, :dc, :sn, 'premium', 'published', NOW() - INTERVAL 1 DAY, NOW()
             FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM news WHERE slug = :s2)"
        );
        $ins->execute([
            ':t' => 'Заседание по вопросам реализации Стратегии «Узбекистан–2030»',
            ':s' => $slug,
            ':e' => 'Обсуждены ключевые приоритеты и ход реализации стратегических инициатив, направленных на устойчивое развитие страны и повышение благосостояния населения.',
            ':b' => 'Мероприятие',
            ':co' => $content,
            ':img' => '/uploads/public/demo-strategy-meeting.jpg',
            ':kp' => "Рассмотрены приоритетные направления Стратегии «Узбекистан–2030»\nПроанализирован прогресс реализации ключевых инициатив\nУтверждены дальнейшие шаги и ответственные исполнители\nОсобое внимание уделено инвестициям, инновациям и человеческому капиталу",
            ':em' => "Дата: 20 мая 2026 года\nФормат: расширенное заседание\nУчастники: министерства, ведомства, регионы",
            ':dc' => json_encode($docs, JSON_UNESCAPED_UNICODE),
            ':sn' => 'Подготовлено пресс-службой Агентства',
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

            if ((bool) $pdo->query("SHOW TABLES LIKE 'news_translations'")->fetchColumn()) {

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

            if ((bool) $pdo->query("SHOW TABLES LIKE 'news_images'")->fetchColumn()) {
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
                ['Приказ №112 об утверждении регламента', 'prikaz-112', ['doc_number' => '112', 'doc_date' => '2026-05-14', 'category' => 'Приказы', 'summary' => 'Об утверждении регламента предоставления государственных услуг.']],
                ['Постановление №34 о мерах поддержки', 'postanovlenie-34', ['doc_number' => '34', 'doc_date' => '2026-04-02', 'category' => 'Постановления', 'summary' => 'О мерах по улучшению качества обслуживания граждан.']],
                ['Приказ №118 о структуре организации', 'prikaz-118', ['doc_number' => '118', 'doc_date' => '2026-05-28', 'category' => 'Приказы', 'summary' => 'Об утверждении организационной структуры.']],
                ['Регламент рассмотрения обращений', 'reglament-obrashcheniy', ['doc_number' => '7-Р', 'doc_date' => '2026-03-10', 'category' => 'Регламенты', 'summary' => 'Порядок и сроки рассмотрения обращений граждан.']],
                ['Отчёт о деятельности за год', 'otchet-god', ['doc_number' => 'ОТ-2026', 'doc_date' => '2026-01-20', 'category' => 'Отчёты', 'summary' => 'Годовой отчёт о результатах деятельности.']],
                ['Положение об антикоррупционной политике', 'polozhenie-antikorrupciya', ['doc_number' => '5-П', 'doc_date' => '2026-02-15', 'category' => 'Положения', 'summary' => 'Основные принципы противодействия коррупции.']],
            ],
            'vakansii' => [
                ['Ведущий специалист отдела ИТ', 'vedushchiy-it', ['department' => 'Отдел информационных технологий', 'salary' => 'по договорённости', 'deadline' => '2026-08-31', 'requirements' => 'Высшее образование, опыт от 3 лет, знание PHP/MySQL.', 'duties' => 'Сопровождение и развитие информационных систем.']],
                ['Юрисконсульт', 'yuriskonsult', ['department' => 'Юридический отдел', 'salary' => 'от 8 000 000 сум', 'deadline' => '2026-08-20', 'requirements' => 'Высшее юридическое образование, опыт от 2 лет.', 'duties' => 'Правовое сопровождение деятельности организации.']],
                ['Специалист по кадрам', 'specialist-kadry', ['department' => 'Отдел кадров', 'salary' => 'от 6 000 000 сум', 'deadline' => '2026-09-10', 'requirements' => 'Опыт кадрового делопроизводства.', 'duties' => 'Ведение кадрового учёта и документации.']],
                ['Пресс-секретарь', 'press-sekretar', ['department' => 'Пресс-служба', 'salary' => 'по итогам собеседования', 'deadline' => '2026-08-05', 'requirements' => 'Опыт в СМИ или PR, грамотная речь.', 'duties' => 'Взаимодействие со СМИ, ведение новостей сайта.']],
            ],
            'tendery' => [
                ['Поставка компьютерной техники', 'postavka-tekhniki', ['tender_number' => 'T-2026-014', 'budget' => '350 000 000 сум', 'start_date' => '2026-06-01', 'deadline' => '2026-07-15', 'summary' => 'Закупка рабочих станций и периферии.']],
                ['Ремонт административного здания', 'remont-zdaniya', ['tender_number' => 'T-2026-019', 'budget' => '1 200 000 000 сум', 'start_date' => '2026-06-10', 'deadline' => '2026-08-01', 'summary' => 'Капитальный ремонт помещений.']],
                ['Услуги охраны объектов', 'uslugi-ohrany', ['tender_number' => 'T-2026-021', 'budget' => '480 000 000 сум', 'start_date' => '2026-06-20', 'deadline' => '2026-07-30', 'summary' => 'Физическая охрана административных объектов.']],
                ['Разработка мобильного приложения', 'razrabotka-prilozheniya', ['tender_number' => 'T-2026-025', 'budget' => '600 000 000 сум', 'start_date' => '2026-07-01', 'deadline' => '2026-08-20', 'summary' => 'Создание мобильного приложения для граждан.']],
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
            }
        }
    }

    private static function seedProjects(PDO $pdo, array &$c): void
    {
        if (!(bool) $pdo->query("SHOW TABLES LIKE 'projects'")->fetchColumn()) {
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
        }

        if ((bool) $pdo->query("SHOW TABLES LIKE 'project_translations'")->fetchColumn()) {
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
        if ((bool) $pdo->query("SHOW TABLES LIKE 'photo_albums'")->fetchColumn()) {
            $albums = [
                ['strategiya-2030-v-deystvii', 'Стратегия «Узбекистан–2030» в действии', 'Рабочие заседания, презентации и обсуждение приоритетных реформ.', '/uploads/public/demo-strategy-meeting.jpg'],
                ['regionalnoe-razvitie', 'Развитие регионов Узбекистана', 'Проекты инфраструктуры и новые точки экономического роста.', '/uploads/public/demo-urban-development.jpg'],
            ];
            $albumIns = $pdo->prepare(
                "INSERT INTO photo_albums (title, slug, description, cover_url, is_published, is_featured, created_at)
                 SELECT :t, :s, :d, :c, 1, 1, NOW()
                 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM photo_albums WHERE slug = :s2)"
            );
            foreach ($albums as $album) {
                $albumIns->execute([':t' => $album[1], ':s' => $album[0], ':d' => $album[2], ':c' => $album[3], ':s2' => $album[0]]);
                $c['albums'] += $albumIns->rowCount();
                if ((bool) $pdo->query("SHOW TABLES LIKE 'photo_album_images'")->fetchColumn()) {
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
                    }
                }
            }
        }

        if ((bool) $pdo->query("SHOW TABLES LIKE 'videos'")->fetchColumn()) {
            $videos = [
                ['uzbekistan-2030-klyuchevye-celi', 'Узбекистан–2030: ключевые цели и приоритеты', '/uploads/public/demo-strategy-meeting.jpg', '02:35'],
                ['zelenaya-ekonomika', 'Переход к «зелёной» экономике', '/uploads/public/demo-green-energy.jpg', '03:12'],
                ['cifrovye-gosuslugi', 'Цифровая трансформация государственных услуг', '/uploads/public/demo-agency-hero.jpg', '02:08'],
            ];
            $videoIns = $pdo->prepare(
                "INSERT INTO videos (title, slug, description, cover_url, video_url, duration, is_published, is_featured, sort_order, created_at)
                 SELECT :t, :s, :d, :c, '/press-centr', :du, 1, 1, :o, NOW()
                 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM videos WHERE slug = :s2)"
            );
            foreach ($videos as $i => $video) {
                $videoIns->execute([':t' => $video[1], ':s' => $video[0], ':d' => 'Информационный видеоматериал Агентства.', ':c' => $video[2], ':du' => $video[3], ':o' => $i, ':s2' => $video[0]]);
                $c['videos'] += $videoIns->rowCount();
            }
        }
    }

    private static function seedForms(PDO $pdo, array &$c): void
    {
        if (!(bool) $pdo->query("SHOW TABLES LIKE 'forms'")->fetchColumn()) {
            return;
        }
        $fields = [
            ['name' => 'name', 'label' => 'Ваше имя', 'type' => 'text', 'required' => true],
            ['name' => 'email', 'label' => 'E-mail', 'type' => 'email', 'required' => true],
            ['name' => 'phone', 'label' => 'Телефон', 'type' => 'tel', 'required' => false],
            ['name' => 'topic', 'label' => 'Тема обращения', 'type' => 'select', 'options' => 'Общий вопрос,Предложение,Запрос информации,Запись на приём', 'required' => true],
            ['name' => 'message', 'label' => 'Сообщение', 'type' => 'textarea', 'required' => true],
        ];
        $ins = $pdo->prepare(
            "INSERT INTO forms (name, slug, fields_json, success_message, created_at)
             SELECT :n, :s, :f, :m, NOW()
             FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM forms WHERE slug = :s2)"
        );
        $ins->execute([
            ':n' => 'Обращение в Агентство',
            ':s' => 'public-appeal',
            ':f' => json_encode($fields, JSON_UNESCAPED_UNICODE),
            ':m' => 'Спасибо! Ваше обращение зарегистрировано.',
            ':s2' => 'public-appeal',
        ]);
        $c['forms'] += $ins->rowCount();
    }

    private static function seedTeam(PDO $pdo, array &$c): void
    {
        if (!(bool) $pdo->query("SHOW TABLES LIKE 'team_members'")->fetchColumn()) {
            return;
        }
        $team = [
            ['Нуриддинов Шерзод Бахтиярович', 'Директор'],
            ['Юлдашева Нилуфар Азизовна', 'Заместитель директора'],
            ['Каримов Бехзод Шухратович', 'Начальник юридического отдела'],
            ['Исмоилова Дилноза Фарходовна', 'Руководитель пресс-службы'],
        ];
        $ins = $pdo->prepare(
            "INSERT INTO team_members (name, position, status, sort_order, created_at)
             SELECT :n, :p, 'published', :o, NOW()
             FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM team_members WHERE name = :n2)"
        );
        foreach ($team as $i => $t) {
            $ins->execute([':n' => $t[0], ':p' => $t[1], ':o' => $i, ':n2' => $t[0]]);
            $c['team'] += $ins->rowCount();
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
            "INSERT INTO pages (title, slug, status, is_home, layout_type, created_at)
             SELECT :t, :s, 'published', 0, 'no_sidebar', NOW()
             FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM pages WHERE slug = :s2)"
        );

        $transIns = $pdo->prepare(
            "INSERT INTO page_translations (page_id, lang, title)
             SELECT :pid, :lang, :title
             FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM page_translations WHERE page_id = :pid2 AND lang = :lang2)"
        );

        $blockIns = $pdo->prepare(
            'INSERT INTO blocks (page_id, lang, type, title, data, sort_order, is_active, created_at)
             VALUES (:pid, :lang, :ty, :ti, :d, :so, 1, NOW())'
        );

        foreach ($pages as $slug => $langData) {
            if (!is_array($langData)) {
                continue;
            }
            $ruData = $langData['ru'] ?? [];
            $defaultTitle = is_array($ruData) ? (string) ($ruData['title'] ?? '') : '';
            $pageIns->execute([':t' => $defaultTitle, ':s' => $slug, ':s2' => $slug]);
            $c['pages'] += $pageIns->rowCount();
            $pid = self::pageId($pdo, $slug);
            if ($pid === null) {
                continue;
            }

            $hasBlocks = (int) $pdo->query('SELECT COUNT(*) FROM blocks WHERE page_id = ' . $pid)->fetchColumn() > 0;

            foreach ($langData as $lang => $data) {
                if (!is_array($data)) {
                    continue;
                }
                $title = (string) ($data['title'] ?? '');
                $blocks = $data['blocks'] ?? [];

                // Вставляем перевод страницы
                $transIns->execute([
                    ':pid' => $pid,
                    ':lang' => $lang,
                    ':title' => $title,
                    ':pid2' => $pid,
                    ':lang2' => $lang
                ]);

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
    }

    private static function seedMenu(PDO $pdo, array &$c): void
    {
        if (!(bool) $pdo->query("SHOW TABLES LIKE 'menu_items'")->fetchColumn()) {
            return;
        }
        if ((int) $pdo->query('SELECT COUNT(*) FROM menu_items')->fetchColumn() > 0) {
            return;
        }
        $items = [
            ['О нас', '/o-nas'],
            ['Направления', '/napravleniya'],
            ['Проекты', '/projects'],
            ['Аналитика', '/analitika'],
            ['Документы', '/catalog/documenty'],
            ['Пресс-центр', '/press-centr'],
            ['Контакты', '/kontakty'],
        ];
        // Меню привязано к конкретному языку (без «Все языки») — сеем свой набор
        // для каждого активного языка.
        $langs = $pdo->query('SELECT code FROM languages WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll(PDO::FETCH_COLUMN) ?: [];
        if ($langs === []) {
            return;
        }
        $ins = $pdo->prepare(
            "INSERT INTO menu_items (lang, title, url_type, url_value, sort_order, is_active, created_at)
             SELECT :lang, :t, 'custom', :u, :o, 1, NOW()
             FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE url_value = :u2 AND lang = :lang2)"
        );
        foreach ($langs as $lang) {
            foreach ($items as $i => $it) {
                $ins->execute([':lang' => (string) $lang, ':t' => $it[0], ':u' => $it[1], ':o' => $i, ':u2' => $it[1], ':lang2' => (string) $lang]);
                $c['menu'] += $ins->rowCount();
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

    private static function pageId(PDO $pdo, string $slug): ?int
    {
        $stmt = $pdo->prepare('SELECT id FROM pages WHERE slug = :s LIMIT 1');
        $stmt->execute([':s' => $slug]);
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
