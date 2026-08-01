<?php

declare(strict_types=1);

/**
 * Типографика контентных страниц: ширина текста и вес ссылок.
 *
 * Мера строки в 65–80 знаков — типографская норма, но владелец выбрал полную
 * ширину колонки: страницы агентства это в основном перечни и документы, и
 * узкая колонка оставляла справа пустое поле. Токен --rich-measure остался у
 * заголовка текстового блока.
 */

test('Контент: ширину текста новости не ограничиваем', function () {
    $css = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/rich-content.css');

    assert_not_contains(':where(.rich-content) > :is(p, ul, ol, dl, h2, h3, h4, h5, h6, blockquote)', $css);

    // Токен остаётся: им пользуется заголовок текстового блока.
    assert_contains('--rich-measure:', $css);
    $theme = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/gov-theme.css');
    assert_contains('.block-text__title { max-width: var(--rich-measure, 72ch); }', $theme);
});

test('Контент: маркер списка — восьмиконечная звезда', function () {
    $css = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/rich-content.css');

    // Руб-эль-Хизб: восемь внешних вершин на радиусе R и восемь внутренних на
    // 0.765R. Форма держится от 10px, поэтому размер задан в пикселях.
    assert_contains(':where(.rich-content) ul > li::before', $css);
    assert_contains('50% 0%, 64.7% 14.6%, 85.4% 14.6%', $css);
    assert_contains('width: 10px;', $css);
    // Нумерованные списки остаются с обычными цифрами.
    assert_contains(':where(.rich-content) ol li::marker', $css);
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
