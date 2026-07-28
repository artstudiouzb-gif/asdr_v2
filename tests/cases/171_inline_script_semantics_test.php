<?php

declare(strict_types=1);

test('public chrome avoids first-party executable inline scripts', function (): void {
    $header = (string) file_get_contents(APP_ROOT . '/app/Views/site/_header.php');
    $footer = (string) file_get_contents(APP_ROOT . '/app/Views/site/_footer.php');
    $toolbar = (string) file_get_contents(APP_ROOT . '/app/Core/AppToolbar.php');

    assert_contains("Asset::url('/assets/js/theme-init.js')", $header);
    assert_not_contains('<script nonce=', $header);
    assert_not_contains('<script', $toolbar);
    assert_not_contains('window.__frontendLabels', $footer);
    assert_not_contains('window.__pushEnabled', $footer);
    assert_not_contains('window.__consent', $footer);
    assert_contains('type="application/json" id="frontend-labels"', $footer);
    assert_contains('type="application/json" id="push-config"', $footer);
    assert_contains('type="application/json" id="consent-config"', $footer);
});

test('public landmarks and builder blocks use semantic markup without style attributes', function (): void {
    $header = (string) file_get_contents(APP_ROOT . '/app/Views/site/_header.php');
    $footer = (string) file_get_contents(APP_ROOT . '/app/Views/site/_footer.php');
    $toolbar = (string) file_get_contents(APP_ROOT . '/app/Core/AppToolbar.php');
    $hero = (string) file_get_contents(APP_ROOT . '/templates/blocks/hero.php');
    $cards = (string) file_get_contents(APP_ROOT . '/templates/blocks/cards_grid.php');

    assert_contains('<aside class="app-admin-bar"', $toolbar);
    assert_contains('<section class="a11y-panel', $header);
    assert_contains('<header class="print-only print-header">', $header);
    assert_contains('<footer class="print-only print-footer">', $footer);
    assert_contains('<h3 class="feature-card__title">', $cards);
    assert_contains('<p class="feature-card__text">', $cards);
    assert_not_contains(' style="', $hero);
    assert_not_contains(' style="', $cards);
});

test('organization JSON-LD is emitted once by the site chrome', function (): void {
    $header = (string) file_get_contents(APP_ROOT . '/app/Views/site/_header.php');
    $footer = (string) file_get_contents(APP_ROOT . '/app/Views/site/_footer.php');

    assert_contains('SeoHelper::organizationSchema($appUrl)', $header);
    assert_not_contains('SchemaOrg::organization(', $footer);
});
