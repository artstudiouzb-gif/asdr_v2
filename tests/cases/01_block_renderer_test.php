<?php

declare(strict_types=1);

use App\Core\BlockRenderer;

// Рендер блока с ПОВРЕЖДЁННЫМ JSON не должен приводить к фаталу и обязан
// вернуть корректную обёртку <section id="block-...">.
test('BlockRenderer: битый JSON не роняет рендер', function () {
    $result = BlockRenderer::render([
        'id' => 7,
        'type' => 'text',
        'data' => '{ это не валидный json',
        'custom_css' => '',
    ]);
    assert_true(is_array($result));
    assert_contains('id="block-7"', $result['html']);
    assert_contains('cms-block--text', $result['html']);
});

test('BlockRenderer: пустой data подставляет дефолты', function () {
    $result = BlockRenderer::render([
        'id' => 3,
        'type' => 'text',
        'data' => '',
        'custom_css' => '',
    ]);
    assert_contains('id="block-3"', $result['html']);
    // Пресет отступов по умолчанию — premium.
    assert_contains('cms-block--space-premium', $result['html']);
});

test('BlockRenderer: неизвестный тип не роняет рендер', function () {
    $result = BlockRenderer::render([
        'id' => 1,
        'type' => 'no_such_type_xyz',
        'data' => '{}',
        'custom_css' => '',
    ]);
    assert_true(is_array($result));
    assert_contains('id="block-1"', $result['html']);
});

test('BlockRenderer: пресет отступов и reveal попадают в классы/атрибуты', function () {
    $result = BlockRenderer::render([
        'id' => 5,
        'type' => 'text',
        'data' => json_encode(['title' => 'T', 'content' => '<p>x</p>', '_spacing' => 'max', '_reveal' => true]),
        'custom_css' => '',
    ]);
    assert_contains('cms-block--space-max', $result['html']);
    assert_contains('data-reveal', $result['html']);
});

test('BlockRenderer: старый cards_grid без _cards_style рендерится классическим стилем', function () {
    $result = BlockRenderer::render([
        'id' => 11,
        'type' => 'cards_grid',
        'data' => json_encode([
            'variant' => 'icon',
            'columns' => 3,
            'items' => [[
                'icon_svg' => 'target',
                'title' => 'Направление',
                'text' => 'Описание',
                'url' => '/o-nas',
            ]],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'custom_css' => '',
    ]);

    assert_contains('id="block-11"', $result['html']);
    assert_contains('class="block-cards"', $result['html']);
    assert_contains('feature-card__icon', $result['html']);
    assert_contains('#block-11 .feature-card__icon', $result['css']);
});
