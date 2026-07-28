<?php

declare(strict_types=1);

namespace App\Core\BlockData;

final class TestimonialsBlockNormalizer
{
    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function normalize(array $input, string $locale = 'ru'): array
    {
        $items = [];
        foreach ((array) ($input['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $quote = trim((string) ($item['quote'] ?? ''));
            $name = trim((string) ($item['name'] ?? ''));
            if ($quote === '' && $name === '') {
                continue;
            }

            $items[] = [
                'quote' => BlockDataInput::plain($item, 'quote', $locale),
                'name' => BlockDataInput::plain($item, 'name', $locale),
                'company' => BlockDataInput::plain($item, 'company', $locale),
                'photo' => BlockDataInput::safeLink($item['photo'] ?? ''),
            ];
        }

        return [
            'title' => BlockDataInput::plain($input, 'title_field', $locale),
            'items' => $items,
        ];
    }
}
