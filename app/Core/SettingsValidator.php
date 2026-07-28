<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Валидаторы значений настроек (задача 116). Приводят пользовательский ввод к
 * строгим форматам, исключая внедрение сырого HTML/JS в поля аналитики и т.п.
 */
final class SettingsValidator
{
    /** Google Analytics Measurement ID: G-XXXXXXXXXX. Иначе — пустая строка. */
    public static function gaId(string $value): string
    {
        $value = strtoupper(trim($value));
        return preg_match('/^G-[A-Z0-9]{4,20}$/', $value) ? $value : '';
    }

    /** Яндекс.Метрика ID: только цифры. */
    public static function ymId(string $value): string
    {
        $value = trim($value);
        return preg_match('/^\d{4,12}$/', $value) ? $value : '';
    }

    /** HEX-цвет #rrggbb. При невалидном — $default. */
    public static function hexColor(string $value, string $default = '#1a1a1a'): string
    {
        $value = trim($value);
        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : $default;
    }

    /** Короткое имя PWA: обрезка до 12 символов, без управляющих символов. */
    public static function shortName(string $value): string
    {
        $value = preg_replace('/[\x00-\x1F<>]/u', '', trim($value)) ?? '';
        return mb_substr($value, 0, 12);
    }

    /** Неотрицательное целое (например, срок хранения ПДн в днях). */
    public static function nonNegativeInt(string $value, int $default = 0): int
    {
        $value = trim($value);
        return ctype_digit($value) ? (int) $value : $default;
    }

    /** Безопасное значение CSS (например, clamp(), px, rem). */
    public static function safeCssValue(string $value, string $default = ''): string
    {
        $value = trim($value);
        return preg_match('/^[a-zA-Z0-9%()., \-+]+$/', $value) ? $value : $default;
    }
}
