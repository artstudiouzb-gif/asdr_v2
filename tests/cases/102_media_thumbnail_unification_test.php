<?php

declare(strict_types=1);

test('медиабиблиотека использует единый thumbnail-контракт в разделе и модальном picker', function (): void {
    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/admin-media-unified.css');
    $js = (string) file_get_contents(APP_ROOT . '/public/assets/js/admin.js');

    assert_contains('.media-grid .media-card,', $css, 'страница медиабиблиотеки входит в общий контракт карточки');
    assert_contains('.media-modal__grid .media-modal__item {', $css, 'модальный picker входит в тот же контракт карточки');
    assert_contains('aspect-ratio: 16 / 9 !important;', $css, 'thumbnail в обоих контекстах имеет пропорцию 16:9');
    assert_contains('.media-grid .media-card__caption,', $css, 'подпись standalone-карточки участвует в общей системе');
    assert_contains('.media-modal__grid .media-modal__item:not(.media-modal__item--file)::before', $css, 'модальная карточка показывает имя файла поверх thumbnail');

    assert_contains('.media-grid .media-card.is-selected,', $css, 'standalone-карточка использует общий selected-state');
    assert_contains('.media-modal__grid .media-modal__item.is-selected {', $css, 'модальная карточка использует тот же selected-state');
    assert_contains('.media-modal__grid .media-modal__item.is-selected::after', $css, 'выбранный файл в модальном picker получает видимую галочку');
    assert_contains("content: '✓';", $css, 'галочка присутствует в модальном picker');

    assert_contains("var isThis = currentMultiple ? selectedUrls.indexOf(itemUrl) !== -1 : itemUrl === selectedUrl;", $js, 'одинарный и множественный выбор вычисляют selected-state одинаково');
    assert_contains("fig.classList.toggle('is-selected', isThis);", $js, 'визуальный selected-state синхронизируется через общий класс');
});
