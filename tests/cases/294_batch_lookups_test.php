<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\TranslationGroupHelper;
use App\Models\Page;

/*
 * Пакетные выборки заменили поштучные там, где страница перебирает записи:
 * карта сайта спрашивала переводы по одной (900 запросов на 450 записей),
 * шапка разрешала цели меню по одной (36 запросов на каждой странице).
 *
 * Быстрый путь обязан давать то же, что медленный, — иначе он тихо разойдётся
 * с ним при первой правке. Здесь это и проверяется: результаты сверяются
 * запись в запись, а не «оба непустые».
 */

test('Пакетное чтение переводов совпадает с поштучным (БД)', function () {
    ensure_test_db();
    $pdo = Database::pdo();
    $pdo->exec('DELETE FROM pages');

    $insert = $pdo->prepare(
        "INSERT INTO pages (title, slug, lang, translation_group_id, entity_type, status, created_at, updated_at)
         VALUES (:title, :slug, :lang, :grp, 'page', :status, NOW(), NOW())"
    );
    // Группа из двух языков, одиночная страница и черновик: getTranslations()
    // отдаёт группу целиком, не фильтруя по статусу, — это и сверяем.
    $insert->execute([':title' => 'Об агентстве', ':slug' => 'about', ':lang' => 'ru', ':grp' => 0, ':status' => 'published']);
    $ruId = (int) $pdo->lastInsertId();
    $insert->execute([':title' => 'Agentlik haqida', ':slug' => 'about-uz', ':lang' => 'uz', ':grp' => $ruId, ':status' => 'published']);
    $uzId = (int) $pdo->lastInsertId();
    $insert->execute([':title' => 'Одиночная', ':slug' => 'alone', ':lang' => 'ru', ':grp' => 0, ':status' => 'published']);
    $aloneId = (int) $pdo->lastInsertId();
    $insert->execute([':title' => 'Черновик', ':slug' => 'draft', ':lang' => 'ru', ':grp' => 0, ':status' => 'draft']);
    $draftId = (int) $pdo->lastInsertId();

    $ids = [$ruId, $uzId, $aloneId, $draftId];
    $batch = TranslationGroupHelper::getTranslationsBatch('pages', $ids);

    foreach ($ids as $id) {
        $one = TranslationGroupHelper::getTranslations('pages', $id);
        $many = $batch[$id] ?? [];
        assert_same(
            array_keys($one),
            array_keys($many),
            "языки записи {$id} совпадают"
        );
        foreach ($one as $lang => $row) {
            assert_same((int) $row['id'], (int) $many[$lang]['id'], "строка {$lang} записи {$id} та же");
        }
    }

    // Несуществующий id не должен ронять выборку и не выдумывает переводов.
    $withGhost = TranslationGroupHelper::getTranslationsBatch('pages', [$ruId, 999999]);
    assert_same([], $withGhost[999999] ?? [], 'у несуществующей записи переводов нет');

    $pdo->exec('DELETE FROM pages');
});

test('Пакетное разрешение целей меню совпадает с поштучным (БД)', function () {
    ensure_test_db();
    $pdo = Database::pdo();
    $pdo->exec('DELETE FROM pages');

    $insert = $pdo->prepare(
        "INSERT INTO pages (title, slug, lang, entity_type, status, created_at, updated_at)
         VALUES (:title, :slug, :lang, :type, :status, NOW(), NOW())"
    );
    $insert->execute([':title' => 'Контакты', ':slug' => 'contacts', ':lang' => 'ru', ':type' => 'page', ':status' => 'published']);
    $insert->execute([':title' => 'Черновик', ':slug' => 'hidden', ':lang' => 'ru', ':type' => 'page', ':status' => 'draft']);
    $insert->execute([':title' => 'Проект', ':slug' => 'bridge', ':lang' => 'ru', ':type' => 'project', ':status' => 'published']);

    // Ведущий слэш, дубль, проект с префиксом, черновик и несуществующий адрес.
    $values = ['contacts', '/contacts', 'projects/bridge', 'hidden', 'nothing-here'];

    Page::forgetMenuTargets();
    $batch = Page::publishedMenuTargets($values, 'ru');

    foreach ($values as $value) {
        Page::forgetMenuTargets();
        $one = Page::findPublishedMenuTarget($value, 'ru');
        $many = $batch[$value] ?? null;
        if ($one === null) {
            assert_same(null, $many, "адрес «{$value}» не разрешается ни там, ни там");
            continue;
        }
        assert_true(is_array($many), "адрес «{$value}» разрешился и пакетом");
        assert_same((int) $one['id'], (int) $many['id'], "адрес «{$value}» ведёт на ту же запись");
    }

    $pdo->exec('DELETE FROM pages');
    Page::forgetMenuTargets();
});

test('Цель пункта меню в пределах запроса спрашивается один раз (БД)', function () {
    ensure_test_db();
    $pdo = Database::pdo();
    $pdo->exec('DELETE FROM pages');
    $pdo->exec("INSERT INTO pages (title, slug, lang, entity_type, status, created_at, updated_at)
                VALUES ('Контакты', 'contacts', 'ru', 'page', 'published', NOW(), NOW())");

    // Шапка спрашивает одну и ту же цель дважды: разрешая дерево пунктов и
    // собирая ссылку каждого из них. Без памяти это давало 36 одинаковых
    // запросов на каждой странице сайта.
    Page::forgetMenuTargets();
    $first = Page::findPublishedMenuTarget('contacts', 'ru');
    assert_true(is_array($first), 'цель найдена');

    $pdo->exec('DELETE FROM pages');
    $cached = Page::findPublishedMenuTarget('contacts', 'ru');
    assert_true(is_array($cached), 'повторный вопрос отвечен из памяти, без запроса');

    // Запись страниц сбрасывает память: адреса изменились.
    Page::forgetMenuTargets();
    assert_same(null, Page::findPublishedMenuTarget('contacts', 'ru'), 'после сброса память пуста');
});
