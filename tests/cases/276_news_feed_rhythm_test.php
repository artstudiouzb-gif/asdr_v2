<?php

declare(strict_types=1);

use App\Core\NewsFeedRhythm;

/*
 * Ритм ленты новостей: цикл «крупная плюс две компактные» → ряд из четырёх
 * компактных → «две компактные плюс крупная».
 *
 * Одинаковые карточки читаются как таблица. Крупная разбивает сетку и
 * показывает анонс, который в компактную не помещается. Отдельной крупной
 * новости над лентой нет: первая карточка цикла и есть главная новость.
 *
 * Главное здесь — арифметика, а не украшение: крупная карточка занимает две
 * ячейки, поэтому цикл из десяти карточек — двенадцать ячеек, то есть три
 * полных ряда при четырёх колонках и шесть при двух. На три колонки цикл не
 * раскладывается, и там ритм выключается в CSS: иначе крупная карточка
 * упирается в последнюю колонку и сетка оставляет дыру.
 */

test('Цикл ритма укладывается в ряды', function () {
    // Две крупные по две ячейки плюс восемь компактных.
    assert_same(12, NewsFeedRhythm::CELLS_PER_CYCLE, 'цикл — двенадцать ячеек');
    assert_same(
        NewsFeedRhythm::CELLS_PER_CYCLE,
        NewsFeedRhythm::CYCLE + count(NewsFeedRhythm::WIDE_POSITIONS),
        'каждая крупная карточка добавляет к циклу лишнюю ячейку'
    );

    $cells = (NewsFeedRhythm::PAGE_SIZE / NewsFeedRhythm::CYCLE) * NewsFeedRhythm::CELLS_PER_CYCLE;
    assert_same(24, $cells, 'страница — два полных цикла');
    // Три колонки в список не входят намеренно: цикл на три колонки не
    // раскладывается, поэтому там ритм выключён в CSS (см. .relnews-card--wide).
    foreach ([4, 2, 1] as $columns) {
        assert_same(0, $cells % $columns, "при {$columns} колонках последний ряд остаётся полным");
    }
    assert_same(0, NewsFeedRhythm::PAGE_SIZE % NewsFeedRhythm::CYCLE, 'страница не должна обрывать цикл');
});

test('Крупные карточки стоят по краям цикла и различаются ролью', function () {
    $slots = [];
    for ($i = 0; $i < NewsFeedRhythm::PAGE_SIZE; $i++) {
        $slot = NewsFeedRhythm::slot($i);
        if ($slot !== NewsFeedRhythm::SLOT_COMPACT) {
            $slots[$i] = $slot;
        }
    }

    // 0 — начало первого ряда, 9 — конец третьего (после двух компактных).
    // Между ними ряд из четырёх компактных карточек.
    assert_same(
        [0 => 'hero', 9 => 'wide', 10 => 'hero', 19 => 'wide'],
        $slots,
        'цикл открывает обложка, замыкает широкая'
    );
    // Виды крупных карточек не взаимозаменяемы: обложка — фотография во всю
    // карточку с текстом поверх (главная новость страницы), широкая — кадр
    // сбоку и текст рядом. Растяжение на две ячейки у них общее.
    assert_true(NewsFeedRhythm::isWide(0) && NewsFeedRhythm::isWide(9), 'обе крупные занимают две ячейки');
    foreach ([1, 2, 3, 4, 5, 6, 7, 8] as $compact) {
        assert_same(NewsFeedRhythm::SLOT_COMPACT, NewsFeedRhythm::slot($compact), "карточка {$compact} остаётся компактной");
    }
});

test('Лента выводит широкую карточку с анонсом, а размер страницы берёт из ритма', function () {
    $listing = (string) file_get_contents(APP_ROOT . '/app/Views/site/_news_list.php');
    $controller = (string) file_get_contents(APP_ROOT . '/app/Controllers/Site/NewsController.php');

    assert_contains('NewsFeedRhythm::slot($index)', $listing, 'ритм считает отдельный класс, а не шаблон');
    assert_contains('relnews-card relnews-card--<?= $slot ?>', $listing, 'класс карточки — её слот');
    assert_contains('relnews-card__excerpt', $listing, 'широкая карточка показывает анонс');
    assert_contains('NewsFeedRhythm::PAGE_SIZE', $controller, 'размер страницы задаёт ритм');
});

test('Широкая карточка занимает две ячейки и складывается в одну колонку', function () {
    $css = theme_css();

    // Класс продублирован в селекторе не для красоты: часть темы
    // blocks/news-detail.css подключается после общего бандла и задаёт
    // `.relnews-card { padding: 0 0 14px }`. Модификатору нужен вес выше, иначе
    // нижний отступ карточки оставлял под фотографией белую полосу.
    assert_true(
        (bool) preg_match('/\.relnews-card\.relnews-card--wide \{\s*grid-column: span 2;/', $css),
        'две ячейки, а не вся строка'
    );
    // В одноколоночной сетке `span 2` создал бы вторую колонку и
    // горизонтальную прокрутку — на узком экране растяжение снимается.
    assert_contains('.relnews-card.relnews-card--wide { grid-column: auto; grid-template-columns: minmax(0, 1fr);', $css);
    assert_contains('.relnews-card--wide .relnews-card__excerpt', $css, 'анонс оформлен только у широкой карточки');
    // Три колонки: цикл на три колонки не раскладывается, поэтому ритм там
    // выключается — иначе крупная карточка упирается в последнюю колонку.
    assert_contains('@media (max-width: 1100px) and (min-width: 1001px)', $css, 'на трёх колонках ритм выключен');
});

