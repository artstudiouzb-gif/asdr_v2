<?php

declare(strict_types=1);

use App\Core\SeoHelper;

test('SEO helper формирует resource hints, схемы и favicon-разметку', function (): void {
// 1. Проверка генерации Resource Hints
$hints = SeoHelper::resourceHintsHtml();
assert_true(str_contains($hints, "fonts.googleapis.com"));

// 2. Проверка генерации микроразметки организации
$org = SeoHelper::organizationSchema("https://example.com");
assert_true(str_contains($org, "GovernmentOrganization"));

// 3. Проверка генерации хлебных крошек
$crumbs = SeoHelper::breadcrumbSchema("https://example.com", [
    ["name" => "Главная", "url" => "/"],
    ["name" => "Новости", "url" => "/news"],
]);
assert_true(str_contains($crumbs, "BreadcrumbList"));

// 4. Проверка фавиконов
$favs = SeoHelper::faviconsHtml("https://example.com");
assert_true(str_contains($favs, "apple-touch-icon"));
});
