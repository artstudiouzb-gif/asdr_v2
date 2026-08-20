<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

/**
 * Единый исполнитель SQL-миграций для CLI, веб-установщика и тестов.
 *
 * schema.sql создаёт базовую структуру и таблицу migrations. После импорта
 * свежей схемы здесь применяются все файлы, которые ещё не отмечены в
 * migrations (в нормальном fresh-install это post-schema миграции).
 */
final class MigrationRunner
{
    /** @return list<string> */
    public static function pending(PDO $pdo, string $migrationsDir): array
    {
        self::ensureMigrationsTable($pdo);

        $applied = $pdo->query('SELECT filename FROM migrations')->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $applied = array_flip(array_map('strval', $applied));

        $files = glob(rtrim($migrationsDir, '/') . '/*.sql') ?: [];
        sort($files, SORT_STRING);

        return array_values(array_filter(
            $files,
            static fn (string $file): bool => !isset($applied[basename($file)])
        ));
    }

    public static function pendingCount(PDO $pdo, string $migrationsDir): int
    {
        return count(self::pending($pdo, $migrationsDir));
    }

    /**
     * @return array{
     *     total: int,
     *     applied_count: int,
     *     pending_count: int,
     *     pending: list<array{name: string, path: string, size: int}>,
     *     applied: list<array{name: string, applied_at: string}>,
     *     all: list<array{name: string, path: string, is_applied: bool, applied_at: ?string, size: int}>
     * }
     */
    public static function status(PDO $pdo, string $migrationsDir): array
    {
        self::ensureMigrationsTable($pdo);

        $appliedRows = [];
        try {
            $appliedRows = $pdo->query('SELECT filename, applied_at FROM migrations ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            $appliedRows = [];
        }

        $appliedMap = [];
        foreach ($appliedRows as $row) {
            $appliedMap[(string) $row['filename']] = (string) ($row['applied_at'] ?? '');
        }

        $files = glob(rtrim($migrationsDir, '/') . '/*.sql') ?: [];
        sort($files, SORT_STRING);

        $all = [];
        $pending = [];
        foreach ($files as $file) {
            $name = basename($file);
            $isApplied = isset($appliedMap[$name]);
            $size = (int) @filesize($file);
            $item = [
                'name' => $name,
                'path' => $file,
                'is_applied' => $isApplied,
                'applied_at' => $appliedMap[$name] ?? null,
                'size' => $size,
            ];
            $all[] = $item;
            if (!$isApplied) {
                $pending[] = [
                    'name' => $name,
                    'path' => $file,
                    'size' => $size,
                ];
            }
        }

        $appliedList = [];
        foreach ($appliedRows as $row) {
            $appliedList[] = [
                'name' => (string) $row['filename'],
                'applied_at' => (string) ($row['applied_at'] ?? ''),
            ];
        }

        return [
            'total' => count($files),
            'applied_count' => count($appliedMap),
            'pending_count' => count($pending),
            'pending' => $pending,
            'applied' => $appliedList,
            'all' => $all,
        ];
    }

    /**
     * @param null|callable(string,string):void $reporter (event, filename)
     * @return list<string> имена применённых миграций
     */
    public static function applyPending(PDO $pdo, string $migrationsDir, ?callable $reporter = null): array
    {
        self::ensureMigrationsTable($pdo);

        $database = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
        $lockName = 'asdr_cms_migrations_' . substr(hash('sha256', $database), 0, 24);
        $lockStmt = $pdo->prepare('SELECT GET_LOCK(:name, 30)');
        $lockStmt->execute([':name' => $lockName]);
        if ((int) $lockStmt->fetchColumn() !== 1) {
            throw new RuntimeException('Не удалось получить блокировку миграций. Другой процесс ещё работает.');
        }

        try {
            // Pending вычисляем уже после получения блокировки: второй процесс
            // мог успеть применить миграции, пока мы ждали GET_LOCK().
            $pending = self::pending($pdo, $migrationsDir);
            if ($pending === []) {
                return [];
            }

            $record = $pdo->prepare(
                'INSERT INTO migrations (filename, applied_at) VALUES (:filename, NOW())'
            );
            $appliedNow = [];

            foreach ($pending as $file) {
                $name = basename($file);
                $sql = file_get_contents($file);
                if ($sql === false || trim($sql) === '') {
                    throw new RuntimeException("Файл миграции {$name} пуст или недоступен.");
                }

                if ($reporter !== null) {
                    $reporter('start', $name);
                }

                // SQL-файлы миграций являются доверенной частью релиза.
                // DDL MySQL выполняет неявный COMMIT, поэтому имя фиксируем
                // только после успешного выполнения всего файла.
                $pdo->exec($sql);
                $record->execute([':filename' => $name]);
                $appliedNow[] = $name;

                if ($reporter !== null) {
                    $reporter('done', $name);
                }
            }

            return $appliedNow;
        } finally {
            $releaseStmt = $pdo->prepare('SELECT RELEASE_LOCK(:name)');
            $releaseStmt->execute([':name' => $lockName]);
        }
    }

    private static function ensureMigrationsTable(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                filename VARCHAR(255) NOT NULL,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_migrations_filename (filename)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }
}
