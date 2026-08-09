<?php

declare(strict_types=1);

test('visual refinement unifies section headings without changing markup', function (): void {
    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/public-layout-polish.css');

    assert_contains('.section-head__title::before', $css);
    assert_contains('background: var(--gov-teal);', $css);
    assert_contains('text-wrap: balance;', $css);
});

test('editorial media treatment excludes portraits and respects forced colors', function (): void {
    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/public-layout-polish.css');

    assert_contains('--editorial-media-filter: saturate(.9) contrast(1.025);', $css);
    assert_contains('.news-card__cover,', $css);
    assert_contains('.project-card__media,', $css);
    assert_contains('@media (forced-colors: active)', $css);
    assert_contains(') img { filter: none; }', $css);
});
