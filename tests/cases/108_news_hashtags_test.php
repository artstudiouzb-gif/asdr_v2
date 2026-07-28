<?php

declare(strict_types=1);

use App\Models\News;
use App\Core\SocialSettings;

test('Хештеги новостей нормализуются и попадают в социальный пост', function (): void {
// 1. Нормализация хештегов
assert_same("#культура #ташкент #события", News::cleanHashtags("культура, #ташкент, события"));
assert_same("#спорт #футбол", News::cleanHashtags("#спорт #футбол #спорт"));
assert_same(null, News::cleanHashtags(""));
assert_same(null, News::cleanHashtags(null));

// 2. Включение хештегов в текст публикаций соцсетей
$post = SocialSettings::buildPost([
    "title" => "Тестовая новость",
    "slug" => "test-news",
    "excerpt" => "Краткий анонс новости",
    "content" => "<p>Текст новости</p>",
    "image" => "/uploads/test.jpg",
    "hashtags" => "культура, #ташкент",
]);

assert_true(str_contains($post["message"], "#культура #ташкент"));
});
