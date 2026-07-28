<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Database;
use App\Core\Heartbeat;
use App\Core\Logger;

/**
 * Health-check для мониторинга (задача 59). Проверяет БД, доступность записи
 * в storage и «живость» фоновых воркеров (heartbeat, группа 2.1). Отдаёт JSON
 * и корректный HTTP-код; при неуспехе шлёт алерт.
 */
final class HealthController
{
    public function index(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');

        $checks = ['db' => false, 'storage' => false];

        try {
            Database::pdo()->query('SELECT 1');
            $checks['db'] = true;
        } catch (\Throwable $e) {
            Logger::critical('Health-check: БД недоступна — ' . $e->getMessage());
        }

        $logDir = (defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 3)) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $checks['storage'] = is_dir($logDir) && is_writable($logDir);
        if (!$checks['storage']) {
            Logger::warning('Health-check: каталог storage/logs недоступен для записи.');
        }

        // Живость фоновых воркеров (группа 2.1). «Залипшим» считается воркер,
        // который запускался хотя бы раз, но давно молчит (cron отвалился).
        $workers = Heartbeat::status();
        $stale = [];
        foreach ($workers as $name => $w) {
            if ($w['stale']) {
                $stale[] = $name;
            }
        }
        if ($stale !== []) {
            // TelegramNotifier throttлит одинаковые алерты по сигнатуре, поэтому
            // частые /health-опросы не спамят.
            Logger::critical('Health-check: воркер(ы) не запускались вовремя — ' . implode(', ', $stale), [
                'stale_workers' => $stale,
            ]);
        }

        // Инфраструктурный статус (db+storage) определяет HTTP-код, чтобы не
        // «флапать» 503 на установках, где часть воркеров осознанно не настроена.
        $ok = $checks['db'] && $checks['storage'];
        http_response_code($ok ? 200 : 503);
        echo json_encode([
            'status' => $ok ? ($stale === [] ? 'ok' : 'degraded') : 'down',
            'checks' => $checks,
            'workers' => $workers,
        ], JSON_UNESCAPED_UNICODE);
    }
}
