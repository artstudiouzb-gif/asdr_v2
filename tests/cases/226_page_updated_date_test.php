<?php

declare(strict_types=1);

use App\Core\DateFormatter;

// Дата последнего обновления страницы — обязательный реквизит сайта госоргана.
// Проверяем формат по языкам, наличие перевода и условия вывода во вью.

test('Дата обновления: длинный формат на всех языках публички', function () {
    assert_same('8 августа 2026 г.', DateFormatter::long('2026-08-08 11:20:00', 'ru'));
    assert_same('8-avgust, 2026-yil', DateFormatter::long('2026-08-08 11:20:00', 'uz'));
    assert_same('August 8, 2026', DateFormatter::long('2026-08-08 11:20:00', 'en'));
    // Пустое значение из БД не должно превращаться в «1 января 1970».
    assert_same('', DateFormatter::long('0000-00-00 00:00:00', 'ru'));
});

test('Дата обновления: строка переведена на узбекский и английский', function () {
    foreach (['uz', 'en'] as $lang) {
        $dictionary = require APP_ROOT . '/app/Core/lang/' . $lang . '.php';
        assert_true(isset($dictionary['Обновлено']), 'нет перевода «Обновлено» в ' . $lang);
        assert_true(trim((string) $dictionary['Обновлено']) !== '', 'пустой перевод в ' . $lang);
    }
});

test('Дата обновления: не выводится на главной, лендинге и при пустой дате', function () {
    $view = (string) file_get_contents(APP_ROOT . '/app/Views/site/page.php');
    assert_contains('page-updated', $view);
    // Условие вывода: не главная, не лендинг (hide_chrome) и дата разбирается.
    assert_contains('!$isHome && !$hideChrome && $updatedTs !== false', $view);
    // Машиночитаемая дата в <time datetime> — для поисковиков и оценки сайта.
    assert_contains('<time datetime=', $view);
});
