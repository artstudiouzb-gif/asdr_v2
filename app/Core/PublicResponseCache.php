<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Language;
use App\Models\Setting;

/** HTTP/CDN-кеширование только общих публичных ответов без сессии. */
final class PublicResponseCache
{
    /** @var list<string> */
    private const PRIVATE_PATHS = [
        '/admin', '/repo', '/install', '/search', '/captcha.png', '/push',
        '/unsubscribe', '/health', '/opendata', '/download.php',
    ];

    /** @var list<string> */
    private const CONTENT_ADMIN_PATHS = [
        '/admin/pages', '/admin/blocks', '/admin/snippets', '/admin/news',
        '/admin/projects', '/admin/team', '/admin/albums', '/admin/videos',
        '/admin/content', '/admin/content-types', '/admin/menu', '/admin/header',
        '/admin/footer', '/admin/widgets', '/admin/design', '/admin/settings',
        '/admin/performance', '/admin/trash', '/admin/bulk',
        '/admin/settings/demo-content', '/admin/redirects', '/admin/languages',
    ];

    public static function apply(string $template): void
    {
        if (!str_starts_with($template, 'site/')) {
            return;
        }

        // Ответ, устанавливающий языковое cookie, не должен попасть в общий CDN-кеш.
        if (LocalePreference::changedThisRequest()) {
            header('Cache-Control: private, no-store');
            return;
        }

        $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
        $status = http_response_code();
        $status = is_int($status) && $status > 0 ? $status : 200;
        $sessionActive = session_status() === PHP_SESSION_ACTIVE;
        $hasAuthorization = isset($_SERVER['HTTP_AUTHORIZATION']);
        if (!self::isCacheableRequest(
            $path,
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $sessionActive,
            $status,
            $hasAuthorization,
            Language::activeCodes()
        )) {
            if ($sessionActive || $hasAuthorization) {
                header('Cache-Control: private, no-store');
            }
            return;
        }

        $browserTtl = max(0, min(3600, (int) Setting::get('perf_public_cache_ttl', '60')));
        $sharedTtl = max(0, min(86400, (int) Setting::get('perf_shared_cache_ttl', '300')));
        if ($browserTtl === 0 && $sharedTtl === 0) {
            return;
        }

        header(sprintf(
            'Cache-Control: public, max-age=%d, s-maxage=%d, stale-while-revalidate=30',
            $browserTtl,
            $sharedTtl
        ));
        // Раньше здесь всегда стоял «Vary: Accept-Encoding, Cookie», потому что
        // Router::resolveLocale() редиректил по сохранённому языку на любом
        // адресе: закешированная копия увела бы посетителя мимо редиректа.
        // Теперь этот редирект работает только на корне сайта (корни ниже
        // исключены из кеширования), поэтому язык страницы определяется её
        // адресом, а не cookie.
        //
        // Остальные значения cookie «a11y» на общий кеш не влияют: атрибуты
        // отображения переставляет theme-init.js до первой отрисовки.
        // Исключение — узбекская кириллица: её даёт транслитерация готовой
        // страницы на сервере (View::wantsCyrillic), клиент это повторить не
        // может, поэтому узбекские ответы по-прежнему зависят от cookie.
        $varyCookie = Locale::current() === 'uz';
        header('Vary: Accept-Encoding' . ($varyCookie ? ', Cookie' : ''));
    }

    /**
     * @param list<string>|null $activeCodes Активные языки; нужны, чтобы
     *        отличить корень языка («/uz») от страницы со схожим slug
     *        («/news»). null — считать корнем только «/».
     */
    public static function isCacheableRequest(
        string $path,
        string $method,
        bool $sessionActive,
        int $status,
        bool $hasAuthorization = false,
        ?array $activeCodes = null
    ): bool {
        if (!in_array(strtoupper($method), ['GET', 'HEAD'], true)
            || $sessionActive
            || $hasAuthorization
            || $status !== 200) {
            return false;
        }

        // Корни языков отдают либо страницу, либо редирект — по cookie.
        if (self::isLanguageRootPath($path, $activeCodes ?? [])) {
            return false;
        }

        if (self::isPrivatePath($path)) {
            return false;
        }

        // Публичные маршруты могут иметь языковой префикс (/uz/search).
        $localizedPath = preg_replace('#^/[a-zA-Z]{2,8}(?=/|$)#', '', $path) ?: '/';
        return !self::isPrivatePath($localizedPath);
    }

    /**
     * Корень сайта и корни языков («/», «/uz», «/uz/»). Только на них
     * Router::resolveLocale() ещё редиректит по сохранённому языку, поэтому
     * ответ зависит от cookie и в общий кеш не годится.
     *
     * Сверяемся со списком активных языков, а не с шаблоном «/xx»: иначе
     * обычная страница со slug из двух-восьми букв (/news, /contacts) тоже
     * считалась бы корнем и потеряла кеширование.
     */
    public static function isLanguageRootPath(string $path, array $activeCodes): bool
    {
        $trimmed = rtrim($path, '/');
        if ($trimmed === '') {
            return true;
        }

        return in_array(strtolower(ltrim($trimmed, '/')), array_map('strtolower', $activeCodes), true);
    }

    private static function isPrivatePath(string $path): bool
    {
        foreach (self::PRIVATE_PATHS as $privatePath) {
            if ($path === $privatePath || str_starts_with($path, $privatePath . '/')) {
                return true;
            }
        }
        return false;
    }

    /** Сброс общего кеша после успешной мутации публичного контента. */
    public static function registerContentInvalidation(): void
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }
        $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
        $matches = false;
        foreach (self::CONTENT_ADMIN_PATHS as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                $matches = true;
                break;
            }
        }
        if (!$matches) {
            return;
        }

        register_shutdown_function(static function (): void {
            $status = http_response_code();
            if (!is_int($status) || $status < 400) {
                Cache::forgetPrefix('page:');
            }
        });
    }
}
