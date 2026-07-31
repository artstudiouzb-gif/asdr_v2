<?php

declare(strict_types=1);

use App\Models\TeamMember;

/**
 * Распределение команды по подразделениям: сектор — верхний уровень и якорь
 * ссылки из схемы оргструктуры, отдел или группа — уровень внутри сектора.
 */

test('Команда: якорь сектора не зависит от языка страницы', function () {
    $ru = ['department' => 'Сектор анализа и исследований'];
    // Так строку отдаёт localize(): перевод наложен, базовое название сохранено.
    $uz = [
        'department' => 'Tahlil va tadqiqotlar shoʻbasi',
        'department_base' => 'Сектор анализа и исследований',
    ];

    assert_same('sektor-analiza-i-issledovaniy', TeamMember::departmentSlug($ru));
    assert_same(TeamMember::departmentSlug($ru), TeamMember::departmentSlug($uz));
    assert_same('', TeamMember::departmentSlug(['department' => '']));
});

test('Команда: группировка по секторам и отделам внутри них', function () {
    $rows = [
        ['name' => 'Директор', 'department' => '', 'unit' => ''],
        ['name' => 'Руководитель сектора', 'department' => 'Сектор анализа и исследований', 'unit' => ''],
        ['name' => 'Кадровик', 'department' => 'Информационно-аналитический сектор', 'unit' => 'группа по работе с кадрами'],
        ['name' => 'Специалист', 'department' => 'Информационно-аналитический сектор', 'unit' => 'первый отдел'],
        ['name' => 'Начальник', 'department' => 'Информационно-аналитический сектор', 'unit' => ''],
    ];

    $groups = TeamMember::groupByDepartment($rows);

    assert_same(3, count($groups));
    assert_same('Сектор анализа и исследований', $groups[0]['name']);
    assert_same('sektor-analiza-i-issledovaniy', $groups[0]['slug']);
    assert_same([], $groups[0]['units']);

    // Внутри сектора: сначала сотрудники без отдела, затем отделы и группы.
    assert_same('Информационно-аналитический сектор', $groups[1]['name']);
    assert_same(1, count($groups[1]['members']));
    assert_same('Начальник', $groups[1]['members'][0]['name']);
    assert_same(2, count($groups[1]['units']));
    assert_same('группа по работе с кадрами', $groups[1]['units'][0]['name']);
    assert_same('первый отдел', $groups[1]['units'][1]['name']);

    // Сотрудник без сектора не теряется: последняя группа без названия.
    assert_same('', $groups[2]['name']);
    assert_same('Директор', $groups[2]['members'][0]['name']);
});

test('Команда: без секторов группировка возвращает пустой список', function () {
    assert_same([], TeamMember::groupByDepartment([]));

    $groups = TeamMember::groupByDepartment([['name' => 'Иванов', 'department' => '', 'unit' => '']]);
    assert_same(1, count($groups));
    assert_same('', $groups[0]['slug']);
});

test('Команда: блок выводит группы с якорями и подзаголовками отделов', function () {
    // Блок обогащает данные из БД, поэтому рендер требует тестовую базу.
    ensure_test_db();
    $rendered = \App\Core\BlockRenderer::render([
        'id' => 880,
        'type' => 'team_list',
        'custom_css' => '',
        'data' => json_encode([
            'title' => 'Команда',
            'members' => [
                ['name' => 'Каримов Б.', 'position' => 'Руководитель сектора'],
                ['name' => 'Ражабов О.', 'position' => 'Главный специалист'],
            ],
            'groups' => [
                [
                    'name' => 'Сектор анализа и исследований',
                    'slug' => 'sektor-analiza-i-issledovaniy',
                    'members' => [['name' => 'Каримов Б.', 'position' => 'Руководитель сектора']],
                    'units' => [
                        ['name' => 'группа по работе с кадрами', 'members' => [['name' => 'Ражабов О.', 'position' => 'Главный специалист']]],
                    ],
                ],
            ],
        ]),
    ]);

    assert_contains('id="team-sektor-analiza-i-issledovaniy"', $rendered['html']);
    assert_contains('block-team__group-title', $rendered['html']);
    assert_contains('группа по работе с кадрами', $rendered['html']);
    assert_contains('Ражабов О.', $rendered['html']);
});

test('Команда: сектор и отдел сохраняются формой и переводами', function () {
    $controller = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Controllers/Admin/TeamController.php');
    assert_contains("'department' => \$department !== '' ? \$department : null", $controller);
    assert_contains("'unit' => \$unit !== '' ? \$unit : null", $controller);
    assert_contains("'department' => trim((string) (\$t['department'] ?? ''))", $controller);
    assert_contains("'unit' => trim((string) (\$t['unit'] ?? ''))", $controller);

    $translation = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Models/TeamMemberTranslation.php');
    assert_contains('department = VALUES(department), unit = VALUES(unit)', $translation);
});
