<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Helper to process page custom CSS and JS fields.
 *
 * Ensures ZERO inline code in page HTML output. Converts external URLs, HTML tags
 * and custom code snippets into list of external CSS/JS file URLs.
 */
final class CustomAssetHelper
{
    /**
     * Resolves custom CSS input into a list of external CSS URLs.
     *
     * @return list<string>
     */
    public static function resolveCssUrls(?string $input, int $pageId): array
    {
        if ($input === null || trim($input) === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($input)) ?: [];
        $urls = [];
        $codeLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            // Extract href from <link ... href="..." ...>
            if (preg_match('/<link\b[^>]*\bhref=["\']([^"\']+)["\']/i', $trimmed, $m)) {
                $urls[] = $m[1];
                continue;
            }

            // Standalone URL check: https://, http://, // or path ending with .css (no CSS syntax like { ; < )
            if (self::isExternalUrl($trimmed, '.css')) {
                $urls[] = $trimmed;
                continue;
            }

            // Collect raw CSS code
            $cleanLine = preg_replace('/<\/?style\b[^>]*>/i', '', $trimmed);
            if (trim($cleanLine) !== '') {
                $codeLines[] = $cleanLine;
            }
        }

        if ($codeLines !== []) {
            $code = implode("\n", $codeLines);
            $publishedUrl = GeneratedCss::publish($code, 'page-' . $pageId);
            if ($publishedUrl !== null) {
                $urls[] = $publishedUrl;
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * Resolves custom JS input into a list of external JS URLs.
     *
     * @return list<string>
     */
    public static function resolveJsUrls(?string $input, int $pageId): array
    {
        if ($input === null || trim($input) === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($input)) ?: [];
        $urls = [];
        $codeLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            // Extract src from <script ... src="..." ...>
            if (preg_match('/<script\b[^>]*\bsrc=["\']([^"\']+)["\']/i', $trimmed, $m)) {
                $urls[] = $m[1];
                continue;
            }

            // Standalone URL check: https://, http://, // or path ending with .js (no JS syntax like ( { ; = < )
            if (self::isExternalUrl($trimmed, '.js')) {
                $urls[] = $trimmed;
                continue;
            }

            // Collect raw JS code
            $cleanLine = preg_replace('/<\/?script\b[^>]*>/i', '', $trimmed);
            if (trim($cleanLine) !== '') {
                $codeLines[] = $cleanLine;
            }
        }

        if ($codeLines !== []) {
            $code = implode("\n", $codeLines);
            $publishedUrl = GeneratedJs::publish($code, 'page-' . $pageId);
            if ($publishedUrl !== null) {
                $urls[] = $publishedUrl;
            }
        }

        return array_values(array_unique($urls));
    }

    private static function isExternalUrl(string $text, string $extension): bool
    {
        if (str_contains($text, '<') || str_contains($text, '{') || str_contains($text, ';')) {
            return false;
        }

        if (preg_match('#^(https?:)?//#i', $text)) {
            return true;
        }

        if (str_starts_with($text, '/') && str_ends_with(strtolower($text), $extension)) {
            return true;
        }

        if (str_ends_with(strtolower($text), $extension) && !str_contains($text, ' ') && !str_contains($text, '=')) {
            return true;
        }

        return false;
    }
}
