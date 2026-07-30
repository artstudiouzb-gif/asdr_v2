<?php

declare(strict_types=1);

namespace App\Core\BlockData;

use App\Core\Icon;

final class AdvantagesBlockNormalizer
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

            $itemTitle = trim((string) ($item['title'] ?? ''));
            $itemText = trim((string) ($item['text'] ?? ''));
            if ($itemTitle === '' && $itemText === '') {
                continue;
            }

            $iconSvg = Icon::cleanName($item['icon_svg'] ?? '');

            $items[] = [
                'icon_svg' => $iconSvg,
                'title' => BlockDataInput::plain($item, 'title', $locale),
                'text' => BlockDataInput::plain($item, 'text', $locale),
            ];
        }

        return [
            'variant' => ($input['variant'] ?? 'grid') === 'band' ? 'band' : 'grid',
            'title' => BlockDataInput::plain($input, 'title_field', $locale),
            'items' => $items,
        ];
    }
}
