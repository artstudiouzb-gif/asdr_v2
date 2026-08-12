<?php

declare(strict_types=1);

use App\Core\BlockData\BlockDataInput;
use App\Core\BlockData\SubscribeBlockNormalizer;
use App\Core\BlockData\TestimonialsBlockNormalizer;
use App\Core\BlockRenderer;

// Варианты вёрстки и настройки у блоков, которые раньше умели только вывести
// список: отзывы, партнёры, карточки персон, подписка, текст с фото, иконки.

/**
 * @param array<string, mixed> $data
 * @return array{html: string, css: string}
 */
function variant_block(string $type, array $data, int $id = 700): array
{
    $rendered = BlockRenderer::render([
        'id' => $id,
        'type' => $type,
        'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
        'custom_css' => '',
    ]);

    return ['html' => (string) $rendered['html'], 'css' => (string) $rendered['css']];
}

test('Ввод блока: значение вне списка и число вне диапазона заменяются умолчанием', function () {
    assert_same('grid', BlockDataInput::enum(['v' => 'grid'], 'v', ['grid', 'carousel'], 'carousel'));
    assert_same('carousel', BlockDataInput::enum(['v' => 'мусор'], 'v', ['grid', 'carousel'], 'carousel'));
    assert_same('carousel', BlockDataInput::enum([], 'v', ['grid', 'carousel'], 'carousel'));

    assert_same(4, BlockDataInput::int(['n' => '4'], 'n', 2, 5, 3));
    assert_same(5, BlockDataInput::int(['n' => '99'], 'n', 2, 5, 3));
    assert_same(2, BlockDataInput::int(['n' => '-1'], 'n', 2, 5, 3));
    assert_same(3, BlockDataInput::int([], 'n', 2, 5, 3), 'без поля берётся умолчание');
    assert_same(3, BlockDataInput::int(['n' => ''], 'n', 2, 5, 3), 'пустая строка — не ноль');
});

test('Отзывы: сетка и карусель — разные раскладки, автопрокрутка только у полосы', function () {
    $items = [
        ['quote' => 'Первый', 'name' => 'Иван', 'role' => 'Директор', 'rating' => 4],
        ['quote' => 'Второй', 'name' => 'Пётр'],
    ];

    $grid = variant_block('testimonials', ['variant' => 'grid', 'columns' => 3, 'items' => $items]);
    assert_contains('block-testimonials__grid', $grid['html']);
    assert_not_contains('data-carousel', $grid['html'], 'сетка полосой не едет');
    assert_contains('--grid-track', $grid['css'], 'последний ряд сетки выравнивается');

    $carousel = variant_block('testimonials', ['variant' => 'carousel', 'autoplay' => 7, 'items' => $items], 701);
    assert_contains('block-testimonials__track', $carousel['html']);
    assert_contains('data-carousel-autoplay="7"', $carousel['html']);

    // Оценка выводится значками, а подпись читает экранный диктор.
    assert_contains('testimonial__star is-on', $grid['html']);
    assert_contains('role="img"', $grid['html']);
    assert_contains('Директор', $grid['html']);

    // Ноль — оценки нет, ряда звёзд тоже.
    $noRating = variant_block('testimonials', ['items' => [['quote' => 'Без оценки', 'name' => 'Анна']]], 702);
    assert_not_contains('testimonial__rating', $noRating['html']);
});

test('Отзывы: нормализатор чистит вариант, колонки и оценку', function () {
    $data = TestimonialsBlockNormalizer::normalize([
        'variant' => 'мозаика',
        'columns' => '9',
        'autoplay' => '99',
        'items' => [['quote' => 'Текст', 'name' => 'Имя', 'rating' => '12']],
    ]);

    assert_same('carousel', $data['variant']);
    assert_same(4, $data['columns']);
    assert_same(30, $data['autoplay']);
    assert_same(5, $data['items'][0]['rating']);
});

