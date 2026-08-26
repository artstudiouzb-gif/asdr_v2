<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\WidgetRenderer;
use App\Models\Goal;

/*
 * «Цели» (Maqsadlar) — тип контента без текста наружу: цель это набор снимков,
 * и на сайте она видна только каруселью в виджете. Записей сотни, поэтому у
 * списка админки есть поиск и постраничность, а у виджета — источник
 * «случайная цель» вместо собственного набора кадров.
 *
 * Главная тонкость — кэш: страница кэшируется общим ключом, и цель, выбранная
 * при её сборке, была бы одной и той же для всех посетителей до сброса кэша.
 * Поэтому свежую цель отдаёт отдельный некэшируемый адрес /goals/random.
 */

test('Цель: снимки переписываются целиком, опасный адрес не сохраняется (БД)', function () {
    ensure_test_db();
    Database::pdo()->exec('DELETE FROM goals');

    $id = Goal::create('Транспорт', true);
    Goal::replaceImages($id, [
        ['image' => '/uploads/public/a.jpg', 'alt' => 'Первый кадр'],
        ['image' => 'javascript:alert(1)', 'alt' => 'Опасный'],
        ['image' => '', 'alt' => 'Удалённая строка формы'],
        ['image' => '/uploads/public/b.jpg', 'alt' => ''],
    ]);

    $images = Goal::images($id);
    assert_same(2, count($images), 'опасный адрес и пустая строка не должны сохраняться');
    assert_same('/uploads/public/a.jpg', $images[0]['image']);
    assert_same(0, (int) $images[0]['sort_order'], 'порядок пересчитывается подряд, без дыр');
    assert_same(1, (int) $images[1]['sort_order']);

    // Повторное сохранение заменяет набор, а не дописывает его.
    Goal::replaceImages($id, [['image' => '/uploads/public/c.jpg', 'alt' => '']]);
    assert_same(1, count(Goal::images($id)), 'снимки должны переписываться целиком');

    // Снимки уходят вместе с целью: строки без владельца никому не видны.
    Goal::delete($id);
    $left = Database::pdo()->prepare('SELECT COUNT(*) FROM goal_images WHERE goal_id = ?');
    $left->execute([$id]);
    assert_same(0, (int) $left->fetchColumn(), 'снимки удалённой цели остались в базе');
});

test('Случайная цель: только включённые и только со снимками (БД)', function () {
    ensure_test_db();
    Database::pdo()->exec('DELETE FROM goals');

    $withImages = Goal::create('Со снимками', true);
    Goal::replaceImages($withImages, [['image' => '/uploads/public/a.jpg', 'alt' => '']]);

    // Пустая карусель хуже, чем соседняя цель, поэтому цель без снимков
    // пропускается; выключенная не выбирается вовсе.
    Goal::create('Без снимков', true);
    $off = Goal::create('Выключенная', false);
    Goal::replaceImages($off, [['image' => '/uploads/public/b.jpg', 'alt' => '']]);

    for ($i = 0; $i < 12; $i++) {
        $random = Goal::random();
        assert_true($random !== null, 'случайная цель не нашлась');
        assert_same('Со снимками', (string) $random['goal']['name'], 'выбрана цель, которой не должно быть в выборке');
    }

    // Ни одной годной цели — виджет обязан честно ответить «нет», а не упасть.
    Database::pdo()->exec('DELETE FROM goals');
    assert_same(null, Goal::random());
});

test('Список админки: поиск и постраничность считают одно и то же (БД)', function () {
    ensure_test_db();
    Database::pdo()->exec('DELETE FROM goals');

    for ($i = 1; $i <= 25; $i++) {
        $id = Goal::create($i % 5 === 0 ? "Транспорт {$i}" : "Цель {$i}", true);
        Goal::replaceImages($id, [['image' => '/uploads/public/a.jpg', 'alt' => '']]);
    }

    assert_same(25, Goal::countAll(''));
    assert_same(5, Goal::countAll('Транспорт'), 'счётчик обязан учитывать поиск');
    assert_same(5, count(Goal::page(20, 0, 'Транспорт')), 'страница обязана учитывать поиск');

    assert_same(20, count(Goal::page(20, 0, '')));
    assert_same(5, count(Goal::page(20, 20, '')), 'вторая страница списка');

    // Число снимков приезжает вместе со строкой: отдельный запрос на каждую
    // цель дал бы N+1 на странице из полусотни записей.
    $rows = Goal::page(5, 0, '');
    assert_true(array_key_exists('image_count', $rows[0]), 'в списке нет числа снимков');
    assert_same(1, (int) $rows[0]['image_count']);
});

