<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\News;

// Рубрикатор новостей по бейджам: distinctBadges + фильтр в published/publishedCount.

test('News::distinctBadges: только бейджи опубликованных, без пустых и дублей', function () {
    ensure_test_db();
    $pdo = Database::pdo();
    $ids = [];
    foreach ([
        ['bdg-a', 'published', 'Пресс-релиз'],
        ['bdg-b', 'published', 'Пресс-релиз'],
        ['bdg-c', 'published', 'Аналитика'],
        ['bdg-d', 'published', null],
        ['bdg-e', 'draft', 'Черновик-бейдж'],
    ] as [$slug, $status, $badge]) {
        $stmt = $pdo->prepare("INSERT INTO news (title, slug, status, published_at, badge) VALUES (?, ?, ?, NOW(), ?)");
        $stmt->execute([$slug, 'test-' . $slug, $status, $badge]);
        $ids[] = (int) $pdo->lastInsertId();
    }

    $badges = News::distinctBadges();
    assert_true(in_array('Пресс-релиз', $badges, true), 'бейдж опубликованных есть в списке');
    assert_true(in_array('Аналитика', $badges, true), 'второй бейдж есть в списке');
    assert_true(!in_array('Черновик-бейдж', $badges, true), 'бейдж черновика не попадает');
    assert_same(1, count(array_keys($badges, 'Пресс-релиз', true)), 'без дублей');

    $pdo->exec('DELETE FROM news WHERE id IN (' . implode(',', $ids) . ')');
});

test('News::published и publishedCount фильтруют по бейджу', function () {
    ensure_test_db();
    $pdo = Database::pdo();
    $ids = [];
    foreach ([['flt-a', 'Пресс-релиз'], ['flt-b', 'Пресс-релиз'], ['flt-c', 'Аналитика']] as [$slug, $badge]) {
        $stmt = $pdo->prepare("INSERT INTO news (title, slug, status, published_at, badge) VALUES (?, ?, 'published', NOW(), ?)");
        $stmt->execute([$slug, 'test-' . $slug, $badge]);
        $ids[] = (int) $pdo->lastInsertId();
    }

    assert_same(2, News::publishedCount('Пресс-релиз'));
    $rows = News::published(50, 0, null, 'Пресс-релиз');
    foreach ($rows as $row) {
        assert_same('Пресс-релиз', (string) $row['badge']);
    }
    assert_true(News::publishedCount() >= 3, 'без фильтра — все опубликованные');
    assert_same(0, News::publishedCount('Нет такого бейджа'));

    $pdo->exec('DELETE FROM news WHERE id IN (' . implode(',', $ids) . ')');
});
