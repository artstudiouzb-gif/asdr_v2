<?php

declare(strict_types=1);

use App\Core\AssetCollector;
use App\Core\BlockRenderer;
use App\Core\BlockTypeRegistry;

/**
 * Блок «Мониторинг Oʻzbekiston — 2030»: готовый документ, перенесённый из
 * standalone-страницы. Тесты стерегут то, что при переносе легко потерять —
 * внешние файлы вместо инлайна, отсутствие второго <main> и работу тёмной темы.
 */

function u30_report_html(): string
{
    return BlockRenderer::render([
        'id' => 7,
        'type' => 'u30_report',
        'custom_css' => null,
        'data' => '{}',
    ])['html'];
}

test('u30_report: тип зарегистрирован и имеет шаблон', function (): void {
    assert_true(BlockTypeRegistry::has('u30_report'));
    assert_same([], BlockTypeRegistry::defaultsFor('u30_report'));

    $template = BlockTypeRegistry::templateFile('u30_report');
    assert_true($template !== null && is_file($template), 'шаблон блока на месте');
});

test('u30_report: в разметке нет инлайн-кода и вложенного <main>', function (): void {
    $html = u30_report_html();

    // CSP публичной части не пропускает инлайн-скрипты и не даёт грузить
    // сторонние домены — весь код должен быть внешними файлами.
    assert_not_contains('<script', $html);
    assert_not_contains('<style', $html);
    assert_not_contains(' style="', $html);
    assert_not_contains('fonts.googleapis.com', $html);

    // Страница сайта уже открыла <main id="main-content">.
    assert_not_contains('<main', $html);
    // Свой skip-link дублировал бы ссылку сайта.
    assert_not_contains('u30-skip-link', $html);
});

test('u30_report: SVG-атрибуты не потеряли регистр при переносе', function (): void {
    $html = u30_report_html();

    // viewbox в нижнем регистре SVG игнорирует — иконки перестают масштабироваться.
    assert_not_contains('viewbox=', $html);
    // Иконки берутся из общего спрайта, а не рисуются геометрией в шаблоне.
    assert_contains('#tabler-', $html);

    $logo = APP_ROOT . '/public/assets/img/u30-strategy-logo.svg';
    assert_true(is_file($logo), 'эмблема вынесена отдельным файлом');
    assert_contains('/assets/img/u30-strategy-logo.svg', $html);
});

test('u30_report: заголовок отчёта уступает h1 другому блоку страницы', function (): void {
    // renderPage сбрасывает признак занятого h1 — первый блок получает h1.
    $alone = BlockRenderer::renderPage([
        ['id' => 7, 'type' => 'u30_report', 'custom_css' => null, 'data' => '{}'],
    ])['html'];
    assert_contains('<h1', $alone);

    // Обложка страницы забирает h1 — отчёт обязан опуститься до h2.
    $withHero = BlockRenderer::renderPage([
        ['id' => 1, 'type' => 'hero', 'custom_css' => null, 'data' => json_encode(['title' => 'Sarlavha'])],
        ['id' => 7, 'type' => 'u30_report', 'custom_css' => null, 'data' => '{}'],
    ])['html'];
    assert_same(1, substr_count($withHero, '<h1'), 'на странице ровно один h1');
});

test('u30_report: стили и скрипт подключаются только на своих страницах', function (): void {
    AssetCollector::reset();
    assert_same('', AssetCollector::renderStyles(), 'без блока стилей нет');
    assert_same('', AssetCollector::renderScripts());

    AssetCollector::requireJs('u30_report');
    $styles = AssetCollector::renderStyles();
    $scripts = AssetCollector::renderScripts();
    assert_contains('/assets/css/blocks/u30-report.css', $styles);
    assert_contains('/assets/js/blocks/u30_report.js', $scripts);

    // Повторный блок того же типа не должен подключать файлы дважды.
    AssetCollector::requireJs('u30_report');
    assert_same(1, substr_count(AssetCollector::renderStyles(), '<link'));
    assert_same(1, substr_count(AssetCollector::renderScripts(), '<script'));
    AssetCollector::reset();
});

test('u30_report: шапка сайта выводит стили блоков', function (): void {
    $header = (string) file_get_contents(APP_ROOT . '/app/Views/site/_header.php');
    assert_contains('AssetCollector::renderStyles()', $header);
});

