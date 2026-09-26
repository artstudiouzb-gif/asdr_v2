<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Хеши паролей админки и портала репозитория.
 *
 * Новый хеш — Argon2id, если PHP собран с ним (libargon2 или libsodium);
 * иначе bcrypt с cost 12. Старые bcrypt-хеши продолжают работать и
 * прозрачно перехешируются при следующем успешном входе (upgrade()).
 *
 * verify() проверяет пароль и тогда, когда пользователя нет: сравнение с
 * заготовленным хешем выравнивает время ответа, и по нему нельзя понять,
 * существует ли логин.
 */
final class Password
{
    private static ?string $dummy = null;

    public static function hash(string $password): string
    {
        return password_hash($password, self::algorithm(), self::options());
    }

    /** @param string|null $hash хеш из БД; null — пользователя нет */
    public static function verify(string $password, ?string $hash): bool
    {
        if ($hash === null || $hash === '') {
            password_verify($password, self::dummyHash());

            return false;
        }

        return password_verify($password, $hash);
    }

    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, self::algorithm(), self::options());
    }

    /**
     * Новый хеш, если прежний сделан устаревшим алгоритмом или параметрами;
     * null — перехешировать не нужно. Зовётся только после успешной проверки,
     * когда открытый пароль известен.
     */
    public static function upgrade(string $password, string $hash): ?string
    {
        return self::needsRehash($hash) ? self::hash($password) : null;
    }

    public static function algorithm(): string
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    }

    /** @return array<string, int> */
    private static function options(): array
    {
        // Параметры Argon2id — умолчания PHP (64 МБ, 4 прохода, 1 поток):
        // укладываются в memory_limit shared-хостинга и дают ~50 мс на вход.
        return self::algorithm() === PASSWORD_BCRYPT ? ['cost' => 12] : [];
    }

    private static function dummyHash(): string
    {
        return self::$dummy ??= self::hash(bin2hex(random_bytes(16)));
    }
}
