<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Router;

/*
 * АДВЕРСАРИАЛЬНАЯ НАХОДКА: языковой префикс протаскивает запрос в /admin в обход
 * всех преддиспетчерских проверок доступа.
 *
 * КАК АТАКОВАТЬ. Любой активный не-дефолтный язык даёт префикс: вместо
 * `/admin/users` запросить `/uz/admin/users`. Router::resolveLocale() срезает
 * `/uz` и диспетчеризует запрос на реальный обработчик `/admin/users`. Но все
 * проверки, которые стоят ДО роутера, сверяют сырой путь запроса строкой
 * `str_starts_with($path, '/admin')` — а `/uz/admin/...` под неё не подпадает.
 *
 * ЧТО ПРОИСХОДИТ. Мимо проверки проходят сразу два документированных контроля:
 *   1) Онбординг второго фактора в public/index.php: сессия с флагом
 *      `2fa_setup_required` (пароль верен, второй фактор ещё не подключён)
 *      должна пускаться только к профилю, а получает полную панель через
 *      `/uz/admin/...` (живая проверка: POST /uz/admin/users/create заводит
 *      супер-админа).
 *   2) AdminEntryGate::enforce() (скрытый вход + IP-allowlist): прямой
 *      `/admin/login` отдаёт 404, а `/uz/admin/login` — форму входа (200),
 *      причём в обход `admin_entry_allowed_cidrs`.
 *
 * ЧЕМ ГРОЗИТ. Полный обход онбординг-ограничения второго фактора и полный
 * обход «скрытой» точки входа вместе с IP-allowlist — оба заявлены в CLAUDE.md
 * как рабочие меры защиты админки.
 *
 * Router прямо документирует обратное: «Админка (/admin) под языковой префикс
 * не попадает». Тест кодирует именно это ожидание, поэтому на текущем коде он
 * ПАДАЕТ: запрос `/uz/admin/...` доходит до admin-обработчика.
 */

test('Языковой префикс не должен протаскивать запрос в /admin (нужна тестовая БД)', function () {
    $db = getenv('TEST_DB_DATABASE');
    if ($db === false || $db === '') {
        skip_test('TEST_DB_* не заданы');
    }

    Database::init([
        'host' => getenv('TEST_DB_HOST') ?: '127.0.0.1',
        'port' => getenv('TEST_DB_PORT') ?: '3306',
        'database' => $db,
        'username' => getenv('TEST_DB_USERNAME') ?: 'root',
        'password' => getenv('TEST_DB_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
    ]);

    // Нужен активный не-дефолтный язык, иначе префикс атаки не существует.
    $active = \App\Models\Language::activeCodes();
    $default = \App\Models\Language::defaultCode();
    $secondary = null;
    foreach ($active as $code) {
        if ($code !== $default) {
            $secondary = $code;
            break;
        }
    }
    if ($secondary === null) {
        skip_test('Нет второго активного языка для проверки префикса');
    }

    // Глобальное состояние ($_SERVER/$_COOKIE/Locale) сохраняем и возвращаем:
    // dispatch() выставляет Locale и языковое cookie, а тест-файлы сортируются
    // строкой (этот идёт раньше поиска) — иначе он ронял бы соседей.
    $savedServer = $_SERVER;
    $savedCookie = $_COOKIE;

    try {
        $GLOBALS['__adv_probe_hit'] = false;
        $router = new Router();
        $router->get('/admin/__adv_probe', static function (): void {
            $GLOBALS['__adv_probe_hit'] = true;
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING'] = '';
        $path = '/' . $secondary . '/admin/__adv_probe';
        $_SERVER['REQUEST_URI'] = $path;

        $router->dispatch('GET', $path);

        assert_false(
            (bool) ($GLOBALS['__adv_probe_hit'] ?? false),
            "запрос {$path} дошёл до admin-обработчика — языковой префикс обошёл преддиспетчерскую проверку /admin"
        );
    } finally {
        $_SERVER = $savedServer;
        $_COOKIE = $savedCookie;
        \App\Core\Locale::set($default);
        \App\Core\Locale::setPath('/');
    }
});
