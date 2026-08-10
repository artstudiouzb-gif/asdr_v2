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

        $iconSizeRaw = BlockDataInput::trimmed($input, 'icon_size');
        $iconSize = $iconSizeRaw !== '' ? (int) $iconSizeRaw : 28;
        $iconBackground = BlockDataInput::trimmed($input, 'icon_bg');
        $iconPosition = BlockDataInput::trimmed($input, 'icon_position');
        $textAlign = BlockDataInput::trimmed($input, 'text_align');

        return [
            'title' => BlockDataInput::plain($input, 'title_field', $locale),
            'card_bg' => BlockDataInput::optionalColor($input, 'card_bg'),
            'text_color' => BlockDataInput::optionalColor($input, 'text_color'),
            'icon_size' => max(16, min(64, $iconSize)),
            'icon_bg' => in_array($iconBackground, ['on', 'off'], true)
                ? $iconBackground
                : 'on',
            'icon_position' => in_array($iconPosition, ['top', 'left', 'right', 'center'], true)
                ? $iconPosition
                : 'left',
            'text_align' => in_array($textAlign, ['left', 'center', 'right'], true)
                ? $textAlign
                : 'left',
            'items' => $items,
        ];
    }
}
