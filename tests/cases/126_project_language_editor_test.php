<?php

declare(strict_types=1);

test('Редактор проекта поддерживает независимые языковые записи', function (): void {
    $form = (string) file_get_contents(APP_ROOT . '/app/Views/admin/projects/form.php');

    assert_contains('renderSidebarMetaBox', $form, 'форма использует блок независимых переводов');
    assert_contains('name="description" data-wysiwyg', $form, 'описание использует визуальный редактор');
    assert_same(1, substr_count($form, 'name="title"'), 'основное название не дублируется');
    assert_same(1, substr_count($form, 'name="description"'), 'основное описание не дублируется');
});
