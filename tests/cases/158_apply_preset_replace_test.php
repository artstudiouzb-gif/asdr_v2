<?php

declare(strict_types=1);

use App\Models\Block;
use App\Models\BlockSnippet;
use App\Models\Page;

test('BlockSnippet::applyToPage с replace=true полностью вычищает старые блоки с любыми языками', function (): void {
    ensure_test_db();

    $pageId = Page::create([
        'title' => 'Тестовая Страница Замены',
        'slug' => 'test-replace-' . bin2hex(random_bytes(2)),
        'status' => 'published',
        'is_home' => 0,
        'lang' => 'ru',
    ]);

    // Создаем старые блоки
    Block::create($pageId, 'ru', 'text', 'Старый текст 1', ['content' => 'Old 1'], '');
    Block::create($pageId, 'uz', 'text', 'Старый текст 2', ['content' => 'Old 2'], '');

    $oldBlocks = Block::forPage($pageId, null, false);
    assert_equals(2, count($oldBlocks), 'Вначале было 2 старых блока');

    $preset = \App\Core\PagePresets::find('home');
    BlockSnippet::applyToPage($preset['blocks'], $pageId, 'ru', true);

    $remainingRu = Block::forPage($pageId, 'ru');
    assert_true(count($remainingRu) > 0, 'Созданы новые блоки');

    $allBlocksAfter = Block::forPage($pageId, 'uz');
    assert_equals(0, count($allBlocksAfter), 'Старый узбекский блок зачищен');
});
