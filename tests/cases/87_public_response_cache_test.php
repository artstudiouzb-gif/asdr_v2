<?php

declare(strict_types=1);

use App\Core\PublicResponseCache;

test('Публичные GET и HEAD без сессии можно кешировать', function () {
    assert_true(PublicResponseCache::isCacheableRequest('/about', 'GET', false, 200));
    assert_true(PublicResponseCache::isCacheableRequest('/uz/news', 'HEAD', false, 200));
});

test('Сессии, авторизация, ошибки и изменяющие запросы не кешируются', function () {
    assert_false(PublicResponseCache::isCacheableRequest('/about', 'GET', true, 200));
    assert_false(PublicResponseCache::isCacheableRequest('/about', 'GET', false, 200, true));
    assert_false(PublicResponseCache::isCacheableRequest('/about', 'POST', false, 200));
    assert_false(PublicResponseCache::isCacheableRequest('/missing', 'GET', false, 404));
});

test('Корни языков не кешируются: на них ещё работает редирект по cookie', function () {
    $langs = ['ru', 'uz', 'en'];
    foreach (['/', '/uz', '/uz/', '/en'] as $path) {
        assert_false(
            PublicResponseCache::isCacheableRequest($path, 'GET', false, 200, false, $langs),
            $path . ' — корень языка, ответ зависит от cookie'
        );
    }

    // Страница со slug из двух-восьми букв корнем не считается, иначе
    // «/news» и «/contacts» потеряли бы кеширование.
    foreach (['/news', '/contacts', '/uz/news'] as $path) {
        assert_true(
            PublicResponseCache::isCacheableRequest($path, 'GET', false, 200, false, $langs),
            $path . ' — обычная страница, кешируется'
        );
    }
});

test('Чувствительные публичные и служебные маршруты исключены из кеша', function () {
    foreach (['/admin', '/repo/login', '/search', '/uz/search', '/captcha.png', '/push/key', '/unsubscribe', '/health'] as $path) {
        assert_false(PublicResponseCache::isCacheableRequest($path, 'GET', false, 200), $path);
    }
});

test('Изменения публичного контента регистрируют общий сброс кеша', function () {
    $source = (string) file_get_contents(APP_ROOT . '/app/Core/PublicResponseCache.php');
    assert_contains("'/admin/news'", $source);
    assert_contains("'/admin/pages'", $source);
    assert_contains("'/admin/menu'", $source);
    assert_contains("Cache::forgetPrefix('page:')", $source);
});

test('По cookie в кеше разделяются только узбекские страницы (кириллица)', function () {
    $source = (string) file_get_contents(APP_ROOT . '/app/Core/PublicResponseCache.php');
    // Узбекская кириллица получается транслитерацией готовой страницы на
    // сервере, поэтому такой ответ зависит от cookie. Для остальных языков
    // Cookie в Vary не добавляется — иначе общий кеш дробится без нужды.
    assert_contains("Locale::current() === 'uz'", $source);
    assert_contains("'Vary: Accept-Encoding'", $source);
    assert_not_contains("header('Vary: Accept-Encoding, Cookie')", $source);
});

test('Настройки отображения переставляются до первой отрисовки, а не сервером одним', function () {
    // Общий ответ может прийти из CDN без data-a11y-*. Синхронный скрипт в
    // <head> обязан поправить атрибуты до первой отрисовки, иначе посетитель
    // с крупным текстом увидит вспышку обычного размера.
    $themeInit = (string) file_get_contents(APP_ROOT . '/public/assets/js/theme-init.js');
    assert_contains('a11y=', $themeInit);
    assert_contains("setAttribute('data-a11y-'", $themeInit);
    // Письменность остаётся серверной: её клиент повторить не может, поэтому
    // ключа script в наборе умолчаний скрипта быть не должно.
    assert_not_contains('script:', $themeInit);
});

test('Анонимному посетителю публичка не заводит сессию', function (): void {
    $auth = (string) file_get_contents(APP_ROOT . '/app/Core/Auth.php');

    // Шапка на каждом рендере зовёт AppToolbar::isVisible() → sessionUser()
    // → check(). Пока там стоял безусловный Session::start(), каждый аноним
    // получал Set-Cookie, а активная сессия делает ответ некэшируемым — общий
    // HTTP-кэш публички не работал вообще ни на одной странице.
    assert_true(
        preg_match('/function check\(\).*?Session::hasCookie\(\).*?Session::start\(\)/s', $auth) === 1,
        'Auth::check() обязан проверить наличие сессии до её запуска'
    );

    $toolbar = (string) file_get_contents(APP_ROOT . '/app/Core/AppToolbar.php');
    assert_contains('Auth::sessionUser()', $toolbar, 'бар администратора спрашивает сессию');
});
