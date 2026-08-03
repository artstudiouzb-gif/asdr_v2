<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/../' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// Глобальные помощники шаблонов (t() — перевод интерфейса).
require __DIR__ . '/helpers.php';

use App\Core\Config;
use App\Core\Database;
use App\Core\ErrorHandler;
use App\Core\SecurityHeaders;

define('APP_ROOT', dirname(__DIR__, 2));

// Заголовки безопасности выставляем до любого вывода, чтобы они попали
// на ВСЕ ответы, включая брендированный fail-safe 503 ниже и страницы ошибок.
SecurityHeaders::send();

$configFile = APP_ROOT . '/config/config.php';
$installedLock = APP_ROOT . '/storage/installed.lock';

// Система считается установленной, когда есть и config.php, и файл-маркер.
define('APP_INSTALLED', is_file($configFile) && is_file($installedLock));

ini_set('log_errors', '1');
ini_set('error_log', APP_ROOT . '/storage/logs/php-error.log');

if (is_file($configFile)) {
    $config = require $configFile;
    Config::set($config);
    date_default_timezone_set($config['app']['timezone'] ?? 'UTC');
    ErrorHandler::register((bool) ($config['app']['debug'] ?? false));

    if (APP_INSTALLED) {
        // Forwarded IP headers are accepted only from explicitly trusted
        // reverse proxies configured outside the database.
        \App\Core\ClientIp::applyTrustedProxy();
        // Inspect the request before opening MySQL so obvious malicious input
        // cannot consume a database connection first.
        \App\Core\WafGuard::inspect();

        // Рабочий режим: недоступность БД -> брендированный 503 (fail-safe),
        // без вывода системного трейса.
        try {
            Database::init($config['db']);
        } catch (\Throwable $e) {
            \App\Core\Logger::critical('Падение БД (503): ' . $e->getMessage(), [
                'url' => $_SERVER['REQUEST_URI'] ?? 'cli',
            ]);
            if (PHP_SAPI !== 'cli') {
                $failedPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
                if ($failedPath === '/health') {
                    $logDir = APP_ROOT . '/storage/logs';
                    http_response_code(503);
                    header('Content-Type: application/json; charset=UTF-8');
                    header('Cache-Control: no-store');
                    header('Retry-After: 60');
                    echo json_encode([
                        'status' => 'down',
                        'checks' => [
                            'db' => false,
                            'storage' => is_dir($logDir) && is_writable($logDir),
                        ],
                        'workers' => [],
                        'release' => \App\Core\Release::id(),
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                http_response_code(503);
                header('Retry-After: 60');
                $view = APP_ROOT . '/app/Views/errors/503.php';
                echo is_file($view) ? file_get_contents($view) : 'Сервис временно недоступен.';
                exit;
            }
            throw $e;
        }
    } else {
        // Установка ещё не завершена, но config.php уже есть (шаг после
        // генерации конфига): подключаемся к БД мягко, без фатала.
        try {
            Database::init($config['db']);
        } catch (\Throwable $e) {
            // БД ещё может быть недоступна — установщик покажет ошибку сам.
        }
    }
} else {
    // Режим установки: config.php ещё нет. Работаем на минимальных дефолтах.
    Config::set(['app' => ['env' => 'production', 'debug' => true, 'url' => '', 'timezone' => 'UTC']]);
    date_default_timezone_set('UTC');
    ErrorHandler::register(true);
}

// Для обычного публичного GET без cookie сессию не создаём. Компоненты,
// которым она нужна (Auth, CSRF, Flash, CAPTCHA), запускают её сами.
if (PHP_SAPI !== 'cli' && \App\Core\Session::hasCookie()) {
    \App\Core\Session::start();
}

// Скрытый административный шлюз должен сработать до регистрации маршрутов:
// прямые неавторизованные /admin/* выглядят как обычный 404 и не успевают
// раскрыть форму входа, сброс пароля или внутренний маршрут панели.
if (PHP_SAPI !== 'cli') {
    \App\Core\AdminEntryGate::enforce();
}
