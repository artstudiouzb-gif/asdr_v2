<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Logger;
use App\Core\Session;
use App\Core\WebAuthn;

final class PasskeyController
{
    /**
     * Возвращает JSON-опции для регистрации Passkey в профиле.
     */
    public function registerOptions(): void
    {
        Session::start();
        $user = Auth::user();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $options = WebAuthn::generateRegisterOptions($user);
        header('Content-Type: application/json');
        echo json_encode($options, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Сохраняет новый зарегистрированный Passkey.
     */
    public function registerVerify(): void
    {
        Session::start();
        $user = Auth::user();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $input = json_decode(file_get_contents('php://input') ?: '{}', true);
        $credentialId = (string) ($input['id'] ?? '');
        $deviceName = (string) ($input['deviceName'] ?? 'Passkey ' . date('Y-m-d'));
        $publicKey = (string) ($input['publicKey'] ?? 'fido2_key');

        if ($credentialId === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Missing credential ID']);
            return;
        }

        $success = WebAuthn::registerPasskey((int) $user['id'], $credentialId, $publicKey, $deviceName);

        header('Content-Type: application/json');
        echo json_encode(['success' => $success]);
    }

    /**
     * Возвращает JSON-опции для входа через Passkey.
     */
    public function loginOptions(): void
    {
        Session::start();
        $options = WebAuthn::generateLoginOptions();
        header('Content-Type: application/json');
        echo json_encode($options, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Проверяет Passkey и авторизует пользователя.
     */
    public function loginVerify(): void
    {
        Session::start();
        $input = json_decode(file_get_contents('php://input') ?: '{}', true);
        $credentialId = (string) ($input['id'] ?? '');

        if ($credentialId === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Missing credential ID']);
            return;
        }

        $user = WebAuthn::authenticate($credentialId);
        if (!$user) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid or unknown Passkey']);
            return;
        }

        Auth::establishSession($user);
        Logger::security('Вход через WebAuthn / Passkey', ['user' => $user['username']]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'redirect' => '/admin']);
    }
}
