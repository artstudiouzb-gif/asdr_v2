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

        self::ensureSavePath();

        $lifetime = (int) Config::get('session.lifetime', 7200);
        $absoluteLifetime = max(
            $lifetime,
            (int) Config::get('session.absolute_lifetime', 28800)
        );
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

        try {
            session_start();
        } catch (\Throwable $e) {
            $fallback = defined('APP_ROOT') ? APP_ROOT . '/storage/sessions' : sys_get_temp_dir();
            if (!is_dir($fallback)) {
                @mkdir($fallback, 0770, true);
            }
            session_save_path($fallback);
            session_start();
        }

        $now = time();
        $idleExpired = !empty($_SESSION['last_activity'])
            && $now - (int) $_SESSION['last_activity'] > $lifetime;
        $absoluteExpired = !empty($_SESSION['started_at'])
            && $now - (int) $_SESSION['started_at'] > $absoluteLifetime;
        if ($idleExpired || $absoluteExpired) {
            $_SESSION = [];
            session_destroy();
            session_start();
        }
        $_SESSION['started_at'] ??= $now;
        $_SESSION['last_activity'] = $now;
    }

    /**
     * Гарантирует доступность каталога для сохранения сессий.
     * Защищает от сбоев Plesk/cPanel/Shared-хостингов, когда системный session.save_path
     * недоступен или использует многоуровневые несуществующие пути.
     */
    public static function ensureSavePath(): void
    {
        $current = (string) session_save_path();
        if (str_contains($current, ';')) {
            $parts = explode(';', $current);
            $checkPath = (string) end($parts);
        } else {
            $checkPath = $current;
        }

        $isUsable = false;
        if ($checkPath !== '' && @is_dir($checkPath) && @is_writable($checkPath)) {
            $testFile = rtrim($checkPath, '/\\') . '/.sess_check_' . uniqid('', true);
            if (@file_put_contents($testFile, '1') !== false) {
                @unlink($testFile);
                $isUsable = true;
            }
        }

        if (!$isUsable) {
            $local = defined('APP_ROOT') ? APP_ROOT . '/storage/sessions' : dirname(__DIR__, 2) . '/storage/sessions';
            if (!is_dir($local)) {
                @mkdir($local, 0770, true);
            }
            if (is_dir($local) && is_writable($local)) {
                session_save_path($local);
            } else {
                $temp = sys_get_temp_dir();
                if (is_dir($temp) && is_writable($temp)) {
                    session_save_path($temp);
                }
            }
        }
    }
}
