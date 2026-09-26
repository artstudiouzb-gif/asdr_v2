<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\FileRateLimiter;

/**
 * Приёмник отчётов CSP (report-uri пробной политики Report-Only).
 *
 * Строгий style-src включается только после того, как журнал на боевом сайте
 * неделю пуст: так видно, что сломалось бы у посетителей (счётчик, внешний
 * виджет, старый контент), до того как это сломается.
 *
 * CSRF-токена у отчёта нет и быть не может: его шлёт браузер сам, без сессии.
 * Защита — ограничитель частоты по адресу, предел размера тела и то, что в
 * журнал попадают только короткие строковые поля, а не тело целиком.
 */
final class CspReportController
{
    private const LIMIT_PER_MINUTE = 20;
    private const MAX_BODY = 8192;
    private const MAX_LOG_BYTES = 1048576;

    public function store(): void
    {
        header('Cache-Control: no-store');
        http_response_code(204);

        $identifier = 'csp:' . sha1((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if (FileRateLimiter::allow($identifier, self::LIMIT_PER_MINUTE, 60) === false) {
            http_response_code(429);
            return;
        }

        $raw = (string) file_get_contents('php://input', false, null, 0, self::MAX_BODY + 1);
        if ($raw === '' || strlen($raw) > self::MAX_BODY) {
            return;
        }
        $line = self::summarize($raw);
        if ($line === null) {
            return;
        }

        self::append($line);
    }

    /**
     * Строка журнала из отчёта формата report-uri ({"csp-report": {...}}):
     * страница без query, нарушенная директива, что заблокировано.
     */
    public static function summarize(string $raw): ?string
    {
        $data = json_decode($raw, true);
        $report = is_array($data) ? ($data['csp-report'] ?? null) : null;
        if (!is_array($report)) {
            return null;
        }

        $field = static function (string $key) use ($report): string {
            $value = $report[$key] ?? '';
            $value = is_scalar($value) ? (string) $value : '';
            // Одна строка журнала на отчёт: переводы строк и управляющие
            // символы из чужого ввода в файл не попадают.
            $value = (string) preg_replace('/[\x00-\x1F\x7F]+/', ' ', $value);

            return mb_substr($value, 0, 200);
        };

        $page = (string) (parse_url($field('document-uri'), PHP_URL_PATH) ?: '');
        $directive = $field('effective-directive') !== '' ? $field('effective-directive') : $field('violated-directive');
        if ($directive === '') {
            return null;
        }

        return gmdate('Y-m-d\TH:i:s\Z') . "\t" . $page . "\t" . $directive . "\t" . $field('blocked-uri')
            . "\t" . $field('source-file') . ':' . $field('line-number');
    }

    private static function append(string $line): void
    {
        $dir = APP_ROOT . '/storage/logs';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return;
        }
        $file = $dir . '/csp-report.log';
        // Журнал не растёт без предела: при переполнении прежний уходит в .1.
        if (is_file($file) && (int) @filesize($file) > self::MAX_LOG_BYTES) {
            @rename($file, $file . '.1');
        }
        @file_put_contents($file, $line . "\n", FILE_APPEND | LOCK_EX);
    }
}
