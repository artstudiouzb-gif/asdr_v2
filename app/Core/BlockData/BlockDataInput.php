<?php

declare(strict_types=1);

namespace App\Core\BlockData;

use App\Core\TextProcessor;
use App\Core\UrlGuard;

/**
 * Общие безопасные преобразования полей формы для нормализаторов блоков.
 */
final class BlockDataInput
{
    /** @param array<string, mixed> $input */
    public static function plain(array $input, string $field, string $locale): string
    {
        return TextProcessor::typographPlain(self::trimmed($input, $field), $locale);
    }

    /** @param array<string, mixed> $input */
    public static function trimmed(array $input, string $field): string
    {
        return trim(self::scalarString($input[$field] ?? null));
    }

    public static function safeLink(mixed $value): string
    {
        $url = trim(self::scalarString($value));
        return $url !== '' && UrlGuard::isSafeLink($url) ? $url : '';
    }

    /** @param array<string, mixed> $input */
    public static function optionalColor(array $input, string $field): string
    {
        if (!empty($input[$field . '_off'])) {
            return '';
        }

        $value = self::trimmed($input, $field);
        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : '';
    }

    private static function scalarString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
