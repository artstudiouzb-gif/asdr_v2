<?php

declare(strict_types=1);

/**
 * Импорт новостей из старой CMS (REST API или WXR) с фотографиями.
 *
 * Примеры:
 *   php scripts/wp_import.php https://old.example --lang uz:uz --lang ru:ru --limit 20
 *   php scripts/wp_import.php export.xml --lang uz:uz --lang ru:ru --lang en:en --dry-run
 *   php scripts/wp_import.php export.xml --lang uz:uz --lang ru:ru --lang en:en --status published
 *
 * Опции:
 *   --limit N            не более N языковых групп новостей (0 = все)
 *   --status STATE       draft (по умолчанию) | published — статус в новой CMS
 *   --author ID          id пользователя-автора (по умолчанию первый админ)
 *   --lang OLD:ART       код языка источника → код языка ArtStudio; повторяемо.
 *                        Первый --lang считается основным языком WXR.
 *   --uploads DIR        локальная копия wp-content/uploads (надёжнее сети)
 *   --include-drafts     дополнительно включить исходные записи status=draft
 *   --wpml-window N      окно безопасного WPML-сопоставления, минут (default 120)
 *   --dry-run            только разобрать и проверить план, без записи/скачиваний
 *
 * Для WXR dry-run обязателен перед боевым переносом: если останутся переводы
 * без основного языка или неоднозначные группы, импорт остановится до записи.
 * При реальном WXR-импорте каждый язык создаётся отдельной связанной записью
 * news: сохраняются собственные slug, дата, featured image и галерея языка.
 */

require __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\Config;
use App\Core\Database;
use App\Core\LegacyCmsImporter;
use App\Core\LegacyWxrImporter;

$args = array_slice($argv, 1);
$source = '';
$opts = [
    'status' => 'draft',
    'limit' => 0,
    'dryRun' => false,
    'authorId' => null,
    'langs' => [],
    'uploadsDir' => null,
    'includeDrafts' => false,
    'pairWindowMinutes' => 120,
];

for ($i = 0; $i < count($args); $i++) {
    $a = $args[$i];
    if ($a === '--limit' && isset($args[$i + 1])) {
        $opts['limit'] = (int) $args[++$i];
    } elseif ($a === '--status' && isset($args[$i + 1])) {
        $opts['status'] = $args[++$i];
    } elseif ($a === '--author' && isset($args[$i + 1])) {
        $opts['authorId'] = (int) $args[++$i];
    } elseif ($a === '--lang' && isset($args[$i + 1])) {
        [$wp, $art] = array_pad(explode(':', $args[++$i], 2), 2, '');
        if ($wp !== '' && $art !== '') {
            $opts['langs'][$wp] = $art;
        }
    } elseif ($a === '--uploads' && isset($args[$i + 1])) {
        $opts['uploadsDir'] = $args[++$i];
    } elseif ($a === '--wpml-window' && isset($args[$i + 1])) {
        $opts['pairWindowMinutes'] = max(1, (int) $args[++$i]);
    } elseif ($a === '--include-drafts') {
        $opts['includeDrafts'] = true;
    } elseif ($a === '--dry-run') {
        $opts['dryRun'] = true;
    } elseif (str_starts_with($a, 'http') || is_file($a) || str_ends_with(strtolower($a), '.xml')) {
        $source = $a;
    }
}

if ($source === '') {
    fwrite(STDERR, "Укажите адрес сайта ИЛИ WXR-файл .xml, например:\n"
        . "  php scripts/wp_import.php export.xml --lang uz:uz --lang ru:ru --lang en:en --dry-run\n");
    exit(2);
}

Database::init((array) Config::get('db'));

if ($opts['authorId'] === null) {
    try {
        $opts['authorId'] = (int) (Database::pdo()->query('SELECT id FROM users ORDER BY id ASC LIMIT 1')->fetchColumn() ?: 0) ?: null;
    } catch (\Throwable) {
    }
}

$isFile = !str_starts_with($source, 'http');
echo 'Импорт из ' . ($isFile ? "файла {$source}" : rtrim($source, '/'))
    . " (статус в новой CMS: {$opts['status']}" . ($opts['dryRun'] ? ', dry-run' : '') . ")…\n";

$r = $isFile
    ? LegacyWxrImporter::importFile($source, $opts)
    : LegacyCmsImporter::importAll(rtrim($source, '/'), $opts);

echo "\n────────────────────────────────────────\n";
if ($isFile && array_key_exists('source_posts', $r)) {
    $langs = is_array($r['by_language'] ?? null) ? $r['by_language'] : [];
    $langText = [];
    foreach ($langs as $code => $count) {
        $langText[] = $code . '=' . (int) $count;
    }
    echo "WXR записей типа post:     " . (int) ($r['source_posts'] ?? 0) . "\n";
    echo "Опубликованных в WP:      " . (int) ($r['source_published'] ?? 0) . "\n";
    echo "Черновиков в WP:          " . (int) ($r['source_drafts'] ?? 0) . "\n";
    echo "Выбрано записей:          " . (int) ($r['selected_posts'] ?? 0) . ($langText !== [] ? ' (' . implode(', ', $langText) . ')' : '') . "\n";
    echo "Групп новостей:           " . (int) ($r['planned_news'] ?? 0) . "\n";
    echo "Языковых переводов:       " . (int) ($r['planned_translations'] ?? 0) . "\n";
    echo "Media attachments:        " . (int) ($r['attachments'] ?? 0) . "\n";
    echo "Gallery shortcode:        " . (int) ($r['gallery_shortcodes'] ?? 0) . "\n";
    echo "Фото из gallery:          " . (int) ($r['gallery_images'] ?? 0) . "\n";
    echo "Комментарии пропущены:    " . (int) ($r['comments'] ?? 0) . "\n";
    $matches = is_array($r['match_counts'] ?? null) ? $r['match_counts'] : [];
    if ($matches !== []) {
        echo 'Связи: Polylang=' . (int) ($matches['polylang'] ?? 0)
            . ', дата=' . (int) ($matches['exact_date'] ?? 0)
            . ', featured=' . (int) ($matches['featured_url'] ?? 0)
            . ', близкое время=' . (int) ($matches['time_proximity'] ?? 0) . "\n";
    }
    echo "────────────────────────────────────────\n";
}

echo "Создано базовых новостей: " . (int) ($r['imported'] ?? 0) . "\n";
echo "Пропущено существующих:   " . (int) ($r['skipped'] ?? 0) . "\n";
echo "Создано переводов:        " . (int) ($r['translations'] ?? 0) . "\n";
echo "Перенесено картинок:      " . (int) ($r['images'] ?? 0) . "\n";
echo "Создано редиректов:       " . (int) ($r['redirects'] ?? 0) . "\n";

if (!empty($r['errors'])) {
    echo "Ошибки (" . count($r['errors']) . "):\n";
    foreach (array_slice($r['errors'], 0, 30) as $e) {
        echo "  • {$e}\n";
    }
    echo "\nИмпорт остановлен/завершён с ошибками. Исправьте план перед боевым переносом.\n";
    exit(1);
}

if ($opts['dryRun']) {
    echo "\nDry-run успешен: неоднозначных/осиротевших переводов и потерянных gallery attachments нет.\n";
} else {
    echo "\nГотово. Проверьте Новости и затем очистите кэш страницы.\n";
}

exit(0);
