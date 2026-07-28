<?php

declare(strict_types=1);

namespace App\Core;

/** Централизованный ленивый запуск защищённой PHP-сессии. */
final class Session
{
    public static function hasCookie(): bool
    {
        $name = (string) Config::get('session.name', 'asc_session');
        return isset($_COOKIE[$name]) && is_string($_COOKIE[$name]) && $_COOKIE[$name] !== '';
    }

    public static function start(): void
    {
        if (PHP_SAPI === 'cli' || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $lifetime = (int) Config::get('session.lifetime', 7200);
        session_name((string) Config::get('session.name', 'asc_session'));
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'domain' => '',
            'secure' => RequestUrl::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
        if (!empty($_SESSION['last_activity']) && time() - (int) $_SESSION['last_activity'] > $lifetime) {
            $_SESSION = [];
            session_destroy();
            session_start();
        }
        $_SESSION['last_activity'] = time();
    }
}
