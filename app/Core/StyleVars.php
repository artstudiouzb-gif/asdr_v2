<?php

declare(strict_types=1);

namespace App\Core;

/**
 * CSS-переменные элемента без атрибута style.
 *
 * Значения, которые знает только рендер (точка фокуса картинки, цвет метки,
 * процент в опросе), раньше приходили атрибутом `style="--x:…"`. Атрибут style
 * держит в публичной CSP `style-src 'unsafe-inline'`, поэтому переменные
 * переезжают в правило класса: элемент получает класс `sv-<хеш>`, а правило
 * `.sv-<хеш>{--x:…}` уходит туда, где оно переживёт кэш:
 *
 * - внутри шаблона блока (BlockRenderer открывает фрейм) — в scoped CSS блока,
 *   который кэшируется вместе с его HTML и публикуется файлом в <head>;
 * - вне блока — одним тегом <style> прямо перед элементом, один раз на класс
 *   за запрос: браузер применяет его до отрисовки элемента, без скачка.
 *
 * Имена переменных и значения проверяются: в правило не попадёт ничего, что
 * может закрыть объявление или тег.
 */
final class StyleVars
{
    /** @var list<array<string, string>> правила открытых фреймов: класс → правило */
    private static array $frames = [];

    /** @var array<string, true> классы, чьё правило уже выведено тегом в этом запросе */
    private static array $emitted = [];

    /** Открывает фрейм: правила до end() копятся, а не выводятся тегом. */
    public static function begin(): void
    {
        self::$frames[] = [];
    }

    /** Закрывает фрейм и отдаёт накопленные правила одной строкой CSS. */
    public static function end(): string
    {
        $rules = array_pop(self::$frames) ?? [];

        return implode("\n", $rules);
    }

    /**
     * Класс для набора переменных и разметка, которую надо вывести перед
     * элементом (пустая внутри фрейма и для уже выведенного класса).
     *
     * @param array<string, string> $vars имя переменной (`--x`) → значение
     * @return array{class: string, style: string} пустой класс, если годных переменных нет
     */
    public static function apply(array $vars): array
    {
        $declarations = [];
        foreach ($vars as $name => $value) {
            if (preg_match('/^--[a-z0-9][a-z0-9-]*$/', $name) !== 1 || !self::safeValue($value)) {
                continue;
            }
            $declarations[] = $name . ':' . $value;
        }
        if ($declarations === []) {
            return ['class' => '', 'style' => ''];
        }

        $body = implode(';', $declarations);
        $class = 'sv-' . substr(hash('sha256', $body), 0, 12);
        $rule = '.' . $class . '{' . $body . '}';

        if (self::$frames !== []) {
            self::$frames[array_key_last(self::$frames)][$class] = $rule;

            return ['class' => $class, 'style' => ''];
        }
        if (isset(self::$emitted[$class])) {
            return ['class' => $class, 'style' => ''];
        }
        self::$emitted[$class] = true;

        return ['class' => $class, 'style' => '<style>' . $rule . '</style>'];
    }

    /** Значение `url('…')` для переменной: кавычка и обратный слеш экранируются. */
    public static function url(string $url): string
    {
        return "url('" . str_replace(['\\', "'", "\n", "\r"], ['\\\\', "\\'", '', ''], $url) . "')";
    }

    /** Сброс памяти запроса — для тестов. */
    public static function reset(): void
    {
        self::$frames = [];
        self::$emitted = [];
    }

    /**
     * Значение не должно выйти за пределы объявления: без `;`, фигурных и
     * угловых скобок, перевода строки и комментария. Кавычки допустимы только
     * внутри url(), который собирает url().
     */
    private static function safeValue(string $value): bool
    {
        if ($value === '' || strlen($value) > 2048) {
            return false;
        }

        return preg_match('/[;{}<>\r\n]|\/\*/', $value) !== 1;
    }
}
