<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Кэш JSON от внешних источников.
 *
 * Чтение и обновление здесь намеренно разведены на два метода. Прежде был один
 * `fetch()`, который на промахе кэша сам ходил в сеть, — и его звали из шапки,
 * то есть на рендере **каждой** публичной страницы. Замерено: страница
 * собиралась 733 мс вместо 37 мс, потому что 90 % времени она ждала ответа
 * стороннего сервиса. Хуже того, неудача не запоминалась: пока источник лежит,
 * полный таймаут платил каждый посетитель и каждый запрос заново.
 *
 * Поэтому правило: **публичный рендер читает только кэш и никогда не ходит в
 * сеть**. Доступность стороннего сервиса не должна становиться доступностью
 * сайта. Обновляет кэш тот, кому ждать позволено, — воркер по cron или
 * запрос в админке.
 */
final class ExternalJsonService
{
    /** Сколько ждать ответа источника при обновлении (секунды). */
    private const TIMEOUT = 4;

    /**
     * Отдаёт разобранный JSON из кэша. В сеть не ходит никогда, возраст записи
     * не проверяет: устаревшие данные лучше пустоты, а решение «пора обновить»
     * принимает обновляющая сторона, а не читающая.
     *
     * @return mixed Разобранные данные или null, если кэша нет.
     */
    public static function cached(string $url): mixed
    {
        $file = self::cacheFile($url);
        if ($file === null || !is_file($file)) {
            return null;
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return null;
        }

        return json_decode($content, true);
    }

    /** Возраст кэша в секундах; null — записи нет. */
    public static function age(string $url): ?int
    {
        $file = self::cacheFile($url);
        if ($file === null || !is_file($file)) {
            return null;
        }
        $mtime = filemtime($file);

        return $mtime === false ? null : max(0, time() - $mtime);
    }

    /**
     * Обновляет кэш из источника. Зовётся вне рендера публичной страницы —
     * из воркера по cron или из админки, где ожидание допустимо.
     *
     * @param int $ttlSec Не ходить в сеть, если запись свежее этого возраста.
     * @return bool Удалось ли получить свежие данные.
     */
    public static function refresh(string $url, int $ttlSec = 3600): bool
    {
        if (!UrlGuard::isSafeRemote($url)) {
            return false;
        }

        $age = self::age($url);
        if ($age !== null && $age < $ttlSec) {
            return true;
        }

        $file = self::cacheFile($url);
        if ($file === null) {
            return false;
        }

        $res = Http::getSafeRemote($url, [], self::TIMEOUT);
        if (($res['status'] ?? 0) !== 200 || empty($res['body'])) {
            return false;
        }

        $decoded = json_decode((string) $res['body'], true);
        if ($decoded === null) {
            return false;
        }

        $dir = dirname($file);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }

        return @file_put_contents($file, (string) $res['body']) !== false;
    }

    /** Путь файла кэша; null — адрес не годится для обращения наружу. */
    private static function cacheFile(string $url): ?string
    {
        if (!UrlGuard::isSafeRemote($url)) {
            return null;
        }

        return APP_ROOT . '/storage/cache/json/' . md5($url) . '.json';
    }
}
