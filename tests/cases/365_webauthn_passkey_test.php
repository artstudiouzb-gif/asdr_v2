<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\WebAuthn\Cbor;
use App\Core\WebAuthn\WebAuthn;
use App\Models\Passkey;

/*
 * Ключи доступа (WebAuthn) без библиотек: тест играет роль аутентификатора —
 * сам собирает attestationObject/authenticatorData в CBOR и подписывает их
 * ключом OpenSSL, а сервер проверяет всё, что проверил бы с настоящим ключом.
 */

function wa_cbor(mixed $value): string
{
    $head = static function (int $major, int $length): string {
        if ($length < 24) {
            return chr(($major << 5) | $length);
        }
        if ($length < 256) {
            return chr(($major << 5) | 24) . chr($length);
        }

        return chr(($major << 5) | 25) . pack('n', $length);
    };
    if (is_int($value)) {
        return $value >= 0 ? $head(0, $value) : $head(1, -1 - $value);
    }
    if ($value instanceof WaText) {
        return $head(3, strlen($value->text)) . $value->text;
    }
    if (is_string($value)) {
        return $head(2, strlen($value)) . $value;
    }
    if (is_array($value)) {
        $out = $head(5, count($value));
        foreach ($value as $key => $item) {
            $out .= wa_cbor(is_string($key) ? new WaText($key) : $key) . wa_cbor($item);
        }

        return $out;
    }
    throw new InvalidArgumentException('тип не нужен тесту');
}

final class WaText
{
    public function __construct(public string $text)
    {
    }
}

/** @return array{private: OpenSSLAsymmetricKey, cose: array<int, mixed>} */
function wa_key(string $type = 'ec'): array
{
    if ($type === 'ec') {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        $ec = openssl_pkey_get_details($key)['ec'];
        $cose = [1 => 2, 3 => -7, -1 => 1, -2 => str_pad($ec['x'], 32, "\0", STR_PAD_LEFT), -3 => str_pad($ec['y'], 32, "\0", STR_PAD_LEFT)];
    } else {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        $rsa = openssl_pkey_get_details($key)['rsa'];
        $cose = [1 => 3, 3 => -257, -1 => $rsa['n'], -2 => $rsa['e']];
    }

    return ['private' => $key, 'cose' => $cose];
}

