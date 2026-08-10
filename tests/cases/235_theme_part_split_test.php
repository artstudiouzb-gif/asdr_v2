<?php

declare(strict_types=1);

use App\Core\AssetCollector;

test('Часть темы подключается отдельно и только на своих страницах', function (): void {
    AssetCollector::reset();
    assert_same('', AssetCollector::renderThemeStyles(), 'без запроса часть темы не подключается');

    AssetCollector::requireThemePart('news_detail');
    $html = AssetCollector::renderThemeStyles();
    assert_true(
        preg_match('#/assets/css/blocks/news-detail(\.min)?\.css#', $html) === 1,
        'подключён файл части темы: ' . $html
    );

    // Повторный запрос не должен дублировать <link>.
    AssetCollector::requireThemePart('news_detail');
    assert_same(1, substr_count(AssetCollector::renderThemeStyles(), '<link'));
    AssetCollector::reset();
});

test('Часть темы выводится после бандла, но до дизайн-CSS из админки', function (): void {
    $header = (string) file_get_contents(APP_ROOT . '/app/Views/site/_header.php');

    // Берём именно места вывода <link>, а не первое упоминание переменной:
    // $generatedCssUrls вычисляется заметно выше своего цикла вывода.
    $bundle = strpos($header, 'FrontendAssets::styles()');
    $part = strpos($header, 'AssetCollector::renderThemeStyles()');
    $generated = strpos($header, 'data-generated-site-css');
    $blocks = strpos($header, 'AssetCollector::renderStyles()');

    assert_true($bundle !== false && $part !== false && $generated !== false && $blocks !== false);
    // Порядок обязателен: вынесенные правила должны стоять там же, где лежали
    // внутри темы. Уедут после дизайн-CSS — настройки раздела «Дизайн»
    // перестанут действовать на этих страницах.
    assert_true($bundle < $part, 'часть темы идёт после общего бандла');
    assert_true($part < $generated, 'часть темы идёт до дизайн-CSS админки');
    assert_true($generated < $blocks, 'стили блоков остаются последними');
});

test('Новостные страницы запрашивают вынесенную часть темы', function (): void {
    $controller = (string) file_get_contents(APP_ROOT . '/app/Controllers/Site/NewsController.php');
    assert_same(
        2,
        substr_count($controller, "requireThemePart('news_detail')"),
        'и детальная новость, и список: список построен на .relnews-card'
    );
});

test('Классы, живущие вне новостей, остались в общей теме', function (): void {
    $theme = (string) file_get_contents(APP_ROOT . '/public/assets/css/gov-theme.css');
    $part = (string) file_get_contents(APP_ROOT . '/public/assets/css/blocks/news-detail.css');

    // Виджет подписки может стоять в сайдбаре любой страницы, а
    // .newsdetail-article__content и .newsdetail__badge есть в project_show.php.
    // Если эти правила уедут в часть темы, они пропадут вне новостей.
    foreach (['newsdetail-subscribe', 'newsdetail-article__content', 'newsdetail__badge'] as $needle) {
        assert_contains($needle, $theme, $needle . ' обязан остаться в общей теме');
    }

    // Обратная проверка: детальные семейства из темы ушли.
    foreach (['newsdetail-gallery', 'newsdetail-toc', 'newsdetail-timeline'] as $needle) {
        assert_contains($needle, $part, $needle . ' должен быть в вынесенной части');
    }
});

test('Сброс hover едет вместе со своим правилом', function (): void {
    $theme = (string) file_get_contents(APP_ROOT . '/public/assets/css/gov-theme.css');
    $part = (string) file_get_contents(APP_ROOT . '/public/assets/css/blocks/news-detail.css');

    // Часть темы подключается позже gov-theme, поэтому сброс transform для
    // тач-устройств обязан лежать в том же файле, что и правило hover, —
    // иначе при тапе плитка «залипает» приподнятой.
    assert_not_contains('.newsdetail-photos__item:hover, .album-card:hover', $theme);
    assert_contains('@media (hover: none)', $part);
});
