<?php

declare(strict_types=1);

namespace App\Core\BlockData;

final class SubscribeBlockNormalizer
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
        ];
    }
}
