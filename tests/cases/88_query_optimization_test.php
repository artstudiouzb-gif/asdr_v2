<?php

declare(strict_types=1);

use App\Models\News;

test('Предзагруженная обложка галереи не требует отдельного SQL-запроса', function () {
    assert_same('/uploads/public/news/cover.webp', News::getCoverImage([
        'id' => 42,
        'image' => '',
        'video_url' => '',
        'first_gallery_image' => '/uploads/public/news/cover.webp',
    ]));
});

test('Списки новостей загружают переводы одним пакетным запросом', function () {
    $news = (string) file_get_contents(APP_ROOT . '/app/Models/News.php');
    $translations = (string) file_get_contents(APP_ROOT . '/app/Models/NewsTranslation.php');

    assert_contains('self::localizePublicRows(', $news);
    assert_contains('NewsTranslation::forNewsIds(', $news);
    assert_contains('WHERE news_id IN ({$placeholders}) AND lang = ?', $translations);
});

test('Списки новостей и меню повторно используются внутри одного запроса', function () {
    $news = (string) file_get_contents(APP_ROOT . '/app/Models/News.php');
    $menu = (string) file_get_contents(APP_ROOT . '/app/Models/MenuItem.php');

    assert_contains('$publishedRequestCache', $news);
    assert_contains('$activeRequestCache', $menu);
});

test('Страница не предзагружает все локальные шрифты одновременно', function () {
    $header = (string) file_get_contents(APP_ROOT . '/app/Views/site/_header.php');
    assert_contains('$fontPreloads', $header);
    assert_contains('array_keys($fontPreloads)', $header);
});

test('Сервер отдаёт заранее сжатые бандлы, а не жмёт их заново', function () {
    // npm run build:assets кладёт рядом с бандлом .br и .gz максимального
    // качества, но сервер их не отдавал: mod_brotli жал ответ заново на каждый
    // запрос и качеством 5 по умолчанию. Замерено на public.min.css —
    // 50.6 КБ готовым файлом против 60.6 КБ на лету.
    $htaccess = (string) file_get_contents(APP_ROOT . '/public/.htaccess');

    assert_contains('.br -f', $htaccess, 'готовый .br подставляется, если он есть');
    assert_contains('.gz -f', $htaccess, 'и .gz для клиентов без brotli');
    assert_contains('Accept-Encoding} (^|,)', $htaccess, 'подстановка только если клиент принимает сжатие');

    // Заголовок ставится по имени файла: переменная из RewriteRule после
    // внутреннего перенаправления становится REDIRECT_*, и браузер получал бы
    // сжатые байты под видом обычного CSS. Проверено на живом Apache.
    assert_true(
        !str_contains($htaccess, '%{ASSET_ENCODING}e'),
        'кодировка не зависит от переменной, теряемой при перенаправлении'
    );
    assert_contains('Header set Content-Encoding "br"', $htaccess);
    assert_contains('Header set Content-Encoding "gzip"', $htaccess);

    // Без Vary общий кэш отдаст сжатую копию клиенту, который её не принимает.
    assert_contains('Header append Vary Accept-Encoding', $htaccess);

    // Тип берётся по исходному имени: файл называется .css.br, и без этого
    // браузер получил бы его как поток байтов.
    assert_contains('ForceType text/css', $htaccess);
    assert_contains('ForceType application/javascript', $htaccess);

    // Готовое не жмём повторно.
    assert_contains('no-brotli no-gzip', $htaccess);

    // Подстановка живёт внутри mod_headers: без него Content-Encoding не
    // выставить, и подменять файл нельзя вовсе.
    $rewriteAt = strpos($htaccess, '.br -f');
    $guardAt = strpos($htaccess, '<IfModule mod_headers.c>');
    assert_true(
        $guardAt !== false && $rewriteAt !== false && $guardAt < $rewriteAt,
        'подстановка обёрнута проверкой mod_headers'
    );

    // Файлы, которые подставляются, обязаны существовать в сборке.
    foreach (['public/assets/css/public.min.css', 'public/assets/js/public.min.js'] as $bundle) {
        foreach (['.br', '.gz'] as $ext) {
            assert_true(
                is_file(APP_ROOT . '/' . $bundle . $ext),
                "сборка кладёт {$bundle}{$ext}"
            );
        }
    }
});

test('Сервер сжимает текст, а hero загружает медиа по приоритету первого экрана', function () {
    $htaccess = (string) file_get_contents(APP_ROOT . '/public/.htaccess');
    assert_contains('BROTLI_COMPRESS', $htaccess);
    assert_contains('DEFLATE', $htaccess);

    $render = static function (array $data): string {
        $block = \App\Core\BlockRenderer::render([
            'id' => 880,
            'type' => 'hero',
            'custom_css' => '',
            'data' => json_encode(array_merge(\App\Core\BlockRenderer::defaultsFor('hero'), $data)),
        ]);

        return (string) $block['html'];
    };

    // Проверяем готовую разметку, а не исходник шаблона: подстроки в PHP-коде
    // ничего не говорят о том, что реально уехало браузеру.
    $image = $render(['bg_type' => 'image', 'image' => '/uploads/public/hero.jpg']);
    assert_contains('loading="eager"', $image);
    assert_contains('fetchpriority="high"', $image);

    // Фон hero — это первый экран, откладывать его нельзя. Смотрим теги <img>:
    // YouTube-подложка подключается по data-src уже после загрузки, и
    // `loading="lazy"` на её <iframe> — ожидаемое поведение, а не отложенная
    // обложка.
    $video = $render(['bg_type' => 'video', 'video_url' => '/uploads/public/hero.mp4', 'image' => '/uploads/public/hero.jpg']);
    assert_contains('preload="metadata"', $video);
    foreach ([$image, $video, $render(['bg_type' => 'youtube', 'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ', 'image' => '/uploads/public/hero.jpg'])] as $html) {
        preg_match_all('/<img\b[^>]*>/is', $html, $images);
        foreach ($images[0] as $tag) {
            assert_not_contains('loading="lazy"', $tag, 'обложка hero отложена до прокрутки');
        }
    }
});
