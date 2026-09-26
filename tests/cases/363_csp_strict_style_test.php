<?php

declare(strict_types=1);

use App\Controllers\Site\CspReportController;
use App\Core\SecurityHeaders;

/*
 * Строгий style-src публичной части: атрибута style в разметке больше нет
 * (StyleVars, т. 362), теги <style> получают nonce. Пока config
 * security.csp_strict_style выключен, строгая политика идёт заголовком
 * Report-Only, а нарушения копятся в журнале через POST /_csp-report.
 */
test('CSP: строгий style-src без unsafe-inline и с nonce', function (): void {
    $relaxed = SecurityHeaders::publicCsp('abc123');
    $strict = SecurityHeaders::publicCsp('abc123', ['strict_style' => true]);

    assert_contains("style-src 'self' 'unsafe-inline'", $relaxed, 'обычная политика не меняется');
    assert_contains("style-src 'self' 'nonce-abc123'", $strict);
    assert_not_contains("'unsafe-inline'", $strict, 'строгая политика не допускает инлайн-стилей');
});

test('CSP: теги <style> получают nonce запроса, устаревший заменяется', function (): void {
    $html = '<style>.a{}</style><p>x</p><style nonce="old">.b{}</style><STYLE media="print">.c{}</STYLE>';
    $out = SecurityHeaders::injectStyleNonce($html, 'fresh');

    assert_same(3, substr_count($out, 'nonce="fresh"'), 'каждый тег с nonce запроса');
    assert_not_contains('nonce="old"', $out);
    assert_same('<p>без стилей</p>', SecurityHeaders::injectStyleNonce('<p>без стилей</p>', 'x'));
});

test('CSP-отчёт: короткая строка журнала без управляющих символов', function (): void {
    $line = CspReportController::summarize((string) json_encode(['csp-report' => [
        'document-uri' => 'https://asdr.uz/news/test?utm=1',
        'effective-directive' => 'style-src-attr',
        'blocked-uri' => "inline\nподмена строки",
        'source-file' => 'https://asdr.uz/news/test',
        'line-number' => 12,
    ]], JSON_THROW_ON_ERROR));

    assert_true($line !== null);
    assert_contains("\t/news/test\tstyle-src-attr\t", (string) $line, 'страница без query и директива');
    assert_not_contains("\n", (string) $line, 'перевод строки из чужого ввода не попадает в журнал');
    assert_same(null, CspReportController::summarize('не json'));
    assert_same(null, CspReportController::summarize('{"csp-report":{}}'), 'без директивы строки нет');
});

test('CSP-отчёт: маршрут открыт и не попадает в общий кеш', function (): void {
    $routes = (string) file_get_contents(APP_ROOT . '/public/index.php');
    $cache = (string) file_get_contents(APP_ROOT . '/app/Core/PublicResponseCache.php');

    assert_contains("post('/_csp-report'", $routes);
    assert_contains("'/_csp-report'", $cache);
    assert_same('/_csp-report', SecurityHeaders::CSP_REPORT_PATH);
});
