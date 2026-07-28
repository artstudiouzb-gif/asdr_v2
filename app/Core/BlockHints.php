<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Предупреждения редактору о полях, которые заполнены, но не сработают.
 *
 * Блоки местами молча игнорируют ввод: кнопка без ссылки не выводится, ручные
 * карточки не показываются при автоматическом источнике, слайд без фотографии
 * отбрасывается при сохранении. Раньше об этом можно было узнать, только
 * открыв сайт и не найдя своего текста.
 */
final class BlockHints
{
    /**
     * @param array<string,mixed> $data данные блока после нормализации
     * @return list<string> сообщения для показа редактору
     */
    public static function forBlock(string $type, array $data): array
    {
        $hints = [];
        $filled = static fn (string $key): bool => trim((string) ($data[$key] ?? '')) !== '';

        // Кнопка выводится только когда есть и подпись, и ссылка.
        foreach ([
            ['button_text', 'button_url', 'Кнопка'],
            ['button2_text', 'button2_url', 'Вторая кнопка'],
            ['all_text', 'all_url', 'Ссылка «Все…»'],
            ['cta_button_text', 'cta_button_url', 'Кнопка в блоке призыва'],
            ['video_button_text', 'video_button_url', 'Кнопка видео'],
        ] as [$textKey, $urlKey, $label]) {
            if (!$filled($textKey) || $filled($urlKey)) {
                continue;
            }
            // У «Подписки» это подпись кнопки отправки формы, ссылка ей не нужна.
            if ($textKey === 'button_text' && $type === 'subscribe') {
                continue;
            }
            // Блоки-обёртки сами подставляют ссылку на раздел при пустом поле.
            if ($textKey === 'all_text' && self::fillsSectionLink($type, $data)) {
                continue;
            }
            $hints[] = $label . ': подпись задана, но не указана ссылка — на сайте она не появится.';
        }

        // Автоматический источник подменяет ручной список целиком.
        if (in_array($type, ['image_cards', 'media_gallery'], true)) {
            $source = (string) ($data['source'] ?? 'manual');
            $items = is_array($data['items'] ?? null) ? $data['items'] : [];
            if ($source !== 'manual' && $items !== []) {
                $hints[] = 'Выбран автоматический источник, поэтому добавленные вручную элементы ('
                    . count($items) . ') не показываются. Выберите источник «Вручную» или очистите список.';
            }
        }

        // Слайд без фотографии отбрасывается при сохранении.
        if ($type === 'slider') {
            $slides = is_array($data['slides'] ?? null) ? $data['slides'] : [];
            $empty = 0;
            foreach ($slides as $slide) {
                if (trim((string) (is_array($slide) ? ($slide['image'] ?? '') : '')) === '') {
                    $empty++;
                }
            }
            if ($empty > 0) {
                $hints[] = 'Слайдов без изображения: ' . $empty . '. Такие слайды не сохраняются — добавьте фото.';
            }
        }

        // Блок формы без выбранной формы виден только как заглушка.
        if ($type === 'form' && (int) ($data['form_id'] ?? 0) === 0) {
            $hints[] = 'Форма не выбрана — на сайте вместо неё будет предупреждение. Выберите форму в поле блока.';
        }

        return $hints;
    }

    /**
     * Подставляет ли блок ссылку «Все…» сам (BlockRenderer::enrichData).
     *
     * @param array<string,mixed> $data
     */
    private static function fillsSectionLink(string $type, array $data): bool
    {
        if (in_array($type, ['news_latest', 'news_feature', 'news_docs'], true)) {
            return true;
        }
        $source = (string) ($data['source'] ?? 'manual');

        return ($type === 'image_cards' && $source === 'projects')
            || ($type === 'media_gallery' && $source === 'albums');
    }

    /**
     * Пустой ли блок после сохранения: такой не выводится на сайте, и редактору
     * стоит сказать об этом сразу, а не оставлять гадать.
     *
     * @param array<string,mixed> $block строка блока (id/type/data)
     */
    public static function rendersEmpty(array $block): bool
    {
        $rendered = BlockRenderer::render($block);

        return empty($rendered['hidden']) && BlockRenderer::isVisuallyEmpty((string) $rendered['html']);
    }
}
