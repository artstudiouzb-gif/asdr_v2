<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Предзагрузка следующей страницы (Speculation Rules API).
 *
 * Сайт серверный: каждая ссылка — новый документ, и переход упирается в то,
 * что документ начинают качать только после клика. `@view-transition` уже
 * включён, но анимации нечего показывать, пока идёт сеть. Правила предзагрузки
 * убирают именно это ожидание: `prefetch` тянет разметку заранее, `prerender`
 * по наведению собирает страницу целиком, и клик открывает готовое.
 *
 * **Правила отдаются файлом, а не инлайновым `<script>`.** Публичная шапка не
 * держит исполняемых инлайн-скриптов даже с nonce (тест 171), и заводить
 * исключение ради предзагрузки не стоит: заголовок `Speculation-Rules` со
 * ссылкой на свой JSON делает ровно то же самое и ничего не ослабляет в CSP —
 * файл свой, `script-src 'self'` его пропускает.
 *
 * **Осторожность важнее охвата.** Предзагрузка выполняет страницу на сервере,
 * поэтому служебные области (админка, портал файлов, установщик, health,
 * смена языка и письменности) в правила не попадают: там за GET стоит
 * действие, а не чтение. `prerender` — только `conservative`, то есть по
 * наведению или нажатию: `eager` тянул бы все ссылки страницы разом, а это
 * госсайт с мобильным трафиком.
 */
final class Speculation
{
    /** Адрес файла правил (он же — значение заголовка). */
    public const RULES_PATH = '/speculation-rules.json';

    /**
     * Пути, которые предзагружать нельзя: за GET там стоит действие
     * (смена языка, выход, машинный ответ), а не показ страницы.
     *
     * @var list<string>
     */
    public const EXCLUDED = [
        '/admin', '/repo', '/install', '/health', '/search', '/push',
        '/unsubscribe', '/opendata', '/download.php', '/_vitals',
        '/captcha.png', '/script', '/goals', '/logout', '/manifest.webmanifest',
    ];

    /**
     * Спекулятивный ли это запрос (prefetch или prerender).
     *
     * Браузер помечает такие запросы заголовком `Sec-Purpose: prefetch` —
     * у prerender значение `prefetch;prerender`, поэтому проверяем вхождение,
     * а не равенство.
     */
    public static function isSpeculative(): bool
    {
        $purpose = strtolower(trim((string) ($_SERVER['HTTP_SEC_PURPOSE'] ?? '')));

        return $purpose !== '' && str_contains($purpose, 'prefetch');
    }

    /**
     * Заголовок документа со ссылкой на правила.
     *
     * Значение — строка в кавычках по RFC 8941 (structured field), иначе
     * браузер молча не примет ссылку.
     */
    public static function sendHeader(): void
    {
        if (headers_sent()) {
            return;
        }

        header('Speculation-Rules: "' . self::RULES_PATH . '"');
    }

    /**
     * Правила предзагрузки.
     *
     * @param list<string> $langPrefixes коды языков с префиксом в адресе
     * @return array<string, list<array<string, mixed>>>
     */
    public static function rules(array $langPrefixes = []): array
    {
        $where = self::where($langPrefixes);

        return [
            'prefetch' => [[
                'where' => $where,
                'eagerness' => 'moderate',
            ]],
            'prerender' => [[
                'where' => $where,
                'eagerness' => 'conservative',
            ]],
        ];
    }

    /** @param list<string> $langPrefixes */
    public static function json(array $langPrefixes = []): string
    {
        return (string) json_encode(
            self::rules($langPrefixes),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );
    }

    /**
     * Условие отбора ссылок: свои страницы, кроме служебных, кроме файлов и
     * ссылок в новую вкладку — предзагружать то, что откроется не здесь,
     * значит платить трафиком ни за что.
     *
     * Языковые префиксы перечисляются явно, а не подставляются шаблоном:
     * `/uz/script/latn` — тот же переключатель письменности, что и
     * `/script/latn`, и попасть в предзагрузку он не должен. Угадывать
     * префикс шаблоном URLPattern значило бы полагаться на то, как он
     * трактует `*` между сегментами.
     *
     * @param list<string> $langPrefixes
     * @return array<string, mixed>
     */
    private static function where(array $langPrefixes = []): array
    {
        $prefixes = [''];
        foreach ($langPrefixes as $code) {
            $code = trim($code, '/');
            if ($code !== '' && preg_match('/^[a-z]{2,8}(-[a-z0-9]{2,8})*$/i', $code) === 1) {
                $prefixes[] = '/' . strtolower($code);
            }
        }

        $excluded = [];
        foreach ($prefixes as $prefix) {
            foreach (self::EXCLUDED as $path) {
                $excluded[] = $prefix . $path;
                $excluded[] = $prefix . $path . '/*';
            }
        }

        return [
            'and' => [
                ['href_matches' => '/*'],
                ['not' => ['href_matches' => $excluded]],
                ['not' => ['selector_matches' => '[download], [target="_blank"], [rel~="nofollow"], .no-speculation']],
            ],
        ];
    }
}