function wa_client(string $type, string $challenge, string $origin = 'https://asdr.test'): string
{
    return json_encode(['type' => $type, 'challenge' => WebAuthn::b64url($challenge), 'origin' => $origin, 'crossOrigin' => false], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
}

function wa_auth_data(string $rpId, int $count, int $flags, string $attested = ''): string
{
    return hash('sha256', $rpId, true) . chr($flags) . pack('N', $count) . $attested;
}

/** @return array{client: string, attestation: string, id: string} */
function wa_register(array $key, string $challenge, string $rpId = 'asdr.test', string $origin = 'https://asdr.test'): array
{
    $id = random_bytes(16);
    $attested = str_repeat("\0", 16) . pack('n', strlen($id)) . $id . wa_cbor($key['cose']);
    $authData = wa_auth_data($rpId, 0, 0x41, $attested);

    return [
        'client' => wa_client('webauthn.create', $challenge, $origin),
        'attestation' => wa_cbor(['fmt' => new WaText('none'), 'attStmt' => [], 'authData' => $authData]),
        'id' => WebAuthn::b64url($id),
    ];
}

/** @return array{client: string, auth: string, signature: string} */
function wa_assert(array $key, string $challenge, int $count, string $rpId = 'asdr.test'): array
{
    $client = wa_client('webauthn.get', $challenge);
    $auth = wa_auth_data($rpId, $count, 0x01);
    openssl_sign($auth . hash('sha256', $client, true), $signature, $key['private'], OPENSSL_ALGO_SHA256);

    return ['client' => $client, 'auth' => $auth, 'signature' => $signature];
}

function wa_rejects(callable $fn, string $message): void
{
    try {
        $fn();
    } catch (InvalidArgumentException) {
        return;
    }
    throw new RuntimeException('ожидался отказ: ' . $message);
}

test('Ключ доступа ES256 и RS256: регистрация и вход проверяются подписью', function (): void {
    foreach (['ec', 'rsa'] as $type) {
        $key = wa_key($type);
        $challenge = random_bytes(32);
        $reg = wa_register($key, $challenge);
        $credential = WebAuthn::verifyRegistration($reg['client'], $reg['attestation'], $challenge, 'https://asdr.test', 'asdr.test');
        assert_same($reg['id'], $credential['id'], $type);
        assert_contains('BEGIN PUBLIC KEY', $credential['pem']);

        $login = random_bytes(32);
        $a = wa_assert($key, $login, 5);
        $count = WebAuthn::verifyAssertion($a['client'], $a['auth'], $a['signature'], $login, 'https://asdr.test', 'asdr.test', $credential['pem'], 0);
        assert_same(5, $count, $type);
    }
});

test('Ключ доступа: чужой сайт, чужой challenge, подмена и откат счётчика отклоняются', function (): void {
    $key = wa_key();
    $challenge = random_bytes(32);
    $reg = wa_register($key, $challenge);
    $pem = WebAuthn::verifyRegistration($reg['client'], $reg['attestation'], $challenge, 'https://asdr.test', 'asdr.test')['pem'];

    wa_rejects(fn () => WebAuthn::verifyRegistration($reg['client'], $reg['attestation'], random_bytes(32), 'https://asdr.test', 'asdr.test'), 'чужой challenge');
    wa_rejects(fn () => WebAuthn::verifyRegistration($reg['client'], $reg['attestation'], $challenge, 'https://evil.test', 'asdr.test'), 'чужой origin');
    wa_rejects(fn () => WebAuthn::verifyRegistration($reg['client'], $reg['attestation'], $challenge, 'https://asdr.test', 'evil.test'), 'чужой rpId');

    $login = random_bytes(32);
    $a = wa_assert($key, $login, 7);
    $verify = fn (array $x, int $stored = 0, string $c = '') => WebAuthn::verifyAssertion($x['client'], $x['auth'], $x['signature'], $c !== '' ? $c : $login, 'https://asdr.test', 'asdr.test', $pem, $stored);

    wa_rejects(fn () => $verify($a, 7), 'счётчик не вырос — копия ключа');
    wa_rejects(fn () => $verify(['signature' => strrev($a['signature'])] + $a), 'испорченная подпись');
    wa_rejects(fn () => $verify(['auth' => wa_auth_data('asdr.test', 8, 0x01)] + $a), 'подписаны другие данные');
    wa_rejects(fn () => $verify(wa_assert($key, $login, 8, 'evil.test')), 'подпись для другого сайта');
    wa_rejects(fn () => $verify($a, 0, random_bytes(32)), 'challenge другого входа');
    wa_rejects(fn () => $verify(wa_assert(wa_key(), $login, 9)), 'подпись чужим ключом');

    $noPresence = wa_assert($key, $login, 9);
    $noPresence['auth'][32] = chr(0);
    wa_rejects(fn () => $verify($noPresence), 'без присутствия пользователя');

    $create = $a;
    $create['client'] = wa_client('webauthn.create', $login);
    wa_rejects(fn () => $verify($create), 'ответ регистрации вместо входа');
});

test('CBOR: обрыв, неопределённая длина и глубокая вложенность отклоняются', function (): void {
    assert_same([1 => 2, 'a' => 'b'], Cbor::decode(wa_cbor([1 => 2, 'a' => new WaText('b')])));
    wa_rejects(fn () => Cbor::decode("\x58\x10abc"), 'строка длиннее данных');
    wa_rejects(fn () => Cbor::decode("\x9f\x01\xff"), 'неопределённая длина');
    wa_rejects(fn () => Cbor::decode(str_repeat("\x81", 40) . "\x01"), 'вложенность');
    wa_rejects(fn () => Cbor::decode(''), 'пустые данные');
});

test('Challenge одноразовый и живёт в сессии', function (): void {
    @session_start();
    $_SESSION = [];
    $challenge = WebAuthn::challenge('register');
    assert_same($challenge, WebAuthn::takeChallenge('register'));
    assert_same(null, WebAuthn::takeChallenge('register'), 'второй раз тот же challenge не выдаётся');
    assert_same(null, WebAuthn::takeChallenge('login'), 'challenge другой цели не подходит');
});

test('Вход с ключом доступа: пароль, затем подпись — полная сессия (БД)', function (): void {
    if ((string) (getenv('TEST_DB_DATABASE') ?: '') === '') {
        skip_test('TEST_DB_* не заданы');
    }
    $previousUrl = \App\Core\Config::get('app.url');
    \App\Core\Config::merge(['app' => ['url' => 'https://asdr.test']]);
    @session_start();
    $_SESSION = [];
    $_SERVER['REMOTE_ADDR'] = '10.0.0.65';

    try {
        $pdo = \App\Core\Database::pdo();
        $pdo->prepare('DELETE FROM users WHERE username = ?')->execute(['passkey_admin']);
        $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)')
            ->execute(['passkey_admin', 'passkey@example.com', password_hash('Str0ng-Pass!2026', PASSWORD_DEFAULT), 'admin']);
        $userId = (int) $pdo->lastInsertId();

        $key = wa_key();
        $challenge = random_bytes(32);
        $reg = wa_register($key, $challenge);
        $credential = WebAuthn::verifyRegistration($reg['client'], $reg['attestation'], $challenge, WebAuthn::origin(), WebAuthn::rpId());
        Passkey::create($userId, $credential['id'], $credential['pem'], 0, 'Тестовый ключ');

        // Единственный фактор — ключ: вход просит подтверждение, а не онбординг.
        assert_same('needs_code', Auth::attemptLogin('passkey_admin', 'Str0ng-Pass!2026')['status']);
        assert_true(Auth::pendingChannels()['passkey']);
        assert_false(Auth::completePasskey($credential['id'], '{}', '', ''), 'мусор вместо подписи не открывает сессию');

        $options = Auth::passkeyLoginOptions();
        assert_true(is_array($options));
        assert_same($credential['id'], $options['allowCredentials'][0]['id']);
        $a = wa_assert($key, WebAuthn::unb64url($options['challenge']), 1);
        assert_true(Auth::completePasskey($credential['id'], $a['client'], $a['auth'], $a['signature']), 'подпись ключа должна открыть сессию');
        assert_true(Auth::check());
        assert_false(Auth::requiresTwoFactorSetup());
        assert_same(1, (int) Passkey::forUser($userId)[0]['sign_count'], 'счётчик сохранён');

        // Повтор той же подписи не сработает: challenge уже израсходован.
        $_SESSION['pending_user_id'] = $userId;
        $_SESSION['pending_since'] = time();
        $_SESSION['pending_passkey'] = true;
        assert_false(Auth::completePasskey($credential['id'], $a['client'], $a['auth'], $a['signature']));
    } finally {
        \App\Core\Database::pdo()->prepare('DELETE FROM users WHERE username = ?')->execute(['passkey_admin']);
        \App\Core\Config::merge(['app' => ['url' => $previousUrl]]);
        \App\Core\RateLimiter::clearAttempts('10.0.0.65|2fa|passkey_admin');
    }
});

test('Ключ доступа удаляется только своим владельцем (БД)', function (): void {
    if ((string) (getenv('TEST_DB_DATABASE') ?: '') === '') {
        skip_test('TEST_DB_* не заданы');
    }
    $pdo = \App\Core\Database::pdo();
    $pdo->prepare('DELETE FROM users WHERE username IN (?, ?)')->execute(['pk_owner', 'pk_other']);
    $ids = [];
    foreach (['pk_owner', 'pk_other'] as $name) {
        $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)')
            ->execute([$name, $name . '@example.com', 'x', 'editor']);
        $ids[$name] = (int) $pdo->lastInsertId();
    }
    $keyId = Passkey::create($ids['pk_owner'], 'cred-' . bin2hex(random_bytes(4)), 'pem', 0, 'k');

    assert_false(Passkey::delete($ids['pk_other'], $keyId), 'чужой ключ удалять нельзя');
    assert_same(1, Passkey::countForUser($ids['pk_owner']));
    assert_true(Passkey::delete($ids['pk_owner'], $keyId));
    assert_same(0, Passkey::countForUser($ids['pk_owner']));

    $pdo->prepare('DELETE FROM users WHERE username IN (?, ?)')->execute(['pk_owner', 'pk_other']);
});
