<?php

declare(strict_types=1);

use App\Core\BlockData\BannerBlockNormalizer;
use App\Core\BlockData\CtaBlockNormalizer;
use App\Core\BlockData\SubscribeBlockNormalizer;

test('CTA normalizer: сохраняет контракт, цвета и безопасную ссылку', function () {
    $data = CtaBlockNormalizer::normalize([
        'title_field' => '  Заголовок  ',
        'text' => '  Описание  ',
        'button_text' => ' Подробнее ',
        'button_url' => ' javascript:alert(1) ',
        'bg_color' => '#AABBCC',
        'text_color' => '#112233',
        'text_color_off' => '1',
        'button_color' => 'bad',
    ]);

    assert_same([
        'title' => 'Заголовок',
        'text' => 'Описание',
        'button_text' => 'Подробнее',
        'button_url' => '',
        'bg_color' => '#aabbcc',
        'text_color' => '',
        'button_color' => '',
    ], $data);

    assert_same('/about', CtaBlockNormalizer::normalize(['button_url' => ' /about '])['button_url']);
});

test('Banner normalizer: сохраняет контракт, стиль и безопасную ссылку', function () {
    $data = BannerBlockNormalizer::normalize([
        'title_field' => '  Баннер  ',
        'text' => '  Текст  ',
        'image' => ' /uploads/public/banner.jpg ',
        'style' => 'light',
        'button_text' => ' Открыть ',
        'button_url' => ' https://example.com/page ',
        'bg_color' => '#010203',
        'text_color' => '#A0B0C0',
        'button_color' => '#FFFFFF',
        'button_color_off' => '1',
    ]);

    assert_same([
        'title' => 'Баннер',
        'text' => 'Текст',
        'image' => '/uploads/public/banner.jpg',
        'style' => 'light',
        'button_text' => 'Открыть',
        'button_url' => 'https://example.com/page',
        'bg_color' => '#010203',
        'text_color' => '#a0b0c0',
        'button_color' => '',
    ], $data);

    $invalid = BannerBlockNormalizer::normalize([
        'style' => 'unknown',
        'button_url' => "https://example.com/\njavascript:alert(1)",
    ]);
    assert_same('dark', $invalid['style']);
    assert_same('', $invalid['button_url']);
});

test('Subscribe normalizer: сохраняет простой текстовый контракт', function () {
    assert_same([
        'title' => 'Подписка',
        'text' => 'Получайте новости',
        'button_text' => 'Подписаться',
    ], SubscribeBlockNormalizer::normalize([
        'title_field' => '  Подписка  ',
        'text' => '  Получайте новости  ',
        'button_text' => ' Подписаться ',
    ]));
});

test('Контроллер делегирует простые блоки отдельным нормализаторам', function () {
    $controller = (string) file_get_contents(APP_ROOT . '/app/Controllers/Admin/BlockController.php');

    assert_contains('CtaBlockNormalizer::normalize($_POST, $locale)', $controller);
    assert_contains('BannerBlockNormalizer::normalize($_POST, $locale)', $controller);
    assert_contains('SubscribeBlockNormalizer::normalize($_POST, $locale)', $controller);
    assert_not_contains('$bannerUrl = trim', $controller);
});
