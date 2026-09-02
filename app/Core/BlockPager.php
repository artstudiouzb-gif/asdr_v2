<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Постраничный вывод внутри блока страницы-конструктора.
 *
 * Блок живёт на обычной странице, поэтому номер страницы приходит отдельным
 * параметром адреса (`?mpage=2`), а не общим `page`: иначе полоса страниц
 * блока конфликтовала бы с пагинацией самого раздела, если страница назначена
 * шапкой ленты новостей.
 *
 * Ссылка полосы ведёт на якорь блока — без него переход на вторую страницу
 * выбрасывал бы читателя в начало длинной страницы, к чужому содержимому.
 *
 * Номер ограничен сверху (MAX_PAGE): он участвует в решении о кэше страницы,
 * и обходчик с `?mpage=100000` не должен превращаться в бесконечную работу.
 */
final class BlockPager
{
    public const PARAM = 'mpage';

    public const MAX_PAGE = 200;

    /** Запрошенная страница блока: 1, если параметра нет или он мусорный. */
    public static function current(): int
    {
        $raw = $_GET[self::PARAM] ?? null;
        if (!is_string($raw) && !is_int($raw)) {
            return 1;
        }
        $page = (int) $raw;

        return max(1, min(self::MAX_PAGE, $page));
    }

    /** Адрес страницы блока: текущий путь + номер + якорь самого блока. */
    public static function url(int $page, int $blockId): string
    {
        $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
        // Путь пришёл из запроса: «//example.com/…» дало бы ссылку на чужой
        // домен прямо в полосе страниц.
        if (!UrlGuard::isSafeLink($path) || !str_starts_with($path, '/')) {
            $path = '/';
        }
        $page = max(1, min(self::MAX_PAGE, $page));
        $query = $page > 1 ? '?' . http_build_query([self::PARAM => $page]) : '';

        return $path . $query . ($blockId > 0 ? '#block-' . $blockId : '');
    }

    /**
     * Разбивка на страницы для блока.
     *
     * @return array{page:int, pages:int, offset:int, per_page:int}
     */
    public static function slice(int $total, int $perPage): array
    {
        $perPage = max(1, $perPage);
        $pages = max(1, (int) ceil(max(0, $total) / $perPage));
        $page = min(self::current(), $pages);

        return [
            'page' => $page,
            'pages' => $pages,
            'offset' => ($page - 1) * $perPage,
            'per_page' => $perPage,
        ];
    }
}
