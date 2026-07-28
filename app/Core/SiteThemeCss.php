<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Setting;

/**
 * Builds the validated, database-driven CSS layer for the public site.
 *
 * The result is published by GeneratedCss, not embedded into page markup.
 */
final class SiteThemeCss
{
    /** @param array<string, string> $designValues
     *  @param array<string, mixed> $headerConfig
     */
    public static function build(array $designValues, array $headerConfig, bool $transparentHeader): string
    {
        $primary = (string) Setting::get('color_primary', '#173a63');
        $accent = (string) Setting::get('color_accent', '#17999b');
        $colors = DesignSettings::semanticColors();
        $spacings = DesignSettings::semanticSpacings();
        $font = self::fontValue((string) Setting::get(
            'font_family',
            "'PT Sans', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif"
        ), 'system-ui, sans-serif');
        $heading = (string) Setting::get('font_heading', '');
        $heading = self::fontValue($heading !== '' ? $heading : $font, "'Montserrat', system-ui, sans-serif");
        $styles = (array) ($headerConfig['styles'] ?? []);

        $variables = [
            '--color-primary' => $primary,
            '--color-accent' => $accent,
            '--gov-navy' => 'var(--color-primary)',
            '--gov-teal' => 'var(--color-accent)',
            '--gov-teal-text' => AccentContrast::onLight($accent, $colors['bg_surface']),
            '--gov-teal-on-dark' => AccentContrast::onDark($accent),
            '--bg-primary' => $colors['bg_primary'],
            '--bg-surface' => $colors['bg_surface'],
            '--text-main' => $colors['text_main'],
            '--text-muted' => $colors['text_muted'],
            '--border-color' => $colors['border_color'],
            '--gov-bg' => 'var(--bg-primary)',
            '--gov-surface' => 'var(--bg-surface)',
            '--gov-ink' => 'var(--text-main)',
            '--gov-muted' => 'var(--text-muted)',
            '--gov-border' => 'var(--border-color)',
            '--space-small' => $spacings['space_small'],
            '--space-premium' => $spacings['space_premium'],
            '--space-max' => $spacings['space_max'],
            '--font-family' => $font,
            '--font-heading' => $heading,
            '--header-logo-width' => (int) ($headerConfig['logo_width'] ?? 240) . 'px',
            '--header-logo-height' => (int) ($headerConfig['logo_height'] ?? 48) . 'px',
            '--header-elements-gap' => self::elementsGap((string) ($styles['elements_gap'] ?? 'normal')),
            '--header-floating-radius' => (int) ($styles['floating_radius'] ?? 18) . 'px',
            '--header-floating-opacity' => (string) round(
                max(5, min(100, (int) ($styles['floating_opacity'] ?? 25))) / 100,
                2
            ),
            '--header-floating-angle' => (int) ($styles['floating_gradient_angle'] ?? 135) . 'deg',
            '--header-floating-blur' => (int) ($styles['floating_blur'] ?? 14) . 'px',
            '--hoverline-bottom' => self::hoverlineOffset((string) ($styles['hoverline_offset'] ?? 'normal')),
            '--hoverline-height' => self::hoverlineThickness((string) ($styles['hoverline_thickness'] ?? '2px')),
            '--hoverline-inset' => self::hoverlineInset((string) ($styles['hoverline_length'] ?? 'normal')),
        ];

        foreach ([
            '--header-mid-bg' => (string) ($headerConfig['middlebar']['bg'] ?? ''),
            '--header-nav-bg' => (string) ($headerConfig['bottombar']['bg'] ?? ''),
            '--menu-color' => (string) ($styles['nav_color'] ?? ''),
            '--menu-hover' => (string) ($styles['nav_hover'] ?? ''),
            '--menu-active' => (string) ($styles['nav_active'] ?? ''),
            '--menu-pill-bg' => (string) ($styles['nav_pill_bg'] ?? ''),
        ] as $name => $value) {
            if ($value !== '') {
                $variables[$name] = $value;
            }
        }
        if (!empty($headerConfig['shadow']['enabled']) && !$transparentHeader) {
            $variables['--header-shadow-size'] = (int) ($headerConfig['shadow']['size'] ?? 14) . 'px';
        }

        $css = ':root{' . implode('', array_map(
            static fn (string $name, string $value): string => $name . ':' . $value . ';',
            array_keys($variables),
            array_values($variables)
        )) . "}\n";
        $css .= DesignSettings::cssVariables($designValues) . "\n";
        $css .= self::headerRules($styles);
        $css = self::fontFaceRule() . $css;

        $globalCss = (string) Setting::get('custom_css_global', '');
        if (trim($globalCss) !== '') {
            $css .= "\n" . $globalCss . "\n";
        }

        return $css;
    }

