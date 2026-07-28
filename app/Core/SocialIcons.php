<?php

declare(strict_types=1);

namespace App\Core;

/**
 * SVG-иконки соцсетей для кнопок шапки/футера. Сеть — свободный текст из
 * конструктора; известные названия получают фирменный глиф (currentColor),
 * неизвестные — фолбэк на первую букву, как раньше.
 */
final class SocialIcons
{
    private const ICONS = [
        'telegram' => '<path d="M21.5 4.6 18.6 19c-.2 1-.8 1.2-1.6.8l-4.5-3.3-2.2 2.1c-.2.2-.4.4-.9.4l.3-4.5L18 7.1c.4-.3-.1-.5-.6-.2L7.3 13.3l-4.4-1.4c-1-.3-1-1 .2-1.4l17.1-6.6c.8-.3 1.5.2 1.3.7z" fill="currentColor" stroke="none"/>',
        'facebook' => '<path d="M14 8.5V7c0-.8.2-1.2 1.3-1.2H17V3h-2.6C11.7 3 10.5 4.4 10.5 7v1.5H8.5V12h2v9H14v-9h2.6l.4-3.5z" fill="currentColor" stroke="none"/>',
        'instagram' => '<rect x="3.5" y="3.5" width="17" height="17" rx="4.5"/><circle cx="12" cy="12" r="3.8"/><circle cx="17.2" cy="6.8" r="1" fill="currentColor" stroke="none"/>',
        'youtube' => '<path d="M22 12s0-3.3-.4-4.8c-.2-.9-.9-1.5-1.8-1.7C18.3 5 12 5 12 5s-6.3 0-7.8.5c-.9.2-1.6.8-1.8 1.7C2 8.7 2 12 2 12s0 3.3.4 4.8c.2.9.9 1.5 1.8 1.7 1.5.5 7.8.5 7.8.5s6.3 0 7.8-.5c-.9-.2 1.6-.8 1.8-1.7.4-1.5.4-4.8.4-4.8z" fill="currentColor" stroke="none"/><path d="m10 9 5 3-5 3z" fill="#fff" stroke="none"/>',
        'x' => '<path d="M4 3h4.6l4 5.7L17.8 3H21l-6.7 7.7L21.4 21h-4.6l-4.4-6.2L7 21H3.8l7-8.1z" fill="currentColor" stroke="none"/>',
        'twitter' => '<path d="M4 3h4.6l4 5.7L17.8 3H21l-6.7 7.7L21.4 21h-4.6l-4.4-6.2L7 21H3.8l7-8.1z" fill="currentColor" stroke="none"/>',
        'linkedin' => '<path d="M6.5 9H3.8v11h2.7zM5.1 7.7a1.6 1.6 0 1 0 0-3.2 1.6 1.6 0 0 0 0 3.2zM20.2 20h-2.7v-5.4c0-1.4-.5-2.3-1.7-2.3-1 0-1.5.6-1.8 1.3-.1.2-.1.6-.1.9V20h-2.7V9h2.7v1.5c.4-.6 1.1-1.7 2.9-1.7 2.1 0 3.4 1.4 3.4 4.2z" fill="currentColor" stroke="none"/>',
        'vk' => '<path d="M12.8 17.9c-5.7 0-9-3.9-9.1-10.4h2.9c.1 4.8 2.2 6.8 3.9 7.2V7.5h2.7v4.1c1.7-.2 3.4-2.1 4-4.1h2.7a7.9 7.9 0 0 1-3.6 5.1c1.4.8 3.2 2.4 3.9 5.3h-3c-.5-1.8-1.9-3.2-3.9-3.4v3.4z" fill="currentColor" stroke="none"/>',
        'odnoklassniki' => '<circle cx="12" cy="6.5" r="3.2"/><path d="M8 13.5c1.2.8 2.6 1.2 4 1.2s2.8-.4 4-1.2M9.5 19l2.5-3 2.5 3" stroke-linecap="round"/>',
        'ok' => '<circle cx="12" cy="6.5" r="3.2"/><path d="M8 13.5c1.2.8 2.6 1.2 4 1.2s2.8-.4 4-1.2M9.5 19l2.5-3 2.5 3" stroke-linecap="round"/>',
    ];

    /** Разметка кнопки: фирменный SVG или первая буква сети (фолбэк). */
    public static function glyph(string $network, int $size = 16): string
    {
        $key = strtolower(trim($network));
        if (isset(self::ICONS[$key])) {
            return '<svg viewBox="0 0 24 24" width="' . $size . '" height="' . $size
                . '" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">' . self::ICONS[$key] . '</svg>';
        }

        return htmlspecialchars(mb_strtoupper(mb_substr($network, 0, 1)), ENT_QUOTES);
    }
}
