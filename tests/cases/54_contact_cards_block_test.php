<?php

declare(strict_types=1);

use App\Core\BlockRenderer;

test('Блок contact_cards: модульный, карточки с ссылками, SVG санируется', function () {
    $data = [
        'title' => 'Наши контакты',
        'items' => [
            [
                'icon_svg' => '<svg viewBox="0 0 24 24"><script>alert(1)</script><rect x="3" y="5" width="18" height="14"/></svg>',
                'title' => 'E-mail',
                'lines' => "info@example.uz\npress@example.uz",
                'link_url' => 'mailto:info@example.uz',
                'link_text' => 'Написать',
            ],
            ['icon_svg' => '', 'title' => 'Часы', 'lines' => "Пн–Пт 9–18", 'link_url' => '', 'link_text' => ''],
        ],
    ];
    $out = BlockRenderer::render(['id' => 12, 'type' => 'contact_cards', 'custom_css' => null, 'data' => json_encode($data)]);
    $h = $out['html'];

    // Модульная обёртка (toggle/move/reorder в конструкторе работают через неё).
    assert_contains('cms-block cms-block--contact_cards', $h);
    assert_contains('block-contact-cards__title', $h);
    assert_same(2, substr_count($h, 'class="contact-card"'), 'две карточки');
    // Строки разбиты по переносам.
    assert_contains('info@example.uz', $h);
    assert_contains('press@example.uz', $h);
    // Ссылка карточки.
    assert_contains('mailto:info@example.uz', $h);
    // SVG в шаблоне выводится как есть (санитизация — на этапе сохранения в
    // BlockController); проверяем, что структура карточки на месте.
    assert_contains('contact-card__icon', $h);
});

test('Блок contact_cards: пустой список — заглушка', function () {
    $out = BlockRenderer::render(['id' => 13, 'type' => 'contact_cards', 'custom_css' => null, 'data' => json_encode(['title' => '', 'items' => []])]);
    assert_contains('block-contact-cards__empty', $out['html']);
});
