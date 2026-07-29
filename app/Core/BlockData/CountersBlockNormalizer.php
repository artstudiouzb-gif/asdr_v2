<?php

declare(strict_types=1);

namespace App\Core\BlockData;

use App\Core\Icon;

final class CountersBlockNormalizer
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

            $value = BlockDataInput::trimmed($item, 'value');
            $label = BlockDataInput::trimmed($item, 'label');
            if ($value === '' && $label === '') {
                continue;
            }

            $iconSvg = Icon::cleanName($item['icon_svg'] ?? '');

            $digits = preg_replace('/\D+/', '', $value) ?? '';
            $items[] = [
                'value' => (int) $digits,
                'suffix' => BlockDataInput::trimmed($item, 'suffix'),
                'label' => BlockDataInput::plain($item, 'label', $locale),
                'icon_svg' => $iconSvg,
            ];
        }

        return [
            'title' => BlockDataInput::plain($input, 'title_field', $locale),
            'card_bg' => BlockDataInput::optionalColor($input, 'card_bg'),
            'text_color' => BlockDataInput::optionalColor($input, 'text_color'),
            'items' => $items,
        ];
    }
}
