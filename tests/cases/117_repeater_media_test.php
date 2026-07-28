<?php

declare(strict_types=1);

test('Поля картинок в повторителях получают выбор из медиабиблиотеки', function () {
    $js = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/js/admin.js');

    // Кнопка навешивается по имени поля, а не по каждому месту в разметке:
    // логотипы, фото и изображения строк повторителя раньше приходилось
    // вписывать путём вручную.
    assert_contains('NAME_RE = /\\[(image|logo|photo|cover|media)\\]$/i', $js);
    assert_contains("setAttribute('data-media-pick'", $js);
    assert_contains("setAttribute('data-media-target', '#' + input.id)", $js);

    // Строки, добавленные после загрузки страницы (шаблон __INDEX__),
    // тоже должны получать кнопку.
    assert_contains('MutationObserver', $js);

    // Поля, уже обёрнутые общим компонентом изображения, не трогаем.
    assert_contains("input.closest('[data-image-field]')", $js);
});

test('Разметка блоков: у полей картинок в повторителях единый формат имени', function () {
    $form = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Views/admin/pages/block_form.php');

    // Именно на эти имена опирается автоматическая кнопка медиабиблиотеки.
    foreach (['[logo]', '[photo]', '[image]'] as $needle) {
        assert_contains($needle, $form, "в форме нет полей {$needle}");
    }

    $css = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/admin.css');
    assert_contains('.repeater-media', $css, 'нет стилей для строки «поле + кнопка»');
});
