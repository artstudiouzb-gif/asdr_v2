<?php

declare(strict_types=1);

namespace App\Core\BlockData;

use App\Core\BlockVisibility;

/**
 * Общие настройки внешнего вида и условий показа для любого типа блока.
 */
final class BlockPresentationNormalizer
{
    /** @var list<string> */
    private const SPACING = ['none', 'small', 'premium', 'max'];

    /** @var list<string> */
    private const REVEAL_TYPES = ['fade', 'slide-up', 'slide-left', 'slide-right', 'zoom-in', 'stagger'];

    /** @var list<string> */
    private const BACKGROUNDS = ['none', 'light', 'tint', 'navy'];

    /** @var list<string> */
    private const SURFACES = ['flat', 'card'];

    /** @var list<string> */
    private const PADDINGS = ['default', 'none', 'small', 'medium', 'large'];

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function normalize(array $input): array
    {
        $spacing = self::scalarString($input['spacing'] ?? null, 'premium');
        $revealType = self::scalarString($input['reveal_type'] ?? null);
        $background = self::scalarString($input['bg'] ?? null, 'none');
        $surface = self::scalarString($input['surface'] ?? null, 'flat');
        $padTop = self::scalarString($input['pad_top'] ?? null, 'default');
        $padBottom = self::scalarString($input['pad_bottom'] ?? null, 'default');
        $device = self::scalarString($input['visible_device'] ?? null);

        $normalized = [
            '_spacing' => in_array($spacing, self::SPACING, true) ? $spacing : 'premium',
            '_reveal' => in_array($revealType, self::REVEAL_TYPES, true)
                ? ['enabled' => true, 'type' => $revealType]
                : ['enabled' => false, 'type' => 'fade'],
            '_bg' => in_array($background, self::BACKGROUNDS, true) ? $background : 'none',
            '_surface' => in_array($surface, self::SURFACES, true) ? $surface : 'flat',
            '_fullwidth' => !empty($input['fullwidth']),
            '_pad_top' => in_array($padTop, self::PADDINGS, true) ? $padTop : 'default',
            '_pad_bottom' => in_array($padBottom, self::PADDINGS, true) ? $padBottom : 'default',
            '_visible_from' => BlockVisibility::normalize(self::scalarString($input['visible_from'] ?? null)),
            '_visible_to' => BlockVisibility::normalize(self::scalarString($input['visible_to'] ?? null)),
            '_visible_device' => in_array($device, ['desktop', 'mobile'], true) ? $device : '',
        ];

        // Настройки карточек добавляются только когда конкретная форма блока
        // их прислала. Так переключатель «старый / новый» остаётся локальным
        // для cards_grid и не загрязняет данные остальных типов блоков.
        if (array_key_exists('cards_style', $input)) {
            $cardsStyle = self::scalarString($input['cards_style'] ?? null, 'old');
            $normalized['_cards_style'] = in_array($cardsStyle, ['old', 'new'], true) ? $cardsStyle : 'old';
        }
        if (array_key_exists('cards_icon_size', $input)) {
            $normalized['_cards_icon_size'] = max(16, min(64, (int) ($input['cards_icon_size'] ?? 22)));
        }

        return $normalized;
    }

    /** @param array<string, mixed> $data */
    public static function hasInvalidVisibilityWindow(array $data): bool
    {
        $from = BlockVisibility::parse($data['_visible_from'] ?? '');
        $to = BlockVisibility::parse($data['_visible_to'] ?? '');

        return $from !== null && $to !== null && $to <= $from;
    }

    private static function scalarString(mixed $value, string $default = ''): string
    {
        return is_scalar($value) ? (string) $value : $default;
    }
}
