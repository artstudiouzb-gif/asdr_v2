<?php

declare(strict_types=1);

namespace App\Core\BlockData;

final class CtaBlockNormalizer
{
    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function normalize(array $input, string $locale = 'ru'): array
    {
        return [
            'title' => BlockDataInput::plain($input, 'title_field', $locale),
            'text' => BlockDataInput::plain($input, 'text', $locale),
            'button_text' => BlockDataInput::trimmed($input, 'button_text'),
            'button_url' => BlockDataInput::safeLink($input['button_url'] ?? ''),
            'bg_color' => BlockDataInput::optionalColor($input, 'bg_color'),
            'text_color' => BlockDataInput::optionalColor($input, 'text_color'),
            'button_color' => BlockDataInput::optionalColor($input, 'button_color'),
        ];
    }
}
