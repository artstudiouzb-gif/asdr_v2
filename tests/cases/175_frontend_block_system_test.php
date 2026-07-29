<?php

declare(strict_types=1);

use App\Core\BlockRenderer;
use App\Core\Lang;
use App\Core\PagePresets;

test('Анимация блока: выключенный режим не переопределяется фронтендом', function (): void {
    $rendered = BlockRenderer::render([
        'id' => 17501,
        'type' => 'text',
        'data' => json_encode([
            'content' => '<p>Видимый текст</p>',
            '_reveal' => ['enabled' => false, 'type' => 'fade'],
        ], JSON_UNESCAPED_UNICODE),
    ]);

    assert_not_contains("\n    data-reveal", $rendered['html']);

    $frontend = (string) file_get_contents(APP_ROOT . '/public/assets/js/frontend.js');
    assert_not_contains("querySelectorAll('.cms-block:not(.cms-block--hero)')", $frontend);
});

test('Анимация блока: stagger помечает элементы, а не скрывает всю секцию', function (): void {
    $rendered = BlockRenderer::render([
        'id' => 17502,
        'type' => 'cards_grid',
        'data' => json_encode([
            'items' => [
                ['title' => 'A', 'text' => '', 'url' => ''],
                ['title' => 'B', 'text' => '', 'url' => ''],
            ],
            '_reveal' => ['enabled' => true, 'type' => 'stagger'],
        ], JSON_UNESCAPED_UNICODE),
    ]);

    assert_contains('data-reveal-items', $rendered['html']);
    assert_not_contains('data-reveal-type="stagger"', $rendered['html']);
});

test('Галерея использует единый lightbox из общего frontend bundle', function (): void {
    $collector = (string) file_get_contents(APP_ROOT . '/app/Core/AssetCollector.php');
    $frontend = (string) file_get_contents(APP_ROOT . '/public/assets/js/frontend.js');

    assert_not_contains('/assets/js/blocks/gallery.js', $collector);
    assert_contains("var PHOTO_SCOPES = '.album-photos, .block-gallery__grid", $frontend);
    assert_true(str_ends_with(trim($frontend), '})();'), 'общий IIFE должен закрываться в конце bundle');
    assert_same(1, substr_count($frontend, "\n})();"), 'frontend helpers не должны выпадать из общего scope');
});

test('Системные подписи блоков переведены на узбекский', function (): void {
    assert_same('Hozircha yangiliklar yo‘q.', Lang::t('Новостей пока нет.', 'uz'));
    assert_same('Media filtri', Lang::t('Фильтр медиа', 'uz'));
    assert_same('Rejalashtirilgan', Lang::t('Запланирован', 'uz'));
    assert_same('Keyingi slayd', Lang::t('Следующий слайд', 'uz'));
});

test('UZ-сборка главной использует узбекский demo fixture', function (): void {
    $fixture = json_decode(
        (string) file_get_contents(APP_ROOT . '/database/demo_assets/home_blocks_uz.json'),
        true
    );
    $preset = PagePresets::find('home', 'uz');

    assert_true(is_array($fixture) && $fixture !== []);
    assert_true(is_array($preset));
    assert_same(
        (string) ($fixture[0]['data']['title'] ?? ''),
        (string) ($preset['blocks'][0]['data']['title'] ?? '')
    );
});
