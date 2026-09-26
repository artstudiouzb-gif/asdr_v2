<?php

declare(strict_types=1);

namespace App\Core\WebAuthn;

use App\Core\AppUrl;
use App\Core\Session;

/**
 * Проверяющая сторона WebAuthn (ключи доступа, passkeys) без библиотек.
 *
 * Регистрация: challenge, тип и origin из clientDataJSON, хеш rpId, флаги
 * присутствия пользователя и данных ключа в authenticatorData, COSE-ключ →
 * PEM. Аттестацию не запрашиваем (`attestation: none`) и не проверяем:
 * производитель ключа нам не важен, важно, что ключ тот же при входе.
 *
 * Вход: те же проверки плюс подпись `authData || SHA-256(clientDataJSON)`
 * через openssl_verify и счётчик подписей — его откат выдаёт клон ключа.
 *
 * rpId и origin берутся из `app.url`, а не из заголовка Host запроса.
 */
final class WebAuthn
{
    private const CHALLENGE_TTL = 300;

    private const FLAG_UP = 0x01;
    private const FLAG_AT = 0x40;

    public static function rpId(): string
    {
        return strtolower((string) (parse_url(AppUrl::base(), PHP_URL_HOST) ?: ''));
    }

    public static function origin(): string
    {
        $parts = parse_url(AppUrl::base());
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        return strtolower((string) $parts['scheme']) . '://' . strtolower((string) $parts['host'])
            . (isset($parts['port']) ? ':' . (int) $parts['port'] : '');
    }

    /** Новый одноразовый challenge для цели (регистрация, вход); хранится в сессии. */
    public static function challenge(string $purpose): string
    {
        Session::start();
        $challenge = random_bytes(32);
        $_SESSION['webauthn'][$purpose] = ['c' => self::b64url($challenge), 't' => time()];

        return $challenge;
    }

    /** Забирает challenge цели: второй раз тот же не выдаётся, просроченный — null. */
    public static function takeChallenge(string $purpose): ?string
    {
        Session::start();
        $entry = $_SESSION['webauthn'][$purpose] ?? null;
        unset($_SESSION['webauthn'][$purpose]);
        if (!is_array($entry) || (time() - (int) ($entry['t'] ?? 0)) > self::CHALLENGE_TTL) {
            return null;
        }
        $challenge = self::unb64url((string) ($entry['c'] ?? ''));

        return $challenge === '' ? null : $challenge;
    }

    /**
     * @return array{id: string, pem: string, count: int} id — base64url идентификатора ключа
     */
    public static function verifyRegistration(string $clientDataJson, string $attestationObject, string $challenge, string $origin, string $rpId): array
    {
        self::checkClientData($clientDataJson, 'webauthn.create', $challenge, $origin);

        $offset = 0;
        $attestation = Cbor::decode($attestationObject, $offset);
        if (!is_array($attestation) || !is_string($attestation['authData'] ?? null)) {
            throw new \InvalidArgumentException('Нет authData в ответе ключа');
        }
        $authData = $attestation['authData'];
        $count = self::checkAuthData($authData, $rpId);
        if ((ord($authData[32]) & self::FLAG_AT) === 0 || strlen($authData) < 55) {
            throw new \InvalidArgumentException('Ключ не передал свои данные');
        }

        $idLength = self::uint(substr($authData, 53, 2));
        $credentialId = substr($authData, 55, $idLength);
        if ($idLength === 0 || $idLength > 1023 || strlen($credentialId) !== $idLength) {
            throw new \InvalidArgumentException('Неверная длина идентификатора ключа');
        }
        $keyOffset = 55 + $idLength;
        $cose = Cbor::decode($authData, $keyOffset);
        if (!is_array($cose)) {
            throw new \InvalidArgumentException('Открытый ключ не разобран');
        }

        return ['id' => self::b64url($credentialId), 'pem' => CoseKey::toPem($cose), 'count' => $count];
    }

    /** @return int новое значение счётчика подписей */
    public static function verifyAssertion(
        string $clientDataJson,
        string $authenticatorData,
        string $signature,
        string $challenge,
        string $origin,
        string $rpId,
        string $pem,
        int $storedCount
    ): int {
        self::checkClientData($clientDataJson, 'webauthn.get', $challenge, $origin);
        $count = self::checkAuthData($authenticatorData, $rpId);

        $signed = $authenticatorData . hash('sha256', $clientDataJson, true);
        if ($signature === '' || openssl_verify($signed, $signature, $pem, OPENSSL_ALGO_SHA256) !== 1) {
            throw new \InvalidArgumentException('Подпись ключа не сошлась');
        }

        // Ключи, которые счётчик не ведут (синхронизируемые passkeys), всегда
        // присылают 0. Если же счётчик есть, он обязан расти.
        if (($count !== 0 || $storedCount !== 0) && $count <= $storedCount) {
            throw new \InvalidArgumentException('Счётчик подписей не вырос — возможна копия ключа');
        }

        return $count;
    }

    public static function b64url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    public static function unb64url(string $text): string
    {
        if (preg_match('/^[A-Za-z0-9_-]*$/', $text) !== 1) {
            return '';
        }

        return (string) base64_decode(strtr($text, '-_', '+/'), true);
    }

    private static function checkClientData(string $json, string $type, string $challenge, string $origin): void
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException('clientDataJSON не разобран');
        }
        if (($data['type'] ?? null) !== $type) {
            throw new \InvalidArgumentException('Неверный тип операции ключа');
        }
        $got = self::unb64url(is_string($data['challenge'] ?? null) ? $data['challenge'] : '');
        if ($challenge === '' || $got === '' || !hash_equals($challenge, $got)) {
            throw new \InvalidArgumentException('Challenge не совпал');
        }
        if ($origin === '' || !is_string($data['origin'] ?? null) || !hash_equals($origin, $data['origin'])) {
            throw new \InvalidArgumentException('Чужой origin');
        }
        if (($data['crossOrigin'] ?? false) === true) {
            throw new \InvalidArgumentException('Вызов из чужого фрейма');
        }
    }

    /** @return int счётчик подписей из authenticatorData */
    private static function checkAuthData(string $authData, string $rpId): int
    {
        if (strlen($authData) < 37) {
            throw new \InvalidArgumentException('authenticatorData короче минимума');
        }
        if ($rpId === '' || !hash_equals(hash('sha256', $rpId, true), substr($authData, 0, 32))) {
            throw new \InvalidArgumentException('Ключ выдан для другого сайта');
        }
        if ((ord($authData[32]) & self::FLAG_UP) === 0) {
            throw new \InvalidArgumentException('Нет подтверждения присутствия пользователя');
        }

        return self::uint(substr($authData, 33, 4));
    }

    /** Беззнаковое целое big-endian из 2 или 4 байт. */
    private static function uint(string $bytes): int
    {
        $value = 0;
        foreach (str_split($bytes) as $byte) {
            $value = ($value << 8) | ord($byte);
        }

        return $value;
    }
}
