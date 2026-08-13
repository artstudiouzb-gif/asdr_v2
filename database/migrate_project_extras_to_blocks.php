<?php

declare(strict_types=1);

/*
 * Галерея и произвольные поля проекта → блоки конструктора (разовый скрипт).
 *
 *   php database/migrate_project_extras_to_blocks.php --dry-run   — показать план
 *   php database/migrate_project_extras_to_blocks.php             — выполнить
 *
 * После слияния проектов со страницами содержимое проекта собирается блоками.
 * Форма проекта больше не редактирует галерею (`project_images`) и свободные
 * поля (`project_fields`) — но данные из них никуда не делись, и на сайте они
 * не выводились вовсе. Скрипт переносит их в блоки той же записи, чтобы
 * содержимое стало видимым и редактируемым в одном месте:
 *
 *   1) галерея        → блок «Медиагалерея» (ручной источник, подписи сохраняются);
 *   2) «Команда: …»   → блок «Иконка и текст» со строками «должность | имя»;
 *   3) «Вакансия: …», «Документ: …», «Мероприятие: …», «Тендер: …» — так же,
 *      каждая группа отдельным блоком со своим заголовком;
 *   4) остальные пары → блок «Иконка и текст» с заголовком «Характеристики».
 *
 * Значения с суффиксом языка (`Ключ [uz]`, `Ключ [en]`) уходят в блок нужного
 * языка: у каждого языка свой стек блоков.
 *
 * Скрипт идемпотентен: блоки помечаются внутренним названием «Перенос: …», и
 * повторный запуск такие блоки пропускает. Исходные строки не удаляются —
 * сверьте результат в админке, прежде чем чистить таблицы.
 */

require __DIR__ . '/../app/Core/Cli.php';
\App\Core\Cli::assertCli();
require __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\Database;
use App\Models\Block;
use App\Models\Language;

$dryRun = in_array('--dry-run', $argv, true);
$pdo = Database::pdo();
$defaultLang = Language::defaultCode();

/** Группы полей: префикс ключа → заголовок будущего блока. */
const FIELD_GROUPS = [
    'Команда:' => 'Команда проекта',
    'team:' => 'Команда проекта',
    'Вакансия:' => 'Вакансии',
    'vacancy:' => 'Вакансии',
    'Документ:' => 'Документы',
    'doc:' => 'Документы',
    'Мероприятие:' => 'Мероприятия',
    'event:' => 'Мероприятия',
    'Тендер:' => 'Тендеры',
    'tender:' => 'Тендеры',
];

/** Заголовок для пар, не попавших ни в одну группу. */
const FIELD_GROUP_OTHER = 'Характеристики проекта';

/** Разбирает ключ на язык и чистое название: «Заказчик [uz]» → ['uz', 'Заказчик']. */
function split_field_key(string $key, string $defaultLang): array
{
    if (preg_match('/^(.*)\s\[([a-z]{2})\]$/u', trim($key), $m) === 1) {
        return [$m[2], trim($m[1])];
    }

    return [$defaultLang, trim($key)];
}

/** Заголовок группы по ключу поля. */
function field_group_title(string $key): string
{
    foreach (FIELD_GROUPS as $prefix => $title) {
        if (str_starts_with($key, $prefix)) {
            return $title;
        }
    }

    return FIELD_GROUP_OTHER;
}

/** Ключ без префикса группы: «Команда: Куратор» → «Куратор». */
function field_label(string $key): string
{
    foreach (array_keys(FIELD_GROUPS) as $prefix) {
        if (str_starts_with($key, $prefix)) {
            return trim(substr($key, strlen($prefix)));
        }
    }

    return $key;
}

$projects = $pdo->query(
    "SELECT id, title, lang FROM pages WHERE entity_type = 'project' ORDER BY id"
)->fetchAll();

if ($projects === []) {
    echo "Проектов нет — переносить нечего.\n";
    exit(0);
}

