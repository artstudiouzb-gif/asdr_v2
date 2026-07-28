<?php

declare(strict_types=1);

namespace App\Core\BlockData;

use App\Core\Uploader;

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

            $iconSvg = trim((string) ($item['icon_svg'] ?? ''));
            if ($iconSvg !== '') {
                $iconSvg = Uploader::sanitizeSvgString($iconSvg);
            }

            $items[] = [
                'icon' => trim((string) ($item['icon'] ?? '')),
                'icon_svg' => $iconSvg,
                'title' => BlockDataInput::plain($item, 'title', $locale),
                'text' => BlockDataInput::plain($item, 'text', $locale),
            ];
        }

        return [
            'title' => BlockDataInput::plain($input, 'title_field', $locale),
            'items' => $items,
        ];
    }
}
