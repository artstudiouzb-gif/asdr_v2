<?php

declare(strict_types=1);

use App\Core\DesignSettings;

test('каталог внешних шрифтов содержит Inter Tight с нужными начертаниями', function (): void {
    assert_true(isset(DesignSettings::GOOGLE_FONTS['inter-tight']));
    $font = DesignSettings::GOOGLE_FONTS['inter-tight'];
    assert_same('Inter Tight', $font[0]);
    assert_contains("'Inter Tight'", $font[1]);
    assert_contains('Inter+Tight:wght@400;500;600;700', $font[2]);
});

test('прозрачная мобильная шапка сохраняет видимый бургер и контрастное раскрытое меню', function (): void {
    $css = file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/gov-theme.css');
    assert_true(is_string($css));
    assert_contains('body .site-header--transparent:not(.is-scrolled) .site-burger,', $css);
    assert_contains('body.design-mmenu-burger.mobile-menu-open .site-header--transparent:not(.is-scrolled) .site-menu__link {', $css);
    assert_contains('color: var(--gov-title);', $css);
});