test('u30_report: стили поддерживают тёмную тему, акцент и настройки отображения', function (): void {
    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/blocks/u30-report.css');

    // Внешних шрифтов и импортов быть не должно: шрифты сайт самохостит.
    assert_not_contains('fonts.googleapis.com', $css);
    assert_not_contains('@import', $css);

    // Акцент настраивается в админке, хардкодить бренд-цвет нельзя.
    assert_contains('--u30-blue: var(--color-accent', $css);
    // Затемнение — через color-mix, а не фиксированным цветом.
    assert_contains('color-mix(in srgb, var(--u30-blue)', $css);

    assert_contains(':root[data-theme="dark"] #uz2030-report', $css);
    assert_contains('data-a11y-motion="off"', $css);

    // Размеры текста в rem: панель «Настройки отображения» меняет font-size у <html>.
    assert_false(
        (bool) preg_match('/font-size:\s*\d+(\.\d+)?px/', $css),
        'размеры шрифта в px не масштабируются настройкой размера текста'
    );

    // Все правила ограничены областью отчёта.
    assert_false(
        (bool) preg_match('/(^|\})\s*(body|html)\s*\{/m', $css),
        'стили блока не должны трогать body/html'
    );
});

test('u30_report: скрипт учитывает остановку анимаций из настроек отображения', function (): void {
    $js = (string) file_get_contents(APP_ROOT . '/public/assets/js/blocks/u30_report.js');

    assert_contains("data-a11y-motion", $js);
    assert_contains('prefers-reduced-motion', $js);
    // Скрипт не должен тянуть ничего извне.
    assert_not_contains('http://', $js);
    assert_not_contains('https://', $js);
});

test('u30_report: числа в шапке сходятся с данными диаграммы направлений', function (): void {
    // Одни и те же величины лежат в двух местах: счётчики в разметке и разбивка
    // по направлениям в скрипте. Разойдясь, они не сломают страницу — отчёт
    // просто покажет разные цифры в разных разделах, и это никто не заметит.
    $js = (string) file_get_contents(APP_ROOT . '/public/assets/js/blocks/u30_report.js');
    preg_match('~const data = \{(.*?)\n  \};~s', $js, $block);
    assert_true(isset($block[1]), 'набор данных диаграмм найден');

    preg_match_all('~green: (\d+), yellow: (\d+), red: (\d+)~', $block[1], $rows);
    assert_true(count($rows[1]) > 0, 'направления разобраны');

    $green = array_sum(array_map('intval', $rows[1]));
    $yellow = array_sum(array_map('intval', $rows[2]));
    $red = array_sum(array_map('intval', $rows[3]));

    $tpl = (string) file_get_contents(APP_ROOT . '/templates/blocks/u30_report.php');
    preg_match_all('~data-count="(\d+)"~', $tpl, $counters);
    $counts = array_map('intval', $counters[1]);

    // Порядок счётчиков в разметке: всего · оценено · зелёные · жёлтые · красные.
    assert_same(5, count($counts), 'счётчиков в шапке пять');
    assert_same($green, $counts[2], 'счётчик «зелёных» = сумма по направлениям');
    assert_same($yellow, $counts[3], 'счётчик «жёлтых» = сумма по направлениям');
    assert_same($red, $counts[4], 'счётчик «красных» = сумма по направлениям');
    assert_same($green + $yellow + $red, $counts[1], 'оценено = зелёные + жёлтые + красные');
    assert_true($counts[0] >= $counts[1], 'всего показателей не меньше оценённых');
});

test('u30_report: наборы организаций читаемы построчно', function (): void {
    // Выгрузки матрицы и рейтинга правятся руками, поэтому они не должны
    // лежать одной строкой на десятки тысяч символов.
    foreach (explode("\n", (string) file_get_contents(APP_ROOT . '/public/assets/js/blocks/u30_report.js')) as $n => $line) {
        assert_true(
            strlen($line) <= 1200,
            'строка ' . ($n + 1) . ': ' . strlen($line) . ' символов — разверните набор данных построчно'
        );
    }
});

test('u30_report: слой инфо-панелей, разделителей и TOP-5 на месте', function (): void {
    // Эти правила в исходном документе лежали внутри <style type="text/css">
    // вместе со служебными классами логотипа и однажды уже терялись при
    // переносе: пропадали разделители секций и нумерация TOP-5.
    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/blocks/u30-report.css');

    assert_contains('.u30-section::before', $css, 'разделитель над секцией');
    assert_contains('.u30-info-topfive li::before', $css, 'нумерация TOP-5');
    assert_contains('.u30-info-topfive {', $css, 'список TOP-5 снимает маркеры браузера');
    assert_contains('.u30-info-panel {', $css, 'боковые информационные панели');
    assert_contains('.u30-org-matrix-guide', $css, 'пояснение к матрице организаций');
});

test('u30_report: шрифт задан списком семейств, а не inherit', function (): void {
    // Часть правил использует шорткат «font: 750 13px/1.3 var(--u30-font-sans)».
    // С «inherit» такой шорткат невалиден, и браузер отбрасывает его целиком —
    // вместе с размером и начертанием, молча.
    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/blocks/u30-report.css');

    assert_true(
        (bool) preg_match('/--u30-font-sans:\s*var\(--font-family/', $css),
        'шрифт отчёта берётся из --font-family дизайн-системы'
    );
    assert_false(
        (bool) preg_match('/--u30-font-sans:\s*inherit/', $css),
        'inherit ломает все font-шорткаты в файле'
    );
});