test('Дата в карточке новости отбита от края наравне с заголовком', function () {
    // Отступ текста задаёт часть темы blocks/news-detail.css: фотография в
    // карточке идёт «в край», поэтому вставку держит текст. Разметок две — в
    // блоке похожих новостей текст лежит прямо в карточке, в ленте /news
    // завёрнут в `.relnews-card__body`. Пока дата отбивалась селектором с `>`,
    // а заголовок — без него, в ленте число стояло вплотную к рамке, а
    // заголовок рядом был отбит на 14px.
    $part = (string) file_get_contents(APP_ROOT . '/public/assets/css/blocks/news-detail.css');
    assert_contains('.relnews-card > .news-meta', $part, 'дата отбита у плоской разметки');
    assert_contains('.relnews-card > .relnews-card__title,', $part, 'заголовок — тем же селектором, что и дата');
    assert_not_contains("\n.relnews-card__title,\n.relnews-card__excerpt { margin-inline", $part, 'вставка не задаётся мимо прямых потомков');

    assert_contains('.relnews-card__body { display: flex;', theme_css(), 'тело карточки описано в теме');
    assert_true(
        (bool) preg_match('/\.relnews-card__body \{[^}]*padding: 4px 14px 0;/', theme_css()),
        'у тела карточки есть боковой отступ — иначе дата упирается в границу'
    );
});

test('Кадры крупных карточек занимают свою площадь целиком', function () {
    $css = theme_css();

    // Обложка: фотография и есть карточка — кадр лежит подложкой, поверх него
    // затемнение, иначе белый текст читался бы только на удачном снимке.
    assert_contains('.relnews-card--hero .news-cover { position: absolute; inset: 0; }', $css);
    assert_contains('.relnews-card--hero .news-cover::after', $css, 'затемнение под текстом');
    assert_contains('.relnews-card--hero .relnews-card__body', $css, 'текст лежит поверх кадра');

    // Широкая: кадр тянется на всю высоту своей половины. Фиксированная
    // пропорция здесь оставляла пустые поля сверху и снизу — высоту ряда
    // задаёт соседняя компактная карточка, а не эта фотография.
    assert_contains('.relnews-card--wide .news-cover { min-width: 0; }', $css);
    assert_contains('.relnews-card--wide .relnews-card__media { height: 100%; aspect-ratio: auto; }', $css);
    // Кадр занимает ровно одну колонку сетки: карточка растянута на две
    // ячейки, и при произвольной доле её внутренняя граница попадала между
    // колонками — текст начинался не там, где начинается карточка ряда выше.
    assert_contains('grid-template-columns: minmax(0, calc(50% - var(--newsgrid-gap, 24px) / 2)) minmax(0, 1fr);', $css);
    assert_contains('column-gap: var(--newsgrid-gap, 24px);', $css, 'промежуток внутри карточки равен промежутку сетки');
    assert_contains('--newsgrid-gap: 24px;', $css, 'промежуток объявлен один раз — у самой сетки');
    assert_true(
        (bool) preg_match('/\.relnews-card\.relnews-card--wide \{[^}]*align-items: stretch;/', $css),
        'кадр занимает всю высоту карточки'
    );
});

test('Соседние новости — зеркальная пара', function () {
    $view = (string) file_get_contents(APP_ROOT . '/app/Views/site/news_show.php');
    $css = theme_css();

    // Порядок разметки и порядок колонок должны совпадать: у «предыдущей»
    // стрелка → кадр → текст, у «следующей» — текст → кадр → стрелка. Прежде
    // кадр в обеих карточках стоял слева, вправо уезжал только текст, и пара
    // читалась как две разные карточки.
    $next = substr($view, (int) strpos($view, 'adjnews adjnews--next'));
    $body = strpos($next, 'adjnews__body');
    $media = strpos($next, 'adjnews__media');
    $arrow = strpos($next, 'adjnews__arrow');
    assert_true($body !== false && $media !== false && $arrow !== false, 'карточка «следующая» собрана из трёх частей');
    assert_true($body < $media, 'у «следующей» текст идёт перед кадром');
    assert_true($media < $arrow, 'стрелка замыкает карточку «следующей»');

    assert_contains('.adjnews--next { grid-template-columns: minmax(0, 1fr) auto auto;', $css, 'колонки зеркальны разметке');
    // На узком экране зеркало теряет смысл: обе карточки читаются слева направо.
    assert_contains('.adjnews--next .adjnews__arrow { order: -2; }', $css);
    // Кадр на телефоне уступает место заголовку — иначе на текст остаётся
    // колонка в полтора слова.
    assert_contains('.adjnews__media { display: none; }', $css);
});
