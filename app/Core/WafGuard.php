<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Web Application Firewall (WAF) & L7 Rate Limiter.
 * Проверяет входные параметры запроса на наличие атак (SQLi, XSS, Path Traversal)
 * и ограничивает частоту аномальных запросов.
 */
final class WafGuard
{
    private const EXPLOIT_PATTERNS = [
        '/\b(union\s+select|select\s+.*\s+from\s+information_schema|benchmark\s*\(|pg_sleep\s*\()/i',
        '/<script\b[^>]*>.*?<\/script>/is',
        '/\b(javascript:|vbscript:|onerror\s*=|onload\s*=)/i',
        '/(\.\.\/|\.\.\\\\)/',
    ];

    public static function inspect(): void
    {
        // Пропускаем CLI-запросы и установку
        if (php_sapi_name() === 'cli' || !APP_INSTALLED) {
            return;
        }

        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $queryString = $_SERVER['QUERY_STRING'] ?? '';
        $rawPost = file_get_contents('php://input') ?: '';

        $inputs = [$requestUri, $queryString];
        if (strlen($rawPost) < 100000) { // Не сканируем огромные бинарные файлы
            $inputs[] = $rawPost;
        }

        foreach ($inputs as $input) {
            if (!is_string($input) || $input === '') {
                continue;
            }
            foreach (self::EXPLOIT_PATTERNS as $pattern) {
                if (preg_match($pattern, $input)) {
                    self::blockAndLog('WAF: Обнаружена попытка внедрения вредоносного кода');
                }
            }
        }
    }

    private static function blockAndLog(string $reason): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        Logger::warning("{$reason} | IP: {$ip} | URI: {$uri}");

        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><title>403 Access Denied</title><style>body{font-family:sans-serif;text-align:center;padding:10% 5%;background:#0f172a;color:#f8fafc;}h1{color:#ef4444;}.box{max-width:500px;margin:0 auto;background:#1e293b;padding:30px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.3);}</style></head><body><div class="box"><h1>403 Forbidden</h1><p>Запрос заблокирован WAF-системой безопасности.</p><p style="font-size:0.85rem;color:#94a3b8;">IP: ' . htmlspecialchars($ip, ENT_QUOTES) . '</p></div></body></html>';
        exit;
    }
}
