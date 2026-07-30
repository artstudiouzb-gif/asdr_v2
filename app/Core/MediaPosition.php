<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Единая безопасная система кадрирования изображений в публичных блоках.
 *
 * Храним не произвольный CSS, а один из девяти понятных пресетов. Это
 * исключает inline-стили и позволяет отдельно настроить мобильный кадр.
 */
final class MediaPosition
{
    /** @var list<string> */
    public const VALUES = [
        'left-top', 'center-top', 'right-top',
        'left-center', 'center-center', 'right-center',
        'left-bottom', 'center-bottom', 'right-bottom',
    ];

    public static function normalize(mixed $value): string
    {
        $value = trim((string) $value);

        return in_array($value, self::VALUES, true) ? $value : 'center-center';
    }

    public static function classes(mixed $desktop, mixed $mobile = null): string
    {
        $desktop = self::normalize($desktop);
        $mobile = self::normalize($mobile ?? $desktop);

        return 'media-position--' . $desktop . ' media-position-mobile--' . $mobile;
    }
}
