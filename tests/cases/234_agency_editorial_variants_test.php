<?php

declare(strict_types=1);

use App\Core\BlockTypeRegistry;

test('Редакционные варианты страницы Агентства являются настройками системных блоков', function (): void {
    $defaults = BlockTypeRegistry::DEFAULTS;
    assert_true(array_key_exists('variant', $defaults['text']));
    assert_true(array_key_exists('aside_title', $defaults['text']));
    assert_true(array_key_exists('items', $defaults['text']));
    assert_true(array_key_exists('quote', $defaults['text']));
    assert_true(array_key_exists('media_type', $defaults['text']));
    assert_true(array_key_exists('media_image', $defaults['text']));
    assert_true(array_key_exists('media_video', $defaults['text']));
    assert_true(array_key_exists('media_youtube', $defaults['text']));
    assert_true(array_key_exists('variant', $defaults['stages']));
    assert_true(array_key_exists('career_title', $defaults['bio_education']));

    $form = (string) file_get_contents(APP_ROOT . '/app/Views/admin/pages/block_form.php');
    foreach (['intro', 'system', 'spotlight', 'indexed', 'history', 'acts-editorial', 'media_image', 'media_video', 'media_youtube'] as $variant) {
        assert_contains($variant, $form, "вариант {$variant} недоступен в редакторе");
    }
});

test('Миграция включает варианты без замены редакторского текста', function (): void {
    $sql = (string) file_get_contents(APP_ROOT . '/database/migrations/2026_08_09_agency_editorial_block_variants.sql');
    assert_contains('-- @post-schema', $sql);
    assert_contains("p.slug = 'o-nas'", $sql);
    assert_contains("'$.variant', 'intro'", $sql);
    assert_contains("'$.variant', 'system'", $sql);
    assert_contains("'$.variant', 'spotlight'", $sql);
    assert_contains("'$.variant', 'acts-editorial'", $sql);
    assert_contains("'$.career_title', 'Профессиональный путь'", $sql);
    assert_not_contains("'$.content'", $sql, 'миграция не должна перезаписывать ручные правки текста');
});

test('Редакционные стили не меняют шапку и подвал', function (): void {
    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/public-editorial-pages.css');
    assert_contains('.block-text--system', $css);
    assert_contains('.block-advantages__grid', $css);
    assert_contains('.block-stages--history', $css);
    assert_contains('.block-docslist--acts-editorial', $css);
    assert_contains('.block-stages--history .stage::after', $css, 'в истории должна быть отключена дублирующая линия');
    assert_not_contains('.site-header', $css);
    assert_not_contains('.site-footer', $css);
});

test('Страница директора использует читаемую системную хронологию', function (): void {
    $content = require APP_ROOT . '/database/content/agency_content.php';
    $director = $content['pages']['direktor']['ru']['blocks'] ?? [];
    $bio = null;
    foreach ($director as $block) {
        if (($block[0] ?? '') === 'bio_education') {
            $bio = $block[2] ?? [];
            break;
        }
    }

    assert_true(is_array($bio));
    assert_same('Профессиональный путь', $bio['career_title'] ?? '');
    assert_true(count($bio['career'] ?? []) >= 10);

    $template = (string) file_get_contents(APP_ROOT . '/templates/blocks/bio_education.php');
    assert_contains('bio-career__title', $template);
    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/public-editorial-pages.css');
    assert_contains('bio-career__item:last-child', $css);
    assert_contains('background: transparent;', $css);
    assert_contains(':root[data-theme="dark"] .editorial-page__content .bio-career__item::before', $css);
    assert_contains('border-color: color-mix(in srgb, var(--gov-teal) 72%, #fff);', $css);
    assert_contains('left: -1px', $css, 'маркеры карьеры должны быть центрированы по линии');
    assert_contains('box-sizing: border-box', $css, 'граница должна входить в размер маркера');
});

