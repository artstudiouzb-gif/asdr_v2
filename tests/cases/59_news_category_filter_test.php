<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\NewsCategoryTranslation;

// Рубрикатор новостей по категориям: publishedCategories + фильтр в
// published/publishedCount. Раньше рубрикой служил свободный текст в
// news.badge — он не сводился в справочник, и «Пресс-релиз» с «пресс-релиз»
// были двумя разными рубриками.

test('News::publishedCategories: только категории опубликованных новостей', function () {
    ensure_test_db();
    $pdo = Database::pdo();

    $pressId = NewsCategory::create('Пресс-релиз');
    $analyticsId = NewsCategory::create('Аналитика');
    $draftOnlyId = NewsCategory::create('Только черновики');
    $hiddenId = NewsCategory::create('Скрытая рубрика', '', false);

    $ids = [];
    foreach ([
        ['cat-a', 'published', $pressId],
        ['cat-b', 'published', $analyticsId],
        ['cat-c', 'published', null],
        ['cat-d', 'draft', $draftOnlyId],
        ['cat-e', 'published', $hiddenId],
    ] as [$slug, $status, $categoryId]) {
        $stmt = $pdo->prepare(
            'INSERT INTO news (title, slug, status, published_at, category_id) VALUES (?, ?, ?, NOW(), ?)'
        );
        $stmt->execute([$slug, 'test-' . $slug, $status, $categoryId]);
        $ids[] = (int) $pdo->lastInsertId();
    }

    $names = array_map(static fn (array $c): string => (string) $c['name'], News::publishedCategories());
    assert_true(in_array('Пресс-релиз', $names, true), 'рубрика опубликованной новости есть в списке');
    assert_true(in_array('Аналитика', $names, true), 'вторая рубрика есть в списке');
    assert_true(!in_array('Только черновики', $names, true), 'рубрика без опубликованных новостей не попадает');
    // Скрытая рубрика не предлагается посетителю, даже если новости в ней есть:
    // иначе выключение рубрики в админке ни на что бы не влияло.
    assert_true(!in_array('Скрытая рубрика', $names, true), 'скрытая рубрика не попадает в рубрикатор');
    assert_same(1, count(array_keys($names, 'Пресс-релиз', true)), 'без дублей');

    $pdo->exec('DELETE FROM news WHERE id IN (' . implode(',', $ids) . ')');
    foreach ([$pressId, $analyticsId, $draftOnlyId, $hiddenId] as $id) {
        NewsCategory::delete((int) $id);
    }
});

test('News::published и publishedCount фильтруют по категории', function () {
    ensure_test_db();
    $pdo = Database::pdo();

    $pressId = (int) NewsCategory::create('Пресс-релиз фильтра');
    $analyticsId = (int) NewsCategory::create('Аналитика фильтра');

    $ids = [];
    foreach ([['flt-a', $pressId], ['flt-b', $pressId], ['flt-c', $analyticsId]] as [$slug, $categoryId]) {
        $stmt = $pdo->prepare(
            "INSERT INTO news (title, slug, status, published_at, category_id) VALUES (?, ?, 'published', NOW(), ?)"
        );
        $stmt->execute([$slug, 'test-' . $slug, $categoryId]);
        $ids[] = (int) $pdo->lastInsertId();
    }

    assert_same(2, News::publishedCount($pressId));
    $rows = News::published(50, 0, null, $pressId);
    assert_same(2, count($rows));
    foreach ($rows as $row) {
        assert_same($pressId, (int) $row['category_id']);
    }
    assert_true(News::publishedCount() >= 3, 'без фильтра — все опубликованные');
    // Несуществующая рубрика — пустая лента, а не вся лента: иначе битая
    // ссылка молча показывала бы всё подряд.
    assert_same(0, News::publishedCount(999999));

    $pdo->exec('DELETE FROM news WHERE id IN (' . implode(',', $ids) . ')');
    NewsCategory::delete($pressId);
    NewsCategory::delete($analyticsId);
});

test('Категория переводится, а slug остаётся общим для всех языков', function () {
    ensure_test_db();

    $id = (int) NewsCategory::create('Мероприятия перевода');
    NewsCategoryTranslation::upsert($id, 'uz', 'Tadbirlar tarjimasi');

    $ru = NewsCategory::localize((array) NewsCategory::find($id), 'ru');
    $uz = NewsCategory::localize((array) NewsCategory::find($id), 'uz');

    assert_same('Мероприятия перевода', (string) $ru['name']);
    assert_same('Tadbirlar tarjimasi', (string) $uz['name']);
    assert_same((string) $ru['slug'], (string) $uz['slug'], 'адрес рубрики один на все языки');

    // Пустой перевод — откат к основному языку, а не пустая рубрика в ленте.
    NewsCategoryTranslation::upsert($id, 'uz', '');
    $uzEmpty = NewsCategory::localize((array) NewsCategory::find($id), 'uz');
    assert_same('Мероприятия перевода', (string) $uzEmpty['name']);

    NewsCategory::delete($id);
});
