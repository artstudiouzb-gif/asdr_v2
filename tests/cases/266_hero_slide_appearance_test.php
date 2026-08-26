<?php

declare(strict_types=1);

use App\Core\Hero\HeroSlideData;

/*
 * Оформление слайда обложки. Поля жили в модели и рисовались рендером, но до
 * них нельзя было дотянуться из админки, а «Цветовая схема» слайда не красила
 * его фон — и то, и другое молчит: форма выглядит рабочей, слайд выглядит
 * нормальным, просто настройка ничего не меняет.
 */

test('У слайда осталось только то, что зависит от кадра', function () {
    // Раскладка, размеры и палитра принадлежат обложке: у слайда они
    // удваивали настройку и разъезжались с ней. От кадра к кадру меняются
    // фотография, наложение поверх неё и цвет текста — они и остались.
    $fields = array_keys(HeroSlideData::defaults());

    foreach (['overlay', 'overlay_color', 'overlay_opacity', 'overlay_direction', 'content_scheme'] as $keep) {
        assert_true(in_array($keep, $fields, true), 'у слайда пропала настройка кадра: ' . $keep);
    }

    foreach ([
        'scheme', 'scheme_bg', 'scheme_text', 'scheme_accent', 'panel',
        'text_position', 'text_align_y', 'title_size', 'subtitle_size',
        'text_offset_top',
    ] as $gone) {
        assert_false(in_array($gone, $fields, true), 'переопределение вернулось к слайду: ' . $gone);
    }
});

test('Цвет наложения у слайда задаётся из формы и доезжает до данных', function () {
    $form = (string) file_get_contents(APP_ROOT . '/app/Views/admin/heroes/slide_form.php');
    assert_contains("'overlay_color'", $form, 'в форме слайда нет цвета наложения');
    assert_contains('Использовать общую настройку обложки', $form, 'наследование потеряло единую формулировку');

    $out = HeroSlideData::normalize(['overlay' => 'solid', 'overlay_color' => '#001122'], 'ru');
    assert_same('#001122', $out['overlay_color']);
    assert_same('', HeroSlideData::normalize([], 'ru')['overlay_color'], 'пусто — «как у обложки»');
});
