<?php

declare(strict_types=1);

use App\Core\BlockBackground;
use App\Core\BlockData\BlockPresentationNormalizer;
use App\Core\BlockRenderer;

// Фон секции: свой цвет, градиент, фотография, плитка и встроенный узор.
// Тот же механизм переиспользует подвал сайта.

test('Фон секции: режим один, неполные настройки откатываются к пресету', function () {
    $preset = BlockPresentationNormalizer::normalize([]);
    assert_same('preset', $preset['_bg_mode']);

    // Градиент без второго цвета — не градиент: секция вышла бы пустой.
    $broken = BlockPresentationNormalizer::normalize(['bg_mode' => 'gradient', 'bg_gradient_from' => '#0f2b46']);
    assert_same('preset', $broken['_bg_mode']);
    $noColor = BlockPresentationNormalizer::normalize(['bg_mode' => 'color']);
    assert_same('preset', $noColor['_bg_mode']);

    // Чужой адрес картинки не сохраняется.
    $unsafe = BlockPresentationNormalizer::normalize(['bg_mode' => 'image', 'bg_image' => 'javascript:alert(1)']);
    assert_same('preset', $unsafe['_bg_mode']);

    // Мусор в цвете — «не задан», а не строка в CSS.
    $junk = BlockPresentationNormalizer::normalize(['bg_mode' => 'color', 'bg_color' => 'жёлтый']);
    assert_same('preset', $junk['_bg_mode']);

    // Затемнение — приём для фотографии: у плитки-узора его по умолчанию нет.
    $tile = BlockPresentationNormalizer::normalize([
        'bg_mode' => 'image', 'bg_image' => '/uploads/public/tile.png', 'bg_repeat' => 'tile',
    ]);
    assert_same(0, $tile['_bg_overlay']);
    $photo = BlockPresentationNormalizer::normalize([
        'bg_mode' => 'image', 'bg_image' => '/uploads/public/photo.jpg',
    ]);
    assert_same(45, $photo['_bg_overlay'], 'у снимка затемнение включено по умолчанию');
});

test('Фон секции: классы и переменные для каждого режима', function () {
    $color = BlockBackground::build(
        BlockPresentationNormalizer::normalize(['bg_mode' => 'color', 'bg_color' => '#0f2b46']),
        10
    );
    assert_contains('cms-block--bg-custom', $color['class']);
    assert_contains('--block-bg:#0f2b46', $color['css']);

    $gradient = BlockBackground::build(
        BlockPresentationNormalizer::normalize([
            'bg_mode' => 'gradient', 'bg_gradient_from' => '#0f2b46',
            'bg_gradient_to' => '#009bbe', 'bg_gradient_angle' => '120',
        ]),
        11
    );
    assert_contains('linear-gradient(120deg, #0f2b46 0%, #009bbe 100%)', $gradient['css']);

    $photo = BlockBackground::build(
        BlockPresentationNormalizer::normalize([
            'bg_mode' => 'image', 'bg_image' => '/uploads/public/photo.jpg',
            'bg_overlay' => '55', 'bg_position' => 'center-bottom', 'bg_fixed' => '1',
        ]),
        12
    );
    assert_contains('cms-block--bg-overlay', $photo['class']);
    assert_contains('cms-block--bg-fixed', $photo['class']);
    assert_contains('--block-bg-position:center bottom', $photo['css']);
    assert_contains('--block-bg-overlay:0.55', $photo['css']);

    $pattern = BlockBackground::build(
        BlockPresentationNormalizer::normalize([
            'bg_mode' => 'pattern', 'bg_pattern' => 'emblem', 'bg_color' => '#0f2b46',
            'bg_pattern_color' => '#ffffff', 'bg_pattern_size' => '48',
        ]),
        13
    );
    assert_contains('cms-block--bg-pattern-emblem', $pattern['class']);
    assert_contains('--block-pattern-size:48px', $pattern['css']);
    assert_contains('color-mix(in srgb, #ffffff', $pattern['css']);
});

test('Светлый текст задаётся по id секции, а не классом тёмной секции', function () {
    $light = BlockBackground::build(
        BlockPresentationNormalizer::normalize([
            'bg_mode' => 'color', 'bg_color' => '#0f2b46', 'bg_light_text' => '1',
        ]),
        14
    );

    // Класс тёмной секции принёс бы свою заливку (--gov-navy) и перекрыл
    // выбранный цвет; id перебивает и правила редакционных страниц.
    assert_not_contains('cms-block--bg-navy', $light['class']);
    assert_contains('#block-14{color:', $light['css']);
    assert_contains('#block-14 :is(h1,h2,h3', $light['css']);
    assert_contains('[class*="__text"]', $light['css'], 'тело текста тоже светлеет');
});

test('Свой фон отменяет пресет темы у секции', function () {
    $rendered = BlockRenderer::render([
        'id' => 790,
        'type' => 'text',
        'data' => json_encode(array_merge(
            ['content' => 'Текст'],
            BlockPresentationNormalizer::normalize(['bg' => 'light', 'bg_mode' => 'color', 'bg_color' => '#0f2b46'])
        ), JSON_UNESCAPED_UNICODE),
        'custom_css' => '',
    ]);

    assert_contains('cms-block--bg-custom', (string) $rendered['html']);
    assert_not_contains('cms-block--bg-light', (string) $rendered['html'], 'два фона на секции — каша');
});

test('Подвал использует тот же фон, эмблема берётся абсолютным адресом', function () {
    $css = BlockBackground::cssFor('.site-footer', [
        '_bg_mode' => 'pattern', '_bg_pattern' => 'emblem',
        '_bg_color' => '#0f2b46', '_bg_pattern_color' => '#ffffff',
        '_bg_pattern_opacity' => 10, '_bg_pattern_size' => 64,
    ]);

    assert_contains('.site-footer{', $css);
    assert_contains('--block-bg:#0f2b46', $css);
    assert_contains('.site-footer::before', $css);
    // var(--gov-emblem) объявлена относительным url и в сгенерированном файле
    // темы (он лежит в /uploads/public) разрешается не туда.
    assert_not_contains('var(--gov-emblem)', $css);
    assert_contains('url("/assets/img/emblem.svg")', $css);

    assert_same('', BlockBackground::cssFor('.site-footer', ['_bg_mode' => 'preset']));
});
