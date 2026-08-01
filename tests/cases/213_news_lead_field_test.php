<?php

declare(strict_types=1);

/**
 * Лид новости — обычный текст. Визуальный редактор здесь был бы ловушкой:
 * лид выводится экранированным в карточках ленты, в поиске, в описании для
 * поисковиков и в постах соцсетей, и разметка превратилась бы в голые теги.
 * Вместо редактора — счётчик с порогами, по которым лид режут.
 */

test('Лид остаётся обычным полем, без визуального редактора', function () {
    $form = (string) file_get_contents(APP_ROOT . '/app/Views/admin/news/form.php');
    $pos = (int) strpos($form, 'name="excerpt"');
    assert_true($pos > 0, 'поле лида на месте');

    $block = substr($form, $pos, 600);
    assert_not_contains('data-wysiwyg', $block);
    assert_contains('data-lead-field', $block);
    assert_contains('data-lead-count', $block);
});

test('Счётчик лида знает пороги обрезки', function () {
    $js = (string) file_get_contents(APP_ROOT . '/public/assets/js/admin.js');
    $pos = (int) strpos($js, 'data-lead-field');
    $block = substr($js, max(0, $pos - 600), 1600);

    assert_contains('160', $block);
    assert_contains('140', $block);
});

test('Пороги счётчика совпадают с реальной обрезкой в шаблонах', function () {
    $latest = (string) file_get_contents(APP_ROOT . '/templates/blocks/news_latest.php');
    $feature = (string) file_get_contents(APP_ROOT . '/templates/blocks/news_feature.php');

    // Если лимиты в шаблонах поменяют, подсказка редактору начнёт врать.
    assert_contains("excerpt((string) \$item['excerpt'], 140)", $latest);
    assert_contains("excerpt((string) \$featured['excerpt'], 160)", $feature);
});
