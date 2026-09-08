<?php

declare(strict_types=1);

/**
 * Отказ `json_encode()` нельзя прятать.
 *
 * Функция объявлена как `string|false`, и `false` она отдаёт на негодных
 * данных — чаще всего на битой кодировке в тексте, который набрал редактор.
 * Дальше расходятся два способа спрятать этот отказ, и оба уже случались:
 *
 *  - результат уходит типизированным параметром (`Setting::set(string)`,
 *    `htmlspecialchars(string)`) — под `strict_types` это `TypeError` без
 *    единого слова о причине. Так падало сохранение пресетов «Дизайна»,
 *    конструкторов шапки и подвала;
 *  - результат приводится `(string)`, и `false` превращается в **пустую
 *    строку**. Это хуже: ошибки нет вовсе. Сторож молча забыл бы, о чём уже
 *    сообщал, а подписи к фотографиям новости исчезли бы все разом — вместе с
 *    `JSON.parse` в скрипте, которому досталась пустая строка.
 *
 * Решение зависит от происхождения данных, и это единственная развилка:
 * **свои данные — бросаем** (`JSON_THROW_ON_ERROR`), потому что записать
 * состояние неверно опаснее, чем не записать; **редакторские — подставляем
 * замену** (`JSON_INVALID_UTF8_SUBSTITUTE`), потому что ронять публичную
 * страницу или закрывать конструктор из-за одного байта нельзя.
 */

/**
 * Вызовы `json_encode()` вместе с их выражением целиком.
 *
 * Разбор именно по выражению, а не по строке: после правки флаг и сам вызов
 * часто оказываются на разных строках, и построчная проверка объявила бы
 * защищённый вызов незащищённым — то есть требовала бы «починить» уже
 * починенное.
 *
 * @return list<array{file: string, line: int, expr: string, cast: bool}>
 */
function json_encode_call_sites(): array
{
    $sites = [];

    foreach ([APP_ROOT . '/app', APP_ROOT . '/templates'] as $root) {
        $dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
        foreach ($dir as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $src = (string) file_get_contents($file->getPathname());
            if (preg_match_all('/(\(string\)\s*)?json_encode\s*\(/', $src, $m, PREG_OFFSET_CAPTURE) === 0) {
                continue;
            }

            foreach ($m[0] as $i => $match) {
                $open = strpos($src, '(', (int) $match[1] + strlen($m[0][$i][0]) - 1);
                if ($open === false) {
                    continue;
                }

                $depth = 0;
                $len = strlen($src);
                $close = $open;
                for ($j = $open; $j < $len; $j++) {
                    if ($src[$j] === '(') {
                        $depth++;
                    } elseif ($src[$j] === ')') {
                        $depth--;
                        if ($depth === 0) {
                            $close = $j;
                            break;
                        }
                    }
                }

                $sites[] = [
                    'file' => str_replace(APP_ROOT . '/', '', $file->getPathname()),
                    'line' => substr_count($src, "\n", 0, (int) $match[1]) + 1,
                    'expr' => substr($src, (int) $match[1], $close - (int) $match[1] + 1),
                    'cast' => $m[1][$i][1] !== -1,
                ];
            }
        }
    }

    return $sites;
}

/** Вызов защищён, если отказ либо назван, либо заменён. */
function json_encode_guarded(string $expr): bool
{
    return str_contains($expr, 'JSON_THROW_ON_ERROR')
        || str_contains($expr, 'JSON_INVALID_UTF8_SUBSTITUTE');
}

test('Число незащищённых json_encode с приведением только уменьшается', function (): void {
    // Оставшиеся — служебные данные, где битой кодировке взяться неоткуда:
    // адреса Cloudflare, claims Web Push, состояние ограничителя частоты.
    // Отдельный случай — `Integrity`: там json_encode считает хеш эталона,
    // и смена флагов сдвинула бы все контрольные суммы. Поэтому здесь не ноль,
    // а бюджет: он может только уменьшаться, как и остальные в проекте.
    $budget = quality_budget('json_encode_unguarded');

    assert_true(
        $budget['value'] <= $budget['ceiling'],
        'незащищённых json_encode с приведением стало больше: ' . $budget['value']
            . ' > ' . $budget['ceiling'] . '. Данные редактора — JSON_INVALID_UTF8_SUBSTITUTE, '
            . 'свои — JSON_THROW_ON_ERROR. Где: ' . $budget['detail']
    );
});

test('json_encode в Setting::set бросает вместо TypeError', function (): void {
    // Настройка — свои данные: записать её неверно опаснее, чем не записать.
    $bad = [];
    foreach (json_encode_call_sites() as $site) {
        if (!str_contains($site['expr'], 'json_encode')) {
            continue;
        }

        $src = (string) file_get_contents(APP_ROOT . '/' . $site['file']);
        $before = substr($src, 0, strpos($src, $site['expr']) ?: 0);
        // Вызов внутри Setting::set(...) — ищем открытый вызов слева.
        $tail = substr($before, -260);
        if (!str_contains($tail, 'Setting::set(')) {
            continue;
        }
        if (!str_contains($site['expr'], 'JSON_THROW_ON_ERROR')) {
            $bad[] = $site['file'] . ':' . $site['line'];
        }
    }

    assert_same([], $bad, 'json_encode уходит в Setting::set без JSON_THROW_ON_ERROR: ' . implode(', ', $bad));
});

test('Вывод редакторского JSON в разметку переживает битую кодировку', function (): void {
    // Здесь бросать нельзя: публичная страница и конструктор обязаны
    // открыться. Замена символа оставляет строку читаемой, а запасное
    // значение не даёт скрипту получить пустой атрибут.
    $views = [
        'app/Views/site/news_show.php' => 'подписи к фотографиям новости',
        'app/Views/admin/header/index.php' => 'подписи элементов конструктора шапки',
    ];

    foreach ($views as $file => $what) {
        $src = (string) file_get_contents(APP_ROOT . '/' . $file);
        assert_contains('JSON_INVALID_UTF8_SUBSTITUTE', $src, $what . ': нет замены негодного байта');
        assert_contains("?: '[]'", $src, $what . ': нет запасного значения для JSON.parse');
    }
});

test('Флаги json_encode делают то, ради чего поставлены', function (): void {
    // Проверка самого механизма, а не только его наличия в коде: если бы
    // JSON_INVALID_UTF8_SUBSTITUTE не спасал от `false`, все правила выше
    // были бы карго-культом.
    $broken = ["label" => "тест\xB1\x31\xB2"];

    assert_same(false, json_encode($broken, JSON_UNESCAPED_UNICODE), 'битая кодировка обязана давать false');
    assert_true(
        is_string(json_encode($broken, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)),
        'с заменой символа json_encode обязана вернуть строку'
    );

    $threw = false;
    try {
        json_encode($broken, JSON_THROW_ON_ERROR);
    } catch (\JsonException) {
        $threw = true;
    }
    assert_true($threw, 'JSON_THROW_ON_ERROR обязан бросать JsonException');
});
