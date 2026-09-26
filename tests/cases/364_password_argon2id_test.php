<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Password;
use App\Models\User;

/*
 * Пароли хешируются Argon2id (bcrypt — запасной вариант без поддержки в
 * PHP). Старые bcrypt-хеши не ломают вход и меняются на Argon2id при первом
 * успешном входе; неверный пароль хеш не трогает.
 */
test('Новый хеш пароля — Argon2id, bcrypt считается устаревшим', function (): void {
    if (!defined('PASSWORD_ARGON2ID')) {
        skip_test('PHP собран без Argon2id');
    }
    $hash = Password::hash('Str0ng-Pass!2026');
    assert_true(str_starts_with($hash, '$argon2id$'), 'ожидался Argon2id: ' . substr($hash, 0, 12));
    assert_true(Password::verify('Str0ng-Pass!2026', $hash));
    assert_false(Password::verify('wrong', $hash));
    assert_false(Password::needsRehash($hash));

    $bcrypt = password_hash('Str0ng-Pass!2026', PASSWORD_BCRYPT, ['cost' => 12]);
    assert_true(Password::verify('Str0ng-Pass!2026', $bcrypt), 'старый хеш по-прежнему принимается');
    $upgraded = Password::upgrade('Str0ng-Pass!2026', $bcrypt);
    assert_true(is_string($upgraded) && str_starts_with($upgraded, '$argon2id$'));
    assert_same(null, Password::upgrade('Str0ng-Pass!2026', $hash), 'свежий хеш не перехешируется');
});

test('Проверка без пользователя отвечает отказом', function (): void {
    assert_false(Password::verify('anything', null));
    assert_false(Password::verify('anything', ''));
});

test('Хеши паролей считает только Password', function (): void {
    // Прямой password_hash() с устаревшими параметрами — то, от чего ушли.
    foreach (['app/Models/User.php', 'app/Models/RepoUser.php', 'app/Core/Auth.php', 'app/Core/RepoAuth.php'] as $file) {
        $src = (string) file_get_contents(APP_ROOT . '/' . $file);
        assert_not_contains('password_hash(', $src, $file);
        assert_not_contains('PASSWORD_BCRYPT', $src, $file);
    }
});

test('Вход перехеширует bcrypt в Argon2id, неверный пароль хеш не трогает (БД)', function (): void {
    if ((string) (getenv('TEST_DB_DATABASE') ?: '') === '') {
        skip_test('TEST_DB_* не заданы');
    }
    if (!defined('PASSWORD_ARGON2ID')) {
        skip_test('PHP собран без Argon2id');
    }
    @session_start();
    $_SESSION = [];
    $_SERVER['REMOTE_ADDR'] = '10.0.0.64';

    $pdo = \App\Core\Database::pdo();
    $pdo->prepare('DELETE FROM users WHERE username = ?')->execute(['argon_admin']);
    $bcrypt = password_hash('Str0ng-Pass!2026', PASSWORD_BCRYPT, ['cost' => 10]);
    $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)')
        ->execute(['argon_admin', 'argon@example.com', $bcrypt, 'admin']);

    $stored = static function (): string {
        $stmt = \App\Core\Database::pdo()->prepare('SELECT password_hash FROM users WHERE username = ?');
        $stmt->execute(['argon_admin']);

        return (string) $stmt->fetchColumn();
    };

    assert_same('invalid', Auth::attemptLogin('argon_admin', 'wrong-pass')['status']);
    assert_same($bcrypt, $stored(), 'неверный пароль не должен менять хеш');

    assert_same('setup_required', Auth::attemptLogin('argon_admin', 'Str0ng-Pass!2026')['status']);
    $after = $stored();
    assert_true(str_starts_with($after, '$argon2id$'), 'после входа хеш — Argon2id');
    assert_true(password_verify('Str0ng-Pass!2026', $after), 'пароль прежний');

    User::forgetCache();
    $pdo->prepare('DELETE FROM users WHERE username = ?')->execute(['argon_admin']);
});
