<?php

declare(strict_types=1);

use App\Core\BlockTypeRegistry;

test('Редакционные варианты страницы Агентства являются настройками системных блоков', function (): void {
    $defaults = BlockTypeRegistry::DEFAULTS;
    assert_true(array_key_exists('variant', $defaults['text']));
    assert_true(array_key_exists('aside_title', $defaults['text']));
    assert_true(array_key_exists('items', $defaults['text']));
    assert_true(array_key_exists('quote', $defaults['text']));
    assert_true(array_key_exists('variant', $defaults['stages']));
    assert_true(array_key_exists('career_title', $defaults['bio_education']));

    $form = (string) file_get_contents(APP_ROOT . '/app/Views/admin/pages/block_form.php');
    foreach (['intro', 'system', 'spotlight', 'indexed', 'history', 'acts-editorial'] as $variant) {
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
    assert_contains('.block-advantages--indexed', $css);
    assert_contains('.block-stages--history', $css);
    assert_contains('.block-docslist--acts-editorial', $css);
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
    assert_contains('bio-career__item:last-child', (string) file_get_contents(APP_ROOT . '/public/assets/css/public-editorial-pages.css'));
});
