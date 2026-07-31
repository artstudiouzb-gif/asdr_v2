<?php

declare(strict_types=1);

/**
 * Типографика контентных страниц: мера строки и вес ссылок.
 *
 * Без ограничения ширины строка на широком экране уходит за 120 знаков при
 * норме 65–80, и глаз теряет начало следующей строки. Ограничение вешаем на
 * сам текст, а не на контейнер, чтобы картинки и таблицы оставались широкими.
 */

test('Контент: у текста задана мера строки', function () {
    $css = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/rich-content.css');

    assert_contains('--rich-measure:', $css);
    assert_contains(':where(.rich-content) > :is(p, ul, ol, dl, h2, h3, h4, h5, h6, blockquote)', $css);
    assert_contains('max-width: var(--rich-measure);', $css);

    // Заголовок текстового блока держит ту же ширину, что и текст под ним.
    $theme = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/gov-theme.css');
    assert_contains('.block-text__title { max-width: var(--rich-measure, 72ch); }', $theme);
});

test('Контент: ссылки в тексте не полужирные', function () {
    $css = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/rich-content.css');

    // Страница с перечнем указов состоит из ссылок: полужирное начертание
    // превращало её в сплошной акцент.
    assert_false(
        (bool) preg_match('/:where\(\.rich-content\) a \{[^}]*font-weight:\s*[6-9]00/s', $css),
        'ссылки в тексте не должны быть полужирными'
    );
});

test('Контент: первый блок не отбивается от шапки страницы дважды', function () {
    $theme = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/gov-theme.css');

    assert_contains('.content-pagehead + .cms-block,', $theme);
    assert_contains('.content-pagehead + .layout .cms-block:first-child', $theme);
});