$imagesStmt = $pdo->prepare(
    'SELECT file_path, caption FROM project_images WHERE project_id = :id ORDER BY sort_order, id'
);
$fieldsStmt = $pdo->prepare(
    'SELECT field_key, field_value FROM project_fields WHERE project_id = :id ORDER BY sort_order, id'
);
$existingStmt = $pdo->prepare(
    'SELECT COUNT(*) FROM blocks WHERE page_id = :id AND title = :title AND lang = :lang'
);

$created = 0;
$skipped = 0;

foreach ($projects as $project) {
    $projectId = (int) $project['id'];
    $projectLang = (string) ($project['lang'] ?: $defaultLang);

    /** @var array<string, array<string, array{title: string, rows: list<string>}>> $planned */
    $planned = [];

    $imagesStmt->execute([':id' => $projectId]);
    $images = $imagesStmt->fetchAll();

    $fieldsStmt->execute([':id' => $projectId]);
    foreach ($fieldsStmt->fetchAll() as $field) {
        $value = trim((string) ($field['field_value'] ?? ''));
        if ($value === '') {
            continue;
        }
        [$lang, $rawKey] = split_field_key((string) $field['field_key'], $projectLang);
        $groupTitle = field_group_title($rawKey);
        $label = field_label($rawKey);
        $planned[$lang][$groupTitle]['title'] = $groupTitle;
        $planned[$lang][$groupTitle]['rows'][] = ($label !== '' ? $label . ' | ' : '') . $value;
    }

    if ($images === [] && $planned === []) {
        continue;
    }

    echo sprintf("#%d %s\n", $projectId, (string) $project['title']);

    // Галерея — один блок «Медиагалерея» на языке самой записи.
    if ($images !== []) {
        $blockTitle = 'Перенос: галерея проекта';
        $existingStmt->execute([':id' => $projectId, ':title' => $blockTitle, ':lang' => $projectLang]);
        if ((int) $existingStmt->fetchColumn() > 0) {
            echo "  · галерея уже перенесена\n";
            $skipped++;
        } else {
            $items = [];
            foreach ($images as $image) {
                $items[] = [
                    'kind' => 'photo',
                    'title' => trim((string) ($image['caption'] ?? '')),
                    'image' => (string) $image['file_path'],
                    'url' => '',
                    'meta' => '',
                    'text' => '',
                ];
            }
            echo sprintf("  + Медиагалерея (%d фото, %s)\n", count($items), $projectLang);
            if (!$dryRun) {
                Block::create(
                    $projectId,
                    $projectLang,
                    'media_gallery',
                    $blockTitle,
                    ['title' => 'Фотографии проекта', 'source' => 'manual', 'items' => $items],
                    ''
                );
            }
            $created++;
        }
    }

    // Свободные поля — по блоку на группу и язык.
    foreach ($planned as $lang => $groups) {
        foreach ($groups as $groupTitle => $group) {
            $blockTitle = 'Перенос: ' . $groupTitle;
            $existingStmt->execute([':id' => $projectId, ':title' => $blockTitle, ':lang' => $lang]);
            if ((int) $existingStmt->fetchColumn() > 0) {
                echo sprintf("  · %s (%s) уже перенесены\n", $groupTitle, $lang);
                $skipped++;
                continue;
            }
            echo sprintf("  + %s (%d строк, %s)\n", $groupTitle, count($group['rows']), $lang);
            if (!$dryRun) {
                Block::create(
                    $projectId,
                    (string) $lang,
                    'icon_text',
                    $blockTitle,
                    [
                        'variant' => 'plain',
                        'title' => $group['title'],
                        'columns' => 2,
                        'items' => [['icon_svg' => '', 'icon_color' => '', 'rows' => implode("\n", $group['rows'])]],
                    ],
                    ''
                );
            }
            $created++;
        }
    }
}

echo $dryRun
    ? sprintf("\nПлан: создать блоков — %d, пропустить — %d. Запустите без --dry-run.\n", $created, $skipped)
    : sprintf("\nГотово: создано блоков — %d, пропущено — %d.\n", $created, $skipped);
