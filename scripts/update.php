<?php

declare(strict_types=1);

/*
 * Обновление сайта из релиза на GitHub.
 *
 *   php scripts/update.php --check                # что стоит и что доступно
 *   php scripts/update.php --dry-run              # показать план замены
 *   php scripts/update.php --yes https://site.uz  # обновить и проверить
 *
 * Порядок жёсткий и прерывается на первой же неудаче: сначала полная
 * резервная копия, потом загрузка и сверка суммы, потом замена файлов, и
 * только затем миграции и проверки. Если замена сорвалась на середине —
 * откатываемся из снимка, снятого перед ней.
 *
 * Обновление НЕ вынесено кнопкой в админку намеренно: замена кода — самое
 * опасное действие в CMS, а веб-запрос может оборваться по таймауту ровно
 * посередине и оставить сайт с половиной старых и половиной новых файлов.
 * Панель только показывает, что вышла новая версия.
 */

require __DIR__ . '/../app/Core/Cli.php';
\App\Core\Cli::assertCli();

require __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\Backup;
use App\Core\Updater;

$root = dirname(__DIR__);
$args = array_slice($argv, 1);
$checkOnly = in_array('--check', $args, true);
$dryRun = in_array('--dry-run', $args, true);
$assumeYes = in_array('--yes', $args, true);
$baseUrl = '';
foreach ($args as $arg) {
    if (str_starts_with($arg, 'http://') || str_starts_with($arg, 'https://')) {
        $baseUrl = rtrim($arg, '/');
    }
}

function updateFail(string $message): never
{
    fwrite(STDERR, 'Обновление остановлено: ' . $message . PHP_EOL);
    exit(1);
}

// --- 1. Что стоит и что доступно ------------------------------------------

$state = Updater::check();
fwrite(STDOUT, 'Репозиторий:      ' . Updater::repo() . PHP_EOL);
fwrite(STDOUT, 'Установлено:      ' . $state['installed'] . PHP_EOL);

if (!($state['ok'] ?? false)) {
    updateFail((string) ($state['error'] ?? 'не удалось узнать последний релиз.'));
}

fwrite(STDOUT, 'Последний релиз:  ' . $state['latest']
    . ($state['published_at'] !== '' ? ' (' . $state['published_at'] . ')' : '') . PHP_EOL);

if (!$state['available']) {
    fwrite(STDOUT, 'Установлена последняя версия — обновлять нечего.' . PHP_EOL);
    exit(0);
}
if (!$state['installable']) {
    updateFail((string) $state['reason']);
}

fwrite(STDOUT, 'Доступно обновление: ' . $state['installed'] . ' → ' . $state['latest'] . PHP_EOL);
if ($checkOnly) {
    exit(0);
}

$archive = $state['asset']['archive'];
$checksum = $state['asset']['checksum'];

// --- 2. Загрузка и сверка суммы -------------------------------------------

$work = $root . '/storage/updates';
if (!is_dir($work) && !@mkdir($work, 0750, true) && !is_dir($work)) {
    updateFail('не удалось создать каталог ' . $work);
}
$zipPath = $work . '/' . basename($archive['name']);
$treeDir = $work . '/tree-' . preg_replace('/[^A-Za-z0-9._-]/', '-', (string) $state['latest']);

fwrite(STDOUT, PHP_EOL . '== Загрузка ' . $archive['name'] . ' ==' . PHP_EOL);
try {
    $size = Updater::download($archive['url'], $zipPath);
    fwrite(STDOUT, 'Скачано ' . number_format($size / 1048576, 2, '.', ' ') . ' МБ.' . PHP_EOL);
    $checksumLine = $work . '/' . basename($checksum['name']);
    Updater::download($checksum['url'], $checksumLine);
} catch (\Throwable $e) {
    updateFail($e->getMessage());
}

if (!Updater::verifyChecksum($zipPath, (string) file_get_contents($checksumLine))) {
    updateFail('контрольная сумма архива не сошлась — загрузка повреждена или подменена.');
}
fwrite(STDOUT, 'Контрольная сумма SHA-256 сошлась.' . PHP_EOL);

// --- 3. Распаковка и проверка формы дерева --------------------------------

if (is_dir($treeDir)) {
    exec('rm -rf ' . escapeshellarg($treeDir));
}
try {
    Updater::extract($zipPath, $treeDir);
} catch (\Throwable $e) {
    updateFail($e->getMessage());
}
// git archive кладёт всё под общий префикс каталога.
$inner = glob($treeDir . '/*', GLOB_ONLYDIR) ?: [];
$newTree = count($inner) === 1 && !is_file($treeDir . '/public/index.php') ? $inner[0] : $treeDir;

$problems = Updater::validateTree($newTree);
if ($problems !== []) {
    updateFail('архив не похож на установочный: ' . implode('; ', $problems));
}
fwrite(STDOUT, 'Состав архива проверен.' . PHP_EOL);

// --- 4. План замены --------------------------------------------------------

