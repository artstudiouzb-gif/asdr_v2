<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Транслитерация узбекской латиницы в кириллицу.
 *
 * Сайт наполняется на латинице, а часть читателей привыкла к кириллице.
 * Перевод делается на выходе, поэтому в базе остаётся один вариант текста:
 * ничего не дублируется и не расходится.
 *
 * Переводится только текст страницы: теги, атрибуты, содержимое script/style,
 * HTML-сущности, адреса и почта остаются как есть. Элемент с атрибутом
 * data-no-translit не трогается вместе со всем содержимым (в панели настроек
 * подписи «Lotin»/«Кирилл» должны остаться собой).
 */
final class UzCyrillic
{
    /** Апострофы, которыми в текстах записывают oʻ, gʻ и tutuq belgisi. */
    private const APOSTROPHES = ['ʻ', 'ʼ', '‘', '’', '`', "'"];

    /** Теги, внутри которых текст не трогаем. */
    private const SKIP_TAGS = ['script', 'style', 'code', 'pre', 'textarea'];

    /** @var array<string, string>|null */
    private static ?array $map = null;

    public static function text(string $latin): string
    {
        if ($latin === '' || !preg_match('/[a-zA-Z]/', $latin)) {
            return $latin;
        }

        // Сущности, ссылки и почта — не текст, их транслитерация ломает.
        $chunks = preg_split(
            '/(&[a-zA-Z]+;|&#\d+;|https?:\/\/\S+|[\w.+\-]+@[\w.\-]+\.\w+|[\w\-]+\.(?:uz|com|org|net|ru|info)\b)/u',
            $latin,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );
        if ($chunks === false) {
            return $latin;
        }

        $out = '';
        foreach ($chunks as $index => $chunk) {
            // Захваченные разделители стоят на нечётных местах.
            $out .= $index % 2 === 1 ? $chunk : self::convert($chunk);
        }

        return $out;
    }

    /** Переводит только текстовые узлы готовой разметки. */
    public static function html(string $html): string
    {
        $parts = preg_split('/(<[^>]*>)/u', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $html;
        }

        $skipTag = null;   // имя тега, внутри которого пропускаем текст
        $skipDepth = 0;
        $out = '';

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if ($part[0] !== '<') {
                $out .= $skipTag === null ? self::text($part) : $part;
                continue;
            }

            $out .= $part;
            if (!preg_match('/^<\s*(\/?)\s*([a-zA-Z0-9-]+)/', $part, $m)) {
                continue;
            }
            $isClosing = $m[1] === '/';
            $tag = strtolower($m[2]);
            $selfClosing = str_ends_with(rtrim($part), '/>');

            if ($skipTag === null) {
                $skipHere = in_array($tag, self::SKIP_TAGS, true) || str_contains($part, 'data-no-translit');
                if (!$isClosing && !$selfClosing && $skipHere) {
                    $skipTag = $tag;
                    $skipDepth = 1;
                }
                continue;
            }

            if ($tag !== $skipTag || $selfClosing) {
                continue;
            }
            if ($isClosing) {
                $skipDepth--;
                if ($skipDepth <= 0) {
                    $skipTag = null;
                }
            } else {
                $skipDepth++;
            }
        }

        return $out;
    }

    private static function convert(string $text): string
    {
        // «e» в начале слова — «э» (Ekologiya → Экология), внутри слова — «е»
        // (kelajak → келажак). Кириллицу дальнейшие замены уже не тронут.
        $text = preg_replace('/(?<![\p{L}\p{N}])e/u', 'э', $text) ?? $text;
        $text = preg_replace('/(?<![\p{L}\p{N}])E/u', 'Э', $text) ?? $text;

        // strtr с массивом подставляет сначала самые длинные ключи, поэтому
        // «sh» срабатывает раньше «s», а «oʻ» — раньше «o».
        return strtr($text, self::map());
    }

    /** @return array<string, string> */
    private static function map(): array
    {
        if (self::$map !== null) {
            return self::$map;
        }

        $map = [];

        // Буквы с апострофом и tutuq belgisi — во всех начертаниях апострофа.
        foreach (self::APOSTROPHES as $apostrophe) {
            $map['o' . $apostrophe] = 'ў';
            $map['O' . $apostrophe] = 'Ў';
            $map['g' . $apostrophe] = 'ғ';
            $map['G' . $apostrophe] = 'Ғ';
            $map[$apostrophe] = 'ъ';
        }

        // Диграфы: строчный, с заглавной и полностью заглавный.
        $digraphs = [
            'sh' => 'ш',
            'ch' => 'ч',
            'yo' => 'ё',
            'yu' => 'ю',
            'ya' => 'я',
            'ye' => 'е',
        ];
        foreach ($digraphs as $latin => $cyrillic) {
            $map[$latin] = $cyrillic;
            $map[ucfirst($latin)] = mb_strtoupper($cyrillic);
            $map[mb_strtoupper($latin)] = mb_strtoupper($cyrillic);
        }

        $letters = [
            'a' => 'а', 'b' => 'б', 'd' => 'д', 'e' => 'е', 'f' => 'ф', 'g' => 'г',
            'h' => 'ҳ', 'i' => 'и', 'j' => 'ж', 'k' => 'к', 'l' => 'л', 'm' => 'м',
            'n' => 'н', 'o' => 'о', 'p' => 'п', 'q' => 'қ', 'r' => 'р', 's' => 'с',
            't' => 'т', 'u' => 'у', 'v' => 'в', 'x' => 'х', 'y' => 'й', 'z' => 'з',
        ];
        foreach ($letters as $latin => $cyrillic) {
            $map[$latin] = $cyrillic;
            $map[mb_strtoupper($latin)] = mb_strtoupper($cyrillic);
        }

        self::$map = $map;

        return $map;
    }
}
