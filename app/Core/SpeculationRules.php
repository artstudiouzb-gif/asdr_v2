<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Правила предзагрузки (Speculation Rules) публичной части.
 *
 * Браузер сам предзагружает внутреннюю ссылку, на которой посетитель задержал
 * курсор (`eagerness: moderate`), — без JS и без инлайн-скрипта: правила
 * приходят заголовком `Speculation-Rules` отдельным JSON-файлом, которому
 * хватает `script-src 'self'`. Только prefetch, не prerender: пререндер
 * исполнял бы скрипты страницы и счётчики посещений до клика.
 *
 * Исключения совпадают с запасным путём в frontend.js (браузеры без
 * поддержки): служебные области, файлы и ссылки-действия — прежде всего
 * переключатель языка `?_lang=`, чей запрос меняет сохранённый язык.
 */
final class SpeculationRules
{
    public const PATH = '/speculation-rules.json';

    /** @var list<string> пути (URL Pattern), которые не предзагружаются */
    public const EXCLUDED_PATHS = [
        '/admin', '/admin/*', '/repo', '/repo/*', '/install', '/install/*',
        '/api/*', '/_*', '/uploads/*', '/storage/*', '/*.xml', '/*.txt', '/*.pdf', '/*.zip',
    ];

    /** Ссылки-действия и внешние по смыслу: язык, фрагменты, скачивание. */
    public const EXCLUDED_SELECTOR = '[href*="_lang="], [href*="_fragment="], [download], '
        . '[rel~="nofollow"], [target="_blank"], [data-no-prefetch]';

    public static function json(): string
    {
        return json_encode([
            'prefetch' => [[
                'source' => 'document',
                'where' => ['and' => [
                    ['href_matches' => '/*'],
                    ['not' => ['href_matches' => self::EXCLUDED_PATHS]],
                    ['not' => ['selector_matches' => self::EXCLUDED_SELECTOR]],
                ]],
                'eagerness' => 'moderate',
            ]],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /** Нужен ли странице заголовок с правилами: только публичная часть. */
    public static function appliesTo(string $path): bool
    {
        foreach (['/admin', '/repo', '/install', '/api/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return false;
            }
        }

        return true;
    }
}
