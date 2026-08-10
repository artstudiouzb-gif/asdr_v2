<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Собирает зависимости (JS/CSS) блоков страницы и выводит каждую строго один
 * раз внизу страницы. Если на странице несколько одинаковых блоков (например,
 * три слайдера), их общий скрипт подключается однократно.
 */
final class AssetCollector
{
    /** @var array<string, bool> */
    private static array $js = [];

    /** @var array<string, string> ключ ассета -> путь к файлу */
    private static array $css = [];

    /** Известные ассеты блоков: ключ -> путь к файлу. */
    private const JS_MAP = [
        'slider' => '/assets/js/blocks/slider.js',
        'anchor_nav' => '/assets/js/blocks/anchor_nav.js',
        'news' => '/assets/js/news.js',
        'u30_report' => '/assets/js/blocks/u30_report.js',
    ];

    /**
     * Стили блоков, слишком объёмные для общего public.min.css: подключаются
     * только на страницах, где такой блок действительно есть.
     */
    private const CSS_MAP = [
        'u30_report' => '/assets/css/blocks/u30-report.css',
    ];

    public static function requireJs(string $key): void
    {
        if (isset(self::JS_MAP[$key])) {
            self::$js[$key] = true;
        }
        // Стили и скрипт блока адресуются одним ключом — типом блока.
        if (isset(self::CSS_MAP[$key])) {
            self::$css[$key] = self::CSS_MAP[$key];
        }
    }

    public static function requireCss(string $key, string $href): void
    {
        self::$css[$key] = $href;
    }

    public static function reset(): void
    {
        self::$js = [];
        self::$css = [];
    }

    public static function renderScripts(): string
    {
        $html = '';
        foreach (array_keys(self::$js) as $key) {
            $src = Asset::url(FrontendAssets::blockAsset(self::JS_MAP[$key]));
            $html .= '<script src="' . htmlspecialchars($src, ENT_QUOTES) . '" defer></script>' . "\n";
        }

        return $html;
    }

    public static function renderStyles(): string
    {
        $html = '';
        foreach (self::$css as $href) {
            $url = Asset::url(FrontendAssets::blockAsset((string) $href));
            $html .= '<link rel="stylesheet" href="' . htmlspecialchars($url, ENT_QUOTES) . '" data-block-css>' . "\n";
        }

        return $html;
    }
}
