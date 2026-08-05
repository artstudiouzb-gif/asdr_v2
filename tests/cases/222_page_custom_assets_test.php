<?php

declare(strict_types=1);

use App\Core\CustomAssetHelper;
use App\Core\GeneratedJs;
use App\Core\GeneratedCss;

test('CustomAssetHelper::resolveCssUrls: разделяет внешние URL и компилирует кастомный CSS во внешние файлы', function () {
    $rawInput = "https://cdn.example.com/animate.min.css\nbody { --custom-accent: #007bff; }";
    $urls = CustomAssetHelper::resolveCssUrls($rawInput, 999);

    assert_same(2, count($urls));
    assert_same('https://cdn.example.com/animate.min.css', $urls[0]);
    assert_true(str_starts_with($urls[1], '/uploads/public/generated-css/page-999-'), 'сгенерирован внешний CSS файл');
    assert_true(str_ends_with($urls[1], '.css'));
});

test('CustomAssetHelper::resolveJsUrls: разделяет внешние URL и компилирует кастомный JS во внешние файлы', function () {
    $rawInput = "https://cdn.example.com/confetti.js\nconsole.log('Custom JS execution');";
    $urls = CustomAssetHelper::resolveJsUrls($rawInput, 999);

    assert_same(2, count($urls));
    assert_same('https://cdn.example.com/confetti.js', $urls[0]);
    assert_true(str_starts_with($urls[1], '/uploads/public/generated-js/page-999-'), 'сгенерирован внешний JS файл');
    assert_true(str_ends_with($urls[1], '.js'));
});

test('GeneratedJs::publish: сохраняет JS во внешний файл на диске без инлайн-тегов', function () {
    $url = GeneratedJs::publish("console.log('unit test js');", 'test-scope');
    assert_true($url !== null);
    assert_true(str_contains($url, '/uploads/public/generated-js/test-scope-'));
    $filePath = APP_ROOT . '/public' . parse_url($url, PHP_URL_PATH);
    assert_true(is_file($filePath), 'файл сгенерирован на диске');
    $content = (string) file_get_contents($filePath);
    assert_contains("console.log('unit test js');", $content);
});
