<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Единая точка рендера и очистки ключей Tabler Icons.
 *
 * В базе и JSON хранится только короткий ключ (например, "home"). Геометрия
 * иконок берётся из локального SVG-спрайта, поэтому в шаблонах больше не нужны
 * собственные <path>, а сайт не зависит от CDN.
 */
final class Icon
{
    public const SPRITE_PATH = '/assets/vendor/tabler/tabler-sprite.svg';
    public const CATALOG_PATH = '/assets/vendor/tabler/tabler-icons.json';

    /**
     * Совместимость со старыми короткими ключами AdminUI. Новые ключи можно
     * указывать напрямую по имени иконки на tabler.io/icons.
     *
     * @var array<string,string>
     */
    private const ALIASES = [
        'a11y' => 'accessible',
        'albums' => 'album',
        'audit' => 'clipboard-list',
        'block' => 'layout-grid',
        'button' => 'rectangle',
        'columns' => 'columns-3',
        'content_types' => 'stack-2',
        'copy' => 'copy',
        'dashboard' => 'layout-dashboard',
        'design' => 'palette',
        'divider' => 'separator-vertical',
        'document' => 'file-text',
        'edit' => 'edit',
        'email' => 'mail',
        'external' => 'external-link',
        'facebook' => 'brand-facebook',
        'files' => 'photo',
        'forms' => 'forms',
        'grid' => 'layout-grid',
        'grip' => 'grip-vertical',
        'header' => 'layout-navbar',
        'footer' => 'layout-bottombar',
        'image' => 'photo',
        'instagram' => 'brand-instagram',
        'info' => 'info-circle',
        'languages' => 'language',
        'layout' => 'layout-dashboard',
        'linkedin' => 'brand-linkedin',
        'logo' => 'box',
        'media' => 'photo',
        'news' => 'news',
        'pages' => 'files',
        'panel-left' => 'layout-sidebar-left-collapse',
        'performance' => 'bolt',
        'pause' => 'player-pause',
        'projects' => 'briefcase',
        'profile' => 'user-circle',
        'redirects' => 'route-alt-right',
        'repository' => 'database',
        'reset' => 'refresh',
        'save' => 'device-floppy',
        'send' => 'send',
        'seo' => 'world-search',
        'sliders' => 'adjustments-horizontal',
        'snippet' => 'source-code',
        'social' => 'share-3',
        'space' => 'spacing-horizontal',
        'spacer' => 'arrows-horizontal',
        'stats' => 'chart-bar',
        'subscribers' => 'address-book',
        'telegram' => 'brand-telegram',
        'tender' => 'building-bank',
        'theme' => 'moon',
        'trash' => 'trash',
        'type' => 'typography',
        'videos' => 'video',
        'warning' => 'alert-triangle',
        'webhooks' => 'webhook',
        'widgets' => 'layout-grid',
    ];

    /** @var array<string,true>|null */
    private static ?array $available = null;

    public static function cleanName(mixed $value): string
    {
        $name = strtolower(trim((string) $value));
        if ($name === '' || str_contains($name, '<') || strlen($name) > 80) {
            return '';
        }
        if (str_starts_with($name, 'tabler-')) {
            $name = substr($name, 7);
        }

        // Подчёркивание допустимо: короткие ключи разделов админки пишутся в
        // snake_case («content_types»), а прежний набор символов отбраковывал
        // такое имя ещё до обращения к ALIASES — псевдоним был недостижим, и
        // раздел оставался без иконки. Итоговое имя всё равно сверяется с
        // каталогом Tabler ниже, поэтому расширение набора безопасно.
        if (preg_match('/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/D', $name) !== 1) {
            return '';
        }

        $resolved = self::ALIASES[$name] ?? $name;

        return self::isAvailable($resolved) ? $name : '';
    }

    public static function resolvedName(mixed $value): string
    {
        $name = self::cleanName($value);

        return self::ALIASES[$name] ?? $name;
    }

    public static function isAvailable(mixed $value): bool
    {
        $name = strtolower(trim((string) $value));
        if ($name === '') {
            return false;
        }
        if (self::$available === null) {
            $path = dirname(__DIR__, 2) . '/public' . self::CATALOG_PATH;
            $decoded = is_file($path)
                ? json_decode((string) file_get_contents($path), true)
                : null;
            $names = is_array($decoded) && is_array($decoded['icons'] ?? null)
                ? $decoded['icons']
                : [];
            self::$available = array_fill_keys(
                array_values(array_filter($names, 'is_string')),
                true
            );
        }

        return isset(self::$available[$name]);
    }

    public static function render(
        mixed $name,
        int $size = 16,
        string $class = 'ui-icon',
        float $strokeWidth = 2.0
    ): string {
        $resolved = self::resolvedName($name);
        if ($resolved === '') {
            return '';
        }

        $size = max(8, min(160, $size));
        $strokeWidth = max(1.0, min(3.0, $strokeWidth));
        $stroke = rtrim(rtrim(number_format($strokeWidth, 2, '.', ''), '0'), '.');
        $class = trim($class);
        $classes = trim('icon icon-tabler ' . $class);
        $href = self::SPRITE_PATH . '#tabler-' . $resolved;

        return '<svg class="' . htmlspecialchars($classes, ENT_QUOTES) . '" width="' . $size
            . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="'
            . $stroke . '" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
            . '<use href="' . htmlspecialchars($href, ENT_QUOTES) . '"></use></svg>';
    }

    /**
     * Шаблон для безопасного создания той же иконки в JavaScript.
     */
    public static function browserConfigHtml(): string
    {
        return '<meta name="asdr-icon-sprite" content="'
            . htmlspecialchars(self::SPRITE_PATH, ENT_QUOTES) . '">'
            . '<meta name="asdr-icon-catalog" content="'
            . htmlspecialchars(self::CATALOG_PATH, ENT_QUOTES) . '">';
    }
}
