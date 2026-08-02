<?php

declare(strict_types=1);

use App\Core\DesignSettings;
use App\Core\LocalGoogleFonts;
use App\Models\Setting;

test('Inter Tight входит в поставку локально со всеми узбекскими подмножествами', function (): void {
    $root = dirname(__DIR__, 2);
    $css = (string) file_get_contents($root . '/public/assets/css/inter-tight.css');

    assert_not_contains('https://', $css);
    foreach (['cyrillic-ext', 'cyrillic', 'latin'] as $subset) {
        foreach ([400, 500, 600, 700] as $weight) {
            $pattern = '/\/\*\s*' . preg_quote($subset, '/') . '\s*\*\/\s*'
                . '@font-face\s*\{(?=[^}]*font-family:\s*\'Inter Tight\';)'
                . '(?=[^}]*font-weight:\s*' . $weight . ';)[^}]*'
                . "url\\('\\.\\.\\/fonts\\/inter-tight\\/([^']+\\.woff2)'\\)[^}]*}/s";
            assert_true((bool) preg_match($pattern, $css, $match), "нет Inter Tight {$weight} {$subset}");
            $file = $root . '/public/assets/fonts/inter-tight/' . $match[1];
            assert_true(is_file($file), "нет {$file}");
            assert_same('wOF2', (string) file_get_contents($file, false, null, 0, 4));
        }
    }
});

test('выбранный каталог подключается только локально и без повторов', function (): void {
    $installed = LocalGoogleFonts::installSelected(['inter-tight', 'inter-tight']);
    assert_true($installed['ok'], $installed['error']);

    Setting::overrideInMemory('design_font_google_body', 'inter-tight');
    Setting::overrideInMemory('design_font_google_heading', 'inter-tight');
    $stylesheets = LocalGoogleFonts::stylesheetsForSelected();
    assert_same(1, count($stylesheets));
    assert_contains('/assets/css/inter-tight.css', $stylesheets[0]);
    assert_not_contains('googleapis.com', $stylesheets[0]);
    assert_not_contains('gstatic.com', $stylesheets[0]);
});

test('неполные для узбекского семейства не предлагаются, остальные ставятся по выбору', function (): void {
    assert_false(isset(DesignSettings::GOOGLE_FONTS['playfair']), 'Playfair Display не содержит cyrillic-ext');
    assert_false(isset(DesignSettings::GOOGLE_FONTS['jost']), 'Jost не содержит cyrillic-ext');

    $installer = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Core/LocalGoogleFonts.php');
    $controller = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Controllers/Admin/DesignController.php');
    $header = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Views/site/_header.php');
    assert_contains("'host'] ?? '') !== 'fonts.gstatic.com'", $installer, 'установщик принимает файлы только Google Fonts');
    assert_true(substr_count($controller, 'LocalGoogleFonts::installSelected') >= 2, 'шрифты импортируются перед сохранением и применением конфигурации');
    assert_contains('LocalGoogleFonts::stylesheetsForSelected', $header);
    assert_not_contains('fonts.googleapis.com', $header, 'браузер не должен обращаться к Google Fonts');
    assert_not_contains('fonts.gstatic.com', $header, 'браузер не должен обращаться к Google Fonts');
});
