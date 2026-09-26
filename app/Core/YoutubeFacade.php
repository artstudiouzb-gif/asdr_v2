<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Обложка YouTube вместо плеера до клика.
 *
 * Ленивый iframe всё равно тянул ~1 МБ скриптов YouTube, как только ролик
 * подходил к экрану, — даже если его не смотрели. Здесь на странице только
 * картинка-превью и ссылка на ролик: без JS она открывает YouTube, с JS
 * (frontend.js) заменяется плеером по нажатию. Скрипт берёт из разметки
 * только id ролика и сам собирает адрес плеера на домене без кук.
 */
final class YoutubeFacade
{
    /**
     * @param string $class классы, которые на месте iframe давала обёртка
     *                      (размер задают её правила) — переходят и к плееру
     */
    public static function html(string $id, string $title, string $class = ''): string
    {
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $id) !== 1) {
            return '';
        }
        $title = trim($title) !== '' ? trim($title) : 'YouTube';
        $classes = trim('yt-facade ' . $class);

        return '<div class="' . htmlspecialchars($classes, ENT_QUOTES) . '" data-yt-facade'
            . ' data-yt-id="' . $id . '"'
            . ' data-yt-title="' . htmlspecialchars($title, ENT_QUOTES) . '">'
            . '<img class="yt-facade__thumb" src="' . htmlspecialchars(Video::youtubeThumbnail($id), ENT_QUOTES) . '"'
            . ' alt="" width="480" height="360" loading="lazy" decoding="async">'
            . '<a class="yt-facade__play" href="https://www.youtube.com/watch?v=' . $id . '" target="_blank" rel="noopener"'
            . ' aria-label="' . htmlspecialchars(t('Смотреть видео') . ': ' . $title, ENT_QUOTES) . '"></a>'
            . '</div>';
    }
}
