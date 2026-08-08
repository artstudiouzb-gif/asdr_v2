<?php

declare(strict_types=1);

/*
 * Загрузка реального контента Агентства вместо демо-содержимого.
 *
 *   php database/seed_agency_content.php --dry-run   # показать, что изменится
 *   php database/seed_agency_content.php             # применить
 *
 * Что делает:
 *   - создаёт или перезаписывает страницы из database/content/agency_content.php
 *     («Об Агентстве», «Руководство», профили руководителей) вместе с их
 *     блоками — для каждого языка свой стек блоков, версии связаны
 *     translation_group_id;
 *   - создаёт или обновляет записи руководителей в разделе «Команда»
 *     с переводами;
 *   - сбрасывает кэш страниц.
 *
 * Идемпотентно: повторный запуск приводит содержимое к состоянию фикстуры.
 * Блоки страницы при этом ПЕРЕЗАПИСЫВАЮТСЯ — ручные правки этих страниц в
 * админке будут потеряны, поэтому после первой выкладки контент правят уже
 * в админке, а не повторным запуском.
 *
 * Меню скрипт не трогает: структура меню — решение владельца. Демо-меню уже
 * ссылается на slug o-nas / rukovodstvo / direktor, поэтому ссылки не ломаются;
 * пункт «Первый заместитель директора» при необходимости добавляется вручную.
 */

require __DIR__ . '/../app/Core/Cli.php';
\App\Core\Cli::assertCli();

require __DIR__ . '/../app/Core/bootstrap.php';

use App\Core\Cache;
use App\Core\Database;
use App\Models\Language;

$dryRun = in_array('--dry-run', $argv, true);

$fixture = require __DIR__ . '/content/agency_content.php';
$pages = (array) ($fixture['pages'] ?? []);
$hierarchy = (array) ($fixture['hierarchy'] ?? []);
$team = (array) ($fixture['team'] ?? []);

if (!Database::isConnected()) {
    fwrite(STDERR, "Нет соединения с базой данных. Проверьте config/config.php.\n");
    exit(1);
}
$pdo = Database::pdo();

// Языки, отключённые в админке, страницу всё равно получат — но она не будет
// видна на сайте, поэтому предупреждаем сразу.
$activeLangs = array_map(static fn (array $row): string => (string) $row['code'], Language::active());
$usedLangs = [];
foreach ($pages as $langData) {
    foreach (array_keys((array) $langData) as $lang) {
        $usedLangs[(string) $lang] = true;
    }
}
foreach (array_keys($usedLangs) as $lang) {
    if (!in_array($lang, $activeLangs, true)) {
        fwrite(STDOUT, "ВНИМАНИЕ: язык «{$lang}» выключен в разделе «Языки» — страницы на нём не будут показаны на сайте.\n");
    }
}

$report = ['pages_created' => 0, 'pages_updated' => 0, 'blocks' => 0, 'team_created' => 0, 'team_updated' => 0];
/** @var array<string, array<string, int>> $pageIds slug => lang => id */
$pageIds = [];

if ($dryRun) {
    fwrite(STDOUT, "Режим --dry-run: изменения не сохраняются.\n\n");
}

$pdo->beginTransaction();

