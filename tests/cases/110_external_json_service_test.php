<?php

declare(strict_types=1);

use App\Core\CurrencyInformer;
use App\Core\ExternalJsonService;

test('Кэш внешнего JSON читается без обращения в сеть', function (): void {
    // Чтение и обновление разведены намеренно: пока их делал один метод,
    // его звали из шапки, и страница ждала сторонний сервис.
    assert_true(method_exists(ExternalJsonService::class, 'cached'), 'есть чтение кэша');
    assert_true(method_exists(ExternalJsonService::class, 'refresh'), 'есть обновление');
    assert_false(method_exists(ExternalJsonService::class, 'fetch'), 'слитного fetch() больше нет');

    // Неизвестного источника в кэше нет — и запроса за ним тоже.
    assert_same(null, ExternalJsonService::cached('https://example.invalid/none.json'));
    assert_same(null, ExternalJsonService::age('https://example.invalid/none.json'));
});

test('Валютный информер отдаёт разметку из кэша и молчит при его отсутствии', function (): void {
    $url = 'https://cbu.uz/ru/arkhiv-kursov-valyut/json/';
    $file = APP_ROOT . '/storage/cache/json/' . md5($url) . '.json';
    $backup = is_file($file) ? (string) file_get_contents($file) : null;

    try {
        @mkdir(dirname($file), 0775, true);
        file_put_contents($file, json_encode([
            ['Ccy' => 'USD', 'CcyNm_RU' => 'Доллар США', 'Rate' => '12750.15', 'Diff' => '5.20'],
            ['Ccy' => 'EUR', 'CcyNm_RU' => 'Евро', 'Rate' => '13820.44', 'Diff' => '-3.10'],
        ], JSON_UNESCAPED_UNICODE));

        $html = CurrencyInformer::renderWidgetHtml();
        assert_contains('currency-widget', $html);
        assert_contains('USD', $html);
        assert_contains('12 750', $html, 'разряды разделены');
        assert_true(is_int(CurrencyInformer::cacheAge()), 'возраст кэша известен');

        // Кэша нет — плашки нет. Это не ошибка: пустая плашка лучше ожидания.
        unlink($file);
        assert_same('', CurrencyInformer::renderWidgetHtml());
        assert_same(null, CurrencyInformer::cacheAge());
    } finally {
        if ($backup !== null) {
            file_put_contents($file, $backup);
        } elseif (is_file($file)) {
            unlink($file);
        }
    }
});

test('Публичный рендер не ходит в сеть за курсами', function (): void {
    // Главное правило этой части: доступность cbu.uz не должна становиться
    // доступностью сайта. Замерено — страница собиралась 733 мс вместо 37 мс,
    // и при недоступном источнике полный таймаут платил каждый посетитель.
    $informer = (string) file_get_contents(APP_ROOT . '/app/Core/CurrencyInformer.php');
    assert_contains('ExternalJsonService::cached', $informer, 'курсы читаются из кэша');
    assert_true(
        preg_match('/function rates\(\).*?ExternalJsonService::refresh/s', $informer) !== 1,
        'rates() не обновляет кэш сам'
    );

    $header = (string) file_get_contents(APP_ROOT . '/app/Views/site/_header.php');
    assert_true(
        !str_contains($header, "'currency' => \\App\\Core\\CurrencyInformer::renderWidgetHtml()"),
        'плашка не собирается безусловно на каждой странице'
    );
    assert_contains("in_array('currency'", $header, 'плашка считается, только если размещена в зоне');

    // Обновляет кэш тот, кому ждать позволено: воркер по cron и админка.
    $worker = (string) file_get_contents(APP_ROOT . '/app/Console/currency_worker.php');
    assert_contains('CurrencyInformer::refresh', $worker);
    $headerController = (string) file_get_contents(APP_ROOT . '/app/Controllers/Admin/HeaderController.php');
    assert_contains('CurrencyInformer::refresh', $headerController, 'после сохранения шапки курсы подтягиваются сразу');
});
