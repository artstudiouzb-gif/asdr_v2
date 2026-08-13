<?php

declare(strict_types=1);

use App\Core\SectionPage;
use App\Models\Page;

// Страница-раздел: обычная страница отдаёт лентам `/news` и `/projects`
// заголовок, лид, SEO и блоки под списком. Раньше эти строки жили в шаблонах
// и в админке не редактировались.

test('Страница-раздел: одна на язык, находится по разделу (БД)', function () {
    ensure_test_db();
    $pdo = \App\Core\Database::pdo();
    $suffix = uniqid();

    $first = Page::create([
        'title' => 'Пресс-центр', 'slug' => 'sec-a-' . $suffix, 'section' => 'news',
        'lead' => 'Официальные сообщения', 'status' => 'published', 'lang' => 'ru',
    ]);
    assert_same('Пресс-центр', (string) Page::forSection('news', 'ru')['title']);

    // Вторая страница того же раздела забирает роль себе: иначе на сайте
    // выигрывала бы случайная запись, а редактор об этом не узнал бы.
    $second = Page::create([
        'title' => 'Новости и аналитика', 'slug' => 'sec-b-' . $suffix, 'section' => 'news',
        'status' => 'published', 'lang' => 'ru',
    ]);
    assert_same('Новости и аналитика', (string) Page::forSection('news', 'ru')['title']);
    assert_same('', (string) Page::findById($first)['section'], 'у прежней страницы роль снята');

    // Черновик разделом не управляет: на сайте его нет.
    Page::update($second, [
        'title' => 'Новости и аналитика', 'slug' => 'sec-b-' . $suffix, 'section' => 'news',
        'status' => 'draft', 'lang' => 'ru',
    ]);
    assert_true(Page::forSection('news', 'ru') === null, 'черновик разделом не управляет');

    // Чужой ключ раздела — обычная страница.
    Page::update($second, [
        'title' => 'Новости и аналитика', 'slug' => 'sec-b-' . $suffix, 'section' => 'мусор',
        'status' => 'published', 'lang' => 'ru',
    ]);
    assert_same('', (string) Page::findById($second)['section']);
    assert_true(Page::forSection('нет-такого', 'ru') === null);

    $pdo->exec('DELETE FROM pages WHERE id IN (' . (int) $first . ',' . (int) $second . ')');
});

test('Страница-раздел отдаёт заголовок, SEO и блоки под списком (БД)', function () {
    ensure_test_db();
    $pdo = \App\Core\Database::pdo();
    $suffix = uniqid();

    $id = Page::create([
        'title' => 'Проекты Агентства', 'slug' => 'sec-p-' . $suffix, 'section' => 'projects',
        'lead' => 'Что мы делаем', 'meta_description' => 'Описание для поиска',
        'status' => 'published', 'lang' => 'ru',
    ]);
    $pdo->prepare('INSERT INTO blocks (page_id, type, data, sort_order, is_active, lang) VALUES (?,?,?,1,1,?)')
        ->execute([$id, 'text', json_encode(['content' => 'Текст под списком'], JSON_UNESCAPED_UNICODE), 'ru']);

    $section = SectionPage::render('projects', 'ru');
    assert_same('Проекты Агентства', $section['title']);
    assert_same('Что мы делаем', $section['lead']);
    assert_same('Описание для поиска', $section['meta_description']);
    assert_contains('Текст под списком', $section['content']);

    // Раздела нет — вью остаётся на строках из шаблона.
    assert_true(SectionPage::render('news', 'ru') === null);

    // Вьюхи выводят блоки под списком, а не над ним.
    foreach (['news_index.php', 'projects_index.php'] as $file) {
        $view = (string) file_get_contents(APP_ROOT . '/app/Views/site/' . $file);
        assert_contains('listing__section-blocks', $view);
        assert_true(
            strpos($view, 'listing__section-blocks') > strpos($view, 'listing__title'),
            $file . ': блоки раздела должны идти после шапки и списка'
        );
    }

    $pdo->exec('DELETE FROM blocks WHERE page_id = ' . (int) $id);
    $pdo->exec('DELETE FROM pages WHERE id = ' . (int) $id);
});
