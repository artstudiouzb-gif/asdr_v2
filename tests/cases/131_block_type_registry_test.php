<?php

declare(strict_types=1);

use App\Core\BlockRenderer;
use App\Core\BlockSamples;
use App\Core\BlockTypeRegistry;

test('Реестр блоков: все источники используют одинаковый набор типов', function () {
    $types = BlockTypeRegistry::types();

    assert_same(38, count($types));
    assert_same($types, array_keys(BlockTypeRegistry::TYPE_LABELS));
    assert_same($types, array_keys(BlockTypeRegistry::editorLabels()));

    $sampleTypes = array_keys(BlockSamples::all());
    sort($types);
    sort($sampleTypes);
    assert_same($types, $sampleTypes);
});

test('Реестр блоков: совместимые фасады рендера не изменились', function () {
    assert_same(BlockTypeRegistry::DEFAULTS, BlockRenderer::DEFAULTS);
    assert_same(BlockTypeRegistry::TYPE_LABELS, BlockRenderer::TYPE_LABELS);
    assert_same(
        BlockTypeRegistry::defaultsFor('hero'),
        BlockRenderer::defaultsFor('hero')
    );
    assert_same([], BlockTypeRegistry::defaultsFor('unknown'));
});

test('Реестр блоков: каждому обычному типу соответствует шаблон', function () {
    foreach (BlockTypeRegistry::types() as $type) {
        $template = BlockTypeRegistry::templateFile($type);
        if ($type === 'columns') {
            assert_same(null, $template);
            continue;
        }

        assert_true($template !== null && is_file($template), "{$type}: шаблон блока не найден");
    }

    assert_same(null, BlockTypeRegistry::templateFile('unknown'));
});

test('Реестр блоков: форма и контроллер не содержат собственных списков типов', function () {
    $controller = (string) file_get_contents(APP_ROOT . '/app/Controllers/Admin/BlockController.php');
    $form = (string) file_get_contents(APP_ROOT . '/app/Views/admin/pages/form.php');

    assert_not_contains('private const TYPES', $controller);
    assert_contains('BlockTypeRegistry::has($type)', $controller);
    assert_contains('BlockTypeRegistry::editorLabels()', $form);
});
