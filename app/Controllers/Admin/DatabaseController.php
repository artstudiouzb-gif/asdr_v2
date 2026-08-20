<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\DatabaseDoctor;
use App\Core\Flash;
use App\Core\MigrationRunner;
use App\Core\View;
use PDO;

/**
 * Управление базой данных, миграциями и системной диагностикой в панели управления.
 */
final class DatabaseController
{
    public function index(): void
    {
        Auth::requireSuperAdmin();

        $pdo = Database::pdo();
        $migrationsDir = APP_ROOT . '/database/migrations';
        $schemaSql = APP_ROOT . '/database/schema.sql';

        $status = MigrationRunner::status($pdo, $migrationsDir);

        $dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
        $dbVersion = (string) $pdo->query('SELECT VERSION()')->fetchColumn();

        $tables = [];
        $totalSizeMb = 0.0;
        try {
            $stmt = $pdo->prepare("
                SELECT TABLE_NAME AS name,
                       ENGINE AS engine,
                       TABLE_ROWS AS rows_count,
                       ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS size_mb,
                       TABLE_COLLATION AS collation
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = :schema AND TABLE_TYPE = 'BASE TABLE'
                ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
            ");
            $stmt->execute([':schema' => $dbName]);
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($tables as $t) {
                $totalSizeMb += (float) ($t['size_mb'] ?? 0.0);
            }
        } catch (\Throwable) {
            $tables = [];
        }

        $doctorReport = null;
        if (isset($_GET['diagnose']) && $_GET['diagnose'] === '1') {
            $doctorReport = DatabaseDoctor::diagnose($pdo, $schemaSql, $migrationsDir);
        }

        View::render('admin/database/index', [
            'status' => $status,
            'dbName' => $dbName,
            'dbVersion' => $dbVersion,
            'tables' => $tables,
            'totalSizeMb' => round($totalSizeMb, 2),
            'doctorReport' => $doctorReport,
        ]);
    }

    public function migrate(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyRequest();

        $pdo = Database::pdo();
        $migrationsDir = APP_ROOT . '/database/migrations';

        try {
            $applied = MigrationRunner::applyPending($pdo, $migrationsDir);
            if ($applied !== []) {
                Flash::success(sprintf(
                    'База данных успешно обновлена. Применено миграций: %d (%s)',
                    count($applied),
                    implode(', ', $applied)
                ));
            } else {
                Flash::info('Все доступные миграции уже применены, структура базы данных актуальна.');
            }
        } catch (\Throwable $e) {
            Flash::error('Ошибка при обновлении базы данных: ' . $e->getMessage());
        }

        header('Location: /admin/database');
        exit;
    }

    public function optimize(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyRequest();

        $pdo = Database::pdo();
        $dbName = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();

        try {
            $stmt = $pdo->prepare("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_TYPE = 'BASE TABLE'");
            $stmt->execute([':schema' => $dbName]);
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

            $optimized = 0;
            foreach ($tables as $table) {
                $cleanName = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $table);
                if ($cleanName !== '') {
                    $pdo->exec("OPTIMIZE TABLE `{$cleanName}`");
                    $optimized++;
                }
            }

            Flash::success(sprintf('Оптимизация и дефрагментация индексов успешно завершена для %d таблиц.', $optimized));
        } catch (\Throwable $e) {
            Flash::error('Ошибка оптимизации таблиц: ' . $e->getMessage());
        }

        header('Location: /admin/database');
        exit;
    }
}
