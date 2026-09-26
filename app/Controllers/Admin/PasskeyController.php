<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Logger;
use App\Core\Password;
use App\Core\WebAuthn\CoseKey;
use App\Core\WebAuthn\WebAuthn;
use App\Models\AuditLog;
use App\Models\Passkey;
use App\Models\User;

/**
 * Ключи доступа (passkeys) — третий канал второго фактора: регистрация в
 * профиле и подтверждение входа. Вызовы navigator.credentials делает
 * admin-passkey.js; сюда приходят поля формы в base64url, CSRF — как у
 * обычной формы.
 */
final class PasskeyController
{
    /** Параметры navigator.credentials.create() для текущего пользователя. */
    public function registerOptions(): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $user = User::findById((int) Auth::id());
        if ($user === null || WebAuthn::rpId() === '') {
            $this->json(['error' => 'Адрес сайта (app.url) не задан — ключ не к чему привязать.'], 409);
        }

        $exclude = [];
        foreach (Passkey::forUser((int) $user['id']) as $key) {
            $exclude[] = ['type' => 'public-key', 'id' => (string) $key['credential_id']];
        }

        $this->json([
            'challenge' => WebAuthn::b64url(WebAuthn::challenge('register')),
            'rp' => ['id' => WebAuthn::rpId(), 'name' => \App\Core\AdminBrand::name()],
            'user' => [
                // Непрозрачный стабильный идентификатор: не логин и не e-mail.
                'id' => WebAuthn::b64url(hash('sha256', 'passkey-user:' . (int) $user['id'], true)),
                'name' => (string) $user['username'],
                'displayName' => (string) $user['username'],
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => CoseKey::ALG_ES256],
                ['type' => 'public-key', 'alg' => CoseKey::ALG_RS256],
            ],
            'authenticatorSelection' => ['residentKey' => 'preferred', 'userVerification' => 'preferred'],
            'attestation' => 'none',
            'excludeCredentials' => $exclude,
            'timeout' => 60000,
        ]);
    }

    /**
     * Сохранение ключа — с подтверждением паролем, как у приложения-
     * аутентификатора: угнанная сессия не должна привязать свой ключ.
     */
    public function register(): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $userId = (int) Auth::id();
        $user = User::findById($userId);
        $challenge = WebAuthn::takeChallenge('register');
        if ($user === null || !Password::verify((string) ($_POST['password'] ?? ''), (string) $user['password_hash'])) {
            $this->json(['error' => 'Неверный пароль. Ключ не добавлен.'], 403);
        }

        try {
            if ($challenge === null) {
                throw new \InvalidArgumentException('Запрос устарел — попробуйте ещё раз.');
            }
            $credential = WebAuthn::verifyRegistration(
                WebAuthn::unb64url((string) ($_POST['client_data'] ?? '')),
                WebAuthn::unb64url((string) ($_POST['attestation'] ?? '')),
                $challenge,
                WebAuthn::origin(),
                WebAuthn::rpId()
            );
            if (Passkey::find($userId, $credential['id']) !== null) {
                throw new \InvalidArgumentException('Этот ключ уже добавлен.');
            }
        } catch (\InvalidArgumentException $e) {
            $this->json(['error' => 'Ключ не добавлен: ' . $e->getMessage()], 422);
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        Passkey::create($userId, $credential['id'], $credential['pem'], $credential['count'], $name !== '' ? $name : 'Ключ доступа');

        $updated = User::findById($userId);
        if ($updated !== null) {
            Auth::syncTwoFactorSetup($updated);
        }
        Logger::security('Добавлен ключ доступа', ['user' => (string) $user['username'], 'ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
        Flash::success('Ключ доступа добавлен. При входе его можно использовать вместо кода.');
        $this->json(['ok' => true]);
    }

    /** Удаление ключа — с паролем: это ослабляет защиту входа. */
    public function delete(): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $userId = (int) Auth::id();
        $user = User::findById($userId);
        if ($user === null || !Password::verify((string) ($_POST['password'] ?? ''), (string) $user['password_hash'])) {
            Flash::error('Неверный пароль. Ключ не удалён.');
        } elseif (Passkey::delete($userId, (int) ($_POST['id'] ?? 0))) {
            $updated = User::findById($userId);
            if ($updated !== null) {
                Auth::syncTwoFactorSetup($updated);
            }
            Logger::security('Удалён ключ доступа', ['user' => (string) $user['username'], 'ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
            Flash::success('Ключ доступа удалён.');
        }
        header('Location: /admin/profile');
        exit;
    }

    /** Параметры navigator.credentials.get() для ожидающего входа. */
    public function loginOptions(): void
    {
        Csrf::verifyRequest();

        $options = Auth::passkeyLoginOptions();
        if ($options === null) {
            $this->json(['error' => 'Вход устарел — введите пароль ещё раз.'], 409);
        }
        $this->json($options);
    }

    public function login(): void
    {
        Csrf::verifyRequest();

        $pendingId = Auth::pendingUserId();
        $pendingUser = $pendingId !== null ? User::findById($pendingId) : null;
        $ok = Auth::completePasskey(
            (string) ($_POST['id'] ?? ''),
            WebAuthn::unb64url((string) ($_POST['client_data'] ?? '')),
            WebAuthn::unb64url((string) ($_POST['authenticator_data'] ?? '')),
            WebAuthn::unb64url((string) ($_POST['signature'] ?? ''))
        );
        if ($ok) {
            AuditLog::auth('2fa.passkey', (int) ($_SESSION['user_id'] ?? 0) ?: null, (string) ($_SESSION['username'] ?? ''));
            $this->json(['ok' => true, 'redirect' => '/admin']);
        }
        AuditLog::auth('2fa.passkey-failed', $pendingId, (string) ($pendingUser['username'] ?? ''));
        $this->json(['error' => 'Ключ не подошёл. Попробуйте ещё раз или введите код.'], 422);
    }

    /** @param array<string, mixed> $payload */
    private function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
