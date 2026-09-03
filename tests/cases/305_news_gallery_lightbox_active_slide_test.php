<?php

declare(strict_types=1);

/**
 * Тест корректного открытия активного слайда галереи новости в лайтбоксе:
 * 1. В news-detail.css неактивные абсолютные слайды получают pointer-events: none и z-index: 0,
 *    а активный слайд (.is-active) — pointer-events: auto и z-index: 1.
 * 2. .newsdetail-gallery__main имеет явный курсор zoom-in.
 * 3. В frontend.js галерея сохраняет root.__ndgShow = show для синхронизации.
 * 4. Обработчик клика лайтбокса при клике на фото внутри [data-ndgallery] всегда находит
 *    текущий активный слайд (.newsdetail-gallery__slide.is-active).
 * 5. Контейнер .newsdetail-gallery__main имеет прямой клик-хэндлер, открывающий активный слайд.
 * 6. Навигация внутри лайтбокса синхронизирует слайдер на странице через gallery.__ndgShow(galleryIndex).
 */

test('Стили галереи: неактивные слайды не перехватывают клики у активного слайда', function () {
     = dirname(__DIR__, 2);
     = (string) file_get_contents( . '/public/assets/css/blocks/news-detail.css');

    assert_contains('.newsdetail-gallery__slide {', );
    assert_contains('pointer-events: none;', );
    assert_contains('z-index: 0;', );
    assert_contains('.newsdetail-gallery__slide.is-active { opacity: 1; pointer-events: auto; z-index: 1; }', );
    assert_contains('.newsdetail-gallery__main { position: relative; aspect-ratio: 16/9; border-radius: var(--radius, 14px); overflow: hidden; background: #16283f; cursor: zoom-in; }', );
});

test('Frontend JS: лайтбокс галереи новости открывает именно активный слайд и синхронизирует навигацию', function () {
     = dirname(__DIR__, 2);
     = (string) file_get_contents( . '/public/assets/js/frontend.js');

    assert_contains('root.__ndgShow = show;', , 'Слайдер должен публиковать функцию переключения слайда');
    assert_contains("var activeImg = gallery.querySelector('.newsdetail-gallery__slide.is-active');", , 'Клик по фото должен разрешаться в активный слайд галереи');
    assert_contains("[data-ndgallery] .newsdetail-gallery__main", , 'Контейнер слайдера должен открывать активный слайд');
    assert_contains("typeof gallery.__ndgShow === 'function'", , 'Навигация в лайтбоксе должна синхронизировать слайдер');
});
