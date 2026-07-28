<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\ContentEntry;
use App\Models\ContentType;
use App\Models\Project;

test('Проекты: публичные списки и блоки не смешивают независимые языковые версии', function () {
    ensure_test_db();
    $pdo = Database::pdo();
    $prefix = 'strict-project-' . bin2hex(random_bytes(4));

    $insert = $pdo->prepare(
        "INSERT INTO projects
            (title, slug, description, status, is_featured, lang, translation_group_id)
         VALUES (?, ?, ?, ?, 1, ?, ?)"
    );

    $insert->execute(['RU independent', $prefix . '-independent', 'RU body', 'published', 'ru', null]);
    $independentBase = (int) $pdo->lastInsertId();
    $pdo->prepare('UPDATE projects SET translation_group_id = ? WHERE id = ?')
        ->execute([$independentBase, $independentBase]);
    $insert->execute(['UZ independent', $prefix . '-independent', 'UZ body', 'published', 'uz', $independentBase]);
    $independentUz = (int) $pdo->lastInsertId();

    $insert->execute(['RU legacy', $prefix . '-legacy', 'Legacy RU body', 'published', 'ru', null]);
    $legacyBase = (int) $pdo->lastInsertId();
    $pdo->prepare('UPDATE projects SET translation_group_id = ? WHERE id = ?')
        ->execute([$legacyBase, $legacyBase]);
    \App\Models\ProjectTranslation::upsert($legacyBase, 'uz', [
        'title' => 'UZ legacy',
        'description' => '',
    ]);

    $insert->execute(['RU shadowed', $prefix . '-shadowed', 'Shadowed RU body', 'published', 'ru', null]);
    $shadowedBase = (int) $pdo->lastInsertId();
    $pdo->prepare('UPDATE projects SET translation_group_id = ? WHERE id = ?')
        ->execute([$shadowedBase, $shadowedBase]);
    \App\Models\ProjectTranslation::upsert($shadowedBase, 'uz', [
        'title' => 'Old UZ legacy',
        'description' => 'Old body',
    ]);
    $insert->execute(['UZ draft', $prefix . '-shadowed', 'Draft body', 'draft', 'uz', $shadowedBase]);
    $shadowDraft = (int) $pdo->lastInsertId();

    $uzRows = Project::published('uz');
    $uzById = [];
    foreach ($uzRows as $row) {
        $uzById[(int) $row['id']] = $row;
    }
    assert_same('UZ independent', $uzById[$independentUz]['title']);
    assert_true(!isset($uzById[$independentBase]), 'RU-версия независимого проекта не дублируется');
    assert_same('UZ legacy', $uzById[$legacyBase]['title']);
    assert_same('Legacy RU body', $uzById[$legacyBase]['description'], 'частичный legacy-перевод сохраняет фолбэк поля');
    assert_true(!isset($uzById[$shadowedBase]), 'черновик независимого перевода подавляет устаревший legacy-перевод');
    assert_true(!isset($uzById[$shadowDraft]), 'черновик не публикуется');

    $homeIds = array_map(static fn (array $row): int => (int) $row['id'], Project::forHome(24, 'uz'));
    assert_true(in_array($independentUz, $homeIds, true), 'языковая выборка работает и для проектного блока');
    assert_true(!in_array($independentBase, $homeIds, true), 'проектный блок не дублирует RU-версию');
    assert_true(!in_array($shadowedBase, $homeIds, true), 'проектный блок не показывает устаревший legacy-перевод');

    $found = Project::findPublishedBySlug($prefix . '-independent', 'uz');
    assert_same($independentUz, (int) ($found['id'] ?? 0));
    assert_true(in_array('uz', Project::availableLangs($independentBase), true));
    assert_true(!in_array('uz', Project::availableLangs($shadowedBase), true), 'черновик не делает язык доступным');

    $ids = implode(',', [$independentBase, $independentUz, $legacyBase, $shadowedBase, $shadowDraft]);
    $pdo->exec("DELETE FROM projects WHERE id IN ({$ids})");
});

test('Документы и вакансии: список, поиск, сортировка и счётчик используют текущий язык', function () {
    ensure_test_db();
    $pdo = Database::pdo();
    $suffix = bin2hex(random_bytes(4));
    $typeId = ContentType::create('strict-catalog-' . $suffix, 'Строгий каталог', true);
    ContentType::replaceFields($typeId, [[
        'name' => 'department',
        'label' => 'Отдел',
        'field_type' => 'text',
        'required' => false,
        'options' => [],
    ]]);

    $first = ContentEntry::create(
        $typeId,
        'Zeta RU',
        'zeta-' . $suffix,
        'published',
        ['department' => 'Русский отдел']
    );
    $second = ContentEntry::create(
        $typeId,
        'Alpha RU',
        'alpha-' . $suffix,
        'published',
        ['department' => 'Русский сектор']
    );
    $withoutTranslation = ContentEntry::create(
        $typeId,
        'Only RU',
        'only-ru-' . $suffix,
        'published',
        ['department' => 'Только русский']
    );
    ContentEntry::upsertTranslation($first, 'uz', 'Alfa UZ', ['department' => 'Raqamli bo‘lim']);
    ContentEntry::upsertTranslation($second, 'uz', 'Zebra UZ', ['department' => 'Moliya bo‘limi']);

    assert_same(3, ContentEntry::countTypePublic($typeId, '', 'ru'));
    assert_same(2, ContentEntry::countTypePublic($typeId, '', 'uz'));
    assert_same(1, ContentEntry::countTypePublic($typeId, 'Raqamli', 'uz'));

    $uzRows = ContentEntry::forTypePublic($typeId, '', 'title', 12, 0, 'uz');
    assert_same([$first, $second], array_map(static fn (array $row): int => (int) $row['id'], $uzRows));
    assert_same('Alfa UZ', $uzRows[0]['title']);
    $firstData = json_decode((string) $uzRows[0]['data'], true);
    assert_same('Raqamli bo‘lim', $firstData['department'] ?? null);
    assert_true(
        !in_array($withoutTranslation, array_column($uzRows, 'id'), false),
        'запись без перевода не попадает в UZ-каталог'
    );

    $searchRows = ContentEntry::forTypePublic($typeId, 'Moliya', 'new', 12, 0, 'uz');
    assert_same([$second], array_map(static fn (array $row): int => (int) $row['id'], $searchRows));
    assert_same(['ru', 'uz'], ContentEntry::availableLangs($first));
    assert_same(['ru'], ContentEntry::availableLangs($withoutTranslation));

    $pdo->prepare('DELETE FROM content_types WHERE id = ?')->execute([$typeId]);
});

test('Публичные шаблоны каталога переводят названия типов и подписи полей', function () {
    $root = dirname(__DIR__, 2);
    $index = (string) file_get_contents($root . '/app/Views/site/content_index.php');
    $list = (string) file_get_contents($root . '/app/Views/site/_catalog_list.php');
    $show = (string) file_get_contents($root . '/app/Views/site/content_show.php');

    assert_contains("t((string) \$type['name'])", $index);
    assert_contains("t((string) \$type['description'])", $index);
    assert_contains("t((string) \$f['label'])", $list);
    assert_contains("t((string) \$f['label'])", $show);
});
