<?php

declare(strict_types=1);

use App\Core\BlockRenderer;

// Поиск по схеме оргструктуры и готовые ссылки на состав сектора.

test('Поиск по схеме включается настройкой и приходит скрытым', function () {
    $on = BlockRenderer::render([
        'id' => 780,
        'type' => 'org_structure',
        'data' => json_encode([
            'head_title' => 'Директор',
            'search' => true,
            'branches' => [['title' => 'Заместитель', 'units' => "Департамент\n  Отдел"]],
        ], JSON_UNESCAPED_UNICODE),
        'custom_css' => '',
    ]);
    $html = (string) $on['html'];

    assert_contains('data-org-structure', $html);
    assert_contains('data-org-search', $html);
    // Без скрипта фильтровать нечем — поле открывает JS.
    assert_contains('hidden', $html);
    assert_contains('data-org-search-status', $html);

    $off = BlockRenderer::render([
        'id' => 781,
        'type' => 'org_structure',
        'data' => json_encode(['head_title' => 'Директор'], JSON_UNESCAPED_UNICODE),
        'custom_css' => '',
    ]);
    assert_not_contains('orgstruct-search', (string) $off['html']);
    assert_not_contains('data-org-structure', (string) $off['html']);
});

test('Скрипт схемы подключается по типу блока и ищет по собственному тексту узла', function () {
    $collector = (string) file_get_contents(APP_ROOT . '/app/Core/AssetCollector.php');
    assert_contains("'org_structure' => '/assets/js/blocks/org_structure.js'", $collector);

    $build = (string) file_get_contents(APP_ROOT . '/scripts/build-assets.mjs');
    assert_contains('public/assets/js/blocks/org_structure.js', $build, 'скрипт должен попадать в сборку');

    $js = (string) file_get_contents(APP_ROOT . '/public/assets/js/blocks/org_structure.js');
    // Текст родителя включает вложенные списки: без отсечения департамент
    // «совпадал» с каждым своим сектором.
    assert_contains('ownText', $js);
    assert_contains('is-match-path', $js);
});

test('Ссылки на состав сектора собираются по данным команды (БД)', function () {
    ensure_test_db();
    $pdo = \App\Core\Database::pdo();
    $suffix = uniqid();

    $pageId = \App\Models\Page::create([
        'title' => 'Команда', 'slug' => 'team-anchor-' . $suffix,
        'status' => 'published', 'lang' => 'ru',
    ]);
    $pdo->prepare('INSERT INTO blocks (page_id, type, data, sort_order, is_active, lang) VALUES (?,?,?,1,1,?)')
        ->execute([$pageId, 'team_list', json_encode(['group_by_department' => true], JSON_UNESCAPED_UNICODE), 'ru']);

    $memberId = \App\Models\TeamMember::create([
        'name' => 'Иванов И.', 'position' => 'Начальник',
        'department' => 'Сектор проверки ' . $suffix,
        'status' => 'published', 'lang' => 'ru',
    ]);

    $options = \App\Core\TeamAnchors::options();
    $found = null;
    foreach ($options as $option) {
        if (str_contains((string) $option['name'], $suffix)) {
            $found = $option;
            break;
        }
    }

    assert_true($found !== null, 'сектор сотрудника попадает в список');
    assert_same('/team-anchor-' . $suffix . '#team-' . $found['slug'], (string) $found['path']);

    $pdo->exec('DELETE FROM team_members WHERE id = ' . (int) $memberId);
    $pdo->exec('DELETE FROM blocks WHERE page_id = ' . (int) $pageId);
    $pdo->exec('DELETE FROM pages WHERE id = ' . (int) $pageId);
});
