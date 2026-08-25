<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Драйверные константы PDO без привязки к версии PHP.
 *
 * В PHP 8.4 у драйверов появились свои подклассы (`Pdo\Mysql`), а в 8.5 старые
 * константы вида `PDO::MYSQL_ATTR_USE_BUFFERED_QUERY` объявлены устаревшими:
 * каждое обращение к ним печатает уведомление. Проект при этом обязан
 * работать и на 8.2, где подклассов ещё нет. Значение у обеих констант
 * одинаковое, поэтому имя выбирается в рантайме, а не при написании кода.
 */
final class PdoAttr
{
    /** @var array<string,int> */
    private static array $cache = [];

    /**
     * @param string $name Имя без приставки — `USE_BUFFERED_QUERY`,
     *                     `MULTI_STATEMENTS`, `INIT_COMMAND` и т. п.
     */
    public static function mysql(string $name): int
    {
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }

        // constant() вместо прямой записи: к устаревшей константе обращаемся
        // только там, где новой ещё нет, иначе уведомление печаталось бы и на
        // 8.5, где нужная константа есть.
        $modern = 'Pdo\\Mysql::ATTR_' . $name;
        $legacy = 'PDO::MYSQL_ATTR_' . $name;
        $const = defined($modern) ? $modern : $legacy;
        if (!defined($const)) {
            throw new \RuntimeException('Неизвестный атрибут драйвера MySQL: ' . $name);
        }

        return self::$cache[$name] = (int) constant($const);
    }
}