test('Партнёры: число логотипов в ряду, высота и обесцвечивание', function () {
    $items = [
        ['logo' => '/uploads/public/a.svg', 'name' => 'А', 'url' => 'https://example.org'],
        ['logo' => '/uploads/public/b.svg', 'name' => 'Б'],
    ];

    $block = variant_block('partners', [
        'columns' => 4,
        'logo_size' => 'large',
        'grayscale' => true,
        'autoplay' => 5,
        'all_text' => 'Все партнёры',
        'all_url' => '/partners',
        'items' => $items,
    ], 710);

    assert_contains('--partners-cols:4', $block['css']);
    assert_contains('block-partners--logo-large', $block['html']);
    assert_contains('block-partners--grayscale', $block['html']);
    assert_contains('data-carousel-autoplay="5"', $block['html']);
    assert_contains('href="/partners"', $block['html'], 'ссылка «Все партнёры» выводится общей шапкой');

    $plain = variant_block('partners', ['grayscale' => false, 'items' => $items], 711);
    assert_not_contains('block-partners--grayscale', $plain['html']);
});

test('Карточки персон: колонки настраиваются, телефон и почта кликабельны', function () {
    $block = variant_block('person_cards', [
        'columns' => 3,
        'items' => [
            ['name' => 'Иванов И.', 'role' => 'Директор', 'phone' => '+998 71 200-00-00', 'email' => 'i@asr.uz'],
            ['name' => 'Петров П.', 'role' => 'Заместитель'],
        ],
    ], 720);

    assert_contains('href="tel:+998712000000"', $block['html']);
    assert_contains('href="mailto:i@asr.uz"', $block['html']);
    // Две карточки в ряд: сетка сжимается до двух дорожек, дыры справа нет.
    assert_contains('--grid-track:2', $block['css']);

    // Четыре карточки в трёх колонках: хвост последнего ряда растягивается.
    $tail = variant_block('person_cards', [
        'columns' => 3,
        'items' => [
            ['name' => 'A', 'role' => 'r'], ['name' => 'B', 'role' => 'r'],
            ['name' => 'C', 'role' => 'r'], ['name' => 'D', 'role' => 'r'],
        ],
    ], 721);
    assert_contains('--grid-span:1', $tail['css']);
    assert_contains(':nth-last-child(-n+1)', $tail['css']);
});

test('Подписка: вариант «на фоне» без картинки становится полосой', function () {
    $withImage = SubscribeBlockNormalizer::normalize([
        'variant' => 'image',
        'image' => '/uploads/public/bg.jpg',
        'note' => 'Отписаться можно в один клик',
        'placeholder' => 'E-mail',
    ]);
    assert_same('image', $withImage['variant']);
    // Примечание проходит типографику (неразрывные пробелы), поэтому сверяем
    // содержание, а не побайтовое совпадение.
    assert_contains('Отписаться можно', $withImage['note']);
    assert_same('E-mail', $withImage['placeholder']);

    $withoutImage = SubscribeBlockNormalizer::normalize(['variant' => 'image', 'image' => '']);
    assert_same('band', $withoutImage['variant'], 'белый текст на белом фоне читать нельзя');
});

test('Текст с фото: пропорция, ширина кадра и кнопка', function () {
    $block = variant_block('text_image', [
        'title' => 'О проекте',
        'text' => 'Описание',
        'image' => '/uploads/public/photo.jpg',
        'image_ratio' => '4-3',
        'image_width' => 40,
        'button_text' => 'Подробнее',
        'button_url' => '/projects',
    ], 730);

    assert_contains('block-textimage--ratio-4-3', $block['html']);
    assert_contains('--textimage-visual:40fr', $block['css']);
    assert_contains('--textimage-info:60fr', $block['css']);
    assert_contains('href="/projects"', $block['html']);

    // Опасная ссылка кнопку не создаёт.
    $unsafe = variant_block('text_image', [
        'text' => 'Описание',
        'button_text' => 'Подробнее',
        'button_url' => 'javascript:alert(1)',
    ], 731);
    assert_not_contains('javascript:', $unsafe['html']);
    assert_not_contains('textimage__button', $unsafe['html']);
});

test('Иконка и текст: вариант вёрстки и выравнивание', function () {
    $items = [['icon_svg' => 'phone', 'rows' => "Приёмная | +998 71 200-00-00"]];

    $plain = variant_block('icon_text', ['variant' => 'plain', 'align' => 'center', 'items' => $items], 740);
    assert_contains('block-icon-text--plain', $plain['html']);
    assert_contains('block-icon-text--align-center', $plain['html']);

    $default = variant_block('icon_text', ['items' => $items], 741);
    assert_contains('block-icon-text--cards', $default['html']);
    assert_contains('block-icon-text--align-left', $default['html']);
});