test('Виджет: источник «случайная цель» отдаёт кадры цели и просит свежую (БД)', function () {
    ensure_test_db();
    Database::pdo()->exec('DELETE FROM goals');
    $id = Goal::create('Единственная', true);
    Goal::replaceImages($id, [
        ['image' => '/uploads/public/one.jpg', 'alt' => 'Кадр один'],
        ['image' => '/uploads/public/two.jpg', 'alt' => 'Кадр два'],
    ]);

    $html = WidgetRenderer::render([
        'id' => 5,
        'type' => 'photo_slider',
        'title' => '',
        'data' => json_encode(['source' => 'goals', 'shuffle' => true], JSON_UNESCAPED_UNICODE),
    ], 'ru');

    assert_contains('/uploads/public/one.jpg', $html, 'кадры цели не доехали до виджета');
    assert_contains('data-goal-slider', $html, 'без признака скрипт не запросит свежую цель');

    // Порядок кадров внутри цели авторский: случайной бывает сама цель, а не
    // история, которую рассказывают её слайды.
    assert_true(
        strpos($html, 'data-slider-shuffle') === false,
        'кадры внутри цели перемешиваться не должны, даже когда галочка включена'
    );

    // Имя цели служебное — наружу оно не выходит.
    assert_true(strpos($html, 'Единственная') === false, 'служебное имя цели попало в разметку');
});

test('Случайность переживает кэш: цель выбирается на своём адресе, а не при сборке', function () {
    // Цель, выбранная при сборке страницы, уехала бы в кэш и стала общей для
    // всех посетителей — «случайной» она была бы ровно один раз.
    $routes = (string) file_get_contents(APP_ROOT . '/public/index.php');
    assert_contains("'/goals/random'", $routes, 'нет адреса, отдающего свежую цель');

    $controller = (string) file_get_contents(APP_ROOT . '/app/Controllers/Site/GoalController.php');
    assert_contains('no-store', $controller, 'ответ со случайной целью обязан быть некэшируемым');
    assert_contains('X-Robots-Tag', $controller, 'служебный фрагмент не должен попадать в индекс');

    $js = (string) file_get_contents(APP_ROOT . '/public/assets/js/blocks/slider.js');
    assert_contains('/goals/random', $js, 'скрипт не запрашивает свежую цель');
    assert_contains('data-goal-slider', $js, 'скрипт не узнаёт карусель целей');

    // Публичной страницы у цели нет: адреса вида /goals/{slug} быть не должно.
    assert_true(
        strpos($routes, "'/goals/{") === false,
        'у цели появился публичный адрес — её содержимое задумано только для виджета'
    );
});

test('Раздел «Цели» подключён в админке целиком', function () {
    // Раздел без строки в карте иконок молча остаётся без значка, а без пункта
    // меню до него нельзя дойти — оба случая тихие.
    assert_true(\App\Core\AdminUi::navigationIcon('goals') !== '', 'у раздела нет иконки');

    $header = (string) file_get_contents(APP_ROOT . '/app/Views/admin/layout/header.php');
    assert_contains("'goals' => ['/admin/goals'", $header, 'раздела нет в меню админки');

    $routes = (string) file_get_contents(APP_ROOT . '/public/index.php');
    foreach (['/admin/goals', '/admin/goals/create', '/admin/goals/{id}/edit', '/admin/goals/{id}/delete'] as $route) {
        assert_contains("'" . $route . "'", $routes, 'нет маршрута ' . $route);
    }

    foreach (['index', 'form'] as $view) {
        assert_true(
            is_file(APP_ROOT . '/app/Views/admin/goals/' . $view . '.php'),
            'нет вьюхи админки: ' . $view
        );
    }
});
