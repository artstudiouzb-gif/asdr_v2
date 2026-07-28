<?php

declare(strict_types=1);

use App\Core\DesignSettings;

test('единый выбор основного шрифта нормализует базовые, Google и собственный варианты', function (): void {
    assert_same(
        ['font_style' => 'serif', 'font_google_body' => ''],
        DesignSettings::normalizeBodyFontChoice('style:serif')
    );
    assert_same(
        ['font_style' => 'system', 'font_google_body' => 'inter'],
        DesignSettings::normalizeBodyFontChoice('google:inter')
    );
    assert_same(
        ['font_style' => 'custom', 'font_google_body' => ''],
        DesignSettings::normalizeBodyFontChoice('google:unknown')
    );
});
test('точные размеры принимают только безопасные значения в заданных диапазонах', function (): void {
    assert_same('16.5px', DesignSettings::normalizeFontSize('16,5'));
    assert_same('24px', DesignSettings::normalizeFontSize('24px'));
    assert_same('', DesignSettings::normalizeFontSize('11'));
    assert_same('', DesignSettings::normalizeFontSize('25'));
    assert_same('0px', DesignSettings::normalizeRadius('0'));
    assert_same('12.5px', DesignSettings::normalizeRadius('12.5'));
    assert_same('', DesignSettings::normalizeRadius('49'));
    assert_same('', DesignSettings::normalizeRadius('calc(1px)'));
    assert_same('1.45', DesignSettings::normalizeLineHeight('1,45'));
    assert_same('2', DesignSettings::normalizeLineHeight('2.0'));
    assert_same('', DesignSettings::normalizeLineHeight('0.9'));
    assert_same('', DesignSettings::normalizeLineHeight('2.6'));
    assert_same('', DesignSettings::normalizeLineHeight('16px'));
    assert_same('34px', DesignSettings::normalizeFsSize('34'));
    assert_same('', DesignSettings::normalizeFsSize('7'));
    assert_same('', DesignSettings::normalizeFsSize('97'));
});

test('форма дизайна объединяет источники шрифта и показывает точные размеры', function (): void {
    $view = file_get_contents(dirname(__DIR__, 2) . '/app/Views/admin/design/index.php');
    assert_true(is_string($view));
    assert_contains('name="font_body_choice"', $view);
    // Подпись группы дополнена предупреждением о внешнем домене, поэтому
    // проверяем начало, а не точный текст.
    assert_contains('optgroup label="Google Fonts', $view);
    assert_contains('data-custom-font-fields', $view);
    assert_contains('name="font_size_custom"', $view);
    assert_contains('name="line_height_custom"', $view);
    // Поля размеров по элементам рендерятся циклом из TYPO_SIZES.
    assert_contains('DesignSettings::TYPO_SIZES as $fsKey', $view);
    assert_true(isset(DesignSettings::TYPO_SIZES['fs_h1'], DesignSettings::TYPO_SIZES['fs_menu'], DesignSettings::TYPO_SIZES['fs_topbar']));
    assert_contains('name="radius_custom"', $view);
    assert_not_contains('<h2 class="design-section__title">Google-шрифты</h2>', $view);
});

test('точные размеры сохраняются и переопределяют CSS-переменные (БД)', function (): void {
    ensure_test_db();
    DesignSettings::save([
        'font_body_choice' => 'google:inter',
        'font_size_custom' => '17.5',
        'radius_custom' => '13',
        'line_height_custom' => '1.35',
        'fs_h1' => '34',
        'fs_menu' => '15',
    ]);

    $css = DesignSettings::cssVariables(DesignSettings::current());
    assert_contains('--base-font-size:17.5px', $css);
    assert_contains('--base-line-height:1.35', $css);
    assert_contains('h1,', $css);
    assert_contains('font-size:34px !important;}', $css);
    assert_contains('.site-menu__link{font-size:15px !important;}', $css);

    // Сброс, чтобы прогон был идемпотентным и не влиял на другие тесты.
    DesignSettings::save(['fs_h1' => '', 'fs_menu' => '']);
    assert_not_contains('font-size:34px', DesignSettings::cssVariables(DesignSettings::current()));
    assert_contains('--radius:13px', $css);
    assert_contains('--btn-radius:13px', $css);
    assert_same('inter', (string) \App\Models\Setting::get('design_font_google_body', ''));
    assert_contains('Inter', (string) \App\Models\Setting::get('font_family', ''));

    // Убираем за собой всё, что писали в БД: иначе значения переживают прогон
    // и следующий запуск валит соседние тесты чужим радиусом и шрифтом.
    DesignSettings::save([
        'font_body_choice' => 'style:pt',
        'font_size_custom' => '',
        'radius_custom' => '',
        'line_height_custom' => '',
    ]);
    \App\Models\Setting::set('design_font_google_body', '');
});
