<?php

declare(strict_types=1);

use App\Core\BlockData\HeroBlockNormalizer;

test('Hero normalizer: формирует прежний JSON-контракт и ограничивает значения', function () {
    $data = HeroBlockNormalizer::normalize([
        'title_field' => '  Заголовок  ',
        'hero_width' => 'standard',
        'hero_height' => 'custom',
        'hero_height_value' => '2500',
        'hero_height_unit' => 'px',
        'eyebrow' => '  Раздел  ',
        'subtitle' => '  Подзаголовок  ',
        'bg_type' => 'none',
        'image' => '/uploads/public/hero.jpg',
        'video_url' => '',
        'youtube_url' => '',
        'overlay_direction' => '90deg;background:red',
        'overlay_color' => '#ABCDEF',
        'overlay_end_color' => 'bad',
        'overlay_opacity' => '150',
        'text_position' => 'outside',
        'text_width_value' => '5',
        'text_width_unit' => '%',
        'text_color' => '#112233',
        'button_color' => '#445566',
        'button_color_off' => '1',
        'bg_color' => 'bad',
        'panel_enabled' => '1',
        'panel_color' => '#AABBCC',
        'panel_opacity' => '-10',
        'button_text' => ' Подробнее ',
        'button_url' => ' javascript:alert(1) ',
        'button2_text' => ' Вторая ',
        'button2_url' => ' /about ',
        'video_button_text' => ' Видео ',
        'video_button_url' => ' https://example.com/video ',
    ]);

    assert_same([
        'title' => 'Заголовок',
        'width' => 'standard',
        'height' => 'custom',
        'custom_height' => '2000px',
        'eyebrow' => 'Раздел',
        'subtitle' => 'Подзаголовок',
        'bg_type' => 'image',
        'image' => '/uploads/public/hero.jpg',
        'video_url' => '',
        'youtube_url' => '',
        'overlay_direction' => 'auto',
        'overlay_color' => '#abcdef',
        'overlay_end_color' => '#0b1a30',
        'overlay_opacity' => 100,
        'text_position' => 'left',
        'text_width' => '10%',
        'text_color' => '#112233',
        'button_color' => '',
        'bg_color' => '',
        'panel_enabled' => true,
        'panel_color' => '#aabbcc',
        'panel_opacity' => 0,
        'button_text' => 'Подробнее',
        'button_url' => '',
        'button2_text' => 'Вторая',
        'button2_url' => '/about',
        'video_button_text' => 'Видео',
        'video_button_url' => 'https://example.com/video',
    ], $data);
});

test('Hero normalizer: приоритет фонового медиа не изменился', function () {
    $youtube = HeroBlockNormalizer::normalize([
        'bg_type' => 'none',
        'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ',
        'video_url' => '/uploads/public/hero.mp4',
        'image' => '/uploads/public/hero.jpg',
    ]);
    assert_same('youtube', $youtube['bg_type']);

    $video = HeroBlockNormalizer::normalize([
        'bg_type' => 'none',
        'video_url' => '/uploads/public/hero.mp4',
        'image' => '/uploads/public/hero.jpg',
    ]);
    assert_same('video', $video['bg_type']);

    $image = HeroBlockNormalizer::normalize([
        'bg_type' => 'none',
        'image' => '/uploads/public/hero.jpg',
    ]);
    assert_same('image', $image['bg_type']);
});

test('Hero normalizer: не зависит от глобального POST', function () {
    $originalPost = $_POST;
    $_POST = ['title_field' => 'Глобальное значение'];

    try {
        $data = HeroBlockNormalizer::normalize(['title_field' => 'Явный вход']);
    } finally {
        $_POST = $originalPost;
    }
    assert_same('Явный вход', $data['title']);
});
