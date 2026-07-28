<?php

declare(strict_types=1);

use App\Core\SecurityHeaders;

// CSP публичной части и админки (roadmap v2, раздел «Безопасность»):
// nonce вместо 'unsafe-inline' для скриптов, условные хосты, HSTS preload.

test('SecurityHeaders::nonce: стабилен в запросе, URL-safe base64', function () {
    $n1 = SecurityHeaders::nonce();
    $n2 = SecurityHeaders::nonce();
    assert_same($n1, $n2, 'один nonce на запрос');
    assert_true(strlen($n1) >= 20, 'достаточная длина');
    assert_true((bool) preg_match('/^[A-Za-z0-9_-]+$/', $n1), 'без спецсимволов');
});

test('adminCsp: без внешних CDN (TinyMCE самохостится), unsafe-inline для скриптов отключён', function () {
    $csp = SecurityHeaders::adminCsp('testnonce');
    assert_contains("script-src 'self' 'nonce-testnonce';", $csp);
    assert_contains("style-src 'self' 'unsafe-inline';", $csp);
    assert_true(!str_contains($csp, 'jsdelivr'), 'внешний CDN убран из CSP');
    assert_true(!str_contains($csp, "script-src 'self' 'unsafe-inline'"), 'unsafe-inline для скриптов убран');
    assert_contains("object-src 'none'", $csp);
    assert_contains("frame-ancestors 'self'", $csp);
});

test('publicCsp: базовая политика разрешает только API YouTube', function () {
    $csp = SecurityHeaders::publicCsp('n0nce', []);
    assert_contains("script-src 'self' 'nonce-n0nce' https://www.youtube.com; ", $csp);
    assert_contains('https://www.youtube.com', $csp, 'разрешён только официальный IFrame API для своего финального экрана');
    assert_true(!str_contains($csp, 'googletagmanager'), 'GA-хостов нет без настройки');
    assert_true(!str_contains($csp, 'fonts.googleapis.com'), 'шрифтовых хостов нет без настройки');
    assert_contains("worker-src 'self'", $csp);
    assert_contains("form-action 'self'", $csp);
});

test('TinyMCE: расширенная панель содержит цитату и индексы', function () {
    $editor = (string) file_get_contents(APP_ROOT . '/public/assets/js/vendor/editor.js');
    assert_contains('subscript superscript | blockquote', $editor);
});

test('publicCsp: разрешает только известный источник скрипта счётчика', function () {
    $csp = SecurityHeaders::publicCsp('counter', [
        'counter_scripts' => ['https://mc.yandex.ru', 'https://example.test'],
    ]);
    assert_contains("script-src 'self' 'nonce-counter' https://www.youtube.com https://mc.yandex.ru", $csp);
    assert_not_contains('example.test', $csp);
});

test('publicCsp: хосты добавляются по включённым настройкам', function () {
    $csp = SecurityHeaders::publicCsp('x', ['google_fonts' => true, 'ga' => true, 'ym' => true]);
    assert_contains('https://fonts.googleapis.com', $csp);
    assert_contains('https://fonts.gstatic.com', $csp);
    assert_contains('https://www.googletagmanager.com', $csp);
    assert_contains('https://mc.yandex.ru', $csp);
    assert_contains("script-src 'self' 'nonce-x' https://www.youtube.com https://www.googletagmanager.com https://mc.yandex.ru", $csp);
});

test('injectScriptNonce: добавляет nonce только тегам без него', function () {
    $html = '<p>текст</p><script>var a=1;</script>'
        . '<script nonce="уже">var b=2;</script>'
        . '<SCRIPT src="/x.js"></SCRIPT>';
    $out = SecurityHeaders::injectScriptNonce($html, 'abc');
    assert_contains('<script nonce="abc">var a=1;</script>', $out);
    assert_contains('<script nonce="уже">var b=2;</script>', $out);
    // Замена нормализует регистр открывающего тега — важен сам nonce.
    assert_contains('<script nonce="abc" src="/x.js">', $out);
    assert_contains('<script nonce="abc" src="/counter.js">', SecurityHeaders::injectScriptNonce('<SCRIPT src="/counter.js"></SCRIPT>', 'abc'));
    assert_same('<p>без скриптов</p>', SecurityHeaders::injectScriptNonce('<p>без скриптов</p>', 'abc'));
});

test('hstsValue: preload по опции', function () {
    assert_same('max-age=31536000; includeSubDomains', SecurityHeaders::hstsValue(false));
    assert_same('max-age=63072000; includeSubDomains; preload', SecurityHeaders::hstsValue(true));
});
