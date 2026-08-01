<?php

declare(strict_types=1);

use App\Core\BlockRenderer;

// Блок счётчиков: настраиваемый цвет карточки и текста через scoped CSS.

test('Counters: цвет карточки и текста отдаются переменными', function () {
    $rendered = BlockRenderer::render(['id' => 1, 'type' => 'counters', 'custom_css' => null, 'data' => json_encode([
        'card_bg' => '#0b1a30', 'text_color' => '#ffffff',
        'items' => [['value' => 100, 'suffix' => '+', 'label' => 'проектов', 'icon_svg' => '']],
    ])]);
    assert_not_contains(' style="', $rendered['html']);
    assert_contains('--counters-bg:#0b1a30', $rendered['css'], 'переменная фона карточки');
    assert_contains('--counters-text:#ffffff', $rendered['css'], 'переменная цвета текста');
});

test('Counters: без цветов — без инлайн-стиля (значения по умолчанию)', function () {
    $rendered = BlockRenderer::render(['id' => 2, 'type' => 'counters', 'custom_css' => null, 'data' => json_encode([
        'items' => [['value' => 5, 'suffix' => '', 'label' => 'X', 'icon_svg' => '']],
    ])]);
    assert_not_contains(' style="', $rendered['html']);
    assert_not_contains('--counters-bg', $rendered['css'], 'без переменной фона');
    assert_not_contains('--counters-text', $rendered['css'], 'без переменной текста');
});

test('Counters: цифры сплошного цвета, а не градиентом по тексту', function () {
    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/gov-theme.css');

    // Градиент по тексту (`background-clip: text` + прозрачная заливка)
    // перекрывал «Цвет текста» блока: что бы редактор ни выбрал, цифры
    // оставались прежними.
    $rules = [];
    if (preg_match_all('/\.counter__value[^{]*\{([^}]*)\}/s', $css, $matches) > 0) {
        $rules = $matches[1];
    }
    assert_true($rules !== [], 'правила для цифр счётчика должны быть в теме');
    foreach ($rules as $rule) {
        assert_not_contains('background-clip: text', $rule, 'цифры красятся цветом, а не градиентом');
        assert_not_contains('text-fill-color: transparent', $rule);
    }
    assert_contains('color: var(--counters-text, var(--gov-title)) !important;', $css);
});