    /** @param array<string, mixed> $headerConfig */
    public static function headerClasses(array $headerConfig, bool $transparentHeader): string
    {
        return (($headerConfig['middlebar']['bg'] ?? '') !== '' ? ' site-header--mid-bg' : '')
            . (($headerConfig['bottombar']['bg'] ?? '') !== '' ? ' site-header--nav-bg' : '')
            . (!empty($headerConfig['shadow']['enabled']) && !$transparentHeader ? ' site-header--shadow' : '');
    }

    /** @param array<string, mixed> $styles */
    private static function headerRules(array $styles): string
    {
        $rules = '';
        foreach ([
            'nav_color' => '.site-menu__link{color:%s !important;}',
            'nav_hover' => '.site-menu__link:hover{color:%s !important;}',
            'nav_active' => '.site-menu__link.is-active,.site-menu__link[aria-current="page"]{color:%s !important;}',
            'topbar_bg' => '.site-topbar{background-color:%s !important;}',
            'topbar_text' => '.site-topbar,.site-topbar a{color:%s !important;}',
            'border_color' => '.site-header{border-bottom-color:%s !important;}',
        ] as $key => $template) {
            $value = (string) ($styles[$key] ?? '');
            if ($value !== '') {
                $rules .= sprintf($template, $value) . "\n";
            }
        }
        $borderWidth = (string) ($styles['border_width'] ?? '');
        if ($borderWidth !== '') {
            $rules .= '.site-header{border-bottom-width:' . $borderWidth
                . " !important;border-bottom-style:solid;}\n";
        }

        return $rules;
    }

    private static function fontFaceRule(): string
    {
        if (DesignSettings::bodyFontChoice() !== 'style:custom') {
            return '';
        }
        $url = (string) Setting::get('font_url', '');
        $name = preg_replace(
            '/[^a-zA-Z0-9 _-]/',
            '',
            trim((string) Setting::get('font_face_name', ''))
        ) ?? '';
        if ($url === '' || $name === '' || !UrlGuard::isSafeLink($url)) {
            return '';
        }
        $url = str_replace(["\\", "'", "\r", "\n"], ["\\\\", "\\'", '', ''], $url);

        return "@font-face{font-family:'{$name}';src:url('{$url}') format('woff2');"
            . "font-weight:100 900;font-display:swap;}\n";
    }

    private static function fontValue(string $value, string $fallback): string
    {
        return preg_replace("/[^a-zA-Z0-9 ,'\\-]/", '', $value) ?: $fallback;
    }

    private static function elementsGap(string $value): string
    {
        return match ($value) {
            'ultra_compact' => '6px',
            'compact' => '10px',
            'spacious' => '28px',
            'loose' => '38px',
            default => '18px',
        };
    }

    private static function hoverlineOffset(string $value): string
    {
        return match ($value) {
            'close', '1px' => '1px',
            'far', '8px' => '8px',
            default => is_numeric($value) ? (int) $value . 'px' : '4px',
        };
    }

    private static function hoverlineThickness(string $value): string
    {
        return match ($value) {
            'thin', '1px' => '1px',
            'normal', '2px' => '2px',
            'thick', '3px' => '3px',
            'heavy', '4px' => '4px',
            '5px' => '5px',
            '6px' => '6px',
            '8px' => '8px',
            default => is_numeric($value) ? (int) $value . 'px' : '2px',
        };
    }

    private static function hoverlineInset(string $value): string
    {
        return match ($value) {
            'compact', '12px' => '12px',
            'full', '0px' => '0px',
            default => is_numeric($value) ? (int) $value . 'px' : '4px',
        };
    }
}
