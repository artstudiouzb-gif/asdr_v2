<?php

declare(strict_types=1);

use App\Core\BlockRenderer;

// Блок счётчиков: настраиваемый цвет карточки и текста (через CSS-переменные).

test('Counters: цвет карточки и текста отдаются переменными', function () {
    $html = BlockRenderer::render(['id' => 1, 'type' => 'counters', 'custom_css' => null, 'data' => json_encode([
        'card_bg' => '#0b1a30', 'text_color' => '#ffffff',
        'items' => [['value' => 100, 'suffix' => '+', 'label' => 'проектов', 'icon_svg' => '']],
    ])])['html'];
    assert_true(str_contains($html, '--counters-bg:#0b1a30'), 'переменная фона карточки');
    assert_true(str_contains($html, '--counters-text:#ffffff'), 'переменная цвета текста');
});

test('Counters: без цветов — без инлайн-стиля (значения по умолчанию)', function () {
    $html = BlockRenderer::render(['id' => 2, 'type' => 'counters', 'custom_css' => null, 'data' => json_encode([
        'items' => [['value' => 5, 'suffix' => '', 'label' => 'X', 'icon_svg' => '']],
    ])])['html'];
    assert_true(!str_contains($html, '--counters-bg'), 'без переменной фона');
    assert_true(!str_contains($html, '--counters-text'), 'без переменной текста');
});