try {
    foreach ($pages as $slug => $langData) {
        $slug = (string) $slug;
        $langData = (array) $langData;
        // Основной язык создаётся первым: его id становится корнем группы
        // переводов, к которой присоединяются остальные версии.
        $ordered = isset($langData['ru']) ? ['ru' => $langData['ru']] + $langData : $langData;
        $groupId = null;

        foreach ($ordered as $lang => $data) {
            $lang = (string) $lang;
            $data = (array) $data;

            $find = $pdo->prepare('SELECT id FROM pages WHERE slug = :slug AND lang = :lang LIMIT 1');
            $find->execute([':slug' => $slug, ':lang' => $lang]);
            $pageId = (int) ($find->fetchColumn() ?: 0);

            $fields = [
                ':title' => (string) ($data['title'] ?? ''),
                ':meta_title' => (string) ($data['meta_title'] ?? ''),
                ':meta_description' => (string) ($data['meta_description'] ?? ''),
                ':lead' => (string) ($data['lead'] ?? ''),
            ];

            if ($pageId > 0) {
                $update = $pdo->prepare(
                    "UPDATE pages
                        SET title = :title, meta_title = :meta_title,
                            meta_description = :meta_description, `lead` = :lead,
                            status = 'published', deleted_at = NULL
                      WHERE id = :id"
                );
                $update->execute($fields + [':id' => $pageId]);
                $report['pages_updated']++;
                fwrite(STDOUT, sprintf("  обновлена  /%s [%s] — %s\n", $slug, $lang, $fields[':title']));
            } else {
                $insert = $pdo->prepare(
                    "INSERT INTO pages
                        (title, slug, meta_title, meta_description, `lead`, status, is_home,
                         layout_type, lang, created_at)
                     VALUES (:title, :slug, :meta_title, :meta_description, :lead, 'published', 0,
                             'no_sidebar', :lang, NOW())"
                );
                $insert->execute($fields + [':slug' => $slug, ':lang' => $lang]);
                $pageId = (int) $pdo->lastInsertId();
                $report['pages_created']++;
                fwrite(STDOUT, sprintf("  создана    /%s [%s] — %s\n", $slug, $lang, $fields[':title']));
            }

            $groupId = $groupId ?? $pageId;
            $pdo->prepare('UPDATE pages SET translation_group_id = :group_id WHERE id = :id')
                ->execute([':group_id' => $groupId, ':id' => $pageId]);
            $pageIds[$slug][$lang] = $pageId;

            // Блоки языковой версии заменяются целиком: страница должна
            // совпасть с фикстурой, а не смешаться с демо-блоками.
            $pdo->prepare('DELETE FROM blocks WHERE page_id = :page_id AND lang = :lang')
                ->execute([':page_id' => $pageId, ':lang' => $lang]);

            $blockIns = $pdo->prepare(
                'INSERT INTO blocks (page_id, lang, type, title, data, sort_order, is_active, created_at)
                 VALUES (:page_id, :lang, :type, :title, :data, :sort_order, 1, NOW())'
            );
            foreach (array_values((array) ($data['blocks'] ?? [])) as $index => $block) {
                [$type, $blockTitle, $blockData] = $block;
                $payload = json_encode($blockData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if (!is_string($payload)) {
                    throw new RuntimeException("Блок {$type} страницы {$slug} [{$lang}] не сериализуется в JSON");
                }
                $blockIns->execute([
                    ':page_id' => $pageId,
                    ':lang' => $lang,
                    ':type' => (string) $type,
                    ':title' => (string) $blockTitle,
                    ':data' => $payload,
                    ':sort_order' => $index,
                ]);
                $report['blocks']++;
            }
        }
    }

    // Родительские страницы проставляются вторым проходом: к этому моменту
    // созданы все языковые версии, и родитель находится на своём языке.
    foreach ($hierarchy as $childSlug => $parentSlug) {
        foreach ($pageIds[(string) $childSlug] ?? [] as $lang => $childId) {
            $parentId = $pageIds[(string) $parentSlug][$lang] ?? null;
            if ($parentId === null || $parentId === $childId) {
                continue;
            }
            $pdo->prepare('UPDATE pages SET parent_id = :parent WHERE id = :id')
                ->execute([':parent' => $parentId, ':id' => $childId]);
        }
    }

    foreach ($team as $member) {
        $member = (array) $member;
        $name = (string) ($member['name'] ?? '');
        if ($name === '') {
            continue;
        }
        $find = $pdo->prepare('SELECT id FROM team_members WHERE name = :name LIMIT 1');
        $find->execute([':name' => $name]);
        $memberId = (int) ($find->fetchColumn() ?: 0);

        $fields = [
            ':name' => $name,
            ':position' => (string) ($member['position'] ?? ''),
            ':department' => (string) ($member['department'] ?? ''),
            ':unit' => (string) ($member['unit'] ?? ''),
            ':sort_order' => (int) ($member['sort_order'] ?? 0),
        ];

        if ($memberId > 0) {
            $pdo->prepare(
                "UPDATE team_members
                    SET position = :position, department = :department, unit = :unit,
                        sort_order = :sort_order, status = 'published'
                  WHERE id = :id"
            )->execute(array_diff_key($fields, [':name' => null]) + [':id' => $memberId]);
            $report['team_updated']++;
        } else {
            $pdo->prepare(
                "INSERT INTO team_members (name, position, department, unit, status, sort_order, created_at)
                 VALUES (:name, :position, :department, :unit, 'published', :sort_order, NOW())"
            )->execute($fields);
            $memberId = (int) $pdo->lastInsertId();
            $report['team_created']++;
        }
        fwrite(STDOUT, sprintf("  сотрудник  %s\n", $name));

        foreach ((array) ($member['translations'] ?? []) as $lang => $translation) {
            $translation = (array) $translation;
            $pdo->prepare(
                'INSERT INTO team_member_translations (member_id, lang, name, position, department, unit)
                 VALUES (:member_id, :lang, :name, :position, :department, :unit)
                 ON DUPLICATE KEY UPDATE name = VALUES(name), position = VALUES(position),
                                         department = VALUES(department), unit = VALUES(unit)'
            )->execute([
                ':member_id' => $memberId,
                ':lang' => (string) $lang,
                ':name' => (string) ($translation['name'] ?? ''),
                ':position' => (string) ($translation['position'] ?? ''),
                ':department' => (string) ($translation['department'] ?? ''),
                ':unit' => (string) ($translation['unit'] ?? ''),
            ]);
        }
    }

    if ($dryRun) {
        $pdo->rollBack();
    } else {
        $pdo->commit();
        Cache::flush();
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'ОШИБКА: ' . $e->getMessage() . "\n");
    exit(1);
}

fwrite(STDOUT, sprintf(
    "\nСтраницы: создано %d, обновлено %d. Блоков записано: %d. Сотрудники: создано %d, обновлено %d.%s",
    $report['pages_created'],
    $report['pages_updated'],
    $report['blocks'],
    $report['team_created'],
    $report['team_updated'],
    PHP_EOL
));

if (!$dryRun) {
    fwrite(STDOUT, "Кэш страниц сброшен. Проверьте /o-nas, /rukovodstvo, /direktor и /pervyy-zamestitel-direktora.\n");
    fwrite(STDOUT, "Пункт меню «Первый заместитель директора» при необходимости добавьте вручную в разделе «Меню».\n");
}

exit(0);
