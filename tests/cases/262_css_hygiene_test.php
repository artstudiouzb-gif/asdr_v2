<?php

declare(strict_types=1);

// Гигиена публичного CSS. Два сторожа против того, из-за чего правки «не
// срабатывают»: мёртвых повторов одного селектора и роста !important.

/**
 * Разбирает CSS на правила с учётом вложенности @media/@supports: повтор
 * селектора внутри медиазапроса законен, а в одном контексте — нет.
 *
 * @return list<array{ctx:string, sel:string, body:string, line:int}>
 */
function css_rules(string $css): array
{
    $out = [];
    $stack = [];
    $len = strlen($css);
    $i = 0;
    $head = 0;
    while ($i < $len) {
        if ($css[$i] === '/' && substr($css, $i, 2) === '/*') {
            $end = strpos($css, '*/', $i);
            $i = $end === false ? $len : $end + 2;
            continue;
        }
        if ($css[$i] === '{') {
            $selector = substr($css, $head, $i - $head);
            // Комментарии между правилами в «голову» селектора не входят.
            $selector = trim((string) preg_replace('#/\*.*?\*/#s', '', $selector));
            if (str_starts_with($selector, '@')) {
                $stack[] = $selector;
                $i++;
                $head = $i;
                continue;
            }
            $depth = 1;
            $j = $i + 1;
            while ($j < $len && $depth > 0) {
                if ($css[$j] === '{') {
                    $depth++;
                } elseif ($css[$j] === '}') {
                    $depth--;
                }
                $j++;
            }
            $out[] = [
                'ctx' => implode(' | ', $stack),
                'sel' => (string) preg_replace('/\s+/', ' ', $selector),
                'body' => substr($css, $i + 1, $j - $i - 2),
                // Строку берём по позиции «{»: перед селектором мог стоять комментарий.
                'line' => substr_count($css, "\n", 0, $i) + 1,
            ];
            $i = $j;
            $head = $i;
            continue;
        }
        if ($css[$i] === '}') {
            array_pop($stack);
            $i++;
            $head = $i;
            continue;
        }
        $i++;
    }

    return $out;
}

/** @return array<string,string> имена свойств правила (без пользовательских) */
function css_props(string $body): array
{
    $props = [];
    foreach (explode(';', $body) as $decl) {
        if (!str_contains($decl, ':')) {
            continue;
        }
        [$name, $value] = explode(':', $decl, 2);
        $name = strtolower(trim($name));
        if ($name !== '' && !str_starts_with($name, '--')) {
            $props[$name] = trim($value);
        }
    }

    return $props;
}

/** @return list<string> публичные таблицы стилей (без собранных и админских) */
function public_css_files(): array
{
    $files = array_merge(
        glob(APP_ROOT . '/public/assets/css/*.css') ?: [],
        glob(APP_ROOT . '/public/assets/css/blocks/*.css') ?: []
    );

    return array_values(array_filter($files, static function (string $path): bool {
        $name = basename($path);

        return !str_contains($name, '.min.') && !str_starts_with($name, 'admin');
    }));
}

test('Публичный CSS не хранит мёртвых повторов одного селектора', function () {
    $dead = [];
    foreach (public_css_files() as $path) {
        $groups = [];
        foreach (css_rules((string) file_get_contents($path)) as $rule) {
            $groups[$rule['ctx'] . '###' . $rule['sel']][] = $rule;
        }
        foreach ($groups as $items) {
            if (count($items) < 2) {
                continue;
            }
            for ($idx = 0; $idx < count($items) - 1; $idx++) {
                $mine = css_props($items[$idx]['body']);
                if ($mine === []) {
                    continue;
                }
                $later = [];
                foreach (array_slice($items, $idx + 1) as $next) {
                    $later += css_props($next['body']);
                    $later = array_merge($later, css_props($next['body']));
                }
                // Все свойства заданы заново ниже — при равной специфичности
                // выигрывает последнее объявление, значит это правило мёртвое.
                $overridden = array_intersect(array_keys($mine), array_keys($later));
                if (count($overridden) === count($mine)) {
                    $dead[] = basename($path) . ':' . $items[$idx]['line'] . ' ' . $items[$idx]['sel'];
                }
            }
        }
    }

    assert_same([], $dead, "мёртвые повторы (правило ниже перекрывает целиком):\n      " . implode("\n      ", $dead));
});

test('Число !important в публичном CSS не растёт', function () {
    // Потолок — текущее состояние после уборки. Значение может только
    // уменьшаться: каждый !important это правка, не выигравшая по
    // специфичности, и следующая правка поверх него становится непредсказуемой.
    $limit = 450;

    $total = 0;
    $perFile = [];
    foreach (public_css_files() as $path) {
        $n = substr_count((string) file_get_contents($path), '!important');
        $total += $n;
        if ($n > 0) {
            $perFile[basename($path)] = $n;
        }
    }

    arsort($perFile);
    $top = [];
    foreach (array_slice($perFile, 0, 5, true) as $name => $n) {
        $top[] = $name . '=' . $n;
    }

    assert_true(
        $total <= $limit,
        'стало ' . $total . ' при потолке ' . $limit . '; больше всего: ' . implode(', ', $top)
    );
});
