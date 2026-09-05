<?php

declare(strict_types=1);

/*
 * Проверка сохранения высоты счетчиков, рамок медиатеки, hover-теней
 * и настроек «Стиля карточек» на главной странице.
 *
 * Компактные стили главной страницы не должны ломать глобальные настройки темы:
 * - Блок счётчиков должен сохранять пропорции и высоту из gov-theme.css;
 * - Медиакарточки должны сохранять рамку, фон и скругление;
 * - Карточки должны иметь тень и подъём при наведении (:hover);
 * - «Стиль карточек» (flat/soft/elevated) через --card-shadow должен работать
 *   на карточках главной без принудительного `box-shadow: none`.
 */

test('public-home.css не сплющивает блок счётчиков и не перебивает его отступы', function () {
    $homeCss = (string) file_get_contents(APP_ROOT . '/public/assets/css/public-home.css');

    assert_not_contains('.block-counters--panel-card', $homeCss, 'Счётчики не должны переопределяться в public-home.css');
    assert_not_contains('.block-counters--row', $homeCss, 'Счётчики не должны переопределяться в public-home.css');
    assert_not_contains('margin-top: -24px', $homeCss, 'Счётчики должны сохранять оригинальное перекрытие hero');
});

test('public-home.css не снимает рамку, фон и скругление с карточек медиатеки', function () {
    $homeCss = (string) file_get_contents(APP_ROOT . '/public/assets/css/public-home.css');

    assert_not_contains('.mediacard', $homeCss, 'Медиакарточки не должны переопределяться в public-home.css');
});

test('public-home.css не подавляет тени и трансформации карточек на :hover', function () {
    $homeCss = (string) file_get_contents(APP_ROOT . '/public/assets/css/public-home.css');

    assert_not_contains('transform: none; box-shadow: none', $homeCss, 'Hover-тени карточек должны работать');
    assert_not_contains(':hover { transform: none', $homeCss);
});

test('Карточки главной страницы подчиняются настройке «Стиль карточек»', function () {
    $homeCss = (string) file_get_contents(APP_ROOT . '/public/assets/css/public-home.css');

    // feature-card не должен принудительно выставлять box-shadow: none
    preg_match('/\.site-home\s+\.feature-card\s*\{([^}]*)\}/', $homeCss, $m);
    $cardBody = $m[1] ?? '';
    assert_not_contains('box-shadow: none', $cardBody, 'feature-card должен наследовать --card-shadow');
    assert_not_contains('box-shadow:', $cardBody, 'feature-card не должен жестко задавать тень');
});
