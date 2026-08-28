<?php

declare(strict_types=1);

use App\Core\NewsFeedRhythm;

/*
 * Ритм ленты новостей: каждая пятая карточка широкая.
 *
 * Двенадцать одинаковых карточек читаются как таблица. Широкая карточка
 * разбивает сетку и показывает анонс, который в компактную не помещается.
 *
 * Главное здесь — арифметика, а не украшение: широкая карточка занимает две
 * ячейки, поэтому группа «4 компактные + 1 широкая» = 6 ячеек. Шесть делится
 * и на 4 колонки, и на 3, и на 2 — ряды остаются полными на любой ширине. Если
 * поменять размер группы или страницы, не сверившись с этим, посреди ленты
 * появится дыра.
 */

test('Группа ритма укладывается в ряды при любом числе колонок', function () {
    assert_same(6, NewsFeedRhythm::CELLS_PER_GROUP, 'четыре компактные плюс широкая на две ячейки');

    $cells = (NewsFeedRhythm::PAGE_SIZE / NewsFeedRhythm::GROUP) * NewsFeedRhythm::CELLS_PER_GROUP;
    assert_same(12, $cells, 'страница — две полные группы');
    foreach ([4, 3, 2, 1] as $columns) {
        assert_same(0, $cells % $columns, "при {$columns} колонках последний ряд остаётся полным");
    }
    assert_same(0, NewsFeedRhythm::PAGE_SIZE % NewsFeedRhythm::GROUP, 'страница не должна обрывать группу');
});

test('Широкой становится каждая пятая карточка сетки', function () {
    $wide = [];
    for ($i = 0; $i < NewsFeedRhythm::PAGE_SIZE; $i++) {
        if (NewsFeedRhythm::isWide($i)) {
            $wide[] = $i;
        }
    }

    assert_same([4, 9], $wide, 'пятая и десятая карточки страницы');
    assert_false(NewsFeedRhythm::isWide(0), 'первая карточка сетки не широкая: над ней уже крупная новость');
});

test('Лента выводит широкую карточку с анонсом, а размер страницы берёт из ритма', function () {
    $listing = (string) file_get_contents(APP_ROOT . '/app/Views/site/_news_list.php');
    $controller = (string) file_get_contents(APP_ROOT . '/app/Controllers/Site/NewsController.php');

    assert_contains('NewsFeedRhythm::isWide($index)', $listing, 'ритм считает отдельный класс, а не шаблон');
    assert_contains('relnews-card--wide', $listing);
    assert_contains('relnews-card__excerpt', $listing, 'широкая карточка показывает анонс');
    assert_contains('NewsFeedRhythm::PAGE_SIZE', $controller, 'размер страницы задаёт ритм');
});

test('Широкая карточка занимает две ячейки и складывается в одну колонку', function () {
    $css = theme_css();

    assert_contains('.relnews-card--wide { grid-column: span 2;', $css, 'две ячейки, а не вся строка');
    // В одноколоночной сетке `span 2` создал бы вторую колонку и
    // горизонтальную прокрутку — на узком экране растяжение снимается.
    assert_contains('.relnews-card--wide { grid-column: auto; flex-direction: column; }', $css);
    assert_contains('.relnews-card--wide .relnews-card__excerpt', $css, 'анонс оформлен только у широкой карточки');
});
