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
    ];

    /**
     * Стили самостоятельных блоков — тех, что не являются частью темы и несут
     * свой собственный вид (свёрстанный документ, виджет со своей графикой).
     * Подключаются только на страницах, где такой блок есть, и ПОСЛЕ
     * дизайн-CSS из админки: у такого блока свой язык, настройки сайта его
     * намеренно не переопределяют.
     *
     * Сейчас пусто. Части ТЕМЫ, вынесенные из общего бандла, живут не здесь,
     * а в THEME_PART_MAP ниже — им место в каскаде до настроек админки.
     */
    private const CSS_MAP = [];

    /**
     * Части темы, вынесенные из общего бандла ради страниц, которым они не
     * нужны. От CSS_MAP отличаются местом подключения: сразу после бандла и
     * ДО дизайн-CSS из админки — то есть там же, где эти правила лежали
     * внутри gov-theme.css. Подключи их наравне с блочными стилями, и
     * настройки раздела «Дизайн» проиграли бы им на этих страницах.
     *
     * Ключ — либо тип блока конструктора (тогда подключение происходит само:
     * BlockRenderer отдаёт список типов страницы, PageController прогоняет их
     * через requireJs), либо имя раздела для страниц вне конструктора —
     * такие вызывает контроллер сам.
     *
     * Выносить сюда стоит не всё подряд: правило окупается, когда стилей
     * много И блок редкий. Разрез общего на десятки мелких файлов даёт
     * обратный эффект — каждый файл это ещё один блокирующий запрос в <head>.
     * Замеры по текущей теме — в docs/PERFORMANCE_PLAN.md, раздел 2.1.
     */
    private const THEME_PART_MAP = [
        'news_detail' => '/assets/css/blocks/news-detail.css',
        'org_structure' => '/assets/css/blocks/org-structure.css',
    ];

    /** @var array<string, bool> */
    private static array $themeParts = [];

    public static function requireJs(string $key): void
    {
        if (isset(self::JS_MAP[$key])) {
            self::$js[$key] = true;
        }
        // Стили и скрипт блока адресуются одним ключом — типом блока.
        if (isset(self::CSS_MAP[$key])) {
            self::$css[$key] = self::CSS_MAP[$key];
        }
        // Часть темы, вынесенная под этот тип блока, подключается сама:
        // PageController уже прогоняет через requireJs каждый тип со страницы
        // (BlockRenderer::renderPage() отдаёт их списком). Достаточно строки
        // в THEME_PART_MAP — правок в контроллерах не требуется.
        self::requireThemePart($key);
    }

    public static function requireCss(string $key, string $href): void
    {
        self::$css[$key] = $href;
    }

    /** Подключает вынесенную часть темы (см. THEME_PART_MAP). */
    public static function requireThemePart(string $key): void
    {
        if (isset(self::THEME_PART_MAP[$key])) {
            self::$themeParts[$key] = true;
        }
    }

    public static function reset(): void
    {
        self::$js = [];
        self::$css = [];
        self::$themeParts = [];
    }

    /** Части темы: выводятся сразу после общего бандла, до дизайн-CSS. */
    public static function renderThemeStyles(): string
    {
        $html = '';
        foreach (array_keys(self::$themeParts) as $key) {
            $url = Asset::url(FrontendAssets::blockAsset(self::THEME_PART_MAP[$key]));
            $html .= '<link rel="stylesheet" href="' . htmlspecialchars($url, ENT_QUOTES) . '" data-theme-part>' . "\n";
        }

        return $html;
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
