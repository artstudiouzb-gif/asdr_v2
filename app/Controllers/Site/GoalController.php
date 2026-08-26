<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Media;
use App\Models\Goal;

/**
 * Случайная цель для виджета-карусели.
 *
 * Отдельный адрес нужен из-за кэша: страница кэшируется общим ключом, и цель,
 * выбранная при её сборке, была бы одной и той же для всех посетителей до
 * сброса кэша. Поэтому страница отдаёт запасную цель, а скрипт просит свежую
 * здесь — этот ответ не кэшируется никогда.
 *
 * Наружу уходят только кадры: имя цели служебное и в разметку не попадает.
 */
final class GoalController
{
    public function random(): void
    {
        $random = Goal::random();

        header('Content-Type: text/html; charset=utf-8');
        // Ответ обязан быть свежим у каждого запроса — иначе браузер или
        // прокси вернут ту же цель, и вся затея теряет смысл.
        header('Cache-Control: no-store, max-age=0');
        header('X-Robots-Tag: noindex');

        if ($random === null) {
            http_response_code(204);
            return;
        }

        $images = $random['images'];
        $total = count($images);
        $html = '';
        foreach ($images as $index => $image) {
            $html .= '<div class="block-slider__slide' . ($index === 0 ? ' is-active' : '') . '"'
                . ' role="group" aria-roledescription="' . htmlspecialchars(t('Слайд'), ENT_QUOTES) . '"'
                . ' aria-label="' . ($index + 1) . ' ' . htmlspecialchars(t('из'), ENT_QUOTES) . ' ' . $total . '"'
                . ' aria-hidden="' . ($index === 0 ? 'false' : 'true') . '">'
                . Media::picture(
                    (string) $image['image'],
                    (string) $image['alt'],
                    null,
                    null,
                    '',
                    $index !== 0,
                    '(max-width: 900px) 100vw, 320px'
                )
                . '</div>';
        }

        echo $html;
    }
}
