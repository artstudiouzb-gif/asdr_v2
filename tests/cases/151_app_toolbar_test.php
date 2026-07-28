<?php

declare(strict_types=1);

use App\Core\AppToolbar;

function establish_toolbar_test_session(string $username = 'editor_user', string $role = 'editor'): void
{
    $_SERVER['HTTP_USER_AGENT'] = 'asdr-toolbar-test';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SESSION = [
        'user_id' => 1,
        'username' => $username,
        'role' => $role,
        'fingerprint' => hash('sha256', 'asdr-toolbar-test|127.0'),
    ];
}

test('Admin Topbar: для гостя выводит только скрипт предзагрузки', function (): void {
    $_SESSION = [];

    $html = AppToolbar::renderHtml([]);
    assert_false(str_contains($html, 'id="app-admin-bar"'), 'Бар администратора скрыт для гостей');
    assert_true(str_contains($html, 'prefetch'), 'Скрипт предзагрузки присутствует');
});

test('Admin Topbar: для авторизованного администратора на странице выводит прямую ссылку редактирования страницы', function (): void {
    establish_toolbar_test_session();

    $context = [
        'page' => ['id' => 42, 'title' => 'Тестовая страница'],
    ];

    $html = AppToolbar::renderHtml($context);
    assert_true(str_contains($html, 'id="app-admin-bar"'), 'Панель администратора отображается');
    assert_true(str_contains($html, '/admin/pages/42/edit'), 'Содержит прямую ссылку на редактирование страницы 42');
    assert_true(str_contains($html, 'editor_user'), 'Содержит имя авторизованного пользователя');
});

test('Admin Topbar: на новости выводит прямую ссылку редактирования новости', function (): void {
    establish_toolbar_test_session();

    $context = [
        'news' => ['id' => 108, 'title' => 'Тестовая новость'],
    ];

    $html = AppToolbar::renderHtml($context);
    assert_true(str_contains($html, '/admin/news/108/edit'), 'Содержит прямую ссылку на редактирование новости 108');
});
