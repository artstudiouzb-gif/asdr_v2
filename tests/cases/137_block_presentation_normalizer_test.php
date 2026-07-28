<?php

declare(strict_types=1);

use App\Core\BlockData\BlockPresentationNormalizer;

test('Block presentation normalizer: формирует прежние значения по умолчанию', function (): void {
    assert_same([
        '_spacing' => 'premium',
        '_reveal' => ['enabled' => false, 'type' => 'fade'],
        '_bg' => 'none',
        '_fullwidth' => false,
        '_pad_top' => 'default',
        '_pad_bottom' => 'default',
        '_visible_from' => '',
        '_visible_to' => '',
        '_visible_device' => '',
    ], BlockPresentationNormalizer::normalize([]));
});

test('Block presentation normalizer: сохраняет допустимые настройки', function (): void {
    assert_same([
        '_spacing' => 'max',
        '_reveal' => ['enabled' => true, 'type' => 'slide-up'],
        '_bg' => 'navy',
        '_fullwidth' => true,
        '_pad_top' => 'none',
        '_pad_bottom' => 'large',
        '_visible_from' => '2026-07-24 10:15',
        '_visible_to' => '2026-07-25 18:30',
        '_visible_device' => 'mobile',
    ], BlockPresentationNormalizer::normalize([
        'spacing' => 'max',
        'reveal_type' => 'slide-up',
        'bg' => 'navy',
        'fullwidth' => '1',
        'pad_top' => 'none',
        'pad_bottom' => 'large',
        'visible_from' => '2026-07-24T10:15',
        'visible_to' => '2026-07-25T18:30',
        'visible_device' => 'mobile',
        'unrelated' => 'не попадает в результат',
    ]));
});

test('Block presentation normalizer: ограничивает неизвестные значения', function (): void {
    $data = BlockPresentationNormalizer::normalize([
        'spacing' => 'huge',
        'reveal_type' => 'spin',
        'bg' => 'script',
        'fullwidth' => '0',
        'pad_top' => 'giant',
        'pad_bottom' => [],
        'visible_from' => 'not-a-date',
        'visible_to' => '<script>',
        'visible_device' => 'tablet',
    ]);

    assert_same('premium', $data['_spacing']);
    assert_same(['enabled' => false, 'type' => 'fade'], $data['_reveal']);
    assert_same('none', $data['_bg']);
    assert_false($data['_fullwidth']);
    assert_same('default', $data['_pad_top']);
    assert_same('default', $data['_pad_bottom']);
    assert_same('', $data['_visible_from']);
    assert_same('', $data['_visible_to']);
    assert_same('', $data['_visible_device']);
});

test('Block presentation normalizer: обнаруживает перевёрнутое окно показа', function (): void {
    assert_true(BlockPresentationNormalizer::hasInvalidVisibilityWindow([
        '_visible_from' => '2026-07-25 10:00',
        '_visible_to' => '2026-07-24 10:00',
    ]));
    assert_true(BlockPresentationNormalizer::hasInvalidVisibilityWindow([
        '_visible_from' => '2026-07-25 10:00',
        '_visible_to' => '2026-07-25 10:00',
    ]));
    assert_false(BlockPresentationNormalizer::hasInvalidVisibilityWindow([
        '_visible_from' => '2026-07-24 10:00',
        '_visible_to' => '2026-07-25 10:00',
    ]));
    assert_false(BlockPresentationNormalizer::hasInvalidVisibilityWindow([]));
});

test('Контроллер делегирует общие настройки BlockPresentationNormalizer', function (): void {
    $controller = (string) file_get_contents(APP_ROOT . '/app/Controllers/Admin/BlockController.php');

    assert_contains('array_merge($data, BlockPresentationNormalizer::normalize($_POST))', $controller);
    assert_contains('BlockPresentationNormalizer::hasInvalidVisibilityWindow($data)', $controller);
    assert_not_contains('$allowedReveal =', $controller);
    assert_not_contains('$padOptions =', $controller);
});
