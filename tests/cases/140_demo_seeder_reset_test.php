<?php

declare(strict_types=1);

use App\Core\DemoSeeder;

test('DemoSeeder RESET полностью заменяет контент эталонными данными (БД)', function (): void {
    ensure_test_db();
    $pdo = \App\Core\Database::pdo();

    // 1. Создаём созданный руками мусорный элемент
    $pdo->exec("INSERT INTO news (title, slug, excerpt, content, status, published_at, created_at) VALUES ('Junk News', 'junk-news-slug-test', 'junk', 'junk', 'published', NOW(), NOW())");
    $hasJunkBefore = (bool) $pdo->query("SELECT 1 FROM news WHERE slug = 'junk-news-slug-test'")->fetchColumn();
    assert_true($hasJunkBefore, 'Мусорная новость создана в БД');

    // 2. Запускаем resetAndRun
    $c = DemoSeeder::resetAndRun($pdo);

    // 3. Проверяем, что мусорная новость удалилась, а демо-новости созданы
    $hasJunkAfter = (bool) $pdo->query("SELECT 1 FROM news WHERE slug = 'junk-news-slug-test'")->fetchColumn();
    assert_false($hasJunkAfter, 'Мусорная новость удалена после RESET + DEMO');

    $hasDemoNews = (bool) $pdo->query("SELECT 1 FROM news WHERE slug = 'zasedanie-strategiya-2030'")->fetchColumn();
    assert_true($hasDemoNews, 'Флагманская демо-новость пересоздана');

    assert_true($c['news'] > 0, 'Счётчик новостей > 0');
    assert_true($c['pages'] > 0, 'Счётчик страниц > 0');
});
