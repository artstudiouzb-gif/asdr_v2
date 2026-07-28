<?php

declare(strict_types=1);

use App\Models\Page;
use App\Models\News;
use App\Models\Project;
use App\Controllers\Admin\TrashController;

test('Корзина: очистка всех удалённых элементов (emptyAll) удаляет страницы, новости и проекты', function (): void {
    ensure_test_db();

    $uid = uniqid();

    // Создаём и мягко удаляем страницу
    $pageId = Page::create([
        'title' => 'Мусорная страница',
        'slug' => 'trash-page-test-' . $uid,
        'meta_title' => null,
        'meta_description' => null,
        'status' => 'draft',
        'lang' => 'ru',
    ]);
    Page::delete($pageId);

    // Создаём и мягко удаляем новость
    $newsId = News::create([
        'title' => 'Мусорная новость',
        'slug' => 'trash-news-test-' . $uid,
        'excerpt' => 'Описание',
        'content' => 'Контент',
        'status' => 'draft',
        'lang' => 'ru',
    ]);
    News::delete($newsId);

    // Создаём и мягко удаляем проект
    $projId = Project::create([
        'title' => 'Мусорный проект',
        'slug' => 'trash-project-test-' . $uid,
        'description' => 'Описание',
        'cover_image' => '',
        'status' => 'draft',
        'lang' => 'ru',
    ]);
    Project::delete($projId);

    // Проверяем, что элементы есть в корзине
    assert_true(count(Page::trashed()) > 0, 'В корзине есть страницы');
    assert_true(count(News::trashed()) > 0, 'В корзине есть новости');
    assert_true(count(Project::trashed()) > 0, 'В корзине есть проекты');

    // Симулируем вызов emptyAll
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['csrf_token'] = 'test_token';
    $_POST['csrf_token'] = 'test_token';

    $controller = new TrashController();

    // Перехватываем редирект
    try {
        $controller->emptyAll();
    } catch (\Throwable) {}

    // Проверяем, что корзина пуста
    assert_same(0, count(Page::trashed()), 'Корзина страниц очищена');
    assert_same(0, count(News::trashed()), 'Корзина новостей очищена');
    assert_same(0, count(Project::trashed()), 'Корзина проектов очищена');
});
