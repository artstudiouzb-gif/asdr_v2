<?php

declare(strict_types=1);

test('Редактор проекта поддерживает независимые языковые записи', function (): void {
    $form = (string) file_get_contents(APP_ROOT . '/app/Views/admin/projects/form.php');

    assert_contains('renderSidebarMetaBox', $form, 'форма использует блок независимых переводов');
    // Тело проекта собирается конструктором блоков (проект — страница с
    // подтипом), а в поле description остаётся короткий анонс для карточки.
    assert_contains('name="description"', $form, 'у проекта есть поле анонса');
    assert_not_contains('name="description" data-wysiwyg', $form, 'анонс — простой текст, без визуального редактора');
    assert_contains("_block_editor.php", $form, 'форма подключает конструктор блоков');
    assert_same(1, substr_count($form, 'name="title"'), 'основное название не дублируется');
    assert_same(1, substr_count($form, 'name="description"'), 'основное описание не дублируется');
});
