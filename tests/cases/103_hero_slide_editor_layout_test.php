<?php

declare(strict_types=1);

test('редактор слайда использует отдельную читаемую UI-систему', function (): void {
    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/admin-hero-slide-editor.css');
    $brand = (string) file_get_contents(APP_ROOT . '/app/Core/AdminBrand.php');

    assert_contains('form[action*="/admin/heroes/"][action*="/slides/"]', $css, 'стили ограничены редактором слайда');
    assert_contains('grid-template-columns: repeat(3, minmax(0, 1fr)) !important;', $css, 'широкий экран использует максимум три читаемые колонки');
    assert_contains('details.form-section > summary', $css, 'секции получают единый заголовок');
    assert_contains('.form-section__state', $css, 'сводка состояния секции оформлена отдельно');
    assert_contains('.form-field--checkbox', $css, 'чекбоксы приведены к единой карточке');
    assert_contains('.image-field__row', $css, 'поля изображений выровнены в общей сетке');
    assert_contains('position: sticky;', $css, 'действия сохранения остаются доступны на длинной форме');
    assert_contains('@media (max-width: 760px)', $css, 'редактор имеет мобильную раскладку');

    assert_contains('/assets/css/admin-hero-slide-editor.css', $brand, 'новый слой подключён через версионируемый Asset::url');
    assert_contains('data-admin-hero-slide-editor-css', $brand, 'подключение можно однозначно проверить в DOM');
});
