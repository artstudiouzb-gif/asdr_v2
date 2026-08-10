<?php

declare(strict_types=1);

use App\Core\DesignSettings;
use App\Core\HeaderConfig;

/**
 * Оформление «из коробки»: то, что видит владелец сразу после установки,
 * до единой настройки. Значения выбраны под фирменный стиль Агентства.
 */

test('По умолчанию: широкий контейнер и кнопки-капсулы', function () {
    assert_same('ultra', DesignSettings::OPTIONS['container']['default']);
    assert_same('pill', DesignSettings::OPTIONS['button']['default']);

    $css = DesignSettings::cssVariables(['container' => 'ultra', 'button' => 'pill']);
    assert_contains('--container-max:1440px', $css);
    assert_contains('--btn-radius:999px', $css);
});

test('По умолчанию: шапка липкая, прозрачная и с тенью', function () {
    assert_true(HeaderConfig::DEFAULTS['sticky'], 'липкая шапка');
    // Прозрачность работает только там, где страница её просит
    // (pages.transparent_header) — то есть на главной.
    assert_true(HeaderConfig::DEFAULTS['transparent'], 'прозрачная шапка');
    assert_true(HeaderConfig::DEFAULTS['shadow']['enabled'], 'тень под шапкой');
});

test('По умолчанию: цвета и шрифты фирменные', function () {
    $theme = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Core/SiteThemeCss.php');

    // Палитра из утверждённой концепции дизайна: тёмно-синий и бирюзовый.
    assert_contains("Setting::get('color_primary', '#0F2B46')", $theme);
    assert_contains("Setting::get('color_accent', '#009BBE')", $theme);
    // Пара по умолчанию: Inter — текст, Noto Serif — заголовки. Оба лежат
    // локально. Умолчания объявлены ровно в одном месте: раньше SiteThemeCss
    // и шапка задавали разные семейства, и сайт предзагружал шрифты, которыми
    // ничего не набрано.
    assert_contains("DEFAULT_BODY_FONT = \"'Inter'", $theme);
    assert_contains("DEFAULT_HEADING_FONT = \"'Noto Serif'", $theme);

    $header = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Views/site/_header.php');
    assert_contains('SiteThemeCss::fontStacks()', $header, 'шапка берёт стек из общего источника');
    assert_not_contains("Setting::get('font_family'", $header, 'второго умолчания в шапке быть не должно');

    // Расширенная кириллица обязательна: узбекские Ғ Қ Ҳ лежат в cyrillic-ext.
    foreach ([
        'inter/inter-400-cyrillic.woff2',
        'inter/inter-400-cyrillic-ext.woff2',
        'noto-serif/noto-serif-400-cyrillic.woff2',
        'noto-serif/noto-serif-400-cyrillic-ext.woff2',
    ] as $file) {
        assert_true(
            is_file(dirname(__DIR__, 2) . '/public/assets/fonts/' . $file),
            "шрифт {$file} должен лежать в проекте, а не грузиться со стороны"
        );
    }
});

test('Расширенная кириллица покрыта: узбекские буквы попадают в подключаемое подмножество', function () {
    // Ғғ Ққ Ҳҳ — U+0492..U+04B3, это диапазон cyrillic-ext. Без него узбекская
    // кириллица рисуется системным шрифтом вперемешку с основным.
    foreach (['inter', 'noto-serif'] as $slug) {
        $css = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/' . $slug . '.css');
        assert_contains('U+0460-052F', $css, $slug . ': нет диапазона расширенной кириллицы');
        assert_true(
            preg_match('/cyrillic-ext/', $css) === 1,
            $slug . ': не объявлено подмножество cyrillic-ext'
        );
    }
});

test('Схема не подменяет фирменные цвета своими', function () {
    $schema = (string) file_get_contents(dirname(__DIR__, 2) . '/database/schema.sql');

    // Раньше установка сеяла чёрный и красный, и дефолты из кода не работали.
    assert_not_contains("('color_primary'", $schema);
    assert_not_contains("('color_accent'", $schema);
    assert_not_contains("('font_family'", $schema);
});