$plan = Updater::plan($newTree, $root);
fwrite(STDOUT, PHP_EOL . '== План ==' . PHP_EOL);
fwrite(STDOUT, 'Заменить файлов: ' . count($plan['copy']) . PHP_EOL);
fwrite(STDOUT, 'Удалить устаревших: ' . count($plan['delete']) . PHP_EOL);
foreach (array_slice($plan['delete'], 0, 20) as $gone) {
    fwrite(STDOUT, '  - ' . $gone . PHP_EOL);
}
if (count($plan['delete']) > 20) {
    fwrite(STDOUT, '  … и ещё ' . (count($plan['delete']) - 20) . PHP_EOL);
}
fwrite(STDOUT, 'Данные сайта (config/config.php, storage, public/uploads) не затрагиваются.' . PHP_EOL);

if ($dryRun) {
    fwrite(STDOUT, PHP_EOL . 'Пробный прогон: ничего не менялось.' . PHP_EOL);
    exit(0);
}

if (!$assumeYes) {
    fwrite(STDOUT, PHP_EOL . 'Продолжить обновление? Введите "да": ');
    $answer = trim((string) fgets(STDIN));
    if (mb_strtolower($answer) !== 'да') {
        fwrite(STDOUT, 'Отменено.' . PHP_EOL);
        exit(0);
    }
}

// --- 5. Резервная копия и снимок для отката --------------------------------

fwrite(STDOUT, PHP_EOL . '== Резервная копия ==' . PHP_EOL);
try {
    $backupPath = Backup::create(true);
} catch (\Throwable $e) {
    updateFail('резервная копия не снята (' . $e->getMessage() . ') — без неё обновление не начинаем.');
}
fwrite(STDOUT, 'Копия: ' . basename($backupPath) . PHP_EOL);

$rollbackDir = $work . '/rollback-' . gmdate('Ymd-His');
if (!@mkdir($rollbackDir, 0750, true)) {
    updateFail('не удалось создать каталог отката ' . $rollbackDir);
}
foreach (array_merge($plan['copy'], $plan['delete']) as $relative) {
    $source = $root . '/' . $relative;
    if (!is_file($source)) {
        continue;
    }
    $target = $rollbackDir . '/' . $relative;
    if (!is_dir(dirname($target))) {
        @mkdir(dirname($target), 0750, true);
    }
    if (!@copy($source, $target)) {
        updateFail('не удалось сохранить для отката: ' . $relative);
    }
}
fwrite(STDOUT, 'Снимок для отката: ' . basename($rollbackDir) . PHP_EOL);

// --- 6. Замена файлов -------------------------------------------------------

fwrite(STDOUT, PHP_EOL . '== Замена файлов ==' . PHP_EOL);
\App\Models\Setting::set('maintenance_mode', '1');
try {
    $result = Updater::apply($newTree, $plan, $root);
    fwrite(STDOUT, 'Заменено ' . $result['copied'] . ', удалено ' . $result['deleted'] . '.' . PHP_EOL);
} catch (\Throwable $e) {
    fwrite(STDERR, 'Сбой замены: ' . $e->getMessage() . PHP_EOL);
    fwrite(STDERR, 'Откатываю файлы из снимка…' . PHP_EOL);
    foreach (Updater::listFiles($rollbackDir) as $relative) {
        $target = $root . '/' . $relative;
        if (!is_dir(dirname($target))) {
            @mkdir(dirname($target), 0755, true);
        }
        @copy($rollbackDir . '/' . $relative, $target);
    }
    \App\Models\Setting::set('maintenance_mode', '0');
    updateFail('файлы возвращены из снимка ' . basename($rollbackDir) . '. Проверьте сайт.');
}

// --- 7. Миграции, эталон целостности, кэш ----------------------------------

function updateRun(string $label, string $script, array $arguments = []): void
{
    fwrite(STDOUT, PHP_EOL . '== ' . $label . ' ==' . PHP_EOL);
    $parts = array_merge([PHP_BINARY, $script], $arguments);
    passthru(implode(' ', array_map('escapeshellarg', $parts)), $code);
    if ($code !== 0) {
        fwrite(STDERR, 'Шаг «' . $label . '» завершился с кодом ' . $code . '.' . PHP_EOL);
        fwrite(STDERR, 'Файлы уже заменены. Откат: распакуйте ' . PHP_EOL);
        exit($code);
    }
}

updateRun('Миграции базы данных', $root . '/database/migrate.php');
updateRun('Эталон целостности файлов', $root . '/app/Console/integrity_check.php', ['--baseline']);

$releaseFile = $root . '/storage/release.json';
file_put_contents($releaseFile, json_encode([
    'release' => (string) $state['latest'],
    'deployed_at' => gmdate('c'),
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL, LOCK_EX);

$cacheDir = $root . '/storage/cache/page';
if (is_dir($cacheDir)) {
    foreach (glob($cacheDir . '/*') ?: [] as $item) {
        if (is_file($item)) {
            @unlink($item);
        }
    }
}

\App\Models\Setting::set('maintenance_mode', '0');

updateRun('Проверка окружения', $root . '/scripts/release_check.php');
if ($baseUrl !== '') {
    updateRun('Smoke-обход сайта', $root . '/scripts/smoke.php', [$baseUrl, '--expect-release', (string) $state['latest']]);
}

fwrite(STDOUT, PHP_EOL . 'Обновлено до ' . $state['latest'] . '.' . PHP_EOL);
if ($baseUrl === '') {
    fwrite(STDOUT, 'Smoke-обход пропущен: адрес сайта не указан. Запустите php scripts/smoke.php https://ваш-домен' . PHP_EOL);
}
exit(0);
