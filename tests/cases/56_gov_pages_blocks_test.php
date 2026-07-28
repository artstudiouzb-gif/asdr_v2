<?php

declare(strict_types=1);

use App\Core\BlockRenderer;

test('Блок person_cards: персона с фото и вакантная карточка', function () {
    $out = BlockRenderer::render(['id' => 40, 'type' => 'person_cards', 'custom_css' => null, 'data' => json_encode([
        'title' => 'Руководство Агентства', 'all_text' => 'Все руководство', 'all_url' => '/o-nas',
        'items' => [
            ['photo' => '/uploads/public/d.jpg', 'name' => 'Элёр Ганиев', 'role' => 'Директор Агентства', 'url' => '/rukovoditel'],
            ['photo' => '', 'name' => '', 'role' => 'Заместитель директора', 'url' => ''],
        ],
    ])])['html'];
    assert_contains('cms-block--person_cards', $out);
    assert_contains('Элёр Ганиев', $out);
    assert_contains('person-card--vacant', $out);
    assert_contains('person-card__vacant', $out);
    assert_contains('href="/rukovoditel"', $out);
});

test('Блок timeline: события, кнопка и CTA-карточка', function () {
    $out = BlockRenderer::render(['id' => 41, 'type' => 'timeline', 'custom_css' => null, 'data' => json_encode([
        'title' => 'История Агентства',
        'items' => [['year' => '2017', 'text' => 'Создан центр'], ['year' => '2023+', 'text' => 'Расширение функций']],
        'button_text' => 'Вся история', 'button_url' => '/o-nas',
        'cta_title' => 'Работаем для целей развития', 'cta_text' => 'Экспертиза и данные',
        'cta_button_text' => 'Стратегия', 'cta_button_url' => '/strategy', 'cta_image' => '/uploads/public/t.jpg',
    ])])['html'];
    assert_contains('block-timeline--with-cta', $out);
    assert_contains('timeline-item__year', $out);
    assert_contains('2023+', $out);
    assert_contains('timeline-cta__title', $out);
    assert_contains("url('/uploads/public/t.jpg')", $out);

    // Без CTA-заголовка карточка не выводится.
    $solo = BlockRenderer::render(['id' => 42, 'type' => 'timeline', 'custom_css' => null, 'data' => json_encode([
        'title' => 'История', 'items' => [['year' => '2017', 'text' => 'x']],
    ])])['html'];
    assert_true(!str_contains($solo, 'timeline-cta'), 'CTA-карточка скрыта без заголовка');
});

test('Блок cta_band: заголовок, текст, кнопка; иконка по умолчанию', function () {
    $out = BlockRenderer::render(['id' => 43, 'type' => 'cta_band', 'custom_css' => null, 'data' => json_encode([
        'title' => 'Есть вопросы или предложения?', 'text' => 'Свяжитесь с нами',
        'button_text' => 'Связаться с нами', 'button_url' => '/kontakty',
    ])])['html'];
    assert_contains('block-ctaband', $out);
    assert_contains('ctaband__icon', $out);
    assert_contains('href="/kontakty"', $out);
});

test('Блок person_profile: фото, контакты, кнопка', function () {
    $out = BlockRenderer::render(['id' => 44, 'type' => 'person_profile', 'custom_css' => null, 'data' => json_encode([
        'photo' => '/uploads/public/dir.jpg', 'name' => 'Элёр Ганиев',
        'position' => 'Директор Агентства', 'text' => 'Руководит деятельностью.',
        'phone' => '+998 71 203 10 00', 'email' => 'info@strategy.uz',
        'button_text' => 'Обратиться', 'button_url' => '/kontakty',
    ])])['html'];
    assert_contains('block-profile', $out);
    assert_contains('tel:+998712031000', $out);
    assert_contains('mailto:info@strategy.uz', $out);
    assert_contains('profile__button', $out);
});

test('Блок feature_band: элементы с иконками', function () {
    $out = BlockRenderer::render(['id' => 45, 'type' => 'feature_band', 'custom_css' => null, 'data' => json_encode([
        'items' => [
            ['icon_svg' => '<svg><path/></svg>', 'title' => 'Стратегическое управление', 'text' => 'Определение приоритетов'],
            ['icon_svg' => '', 'title' => 'Координация реформ', 'text' => ''],
        ],
    ])])['html'];
    assert_contains('featband__item', $out);
    assert_contains('Стратегическое управление', $out);
});

test('Блок bio_education: карьера, образование, доп. список и цитата', function () {
    $out = BlockRenderer::render(['id' => 46, 'type' => 'bio_education', 'custom_css' => null, 'data' => json_encode([
        'bio_title' => 'Биография', 'bio_text' => 'Более 15 лет опыта.',
        'career' => [['years' => '2023 – н.в.', 'text' => 'Директор Агентства']],
        'edu_title' => 'Образование',
        'edu_items' => [['years' => '2011 – 2013', 'title' => 'Магистр (MPA)', 'org' => 'NUS, Сингапур']],
        'extra_title' => 'Дополнительное образование', 'extra_text' => "Executive Program\nCertificate in Strategic Leadership",
        'quote_text' => 'Наша цель — устойчивая экономика.', 'quote_author' => 'Элёр Ганиев',
    ])])['html'];
    assert_contains('bio-career__years', $out);
    assert_contains('bio-edu__degree', $out);
    assert_contains('<li>Executive Program</li>', $out);
    assert_contains('bio-quote__text', $out);
    assert_contains('Элёр Ганиев', $out);
});

test('Блок news_docs: документы и заглушки; ссылка «Все» у документов', function () {
    ensure_test_db();
    $out = BlockRenderer::render(['id' => 47, 'type' => 'news_docs', 'custom_css' => null, 'data' => json_encode([
        'news_title' => 'Актуальные новости', 'docs_title' => 'Документы',
        'docs_all_text' => 'Все документы', 'docs_all_url' => '/docs',
        'docs' => [['title' => 'Стратегия «Узбекистан–2030»', 'meta' => 'PDF · 2.4 МБ', 'url' => '/uploads/public/s.pdf']],
    ])])['html'];
    assert_contains('cms-block--news_docs', $out);
    assert_contains('doc-card__title', $out);
    assert_contains('PDF · 2.4 МБ', $out);
    assert_contains('href="/uploads/public/s.pdf"', $out);
    assert_contains('href="/docs"', $out);
});
