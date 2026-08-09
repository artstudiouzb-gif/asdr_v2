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
    // Manrope и в заголовках, и в тексте — так задано в концепции; лежит локально.
    assert_contains("'Manrope', system-ui", $theme);
    assert_not_contains("'PT Serif', Georgia, serif", $theme);

    foreach (['manrope-400-cyrillic.woff2', 'manrope-700-cyrillic.woff2'] as $file) {
        assert_true(
            is_file(dirname(__DIR__, 2) . '/public/assets/fonts/' . $file),
            "шрифт {$file} должен лежать в проекте, а не грузиться со стороны"
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
