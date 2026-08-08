<?php

declare(strict_types=1);

use App\Core\BlockRenderer;

// Сетки блоков «Преимущества» и «Этапы» считают колонки от числа элементов:
// пять карточек при четырёх колонках давали ряд 4+1 с одинокой карточкой, а
// четыре этапа при жёстких пяти колонках обрывали линию хронологии.

/** Собирает CSS блока с заданным числом однотипных элементов. */
function grid_block_css(string $type, int $count, array $extra = []): string
{
    $items = [];
    for ($i = 1; $i <= $count; $i++) {
        $items[] = ['title' => 'Пункт ' . $i, 'text' => 'Описание', 'year' => (string) (2020 + $i)];
    }
    $rendered = BlockRenderer::render([
        'id' => 500 + $count,
        'type' => $type,
        'data' => json_encode($extra + ['items' => $items], JSON_UNESCAPED_UNICODE),
        'custom_css' => '',
    ]);

    return (string) ($rendered['css'] ?? '');
}

test('Преимущества: в последнем ряду не остаётся одинокой карточки', function () {
    $columns = static function (int $count): int {
        $css = grid_block_css('advantages', $count, ['variant' => 'grid']);
        preg_match('/--adv-cols:(\d+)/', $css, $m);

        return (int) ($m[1] ?? 0);
    };

    assert_same(2, $columns(2));
    assert_same(3, $columns(3));
    assert_same(4, $columns(4));
    assert_same(3, $columns(5), 'пятёрка раскладывается 3+2, а не 4+1');
    assert_same(4, $columns(6));
    assert_same(4, $columns(7));
    assert_same(4, $columns(8));
    assert_same(3, $columns(9), 'девятка ложится 3+3+3, а не 4+4+1');

    foreach (range(2, 12) as $count) {
        $cols = $columns($count);
        assert_true($cols > 0, 'колонки не посчитаны для ' . $count);
        assert_true($count % $cols !== 1, "остаётся одинокая карточка: {$count} элементов в {$cols} колонок");
    }
});

test('Этапы: колонок ровно по числу этапов, дальше карусель', function () {
    $columns = static function (int $count): int {
        $css = grid_block_css('stages', $count);
        preg_match('/--stages-count:(\d+)/', $css, $m);

        return (int) ($m[1] ?? 0);
    };

    assert_same(3, $columns(3));
    assert_same(4, $columns(4), 'четыре этапа занимают всю ширину, без пустой пятой колонки');
    assert_same(5, $columns(5));
    // Больше пяти — горизонтальная карусель, ширину колонки задаёт она сама.
    assert_same(5, $columns(7));
});

test('Этапы: сплошной полосы во всю ширину нет — линия кончается на последней точке', function () {
    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/gov-theme.css');
    // Полоса .stages::before тянулась от left:0 до right:0 и продолжалась
    // за последнюю точку хвостом на всю пустую часть ряда.
    assert_not_contains('.stages::before', $css);
    assert_contains('.stage:not(:last-child)::before', $css);
});
