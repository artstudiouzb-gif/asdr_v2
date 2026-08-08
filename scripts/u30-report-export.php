<?php

declare(strict_types=1);

/**
 * Экспорт блока «Мониторинг Oʻzbekiston — 2030» в самостоятельные файлы.
 *
 * Нужен, чтобы отчёт можно было править и смотреть вне CMS, не заводя вторую
 * копию документа: обе версии собираются из тех же файлов, что работают на
 * сайте, и разъехаться с ним не могут.
 *
 *   php scripts/u30-report-export.php [каталог]      (по умолчанию build/u30-report)
 *
 * На выходе:
 *   index.html                    — разметка, стили и скрипт отдельными файлами
 *   assets/u30-report.css|js|svg  — они же
 *   u30-report-standalone.html    — то же самое одним файлом
 *
 * Обратный перенос правок: css и js копируются в public/assets/... как есть,
 * из index.html берётся только разметка от <div id="uz2030-report"> до её
 * закрывающего тега — она заменяет тело templates/blocks/u30_report.php.
 * Оболочка страницы (<style id="u30-standalone-shell">) в CMS не нужна:
 * шрифт и фон там задаёт сайт.
 */

require __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\BlockRenderer;
use App\Core\Icon;

$target = $argv[1] ?? (APP_ROOT . '/build/u30-report');
$assets = $target . '/assets';
if (!is_dir($assets) && !mkdir($assets, 0755, true) && !is_dir($assets)) {
    fwrite(STDERR, "Не удалось создать каталог {$assets}\n");
    exit(1);
}

$markup = BlockRenderer::render(['id' => 1, 'type' => 'u30_report', 'custom_css' => null, 'data' => '{}'])['html'];
// Секция-обёртка блока (id="block-N") за пределами CMS не нужна.
$markup = (string) preg_replace('~^<section\b[^>]*>\s*~s', '', $markup);
$markup = (string) preg_replace('~\s*</section>$~s', '', $markup);
// Версионные хвосты Asset::url в статичных файлах только мешают.
$markup = (string) preg_replace('~\?v=[0-9a-f]+~', '', $markup);

$css = (string) file_get_contents(APP_ROOT . '/public/assets/css/blocks/u30-report.css');
$js = (string) file_get_contents(APP_ROOT . '/public/assets/js/blocks/u30_report.js');
$logo = trim((string) file_get_contents(APP_ROOT . '/public/assets/img/u30-strategy-logo.svg'));

// Символы Tabler, которые на сайте встраивает Icon::injectSprite.
preg_match(
    '~<svg class="tabler-sprite".*?</svg>~s',
    Icon::injectSprite('<html><body>' . $markup . '</body></html>'),
    $spriteMatch
);
$sprite = $spriteMatch[0] ?? '';

$shell = <<<'HTML'
<style id="u30-standalone-shell">
  /* Оболочка страницы: в CMS её роль играет сайт. При переносе не копировать. */
  html {
    background: #f1f5f9;
    font-family: 'Inter Tight', Inter, system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
  }
  html[data-theme="dark"] { background: #0b1120; }
  body { margin: 0; padding: clamp(12px, 3vw, 32px); }
</style>

HTML;

$note = <<<'HTML'
<!--
  “Oʻzbekiston — 2030” strategiyasi monitoring hisoboti.
  Собрано командой: php scripts/u30-report-export.php

  Источники в CMS (тип блока u30_report):
    templates/blocks/u30_report.php         — разметка
    public/assets/css/blocks/u30-report.css — стили
    public/assets/js/blocks/u30_report.js   — диаграммы и таблицы
    public/assets/img/u30-strategy-logo.svg — эмблема

  Тёмная тема:         <html data-theme="dark">
  Остановить анимации: <html data-a11y-motion="off">
-->

HTML;

$head = static function (string $links) use ($shell, $note): string {
    return "<!doctype html>\n<html lang=\"uz-Latn\">\n<head>\n"
        . "<meta charset=\"utf-8\">\n"
        . "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n"
        . "<meta name=\"description\" content=\"“Oʻzbekiston — 2030” strategiyasining 2026-yil I yarim yillik monitoring hisoboti.\">\n"
        . "<title>“Oʻzbekiston — 2030” strategiyasi monitoring hisoboti</title>\n"
        . $links . $shell . "</head>\n" . $note;
};

// --- Разделённая версия ---
file_put_contents($assets . '/u30-report.css', $css);
file_put_contents($assets . '/u30-report.js', $js);
file_put_contents($assets . '/u30-strategy-logo.svg', $logo . "\n");
file_put_contents(
    $target . '/index.html',
    $head("<link rel=\"stylesheet\" href=\"assets/u30-report.css\">\n")
        . "<body>\n" . $sprite . "\n"
        . str_replace('/assets/img/u30-strategy-logo.svg', 'assets/u30-strategy-logo.svg', $markup) . "\n"
        . "<script src=\"assets/u30-report.js\"></script>\n</body>\n</html>\n"
);

// --- Одним файлом ---
$logoInline = (string) preg_replace('~^<\?xml[^>]*\?>\s*~', '', $logo);
$logoInline = (string) preg_replace('~^<svg~', '<svg class="u30-brand-logo" role="img"', $logoInline, 1);
file_put_contents(
    $target . '/u30-report-standalone.html',
    $head("<style>\n" . $css . "\n</style>\n")
        . "<body>\n" . $sprite . "\n"
        . preg_replace('~<img class="u30-brand-logo"[^>]*>~', $logoInline, $markup) . "\n"
        . "<script>\n" . $js . "\n</script>\n</body>\n</html>\n"
);

printf("Готово: %s\n", $target);
foreach (['index.html', 'u30-report-standalone.html', 'assets/u30-report.css', 'assets/u30-report.js', 'assets/u30-strategy-logo.svg'] as $file) {
    printf("  %-32s %6.0f КБ\n", $file, filesize($target . '/' . $file) / 1024);
}
