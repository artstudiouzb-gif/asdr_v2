<?php

declare(strict_types=1);

test('служебная подпись Поделиться не входит в иерархию заголовков', function (): void {
    $view = (string) file_get_contents(APP_ROOT . '/app/Views/site/news_show.php');

    assert_true(!str_contains($view, '<h2 class="newsdetail-share__title">'));
    assert_contains('<p class="newsdetail-share__title">', $view);
});

test('заголовки самостоятельных виджетов используют второй уровень', function (): void {
    $renderer = (string) file_get_contents(APP_ROOT . '/app/Core/WidgetRenderer.php');

    assert_contains('<h2 class="widget__title">', $renderer);
    assert_true(!str_contains($renderer, '<h3 class="widget__title">'));
});
