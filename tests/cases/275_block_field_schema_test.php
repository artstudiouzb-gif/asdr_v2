<?php

declare(strict_types=1);

use App\Core\BlockData\BlockFieldSchema;
use App\Core\BlockTypeRegistry;

/*
 * Схема настроек блока: одно описание вместо четырёх копий.
 *
 * Прежде настройка жила в четырёх местах — умолчание в реестре, поле в
 * block_form.php, ветка в collectData() и повторная проверка в шаблоне. Списки
 * допустимых значений расходились молча: форма отдавала значение, нормализатор
 * его принимал, а шаблон о нём не знал и откатывался к умолчанию.
 *
 * Проверяем ровно это: у типа со схемой всё перечисленное берётся из неё, а
 * шаблон второй копии списка не содержит.
 */

test('Умолчания типа со схемой берутся из схемы, а не из реестра', function () {
    foreach (BlockTypeRegistry::BASE_DEFAULTS as $type => $base) {
        if (BlockFieldSchema::has($type)) {
            assert_same([], $base, "у типа со схемой в BASE_DEFAULTS не должно быть своих полей: {$type}");
            assert_same(
                BlockFieldSchema::defaults($type),
                BlockTypeRegistry::defaultsFor($type),
                "умолчания {$type} обязаны приходить из схемы"
            );
        } else {
            assert_true($base !== [], "тип без схемы обязан объявить умолчания: {$type}");
        }
    }

    // Порядок типов — это порядок в редакторе, переезд на схему его не меняет.
    assert_same(array_keys(BlockTypeRegistry::BASE_DEFAULTS), array_keys(BlockTypeRegistry::defaults()));
});

test('Поля схемы описаны полностью и осмысленно', function () {
    foreach (BlockFieldSchema::all() as $type => $fields) {
        assert_true($fields !== [], "пустая схема бессмысленна: {$type}");
        foreach ($fields as $key => $field) {
            assert_true(in_array($field->kind, \App\Core\BlockData\Field::KINDS, true), "неизвестный тип поля {$type}.{$key}");
            assert_true($field->label !== '', "поле без подписи: {$type}.{$key}");
            if ($field->kind === 'enum') {
                assert_true(count($field->options) > 1, "список из одного значения не настройка: {$type}.{$key}");
                assert_true(
                    isset($field->options[(string) $field->default]),
                    "умолчание вне списка значений: {$type}.{$key}"
                );
            }
            if ($field->kind === 'int') {
                assert_true($field->min < $field->max, "пустой диапазон: {$type}.{$key}");
            }
        }
    }
});

test('Присланное формой значение приводится к схеме', function () {
    $data = BlockFieldSchema::normalize('partners', [
        'variant' => 'marquee',
        'columns' => '99',              // за границей — прижимается к максимуму
        'logo_size' => 'gigantic',      // чужое значение — умолчание
        'autoplay' => '7',
        'title_field' => '  Партнёры  ',
        'all_url' => 'javascript:alert(1)',
    ], 'ru');

    assert_same('marquee', $data['variant']);
    assert_same(8, $data['columns']);
    assert_same('medium', $data['logo_size']);
    assert_same(7, $data['autoplay']);
    assert_same('Партнёры', $data['title']);
    assert_same('', $data['all_url'], 'опасная ссылка не сохраняется');
    // Флажок без отметки приходит отсутствием поля, а не нулём.
    assert_same(false, $data['grayscale']);
});

test('Сохранённые данные тоже приводятся к схеме — на выводе', function () {
    // Так выглядит блок из старой записи или из загруженного файла шаблона
    // страницы: ключи проверены, значения — нет.
    $data = BlockFieldSchema::apply('columns', [
        'columns' => 9,
        'gap' => 'huge',
        'valign' => 'diagonal',
        'mobile_order' => 'reverse',
    ]);

    assert_same(4, $data['columns']);
    assert_same('medium', $data['gap']);
    assert_same('stretch', $data['valign']);
    assert_same('reverse', $data['mobile_order']);
});

test('Форма и сохранение типа со схемой обращаются к ней, а не к рукописным полям', function () {
    $form = (string) file_get_contents(APP_ROOT . '/app/Views/admin/pages/block_form.php');
    $controller = (string) file_get_contents(APP_ROOT . '/app/Controllers/Admin/BlockController.php');

    foreach (array_keys(BlockFieldSchema::all()) as $type) {
        assert_contains("BlockFieldSchema::formHtml('{$type}'", $form, "форма {$type} рисуется по схеме");
        assert_contains("BlockFieldSchema::normalize('{$type}'", $controller, "сохранение {$type} идёт по схеме");

        $html = BlockFieldSchema::formHtml($type, []);
        foreach (BlockFieldSchema::fields($type) as $key => $field) {
            assert_contains('name="' . $field->inputName($key) . '"', $html, "поле {$type}.{$key} есть в форме");
        }
    }
});

test('Новые настройки колонок доезжают до разметки', function () {
    // id = 0: без реального id блок не ходит в БД за вложенными блоками, и
    // обёртку контейнера можно проверить без базы.
    $render = static function (array $data): string {
        $rendered = \App\Core\BlockRenderer::render([
            'id' => 0,
            'type' => 'columns',
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'custom_css' => '',
        ]);

        return (string) $rendered['html'];
    };

    $default = $render([]);
    assert_not_contains('cms-columns--valign', $default, 'растяжение — поведение сетки по умолчанию, класса ему не нужно');
    assert_not_contains('cms-columns--mobile-reverse', $default);

    $changed = $render(['valign' => 'center', 'mobile_order' => 'reverse']);
    assert_contains('cms-columns--valign-center', $changed);
    assert_contains('cms-columns--mobile-reverse', $changed);
});

test('Шаблон типа со схемой не перепроверяет значения', function () {
    // Второй список допустимых значений — это и есть та копия, ради которой всё
    // затевалось: она разъезжается с первой молча.
    $partners = (string) file_get_contents(APP_ROOT . '/templates/blocks/partners.php');
    assert_not_contains('in_array(', $partners, 'значения уже проверены схемой');

    $renderer = (string) file_get_contents(APP_ROOT . '/app/Core/BlockRenderer.php');
    assert_contains('BlockFieldSchema::apply($type, $data)', $renderer, 'рендер приводит данные к схеме');
});
