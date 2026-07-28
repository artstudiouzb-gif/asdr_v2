<?php

declare(strict_types=1);

test('Колонка действий остаётся частью таблицы в списках медиа и новостей', function () {
    $root = dirname(__DIR__, 2);
    $css = (string) file_get_contents($root . '/public/assets/css/admin.css');
    $files = (string) file_get_contents($root . '/app/Views/admin/files/index.php');
    $news = (string) file_get_contents($root . '/app/Views/admin/news/index.php');

    assert_contains('.data-table__action-cell', $css);
    assert_contains('.data-table td.data-table__actions { display: table-cell;', $css);

    foreach ([$files, $news] as $view) {
        assert_contains('<td class="data-table__action-cell">', $view);
        assert_contains('<div class="data-table__actions">', $view);
        assert_contains('<th class="data-table__action-cell">Действия</th>', $view);
    }
});

