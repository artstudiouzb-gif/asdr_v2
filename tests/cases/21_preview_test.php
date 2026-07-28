<?php

declare(strict_types=1);

use App\Core\View;

test('Предпросмотр: _header выдаёт noindex и полосу предпросмотра по флагам (БД, группа 5.2)', function () {
    ensure_test_db();
    $_SESSION = $_SESSION ?? [];

    $page = [
        'id' => 0,
        'title' => 'Черновик',
        'meta_title' => 'Черновик',
        'meta_description' => 'превью',
        'layout_type' => 'no_sidebar',
    ];

    $html = View::renderPartial('site/page', [
        'page' => $page,
        'content' => '<div class="cms-block">Тело блока предпросмотра</div>',
        'blockCss' => '#block-1{color:red}',
        'layoutType' => 'no_sidebar',
        'sidebar' => null,
        'robotsNoindex' => true,
        'previewNotice' => true,
    ]);

    assert_contains('name="robots" content="noindex, nofollow"', $html, 'должен быть noindex');
    assert_contains('preview-bar', $html, 'должна быть полоса предпросмотра');
    assert_contains('Тело блока предпросмотра', $html, 'контент блока присутствует');
    assert_contains('#block-1{color:red}', $html, 'scoped CSS блока присутствует');
});

test('Обычная страница НЕ содержит noindex/предпросмотр (БД, группа 5.2)', function () {
    ensure_test_db();
    $_SESSION = $_SESSION ?? [];

    $page = ['id' => 0, 'title' => 'Обычная', 'meta_title' => 'Обычная', 'meta_description' => '', 'layout_type' => 'no_sidebar'];
    $html = View::renderPartial('site/page', [
        'page' => $page,
        'content' => '<div>обычная</div>',
        'blockCss' => '',
        'layoutType' => 'no_sidebar',
        'sidebar' => null,
    ]);

    assert_true(!str_contains($html, 'noindex'), 'у обычной страницы noindex быть не должно');
    assert_true(!str_contains($html, 'preview-bar'), 'полосы предпросмотра быть не должно');
});
