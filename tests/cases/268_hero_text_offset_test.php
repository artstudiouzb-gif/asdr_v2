<?php

declare(strict_types=1);

use App\Core\Hero\HeroRenderer;
use App\Core\Hero\HeroSettings;
use App\Core\Hero\HeroSlideData;

/*
 * Отступ всего текстового блока обложки сверху. Промежутки gap_* работают
 * между частями, и у первой части сняты правилом `.hero__text > :first-child`,
 * поэтому опустить колонку целиком ими нельзя — до появления настройки это
 * дописывали padding'ом в «Свой CSS» страницы.
 */

/** @param array<string, mixed> $slide */
function hero_offset_css(array $slide, array $settings = []): string
{
    $rendered = HeroRenderer::render(
        ['id' => 1, 'name' => 'Проба'],
        [['id' => 1, 'hero_id' => 1, 'title' => 'Т', 'sort_order' => 0, 'is_active' => 1,
          'data' => HeroSlideData::withDefaults($slide)]],
        HeroSettings::withDefaults($settings),
        21
    );

    return (string) ($rendered['css'] ?? '');
}

test('Отступ текста сверху доезжает до стилей обложки, слайда и телефона', function () {
    $slide = ['title' => 'Заголовок', 'media_type' => 'none'];

    $hero = hero_offset_css($slide, ['text_offset_top' => 40]);
    assert_contains('--hero-text-offset:40px', $hero, 'отступ обложки не попал в стили');

    // Телефон: своё значение объявляется в медиазапросе, десктопное остаётся.
    $mobile = hero_offset_css($slide, ['text_offset_top' => 40, 'text_offset_top_mobile' => 16]);
    assert_contains('--hero-text-offset:16px', $mobile, 'мобильный отступ не попал в стили');

    // Слайд переопределяет обложку.
    $own = hero_offset_css($slide + ['text_offset_top' => 90], ['text_offset_top' => 40]);
    assert_contains('--hero-text-offset:90px', $own, 'слайд не может задать свой отступ');

    // Пустое значение у слайда — «как у обложки»: своей переменной нет.
    $inherited = hero_offset_css($slide, ['text_offset_top' => 40]);
    assert_same(
        1,
        substr_count($inherited, '--hero-text-offset'),
        'слайд объявляет отступ, хотя должен наследовать значение обложки'
    );
});

test('Блок текста обложки читает отступ из переменной', function () {
    // Без этого правила настройка остаётся числом в базе.
    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/blocks/hero.css');
    $rule = (string) preg_replace('/^.*?\n\.hero__text \{(.*?)\n\}.*$/s', '$1', $css);
    assert_contains('padding-top: var(--hero-text-offset', $rule, 'блок текста перестал читать отступ');
});

test('Отступ задаётся из форм, а не только из базы', function () {
    $hero = (string) file_get_contents(APP_ROOT . '/app/Views/admin/heroes/form.php');
    assert_contains('name="text_offset_top"', $hero, 'в форме обложки нет поля отступа');
    assert_contains('name="text_offset_top_mobile"', $hero, 'в форме обложки нет отступа для телефона');

    $slide = (string) file_get_contents(APP_ROOT . '/app/Views/admin/heroes/slide_form.php');
    assert_contains('name="text_offset_top"', $slide, 'в форме слайда нет поля отступа');

    // Значения доезжают до данных: у обложки числом, у слайда — с пустым
    // значением как признаком наследования.
    $settings = HeroSettings::normalize(['text_offset_top' => '40', 'text_offset_top_mobile' => '']);
    assert_same(40, $settings['text_offset_top']);
    assert_same('', $settings['text_offset_top_mobile'], 'пустой мобильный отступ должен означать «как на десктопе»');

    $data = HeroSlideData::normalize(['text_offset_top' => '90'], 'ru');
    assert_same(90, $data['text_offset_top']);
    assert_same('', HeroSlideData::normalize([], 'ru')['text_offset_top'], 'пусто у слайда — «как у обложки»');
});
