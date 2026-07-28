<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\HeaderConfig;
use App\Models\MenuItem;

test('Разделители дизайна применяются между пунктами главного меню', function (): void {
    $css = file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/frontend.css');
    assert_true(is_string($css));
    assert_contains('.site-menu > .site-menu__link:not(:first-child)::before', $css);
    assert_contains('var(--menu-divider-width, 1px)', $css);
    assert_contains('var(--menu-divider-height, 18px)', $css);
    assert_contains('var(--menu-divider-color, color-mix(in srgb, currentColor 35%, transparent))', $css);
});

test('HeaderConfig: макет валидируется, мусор → stacked', function () {
    $cfg = HeaderConfig::normalize(['layout' => 'drawer']);
    assert_same('drawer', $cfg['layout']);

    $cfg = HeaderConfig::normalize(['layout' => 'нечто']);
    assert_same('stacked', $cfg['layout'], 'недопустимый макет откатывается к stacked');

    // Все 4 макета допустимы.
    foreach (['stacked', 'inline', 'centered', 'drawer'] as $l) {
        assert_same($l, HeaderConfig::normalize(['layout' => $l])['layout']);
    }
});

test('HeaderConfig: конструктор зон — мусор отброшен, дубли уникальны, разделитель повторяем', function () {
    $cfg = HeaderConfig::normalize(['elements' => [
        'left' => ['search', 'нечто', 'divider'],
        'center' => ['search', 'divider'],          // повторный search выкидывается, divider остаётся
        'right' => ['language', 'theme', 'a11y', 'language'],
    ]]);

    assert_same(['search', 'divider'], $cfg['elements']['left'], 'мусор убран, search+divider на месте');
    assert_same(['divider'], $cfg['elements']['center'], 'повторный search убран, divider повторяем');
    assert_same(['language', 'theme', 'a11y'], $cfg['elements']['right'], 'повторный language убран');

    // Пустой конфиг → дефолтная раскладка.
    $def = HeaderConfig::normalize([]);
    assert_same(['search', 'language', 'theme', 'a11y'], $def['elements']['right']);
});

test('HeaderConfig: мобильная раскладка независима от десктопной', function () {
    $cfg = HeaderConfig::normalize([
        'elements' => ['left' => ['logo'], 'center' => ['menu'], 'right' => ['search', 'language', 'theme', 'a11y']],
        'elements_mobile' => ['left' => ['logo'], 'center' => [], 'right' => ['search']],
    ]);
    assert_same(['logo'], $cfg['elements']['left'], 'десктоп-логотип сохранён');
    assert_same(['menu'], $cfg['elements']['center'], 'десктоп-меню сохранено');
    assert_same(['search', 'language', 'theme', 'a11y'], $cfg['elements']['right'], 'десктоп-набор сохранён');
    assert_same(['search'], $cfg['elements_mobile']['right'], 'мобильный набор отдельный');

    // Дефолт мобильного — компактнее (с logo, search, language).
    $def = HeaderConfig::normalize([]);
    assert_same(['logo'], $def['elements_mobile']['left']);
    assert_same(['search', 'language'], $def['elements_mobile']['right']);
});

test('HeaderConfig: логотип и меню можно свободно позиционировать в любых зонах', function () {
    $cfg = HeaderConfig::normalize([
        'topbar' => [
            'enabled' => true,
            'zones' => ['left' => ['logo'], 'center' => ['menu'], 'right' => ['phone', 'email']],
        ],
    ]);
    assert_same(['logo'], $cfg['topbar']['zones']['left'], 'логотип в topbar.left');
    assert_same(['menu'], $cfg['topbar']['zones']['center'], 'меню в topbar.center');
});

test('HeaderConfig: нормализует show_border и расширенные стили меню', function () {
    $cfg = HeaderConfig::normalize([
        'container_mode' => 'floating',
        'topbar' => ['enabled' => true, 'show_border' => true],
        'styles' => [
            'nav_font_size' => 'compact',
            'nav_transform' => 'capitalize',
            'nav_letter_spacing' => 'wide',
            'nav_style_type' => 'dot',
            'nav_padding' => 'compact',
            'nav_icon_pos' => 'top',
            'nav_item_dividers' => true,
            'nav_pill_bg' => '#2563EB',
        ],
    ]);
    assert_same('floating', $cfg['container_mode'], 'container_mode = floating');
    assert_true($cfg['topbar']['show_border'], 'topbar.show_border включён');
    assert_same('compact', $cfg['styles']['nav_font_size']);
    assert_same('capitalize', $cfg['styles']['nav_transform']);
    assert_same('wide', $cfg['styles']['nav_letter_spacing']);
    assert_same('dot', $cfg['styles']['nav_style_type']);
    assert_same('compact', $cfg['styles']['nav_padding']);
    assert_same('top', $cfg['styles']['nav_icon_pos']);
    assert_true($cfg['styles']['nav_item_dividers']);
    assert_same('#2563eb', $cfg['styles']['nav_pill_bg']);
});

test('MenuItem: SVG-иконка санируется при сохранении, разделитель сохраняется (БД)', function () {
    ensure_test_db();
    $pdo = Database::pdo();
    $pdo->exec('DELETE FROM menu_items');

    // Иконка со скриптом — script вырезается, безопасная разметка остаётся.
    $dirtyIcon = '<svg viewBox="0 0 24 24"><script>alert(1)</script><path d="M3 3h18"/></svg>';
    $id = MenuItem::create([
        'title' => 'Раздел', 'lang' => 'ru', 'url_type' => 'custom', 'url_value' => '/x',
        'is_active' => 1, 'icon_svg' => $dirtyIcon,
    ]);
    $row = MenuItem::findById($id);
    assert_true(!str_contains((string) $row['icon_svg'], '<script'), 'скрипт вырезан из иконки');
    assert_contains('<path', (string) $row['icon_svg'], 'безопасная разметка иконки сохранена');

    // Не-SVG в поле иконки → null.
    $id2 = MenuItem::create([
        'title' => 'Без иконки', 'lang' => 'ru', 'url_type' => 'custom', 'url_value' => '/y',
        'is_active' => 1, 'icon_svg' => 'просто текст',
    ]);
    assert_same(null, MenuItem::findById($id2)['icon_svg']);

    // Разделитель.
    $id3 = MenuItem::create([
        'title' => '—', 'lang' => 'ru', 'url_type' => 'custom', 'url_value' => null,
        'is_active' => 1, 'is_divider' => true,
    ]);
    assert_same(1, (int) MenuItem::findById($id3)['is_divider'], 'разделитель сохранён');

    // Обновление снимает разделитель и меняет иконку, а также добавляет плашку.
    MenuItem::update($id3, [
        'title' => 'Теперь ссылка', 'lang' => 'ru', 'url_type' => 'custom', 'url_value' => '/z',
        'is_active' => 1, 'is_divider' => false, 'icon_svg' => '<svg><circle cx="5" cy="5" r="4"/></svg>',
        'badge_text' => ' АКТУАЛЬНО! ', 'badge_color' => 'red', 'badge_pos' => 'right',
    ]);
    $upd = MenuItem::findById($id3);
    assert_same(0, (int) $upd['is_divider']);
    assert_same('АКТУАЛЬНО!', $upd['badge_text'], 'текст бейджа очищен и сохранён');
    assert_same('red', $upd['badge_color'], 'цвет бейджа сохранён');
    assert_same('right', $upd['badge_pos'], 'позиция бейджа сохранена');
    assert_contains('<circle', (string) $upd['icon_svg']);
});
