<?php

declare(strict_types=1);

use App\Core\Database;
use App\Models\News;
use App\Controllers\Site\NewsController;

test('News: sidebar_layout saves, updates and defaults to right_sidebar', function () {
    ensure_test_db();
    $pdo = Database::pdo();

    // 1. Создаём новость без явного указания sidebar_layout
    $slug = 'test-news-sidebar-' . bin2hex(random_bytes(3));
    $nid = News::create([
        'title' => 'Тестовая новость',
        'slug' => $slug,
        'excerpt' => 'Описание',
        'content' => 'Текст',
        'image' => '',
        'status' => 'draft',
        'published_at' => date('Y-m-d H:i:s'),
        'author_id' => null,
    ]);

    // По умолчанию должно быть right_sidebar
    $news = News::findById($nid);
    assert_same('right_sidebar', $news['sidebar_layout']);

    // 2. Обновляем sidebar_layout на left_sidebar
    $data = [
        'title' => 'Тестовая новость 2',
        'slug' => $slug,
        'excerpt' => 'Описание 2',
        'content' => 'Текст 2',
        'image' => '',
        'status' => 'draft',
        'published_at' => date('Y-m-d H:i:s'),
        'sidebar_layout' => 'left_sidebar',
    ];
    News::update($nid, $data);

    $news = News::findById($nid);
    assert_same('left_sidebar', $news['sidebar_layout']);

    // 3. Добавляем audio_url, audio_title и hashtags
    $data['audio_url'] = '/uploads/public/test-podcast.mp3';
    $data['audio_title'] = 'Аудиоверсия выпуска';
    $data['hashtags'] = 'культура, #ташкент, события';
    News::update($nid, $data);

    $news = News::findById($nid);
    assert_same('/uploads/public/test-podcast.mp3', $news['audio_url']);
    assert_same('Аудиоверсия выпуска', $news['audio_title']);
    assert_same('#культура #ташкент #события', $news['hashtags']);

    // 4. Обновляем sidebar_layout на no_sidebar
    $data['sidebar_layout'] = 'no_sidebar';
    News::update($nid, $data);

    $news = News::findById($nid);
    assert_same('no_sidebar', $news['sidebar_layout']);

    // Очистка
    $pdo->exec("DELETE FROM news WHERE id = {$nid}");
});

test('News: frontend layout rendering with and without sidebar', function () {
    ensure_test_db();
    $pdo = Database::pdo();

    // Создаём активный виджет в правом сайдбаре, чтобы renderSidebar вернул непустую строку
    $wid = \App\Models\Widget::create([
        'sidebar' => 'right',
        'type' => 'contacts',
        'title' => 'Контакты',
        'lang' => '',
        'data' => [],
        'is_active' => 1,
    ]);

    // Создаём опубликованную новость с тезисами
    $slug = 'test-news-layout-' . bin2hex(random_bytes(3));
    $nid = News::create([
        'title' => 'Супер новость',
        'slug' => $slug,
        'excerpt' => 'Лид новости',
        'content' => 'Основной текст',
        'image' => '',
        'status' => 'published',
        'published_at' => date('Y-m-d H:i:s'),
        'author_id' => null,
    ]);

    // Добавляем тезисы (key_points)
    News::updateExtras($nid, [
        'badge' => 'Важное',
        'key_points' => "Первый важный тезис\nВторой важный тезис",
    ]);

    // 1. Тест рендеринга с правым сайдбаром (по умолчанию)
    // Убеждаемся, что при включенном сайдбаре тезисы рендерятся инлайн внутри статьи,
    // а страница имеет разметку .layout--right
    $controller = new NewsController();
    
    ob_start();
    try {
        $controller->show(['slug' => $slug]);
    } catch (\Throwable $e) {
        ob_get_clean();
        throw $e;
    }
    $html = ob_get_clean();

    assert_contains('layout layout--right', $html, 'Контейнер разметки с правым сайдбаром на месте');
    assert_contains('newsdetail-card--thesis-inline', $html, 'Тезисы отображаются инлайново при наличии сайдбара');
    assert_contains('Первый важный тезис', $html);

    // 2. Тест рендеринга без сайдбара
    // Устанавливаем макет no_sidebar
    News::update($nid, [
        'title' => 'Супер новость',
        'slug' => $slug,
        'excerpt' => 'Лид новости',
        'content' => 'Основной текст',
        'image' => '',
        'status' => 'published',
        'published_at' => date('Y-m-d H:i:s'),
        'sidebar_layout' => 'no_sidebar',
    ]);

    ob_start();
    try {
        $controller->show(['slug' => $slug]);
    } catch (\Throwable $e) {
        ob_get_clean();
        throw $e;
    }
    $htmlNoSidebar = ob_get_clean();

    assert_not_contains('layout layout--', $htmlNoSidebar, 'Контейнер сайдбара отсутствует при no_sidebar');
    assert_not_contains('newsdetail-card--thesis-inline', $htmlNoSidebar, 'Тезисы выводятся в сайдбаре новости, а не инлайново');
    assert_contains('newsdetail-side', $htmlNoSidebar, 'Тезисы выводятся в отдельной колонке newsdetail-side');

    // Очистка
    $pdo->exec("DELETE FROM news WHERE id = {$nid}");
    $pdo->exec("DELETE FROM widgets WHERE id = {$wid}");
});
