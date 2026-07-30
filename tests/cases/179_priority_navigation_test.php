<?php

declare(strict_types=1);

test('Priority navigation отделена от полного мобильного drawer', function (): void {
    $template = (string) file_get_contents(APP_ROOT . '/app/Views/site/_header.php');

    assert_contains("'menu' => \$priorityMenuHtml", $template);
    assert_contains('$drawerMenu = $menuHtml', $template);
    assert_contains('data-priority-overflow-toggle', $template);
    assert_contains('data-mobile-menu-toggle', $template);
});

test('Desktop priority navigation сохраняет порядок пунктов и доступность', function (): void {
    $script = (string) file_get_contents(APP_ROOT . '/public/assets/js/frontend.js');

    assert_contains("panel.insertBefore(item, panel.firstChild)", $script);
    assert_contains("menu.insertBefore(parts.panel.firstElementChild, parts.overflow)", $script);
    assert_contains("while (items.length > 1 && doesNotFit(header, menu, parts))", $script);
    assert_contains('itemRect.left < menuRect.left', $script);
    assert_contains('itemRect.right > menuRect.right', $script);
    assert_contains("toggle.setAttribute('aria-expanded'", $script);
    assert_contains("desktop.matches", $script);
});

test('Priority-бургер является последним элементом горизонтального меню', function (): void {
    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/frontend.css');

    assert_contains('.site-menu__overflow {', $css);
    assert_contains('.site-menu__overflow-panel {', $css);
    assert_contains('.hdr-util:has(> [data-priority-menu])', $css);
    assert_contains('flex-wrap: nowrap !important', $css);
    assert_contains('.site-menu__overflow.is-open .site-menu__overflow-toggle span:nth-child(1)', $css);
    assert_contains('transform: translateY(6px) rotate(45deg)', $css);
    assert_contains('@media (max-width: 720px)', $css);
    assert_contains('[data-priority-overflow]', $css);
});
