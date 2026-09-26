<?php

declare(strict_types=1);

use App\Core\LocalePreference;
use App\Core\SpeculationRules;

/*
 * Предзагрузка внутренних ссылок — Speculation Rules заголовком (без
 * инлайн-скрипта), запасной путь — наведение в frontend.js. Ни тот, ни другой
 * не трогают ссылки-действия: предзагрузка `?_lang=uz` молча меняла
 * сохранённый язык посетителя (ответ ставил cookie до клика).
 */
test('Правила предзагрузки: только prefetch по наведению и без служебных ссылок', function (): void {
    $rules = json_decode(SpeculationRules::json(), true, 512, JSON_THROW_ON_ERROR);
    assert_same(['prefetch'], array_keys($rules), 'prerender исполнял бы скрипты и счётчики до клика');
    $rule = $rules['prefetch'][0];
    assert_same('moderate', $rule['eagerness']);

    $json = SpeculationRules::json();
    foreach (['/admin/*', '/repo/*', '/uploads/*', '/_*'] as $path) {
        assert_contains('"' . $path . '"', $json);
    }
    foreach (['_lang=', '_fragment=', '[download]', 'nofollow'] as $needle) {
        assert_contains($needle, SpeculationRules::EXCLUDED_SELECTOR);
    }

    assert_true(SpeculationRules::appliesTo('/news/x'));
    assert_false(SpeculationRules::appliesTo('/admin/pages'));
    assert_false(SpeculationRules::appliesTo('/repo/login'));
});

test('Правила отдаются своим MIME, заголовок ставит публичная часть', function (): void {
    $routes = (string) file_get_contents(APP_ROOT . '/public/index.php');
    assert_contains('SpeculationRules::PATH', $routes);
    $controller = (string) file_get_contents(APP_ROOT . '/app/Controllers/Site/SpeculationRulesController.php');
    assert_contains('application/speculationrules+json', $controller);
    $headers = (string) file_get_contents(APP_ROOT . '/app/Core/SecurityHeaders.php');
    assert_contains("'Speculation-Rules: \"' . SpeculationRules::PATH", $headers);
    assert_false(is_file(APP_ROOT . '/public/assets/js/instant-prefetch.js'), 'второй, неподключённый скрипт предзагрузки не нужен');
});

test('Предзагрузка переключателя языка не меняет выбор посетителя', function (): void {
    assert_true(LocalePreference::isSpeculative(['HTTP_SEC_PURPOSE' => 'prefetch']));
    assert_true(LocalePreference::isSpeculative(['HTTP_SEC_PURPOSE' => 'prefetch;prerender']));
    assert_true(LocalePreference::isSpeculative(['HTTP_PURPOSE' => 'prefetch']));
    assert_true(LocalePreference::isSpeculative(['HTTP_X_MOZ' => 'prefetch']));
    assert_false(LocalePreference::isSpeculative([]));

    // Роутер отказывает такой предзагрузке до remember(): ответ без cookie
    // иначе подставился бы браузером в настоящий клик.
    $router = (string) file_get_contents(APP_ROOT . '/app/Core/Router.php');
    $guard = strpos($router, 'LocalePreference::isSpeculative($_SERVER)');
    $remember = strpos($router, 'LocalePreference::remember($requestedCode)');
    assert_true($guard !== false && $remember !== false && $guard < $remember);

    $js = (string) file_get_contents(APP_ROOT . '/public/assets/js/frontend.js');
    assert_contains("HTMLScriptElement.supports('speculationrules')", $js);
    assert_contains('[href*="_lang="]', $js);
});
