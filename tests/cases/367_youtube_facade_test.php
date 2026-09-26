<?php

declare(strict_types=1);

use App\Core\SocialEmbed;
use App\Core\YoutubeFacade;

/*
 * Ролики YouTube на публичных страницах — обложка до нажатия. Ленивый iframe
 * всё равно загружал скрипты YouTube, как только ролик подходил к экрану.
 * Плеер (домен без кук) собирает frontend.js по клику; без JS ссылка ведёт
 * на сам ролик.
 */
test('Обложка YouTube: превью и ссылка вместо iframe, плеер без кук', function (): void {
    $html = YoutubeFacade::html('dQw4w9WgXcQ', 'Ролик «О нас»', 'block-text__media-video');
    assert_not_contains('<iframe', $html);
    assert_contains('data-yt-id="dQw4w9WgXcQ"', $html);
    assert_contains('https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg', $html);
    assert_contains('href="https://www.youtube.com/watch?v=dQw4w9WgXcQ"', $html, 'без JS ролик открывается ссылкой');
    assert_contains('aria-label="', $html);
    assert_contains('Ролик «О нас»', $html);
    assert_contains('class="yt-facade block-text__media-video"', $html);

    assert_same('', YoutubeFacade::html('bad"id', 'x'), 'неверный id не выводится');
    assert_same('', YoutubeFacade::html('', 'x'));
});

test('YouTube в тексте новости, во врезке и в блоке «Текст» — через обложку', function (): void {
    $news = SocialEmbed::transform('<p>https://www.youtube.com/watch?v=dQw4w9WgXcQ</p>');
    assert_contains('data-yt-facade', $news);
    assert_not_contains('<iframe', $news);

    $embed = (string) file_get_contents(APP_ROOT . '/templates/blocks/embed.php');
    assert_contains('YoutubeFacade::html(', $embed);
    $text = (string) file_get_contents(APP_ROOT . '/templates/blocks/text.php');
    assert_contains('YoutubeFacade::html(', $text);
    assert_not_contains('youtube-nocookie.com/embed/<?=', $text, 'iframe YouTube в блоке «Текст» больше не выводится сразу');

    $js = (string) file_get_contents(APP_ROOT . '/public/assets/js/frontend.js');
    assert_contains("closest('[data-yt-facade] .yt-facade__play')", $js);
    assert_contains("'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(id)", $js, 'адрес плеера собирает скрипт, из разметки — только id');
    assert_contains('/^[A-Za-z0-9_-]{11}$/.test(id)', $js);
});
