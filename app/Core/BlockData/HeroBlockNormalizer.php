<?php

declare(strict_types=1);

namespace App\Core\BlockData;

use App\Core\Video;

/**
 * Преобразует поля формы Hero в стабильную JSON-структуру блока.
 *
 * Класс не читает $_POST: явный вход делает правила проверяемыми отдельно от
 * HTTP-контроллера и позволяет постепенно переносить сюда остальные блоки.
 */
final class HeroBlockNormalizer
{
    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function normalize(array $input, string $locale = 'ru'): array
    {
        $bgType = (string) ($input['bg_type'] ?? 'image');
        $bgType = in_array($bgType, ['none', 'image', 'video', 'youtube'], true) ? $bgType : 'image';
        $youtubeUrl = trim((string) ($input['youtube_url'] ?? ''));
        $videoUrl = trim((string) ($input['video_url'] ?? ''));
        $image = trim((string) ($input['image'] ?? ''));

        // Заполненное медиа — явное намерение редактора, даже если список
        // «Фон секции» остался в положении «Без фона».
        if ($bgType === 'none' && Video::youtubeId($youtubeUrl) !== null) {
            $bgType = 'youtube';
        } elseif ($bgType === 'none' && $videoUrl !== '') {
            $bgType = 'video';
        } elseif ($bgType === 'none' && $image !== '') {
            $bgType = 'image';
        }

        $heightMode = (string) ($input['hero_height'] ?? 'regular');
        $heightMode = in_array($heightMode, ['regular', 'full', 'custom'], true) ? $heightMode : 'regular';
        $heightUnit = (string) ($input['hero_height_unit'] ?? 'px');
        $heightUnit = in_array($heightUnit, ['px', 'vh', 'dvh', 'rem'], true) ? $heightUnit : 'px';
        $heightValue = is_numeric($input['hero_height_value'] ?? null) ? (float) $input['hero_height_value'] : 720.0;
        $heightLimits = $heightUnit === 'px' ? [160.0, 2000.0]
            : ($heightUnit === 'rem' ? [10.0, 120.0] : [20.0, 150.0]);
        $heightValue = max($heightLimits[0], min($heightLimits[1], $heightValue));

        $overlayDirection = (string) ($input['overlay_direction'] ?? 'auto');
        $overlayDirections = ['auto', 'solid', 'to_right', 'to_left', 'to_bottom', 'to_top', 'to_bottom_right', 'to_bottom_left', 'to_top_right', 'to_top_left'];
        $overlayDirection = in_array($overlayDirection, $overlayDirections, true) ? $overlayDirection : 'auto';

        $textWidth = '';
        if (is_numeric($input['text_width_value'] ?? null)) {
            $textWidthUnit = (string) ($input['text_width_unit'] ?? 'px');
            $textWidthUnit = in_array($textWidthUnit, ['px', '%', 'vw'], true) ? $textWidthUnit : 'px';
            $textWidthLimits = $textWidthUnit === 'px' ? [200.0, 2000.0] : [10.0, 100.0];
            $textWidthValue = max($textWidthLimits[0], min($textWidthLimits[1], (float) $input['text_width_value']));
            $textWidth = self::number($textWidthValue) . $textWidthUnit;
        }

        $textPosition = (string) ($input['text_position'] ?? 'left');

        return [
            'title' => BlockDataInput::plain($input, 'title_field', $locale),
            'width' => ($input['hero_width'] ?? 'full') === 'standard' ? 'standard' : 'full',
            'height' => $heightMode,
            'custom_height' => self::number($heightValue) . $heightUnit,
            'eyebrow' => BlockDataInput::plain($input, 'eyebrow', $locale),
            'subtitle' => BlockDataInput::plain($input, 'subtitle', $locale),
            'bg_type' => $bgType,
            'image' => $image,
            'video_url' => $videoUrl,
            'youtube_url' => $youtubeUrl,
            'overlay_direction' => $overlayDirection,
            'overlay_color' => self::hexOrDefault($input['overlay_color'] ?? '', '#0b1a30'),
            'overlay_end_color' => self::hexOrDefault($input['overlay_end_color'] ?? '', '#0b1a30'),
            'overlay_opacity' => self::percentage($input['overlay_opacity'] ?? null, 55),
            'text_position' => in_array($textPosition, ['left', 'center', 'right'], true) ? $textPosition : 'left',
            'text_width' => $textWidth,
            'text_color' => BlockDataInput::optionalColor($input, 'text_color'),
            'button_color' => BlockDataInput::optionalColor($input, 'button_color'),
            'bg_color' => BlockDataInput::optionalColor($input, 'bg_color'),
            'panel_enabled' => !empty($input['panel_enabled']),
            'panel_color' => self::hexOrDefault($input['panel_color'] ?? '', '#0b1a30'),
            'panel_opacity' => self::percentage($input['panel_opacity'] ?? null, 40),
            'button_text' => trim((string) ($input['button_text'] ?? '')),
            'button_url' => BlockDataInput::safeLink($input['button_url'] ?? ''),
            'button2_text' => trim((string) ($input['button2_text'] ?? '')),
            'button2_url' => BlockDataInput::safeLink($input['button2_url'] ?? ''),
            'video_button_text' => trim((string) ($input['video_button_text'] ?? '')),
            'video_button_url' => BlockDataInput::safeLink($input['video_button_url'] ?? ''),
        ];
    }

    private static function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    }

    private static function percentage(mixed $value, int $default): int
    {
        return is_numeric($value) ? max(0, min(100, (int) $value)) : $default;
    }

    private static function hexOrDefault(mixed $value, string $default): string
    {
        $value = trim((string) $value);
        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : $default;
    }
}
