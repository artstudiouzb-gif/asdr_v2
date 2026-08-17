<?php

declare(strict_types=1);

test('Admin slider settings: compact layout layer is loaded and scoped', function (): void {
    $loader = file_get_contents(APP_ROOT . '/public/assets/js/admin-media-loader.js');
    $asset = file_get_contents(APP_ROOT . '/app/Core/Asset.php');
    $script = file_get_contents(APP_ROOT . '/public/assets/js/admin-slider-settings-layout.js');
    $css = file_get_contents(APP_ROOT . '/public/assets/css/admin-slider-settings-layout.css');

    assert_true(is_string($loader));
    assert_true(is_string($asset));
    assert_true(is_string($script));
    assert_true(is_string($css));

    assert_contains("loadStyle('admin-slider-settings-layout.css')", $loader);
    assert_contains("data-admin-slider-settings-layout", $loader);
    assert_contains("load('admin-slider-settings-layout.js')", $loader);
    assert_contains('/assets/js/admin-slider-settings-layout.js', $asset);
    assert_contains('/assets/css/admin-slider-settings-layout.css', $asset);

    foreach ([
        'настройки слайдера',
        'Автопрокрутка',
        'Стрелки',
        'Пагинация',
        'Цикл',
        'Интервал, мс',
        'Переход, мс',
        'Эффект перехода',
        'Навигация',
        'Поведение',
        'Тайминг и переход',
    ] as $contract) {
        assert_contains($contract, $script);
    }

    assert_contains('.slider-settings-layout__groups', $css);
    assert_contains('.slider-settings-layout__toggle-grid', $css);
    assert_contains('.slider-settings-layout__field-grid', $css);
    assert_contains('.slider-settings-layout__switch:checked', $css);
    assert_contains('@media (max-width: 640px)', $css);
});
