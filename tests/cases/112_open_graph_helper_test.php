<?php

declare(strict_types=1);

use App\Core\OpenGraphHelper;

test('OpenGraph helper формирует метаданные и fallback-обложку', function (): void {
// 1. Проверка генерации OpenGraph для новости
$og = OpenGraphHelper::render(
    "https://example.com",
    "Заголовок новости",
    "Описание новости",
    "https://example.com/news/1",
    "ru",
    "https://example.com/cover.jpg",
    "article",
    "2026-07-25 10:00:00"
);

assert_true(str_contains($og, "og:title"));
assert_true(str_contains($og, "og:image:width"));
assert_true(str_contains($og, "summary_large_image"));
assert_true(str_contains($og, "article:published_time"));

// 2. Проверка дефолтного фолбэка обложки при пустой картинке
$ogFallback = OpenGraphHelper::render(
    "https://example.com",
    "Главная страница",
    "Описание главной",
    "https://example.com/",
    "ru"
);
assert_true(str_contains($ogFallback, "og:image"));
});
