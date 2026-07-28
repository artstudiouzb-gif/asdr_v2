<?php

declare(strict_types=1);

use App\Core\BlockData\CountersBlockNormalizer;

test('Counters normalizer: сохраняет числа, подписи, цвета и безопасный SVG', function (): void {
    $data = CountersBlockNormalizer::normalize([
        'title_field' => ' Наши результаты ',
        'card_bg' => '#AABBCC',
        'text_color' => '#112233',
        'items' => [
            [
                'value' => ' 1 250+ ',
                'suffix' => ' + ',
                'label' => ' реализованных проектов ',
                'icon_svg' => '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><script>alert(1)</script><circle cx="1" cy="1" r="1"/></svg>',
            ],
            ['value' => '', 'label' => '', 'suffix' => '%', 'icon_svg' => '<svg/>'],
            'unexpected',
        ],
    ]);

    assert_same('Наши результаты', $data['title']);
    assert_same('#aabbcc', $data['card_bg']);
    assert_same('#112233', $data['text_color']);
    assert_same(1, count($data['items']));
    assert_same(1250, $data['items'][0]['value']);
    assert_same('+', $data['items'][0]['suffix']);
    assert_same('реализованных проектов', $data['items'][0]['label']);
    assert_not_contains('<script', $data['items'][0]['icon_svg']);
    assert_not_contains('onload=', $data['items'][0]['icon_svg']);
});

test('Counters normalizer: цвета по умолчанию и повреждённые поля не вызывают предупреждений', function (): void {
    $data = CountersBlockNormalizer::normalize([
        'title_field' => [],
        'card_bg' => ['#ffffff'],
        'text_color' => '#ffffff',
        'text_color_off' => '1',
        'items' => [
            [
                'value' => [],
                'label' => ' Сотрудников ',
                'suffix' => [],
                'icon_svg' => [],
            ],
        ],
    ]);

    assert_same('', $data['title']);
    assert_same('', $data['card_bg']);
    assert_same('', $data['text_color']);
    assert_same(1, count($data['items']));
    assert_same(0, $data['items'][0]['value']);
    assert_same('', $data['items'][0]['suffix']);
    assert_same('Сотрудников', $data['items'][0]['label']);
    assert_same('', $data['items'][0]['icon_svg']);
});

test('Counters normalizer: сохраняет прежнее извлечение цифр из значения', function (): void {
    $items = CountersBlockNormalizer::normalize([
        'items' => [
            ['value' => '-12.5', 'label' => 'процента'],
            ['value' => 'нет числа', 'label' => 'подпись'],
        ],
    ])['items'];

    assert_same(125, $items[0]['value']);
    assert_same(0, $items[1]['value']);
});

test('Контроллер делегирует счётчики CountersBlockNormalizer', function (): void {
    $controller = (string) file_get_contents(APP_ROOT . '/app/Controllers/Admin/BlockController.php');

    assert_contains('CountersBlockNormalizer::normalize($_POST, $locale)', $controller);
    assert_not_contains('// Число хранится как целое', $controller);
});
