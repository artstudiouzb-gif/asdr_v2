<?php

declare(strict_types=1);

namespace App\Core\BlockData;

use App\Core\Uploader;

final class ContactCardsBlockNormalizer
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
            $lines = trim((string) ($item['lines'] ?? ''));
            if ($itemTitle === '' && $lines === '') {
                continue;
            }

            $iconSvg = trim((string) ($item['icon_svg'] ?? ''));
            if ($iconSvg !== '') {
                $iconSvg = Uploader::sanitizeSvgString($iconSvg);
            }

            $items[] = [
                'icon_svg' => $iconSvg,
                'title' => BlockDataInput::plain($item, 'title', $locale),
                'lines' => $lines,
                'link_url' => BlockDataInput::safeLink($item['link_url'] ?? ''),
                'link_text' => trim((string) ($item['link_text'] ?? '')),
            ];
        }

        return [
            'title' => BlockDataInput::plain($input, 'title_field', $locale),
            'items' => $items,
        ];
    }
}
