<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;
use Throwable;

/**
 * W3C WebAuthn / Passkeys Engine (Биометрическая аутентификация без паролей).
 * Поддерживает Touch ID, Face ID, Windows Hello и аппаратные ключи FIDO2/U2F.
 */
final class WebAuthn
{
    /**
     * Генерирует параметры запроса для navigator.credentials.create()
     *
     * @param array{id: int, username: string} $user
     * @return array<string, mixed>
     */
    public static function generateRegisterOptions(array $user): array
    {
        $challenge = random_bytes(32);
        Session::start();
        $_SESSION['webauthn_register_challenge'] = base64_encode($challenge);
        $_SESSION['webauthn_register_user_id'] = (int) $user['id'];

        $rpId = parse_url($_SERVER['HTTP_HOST'] ?? 'localhost', PHP_URL_HOST)
            ?: ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $rpId = preg_replace('/:\d+$/', '', $rpId);

        return [
            'challenge' => self::base64UrlEncode($challenge),
            'rp' => [
                'name' => (string) Config::get('app.name', 'ASDR CMS'),
                'id' => $rpId,
            ],
            'user' => [
                'id' => self::base64UrlEncode((string) $user['id']),
                'name' => (string) $user['username'],
                'displayName' => (string) ($user['name'] ?? $user['username']),
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],  // ES256 (ECDSA w/ SHA-256)
                ['type' => 'public-key', 'alg' => -257], // RS256 (RSA w/ SHA-256)
            ],
            'authenticatorSelection' => [
                'userVerification' => 'preferred',
                'residentKey' => 'preferred',
            ],
            'timeout' => 60000,
            'attestation' => 'none',
        ];
    }

    /**
     * Регистрирует новый Passkey в БД после подтверждения браузером.
     */
    public static function registerPasskey(int $userId, string $credentialId, string $publicKey, string $deviceName = 'Passkey Device'): bool
    {
        if (!Database::isConnected()) {
            return false;
        }

        try {
            $stmt = Database::pdo()->prepare(
                'INSERT INTO user_passkeys (user_id, credential_id, public_key, device_name, created_at)
                 VALUES (:user_id, :credential_id, :public_key, :device_name, NOW())
                 ON DUPLICATE KEY UPDATE public_key = VALUES(public_key), device_name = VALUES(device_name)'
            );
            return $stmt->execute([
                ':user_id' => $userId,
                ':credential_id' => $credentialId,
                ':public_key' => $publicKey,
                ':device_name' => mb_substr($deviceName, 0, 100),
            ]);
        } catch (Throwable $e) {
            Logger::error('WebAuthn::registerPasskey failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Генерирует параметры запроса для navigator.credentials.get() (Вход).
     *
     * @return array<string, mixed>
     */
    public static function generateLoginOptions(): array
    {
        $challenge = random_bytes(32);
        Session::start();
        $_SESSION['webauthn_login_challenge'] = base64_encode($challenge);

        $rpId = parse_url($_SERVER['HTTP_HOST'] ?? 'localhost', PHP_URL_HOST)
            ?: ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $rpId = preg_replace('/:\d+$/', '', $rpId);

        return [
            'challenge' => self::base64UrlEncode($challenge),
            'rpId' => $rpId,
            'userVerification' => 'preferred',
            'timeout' => 60000,
        ];
    }

    /**
     * Проверяет Passkey и возвращает пользователя при успешном входе.
     *
     * @return array<string, mixed>|null
     */
    public static function authenticate(string $credentialId): ?array
    {
        if (!Database::isConnected()) {
            return null;
        }

        try {
            $stmt = Database::pdo()->prepare(
                'SELECT u.*, p.id AS passkey_id, p.counter
                 FROM user_passkeys p
                 JOIN users u ON u.id = p.user_id
                 WHERE p.credential_id = :cred_id AND u.status = 1
                 LIMIT 1'
            );
            $stmt->execute([':cred_id' => $credentialId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return null;
            }

            // Обновляем время последнего использования ключа
            $update = Database::pdo()->prepare('UPDATE user_passkeys SET last_used_at = NOW() WHERE id = :id');
            $update->execute([':id' => $user['passkey_id']]);

            return $user;
        } catch (Throwable $e) {
            Logger::error('WebAuthn::authenticate failed: ' . $e->getMessage());
            return null;
        }
    }

    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }
}
