<?php

declare(strict_types=1);

use App\Core\Hero\HeroSlideData;

test('Hero: custom artwork and CTA image sizes are normalized', function (): void {
    $data = HeroSlideData::normalize([
        'art_size' => 'custom', 'art_width' => '777',
        'cta_image_mode' => 'fill', 'cta_image_width' => '333',
        'cta2_image_mode' => 'custom', 'cta2_image_width' => '222',
    ]);
    assert_same('custom', $data['art_size']);
    assert_same(777, $data['art_width']);
    assert_same('fill', $data['cta_image_mode']);
    assert_same(333, $data['cta_image_width']);
    assert_same('custom', $data['cta2_image_mode']);
    assert_same(222, $data['cta2_image_width']);
});

test('Hero editor: one sizing system and translated action URLs', function (): void {
    $form = (string) file_get_contents(APP_ROOT . '/app/Views/admin/heroes/slide_form.php');
    $renderer = (string) file_get_contents(APP_ROOT . '/app/Core/Hero/HeroRenderer.php');
    $layoutCss = (string) file_get_contents(APP_ROOT . '/public/assets/css/blocks/hero-art-layout.css');
    $baseCss = (string) file_get_contents(APP_ROOT . '/public/assets/css/blocks/hero.css');
    $translations = (string) file_get_contents(APP_ROOT . '/app/Models/HeroSlideTranslation.php');
    $slide = (string) file_get_contents(APP_ROOT . '/app/Models/HeroSlide.php');

    assert_contains("'custom' => 'Свой размер'", $form, 'logo custom size');
    assert_contains('name="art_width"', $form, 'logo width field');
    assert_contains("'fill' => 'На всю высоту кнопки'", $form, 'CTA fill mode');
    assert_contains('name="cta_image_width"', $form, 'CTA custom width');
    assert_contains('name="cta2_image_width"', $form, 'CTA2 custom width');
    assert_not_contains('Как у обложки', $form, 'ambiguous legacy wording removed');
    assert_contains('Использовать общую настройку обложки', $form, 'inheritance wording is explicit');

    assert_contains('hero__cta--image-', $renderer, 'renderer emits CTA image mode');
    assert_contains("'custom' => (int) $d['art_width']", $renderer, 'renderer uses custom logo width');
    assert_not_contains('.hero__art--large img { max-height:', $baseCss, 'legacy max-height artwork sizing is removed');
    assert_not_contains('.hero__art--large img', $layoutCss, 'side-layout CSS does not duplicate artwork width presets');
    assert_contains("'large' => 360", $renderer, 'large artwork width has one renderer source');
    assert_contains('.hero__cta--image-fill .hero__cta-icon img', $layoutCss, 'fill mode has a dedicated override');
    assert_contains('height: 44px;', $layoutCss, 'fill mode occupies the button inner height');

    foreach (['cta_url', 'cta2_url', 'link_url'] as $field) {
        assert_contains("[{$field}]", $form, "translation UI includes {$field}");
        assert_contains("'{$field}'", $translations, "translation model stores {$field}");
        assert_contains("'{$field}' => '{$field}'", $slide, "display applies {$field}");
    }
});