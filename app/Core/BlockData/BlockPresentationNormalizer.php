<?php

declare(strict_types=1);

namespace App\Core\BlockData;

use App\Core\BlockVisibility;
use App\Core\MediaPosition;
use App\Core\UrlGuard;

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

    /** Способ залить фон секции: пресет темы, свой цвет, градиент, фото, узор. */
    private const BACKGROUND_MODES = ['preset', 'color', 'gradient', 'image', 'pattern'];

    /** Встроенные узоры: рисуются градиентами и маской, файлов не требуют. */
    private const PATTERNS = ['dots', 'grid', 'diagonal', 'emblem'];

    /**
     * Заливка секции: пресет темы, свой цвет, градиент или фотография.
     *
     * Режимы взаимно исключают друг друга — два фона на одной секции дают
     * кашу, и предугадать, какой из них «главный», нельзя. Поэтому режим
     * один, а лишние значения в данные блока просто не попадают.
     *
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function background(array $input): array
    {
        $mode = self::scalarString($input['bg_mode'] ?? null, 'preset');
        if (!in_array($mode, self::BACKGROUND_MODES, true)) {
            $mode = 'preset';
        }

        $color = self::color($input['bg_color'] ?? null);
        $from = self::color($input['bg_gradient_from'] ?? null);
        $to = self::color($input['bg_gradient_to'] ?? null);
        $image = trim(self::scalarString($input['bg_image'] ?? null));
        if ($image !== '' && !UrlGuard::isSafeMedia($image)) {
            $image = '';
        }

        // Режим без своего значения — обычный пресет: пустая секция с
        // включённым «градиентом» без цветов выглядела бы сломанной.
        if (($mode === 'color' && $color === '')
            || ($mode === 'gradient' && ($from === '' || $to === ''))
            || ($mode === 'image' && $image === '')) {
            $mode = 'preset';
        }

        if ($mode === 'preset') {
            return ['_bg_mode' => 'preset'];
        }

        $data = ['_bg_mode' => $mode];
        if ($mode === 'color') {
            $data['_bg_color'] = $color;
        }
        if ($mode === 'gradient') {
            $data['_bg_gradient_from'] = $from;
            $data['_bg_gradient_to'] = $to;
            $data['_bg_gradient_angle'] = max(0, min(360, (int) ($input['bg_gradient_angle'] ?? 135)));
        }
        if ($mode === 'image') {
            $data['_bg_image'] = $image;
            // Загруженный файл бывает и снимком, и плиткой узора: у плитки
            // важен размер повтора, у снимка — какая часть кадра видна.
            $repeat = self::scalarString($input['bg_repeat'] ?? null, 'cover');
            $data['_bg_repeat'] = $repeat === 'tile' ? 'tile' : 'cover';
            if ($data['_bg_repeat'] === 'tile') {
                $data['_bg_tile_size'] = max(16, min(600, (int) ($input['bg_tile_size'] ?? 120)));
                // Затемнение — приём для фотографии; поверх плитки-узора оно
                // просто гасит секцию, поэтому по умолчанию его нет.
                $data['_bg_overlay'] = max(0, min(80, (int) ($input['bg_overlay'] ?? 0)));
            } else {
                $data['_bg_overlay'] = max(0, min(80, (int) ($input['bg_overlay'] ?? 45)));
                $data['_bg_position'] = MediaPosition::normalize($input['bg_position'] ?? null);
                $data['_bg_fixed'] = !empty($input['bg_fixed']);
            }
        }
        if ($mode === 'pattern') {
            $pattern = self::scalarString($input['bg_pattern'] ?? null, 'dots');
            $data['_bg_pattern'] = in_array($pattern, self::PATTERNS, true) ? $pattern : 'dots';
            // Узор лежит на заливке: без неё он висел бы на фоне страницы и
            // «полноширинная» секция теряла бы границы.
            $data['_bg_color'] = $color;
            $data['_bg_pattern_color'] = self::color($input['bg_pattern_color'] ?? null);
            $data['_bg_pattern_opacity'] = max(3, min(60, (int) ($input['bg_pattern_opacity'] ?? 22)));
            $data['_bg_pattern_size'] = max(8, min(240, (int) ($input['bg_pattern_size'] ?? 28)));
        }
        // Светлый текст нужен на любом тёмном фоне — и на своём цвете, и на
        // градиенте, и на снимке: по цвету угадать нельзя, решает редактор.
        $data['_bg_light_text'] = !empty($input['bg_light_text']);

        return $data;
    }

    /** Цвет из формы: принимаем только #rrggbb, остальное — «не задан». */
    private static function color(mixed $value): string
    {
        $value = trim(self::scalarString($value));

        return preg_match('/^#[0-9a-f]{6}$/i', $value) === 1 ? strtolower($value) : '';
    }

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

        $normalized += self::background($input);

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
        if (array_key_exists('cards_icon_bg', $input)) {
            $iconBackground = self::scalarString($input['cards_icon_bg'] ?? null, 'on');
            $normalized['_cards_icon_bg'] = in_array($iconBackground, ['on', 'off'], true)
                ? $iconBackground
                : 'on';
        }
        if (array_key_exists('cards_icon_position', $input)) {
            $iconPosition = self::scalarString($input['cards_icon_position'] ?? null, 'top');
            $normalized['_cards_icon_position'] = in_array($iconPosition, ['top', 'left', 'right', 'center'], true)
                ? $iconPosition
                : 'top';
        }
        if (array_key_exists('cards_text_align', $input)) {
            $textAlign = self::scalarString($input['cards_text_align'] ?? null, 'left');
            $normalized['_cards_text_align'] = in_array($textAlign, ['left', 'center', 'right'], true)
                ? $textAlign
                : 'left';
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
