<?php

declare(strict_types=1);

test('Сдвиг при наведении в публичном CSS уважает «Уменьшить движение»', function (): void {
    // Карточки, кнопки и иконки поднимаются или увеличиваются при наведении.
    // Посетителю с системной настройкой «Уменьшить движение» это мешает, и
    // восемь файлов её не учитывали: правило жило в базе и теме, а поздние
    // файлы и блочные стили писались мимо. Сторож грубый намеренно — файл с
    // движением на :hover/:active/:focus или с @keyframes обязан иметь ветку
    // prefers-reduced-motion; какие именно селекторы она гасит, видно в ревью.
    $root = dirname(__DIR__, 2) . '/public/assets/css';
    $files = array_merge(glob($root . '/*.css') ?: [], glob($root . '/blocks/*.css') ?: []);

    $missing = [];
    foreach ($files as $file) {
        $name = basename($file);
        if (str_ends_with($name, '.min.css') || str_starts_with($name, 'admin')) {
            continue;
        }

        $css = (string) preg_replace('#/\*.*?\*/#s', '', (string) file_get_contents($file));
        if (str_contains($css, 'prefers-reduced-motion')) {
            continue;
        }

        $moves = str_contains($css, '@keyframes');
        preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $rules, PREG_SET_ORDER);
        foreach ($rules as [, $selector, $body]) {
            if (preg_match('/:(hover|active|focus)/', $selector) === 1
                && preg_match('/transform\s*:\s*(?!none)[^;]*(translate|scale|rotate)/', $body) === 1) {
                $moves = true;
                break;
            }
        }

        if ($moves) {
            $missing[] = substr($file, strlen($root) + 1);
        }
    }

    assert_same([], $missing, 'движение без ветки prefers-reduced-motion: ' . implode(', ', $missing));
});
