<?php

declare(strict_types=1);

use App\Core\LocalePreference;
use App\Core\Speculation;

/*
 * Предзагрузка следующей страницы (Speculation Rules) и учёт просмотров.
 *
 * Сайт серверный: переход упирается в то, что документ начинают качать после
 * клика. Правила снимают именно это ожидание, но платят тем, что страница
 * выполняется до визита — значит, за GET на предзагружаемом адресе не должно
 * стоять действия, а «показали HTML» перестаёт означать «человек прочитал».
 */

test('Правила предзагрузки: свои страницы, но не служебные', function () {
    $rules = Speculation::rules(['uz', 'en']);

    assert_same('moderate', $rules['prefetch'][0]['eagerness'], 'разметку тянем заранее');
    // conservative — по наведению или нажатию. eager собирал бы все ссылки
    // страницы разом: это госсайт с мобильным трафиком.
    assert_same('conservative', $rules['prerender'][0]['eagerness'], 'страницу целиком — только по наведению');

    $json = Speculation::json(['uz', 'en']);
    $decoded = json_decode($json, true);
    assert_true(is_array($decoded), 'правила — валидный JSON');

    $excluded = $rules['prefetch'][0]['where']['and'][1]['not']['href_matches'];
    foreach (['/admin', '/admin/*', '/repo', '/install', '/health', '/script', '/goals'] as $path) {
        assert_true(in_array($path, $excluded, true), "служебный путь {$path} не предзагружается");
    }
    // Языковой префикс — тот же переключатель письменности и та же админка.
    foreach (['/uz/script', '/uz/admin/*', '/en/health'] as $path) {
        assert_true(in_array($path, $excluded, true), "{$path} исключён вместе с непрефиксным");
    }
    assert_true(!in_array('/news', $excluded, true), 'ради чего всё затевалось — лента предзагружается');

    $selector = $rules['prefetch'][0]['where']['and'][2]['not']['selector_matches'];
    assert_contains('[download]', $selector, 'файл предзагружать незачем');
    assert_contains('[target="_blank"]', $selector, 'ссылка в новую вкладку откроется не здесь');
});

test('Правила отдаются файлом, а не инлайновым скриптом', function () {
    $header = (string) file_get_contents(APP_ROOT . '/app/Views/site/_header.php');
    $footer = (string) file_get_contents(APP_ROOT . '/app/Views/site/_footer.php');
    $controller = (string) file_get_contents(APP_ROOT . '/app/Controllers/Site/SpeculationController.php');
    $routes = (string) file_get_contents(APP_ROOT . '/public/index.php');
    $view = (string) file_get_contents(APP_ROOT . '/app/Core/View.php');

    // Публичная шапка не держит исполняемых инлайн-скриптов (тест 171), и
    // предзагрузка не повод заводить исключение: заголовок со ссылкой на свой
    // файл делает то же самое и ничего не ослабляет в CSP.
    assert_not_contains('speculationrules', $header);
    assert_not_contains('speculationrules', $footer);
    assert_contains('application/speculationrules+json', $controller, 'с application/json браузер правила не примет');
    assert_contains("'/speculation-rules.json'", $routes, 'файл правил доступен по своему адресу');
    assert_contains('Speculation::sendHeader();', $view, 'заголовок объявляет сам документ');
    // Языкового двойника у машинного ответа не бывает (см. тест 290).
    assert_true(!LocalePreference::managesPath(Speculation::RULES_PATH), 'файл правил вне языковых префиксов');
});

test('Спекулятивный запрос узнаётся по Sec-Purpose', function () {
    $before = $_SERVER['HTTP_SEC_PURPOSE'] ?? null;

    unset($_SERVER['HTTP_SEC_PURPOSE']);
    assert_true(!Speculation::isSpeculative(), 'обычный запрос — не спекулятивный');

    // У prerender значение составное, поэтому сравнение на равенство не годится.
    foreach (['prefetch', 'prefetch;prerender', 'Prefetch'] as $purpose) {
        $_SERVER['HTTP_SEC_PURPOSE'] = $purpose;
        assert_true(Speculation::isSpeculative(), "«{$purpose}» — предзагрузка");
    }

    if ($before === null) {
        unset($_SERVER['HTTP_SEC_PURPOSE']);
    } else {
        $_SERVER['HTTP_SEC_PURPOSE'] = $before;
    }
});

test('Просмотр новости считает маячок, а не показ страницы', function () {
    $controller = (string) file_get_contents(APP_ROOT . '/app/Controllers/Site/NewsController.php');
    $model = (string) file_get_contents(APP_ROOT . '/app/Models/News.php');
    $view = (string) file_get_contents(APP_ROOT . '/app/Views/site/news_show.php');
    $js = (string) file_get_contents(APP_ROOT . '/public/assets/js/news.js');
    $routes = (string) file_get_contents(APP_ROOT . '/public/index.php');

    // Разметку браузер забирает и без читателя: из общего кэша и
    // предзагрузкой. При prerender «просмотром» становилось бы наведение.
    assert_not_contains("News::incrementViews((int) \$news['id']);", $controller, 'показ страницы просмотр не считает');
    assert_contains('public function countView(): void', $controller);
    assert_contains("'/news/view'", $routes, 'у маячка свой адрес');
    assert_contains('data-news-view=', $view, 'страница называет маячку свой id');
    assert_contains("status = 'published'", $model, 'id приходит снаружи — черновик не считаем');

    // Главное в маячке: при prerender страница выполняется до визита, поэтому
    // отправка ждёт активации вкладки.
    assert_contains('document.prerendering', $js, 'до активации просмотр не засчитывается');
    assert_contains("navigator.sendBeacon('/news/view'", $js);
    // Ограничитель частоты — тот же уровень, что у приёма Web Vitals.
    assert_contains('FileRateLimiter::allow', $controller);
});
