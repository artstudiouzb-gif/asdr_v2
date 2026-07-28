<?php

declare(strict_types=1);

use App\Core\BlockRenderer;

test('Блок hero: титул, подзаголовок, фон-фото, безопасная кнопка', function () {
    $out = BlockRenderer::render(['id' => 20, 'type' => 'hero', 'custom_css' => null, 'data' => json_encode([
        'title' => 'Пресс-центр', 'subtitle' => 'Оперативная информация',
        'image' => '/uploads/public/x.jpg', 'button_text' => 'Все новости', 'button_url' => '/news',
    ])])['html'];
    assert_contains('cms-block cms-block--hero', $out);
    assert_contains('block-hero--media', $out);
    assert_contains('Пресс-центр', $out);
    assert_contains('<picture class="block-hero__media">', $out);
    assert_contains('src="/uploads/public/x.jpg"', $out);
    assert_contains('fetchpriority="high"', $out);
    assert_contains('href="/news"', $out);

    // Небезопасная ссылка кнопки не выводится.
    $bad = BlockRenderer::render(['id' => 21, 'type' => 'hero', 'custom_css' => null, 'data' => json_encode([
        'title' => 'T', 'button_text' => 'X', 'button_url' => 'javascript:alert(1)',
    ])])['html'];
    assert_true(!str_contains($bad, 'block-hero__button'), 'javascript: кнопка не рендерится');
});

test('Блок categories_grid: плитки, первая активна', function () {
    $out = BlockRenderer::render(['id' => 22, 'type' => 'categories_grid', 'custom_css' => null, 'data' => json_encode([
        'title' => 'Категории', 'items' => [
            ['icon_svg' => '<svg><rect/></svg>', 'label' => 'Новости', 'url' => '/news'],
            ['icon_svg' => '', 'label' => 'Видео', 'url' => ''],
        ],
    ])])['html'];
    assert_contains('cms-block--categories_grid', $out);
    assert_contains('cat-tile is-active', $out);
    assert_contains('href="/news"', $out);
    // Пункт без URL — span, не ссылка.
    assert_contains('<span class="cat-tile"', $out);
});

test('Блок media_materials: элементы с действием и заглушка', function () {
    $out = BlockRenderer::render(['id' => 23, 'type' => 'media_materials', 'custom_css' => null, 'data' => json_encode([
        'title' => 'Медиа', 'items' => [['icon_svg' => '', 'label' => 'Фото', 'action' => 'Смотреть', 'url' => '/albums']],
    ])])['html'];
    assert_contains('cms-block--media_materials', $out);
    assert_contains('media-item__action', $out);
    assert_contains('Смотреть', $out);

    $empty = BlockRenderer::render(['id' => 24, 'type' => 'media_materials', 'custom_css' => null, 'data' => json_encode(['title' => '', 'items' => []])])['html'];
    assert_contains('block-media__empty', $empty);
});

test('Блок hero: видео-фон и надзаголовок; безопасность кнопок', function () {
    $out = BlockRenderer::render(['id' => 25, 'type' => 'hero', 'custom_css' => null, 'data' => json_encode([
        'title' => 'Строим будущее', 'eyebrow' => 'Стратегия', 'image' => '/uploads/public/p.jpg',
        'video_url' => '/uploads/public/hero.mp4',
        'button_text' => 'Об агентстве', 'button_url' => '/o-nas',
        'button2_text' => 'Стратегия', 'button2_url' => 'javascript:alert(1)',
        'video_button_text' => 'Смотреть видео', 'video_button_url' => '/news',
    ])])['html'];
    assert_contains('block-hero--video', $out);
    assert_contains('<video', $out);
    assert_contains('/uploads/public/hero.mp4', $out);
    assert_contains('block-hero__eyebrow', $out);
    assert_contains('block-hero__play', $out);
    // Небезопасная вторая кнопка не рендерится.
    assert_true(!str_contains($out, 'block-hero__button--ghost'), 'javascript: вторая кнопка отсеяна');
});

test('Блоки cards_grid / image_cards / media_gallery: обёртки и содержимое', function () {
    $cards = BlockRenderer::render(['id' => 26, 'type' => 'cards_grid', 'custom_css' => null, 'data' => json_encode([
        'title' => 'Направления', 'all_text' => 'Все', 'all_url' => '/news', 'columns' => 5,
        'items' => [['icon_svg' => '<svg><path/></svg>', 'title' => 'Рост', 'text' => 'описание', 'url' => '/news']],
    ])])['html'];
    assert_contains('cms-block--cards_grid', $cards);
    assert_contains('feature-card', $cards);
    assert_contains('section-head__all', $cards);

    $imgs = BlockRenderer::render(['id' => 27, 'type' => 'image_cards', 'custom_css' => null, 'data' => json_encode([
        'title' => 'Проекты', 'items' => [['image' => '/uploads/public/p.jpg', 'title' => 'Проект', 'url' => '/news']],
    ])])['html'];
    assert_contains('imgcard', $imgs);
    assert_contains('/uploads/public/p.jpg', $imgs);

    $media = BlockRenderer::render(['id' => 28, 'type' => 'media_gallery', 'custom_css' => null, 'data' => json_encode([
        'title' => 'Медиа', 'items' => [['image' => '/x.jpg', 'title' => 'Видео', 'meta' => '02:35', 'text' => '20 мая', 'url' => '/news']],
    ])])['html'];
    assert_contains('mediacard', $media);
    assert_contains('mediacard__duration', $media);
    assert_contains('02:35', $media);
});

test('Блок news_feature: обёртка, заголовок секции и ссылка «Все» (лента из БД)', function () {
    ensure_test_db();
    // Енрич подтягивает новости из БД; заголовок/ссылка секции рендерятся всегда.
    $out = \App\Core\BlockRenderer::render(['id' => 30, 'type' => 'news_feature', 'custom_css' => null, 'data' => json_encode([
        'title' => 'Новости и аналитика', 'all_text' => 'Все новости', 'all_url' => '/news', 'limit' => 6,
    ])])['html'];
    assert_contains('cms-block--news_feature', $out);
    assert_contains('block-newsfeat', $out);
    assert_contains('section-head__all', $out);
    assert_contains('Новости и аналитика', $out);
});

test('Блок media_gallery: переключатели видео/фото при смешанном наборе', function () {
    $out = \App\Core\BlockRenderer::render(['id' => 31, 'type' => 'media_gallery', 'custom_css' => null, 'data' => json_encode([
        'title' => 'Медиа', 'items' => [
            ['image' => '/x.jpg', 'title' => 'Видео', 'meta' => '02:35', 'kind' => 'video', 'url' => '/n'],
            ['image' => '/y.jpg', 'title' => 'Фото', 'meta' => '', 'kind' => 'photo', 'url' => '/a'],
        ],
    ])])['html'];
    assert_contains('media-tabs', $out);
    assert_contains('data-media-kind="video"', $out);
    assert_contains('data-media-kind="photo"', $out);

    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/gov-theme.css');
    assert_contains('.media-tabs__tab::after', $css);
    assert_contains('.media-tabs__tab.is-active::after', $css);
});
