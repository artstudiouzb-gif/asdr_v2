<?php

declare(strict_types=1);

use App\Core\Hero\HeroSlideData;

/*
 * Оформление слайда обложки. Поля жили в модели и рисовались рендером, но до
 * них нельзя было дотянуться из админки, а «Цветовая схема» слайда не красила
 * его фон — и то, и другое молчит: форма выглядит рабочей, слайд выглядит
 * нормальным, просто настройка ничего не меняет.
 */

test('Схема слайда красит фон самого слайда', function () {
    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/blocks/hero.css');
    $renderer = (string) file_get_contents(APP_ROOT . '/app/Core/Hero/HeroRenderer.php');

    // --hero-bg применяется правилом на контейнере .hero, и объявление той же
    // переменной на слайде его не перекрашивает: потомок предка не красит.
    // Поэтому у слайда своя переменная и своё правило.
    $rule = (string) preg_replace('/^.*?\n\.hero__slide \{(.*?)\n\}.*$/s', '$1', $css);
    assert_contains('--hero-slide-bg', $rule, 'слайд перестал красить свой фон — схема слайда снова ничего не меняет');
    assert_contains('transparent', $rule, 'без отката на прозрачный слайд перекроет фон обложки');

    // Переменная объявляется только когда у слайда есть своя схема: иначе все
    // слайды получили бы заливку и перекрыли фон обложки.
    $slideCss = (string) preg_replace(
        '/^.*?private static function slideCss\(.*?\{(.*?)\n    \}.*$/s',
        '$1',
        $renderer
    );
    assert_contains("--hero-slide-bg", $slideCss, 'сервер не объявляет фон слайда');
    $head = substr($slideCss, 0, (int) strpos($slideCss, '--hero-slide-bg'));
    assert_contains("\$d['scheme'] !== ''", $head, 'фон слайда объявляется даже без своей схемы');
});

test('Оформление слайда настраивается из формы, а не только из базы', function () {
    $form = (string) file_get_contents(APP_ROOT . '/app/Views/admin/heroes/slide_form.php');

    // Эти поля рендер читал и раньше, но задать их можно было только у
    // обложки — на кадре с другим настроением приходилось менять её целиком.
    foreach ([
        'overlay_color' => 'цвет затемнения',
        'panel' => 'подложка под текстом',
        'scheme_bg' => 'свой фон схемы Custom',
        'scheme_text' => 'свой цвет текста',
        'scheme_accent' => 'цвет кнопки',
    ] as $field => $what) {
        assert_contains("'" . $field . "'", $form, 'в форме слайда нет поля: ' . $what);
    }

    // Пустое значение — «как у обложки». Формулировка в форме одна на все
    // поля, её стережёт тест 104.
    assert_contains('Использовать общую настройку обложки', $form, 'наследование потеряло единую формулировку');
});

test('Новые поля слайда доезжают до данных', function () {
    $out = HeroSlideData::normalize([
        'scheme' => 'custom',
        'scheme_bg' => '#123456',
        'scheme_text' => '#abcdef',
        'scheme_accent' => '#ff8800',
        'overlay_color' => '#001122',
        'panel' => 'on',
    ], 'ru');

    assert_same('#123456', $out['scheme_bg']);
    assert_same('#abcdef', $out['scheme_text']);
    assert_same('#ff8800', $out['scheme_accent']);
    assert_same('#001122', $out['overlay_color']);
    assert_same('on', $out['panel']);

    // Галочка «как у обложки» приходит отдельным полем и обнуляет цвет.
    $inherited = HeroSlideData::normalize([
        'scheme_bg' => '#123456',
        'scheme_bg_off' => '1',
    ], 'ru');
    assert_same('', $inherited['scheme_bg'], 'галочка наследования не обнуляет цвет');
});