test('Профиль руководителя использует адаптивную колонку без искусственного сужения текста', function (): void {
    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/public-editorial-pages.css');
    assert_contains('aspect-ratio: 4 / 5', $css, 'портрет должен сохранять вертикальную пропорцию');
    assert_contains('.editorial-page__content .profile__info::before', $css, 'между портретом и текстом нужен редакционный акцент');

    foreach (['bio__text', 'profile__name', 'profile__position', 'profile__text'] as $selector) {
        $matched = preg_match(
            '/\\.editorial-page__content \\.' . preg_quote($selector, '/') . '\\s*\\{([^}]*)\\}/',
            $css,
            $rule,
        );
        assert_same(1, $matched, "нет правила {$selector}");
        assert_not_contains('max-width', $rule[1], "{$selector} не должен иметь фиксированную максимальную ширину");
    }
});

test('Преимущества, контакты и правовые акты используют один feature-card', function (): void {
    $advantages = (string) file_get_contents(APP_ROOT . '/templates/blocks/advantages.php');
    $contacts = (string) file_get_contents(APP_ROOT . '/templates/blocks/contact_cards.php');
    $acts = (string) file_get_contents(APP_ROOT . '/templates/blocks/partials/act_card.php');
    assert_contains('feature-card block-advantages__item', $advantages);
    assert_contains('feature-card contact-card', $contacts);
    assert_contains('feature-card act-card', $acts);
    assert_contains('feature-card__num block-advantages__index', $advantages);
    assert_contains('feature-card__num', $contacts);
    assert_contains('feature-card__title act-card__number', $acts);
    assert_contains('feature-card__text act-card__title', $acts);

    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/gov-theme.css');
    assert_contains('.feature-card.block-advantages__item', $css);
    assert_contains('.feature-card.contact-card', $css);
    assert_contains('.feature-card.act-card', $css);
    assert_contains('--feature-card-motion-duration: .62s', $css);

    $editorial = (string) file_get_contents(APP_ROOT . '/public/assets/css/public-editorial-pages.css');
    assert_contains('.anim-card:not(.feature-card)', $editorial);
    assert_not_contains('.feature-card.block-advantages__item:hover', $editorial);

    $layout = (string) file_get_contents(APP_ROOT . '/public/assets/css/public-layout-polish.css');
    assert_contains('.block-advantages__item:not(.feature-card)', $layout);
});

test('Вводный блок Агентства имеет управляемую медиаколонку и безопасную заглушку', function (): void {
    $base = [
        'id' => 23401,
        'type' => 'text',
        'custom_css' => null,
    ];

    $placeholder = \App\Core\BlockRenderer::render($base + ['data' => json_encode([
        'variant' => 'intro',
        'content' => '<p>О работе Агентства</p>',
        'media_type' => 'none',
    ], JSON_UNESCAPED_UNICODE)])['html'];
    assert_contains('block-text__intro-copy', $placeholder);
    assert_contains('block-text__media--placeholder', $placeholder);

    $image = \App\Core\BlockRenderer::render($base + ['data' => json_encode([
        'variant' => 'intro',
        'content' => '<p>О работе Агентства</p>',
        'media_type' => 'image',
        'media_image' => '/uploads/public/about.jpg',
        'media_alt' => 'Рабочая встреча',
    ], JSON_UNESCAPED_UNICODE)])['html'];
    assert_contains('block-text__media--image', $image);
    assert_contains('/uploads/public/about.jpg', $image);
    assert_contains('alt="Рабочая встреча"', $image);

    $video = \App\Core\BlockRenderer::render($base + ['data' => json_encode([
        'variant' => 'intro',
        'content' => '<p>О работе Агентства</p>',
        'media_type' => 'video',
        'media_video' => '/uploads/public/about.mp4',
    ], JSON_UNESCAPED_UNICODE)])['html'];
    assert_contains('<video class="block-text__media-video" controls', $video);
    assert_contains('/uploads/public/about.mp4', $video);

    $youtube = \App\Core\BlockRenderer::render($base + ['data' => json_encode([
        'variant' => 'intro',
        'content' => '<p>О работе Агентства</p>',
        'media_type' => 'youtube',
        'media_youtube' => 'https://youtu.be/dQw4w9WgXcQ',
    ], JSON_UNESCAPED_UNICODE)])['html'];
    assert_contains('youtube-nocookie.com/embed/dQw4w9WgXcQ', $youtube);
    assert_contains('loading="lazy"', $youtube);

    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/public-editorial-pages.css');
    assert_contains('.block-text__media--placeholder', $css);
    assert_contains('grid-template-columns: minmax(0, 1.08fr) minmax(300px, .82fr)', $css);
});
