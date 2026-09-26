<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Ключи доступа (WebAuthn) пользователей админки: открытый ключ и счётчик. */
final class Passkey
{
    /** @return list<array<string, mixed>> */
    public static function forUser(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, credential_id, public_key, sign_count, name, created_at, last_used_at
               FROM user_passkeys WHERE user_id = :u ORDER BY id'
        );
        $stmt->execute([':u' => $userId]);

        return Database::rows($stmt);
    }

    public static function countForUser(int $userId): int
    {
        try {
            $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM user_passkeys WHERE user_id = :u');
            $stmt->execute([':u' => $userId]);

            return (int) $stmt->fetchColumn();
        } catch (\PDOException) {
            // Миграция ещё не применена: канала нет, вход работает как раньше.
            return 0;
        }
    }

    /** @return array<string, mixed>|null */
    public static function find(int $userId, string $credentialId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, credential_id, public_key, sign_count FROM user_passkeys
              WHERE user_id = :u AND credential_id = :c LIMIT 1'
        );
        $stmt->execute([':u' => $userId, ':c' => $credentialId]);

        return Database::row($stmt);
    }

    public static function create(int $userId, string $credentialId, string $pem, int $count, string $name): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO user_passkeys (user_id, credential_id, public_key, sign_count, name)
             VALUES (:u, :c, :k, :n, :name)'
        );
        $stmt->execute([
            ':u' => $userId,
            ':c' => $credentialId,
            ':k' => $pem,
            ':n' => $count,
            ':name' => mb_substr($name, 0, 100),
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function markUsed(int $id, int $count): void
    {
        Database::pdo()->prepare('UPDATE user_passkeys SET sign_count = :n, last_used_at = NOW() WHERE id = :id')
            ->execute([':n' => $count, ':id' => $id]);
    }

    public static function delete(int $userId, int $id): bool
    {
        $stmt = Database::pdo()->prepare('DELETE FROM user_passkeys WHERE id = :id AND user_id = :u');
        $stmt->execute([':id' => $id, ':u' => $userId]);

        return $stmt->rowCount() > 0;
    }
}
