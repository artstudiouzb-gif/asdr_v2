<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Иерархия страниц: варианты родителя для формы, проверка родителя (не сам
 * себе, без циклов, внутри одной языковой группы) и цепочка предков для
 * хлебных крошек.
 *
 * Выделено из Page: модель отвечает за запись и чтение самой страницы, а
 * обход дерева живёт здесь и читает страницы через её публичный API.
 */
final class PageHierarchy
{
    /**
     * Варианты родительской страницы для административной формы.
     *
     * В список попадают только основные записи групп переводов. Текущая
     * страница и все её потомки исключаются, поэтому интерфейс не предлагает
     * создать цикл.
     *
     * @return list<array{id:int,label:string,depth:int}>
     */
    public static function parentOptions(?int $excludeId = null): array
    {
        $rows = Database::pdo()->query(
            "SELECT id, title, slug, is_home, parent_id, translation_group_id
             FROM pages
             WHERE deleted_at IS NULL AND entity_type = 'page'
             ORDER BY title ASC, id ASC"
        )->fetchAll();

        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row['id']] = $row;
        }

        $excludedGroup = $excludeId !== null ? self::logicalGroupId($excludeId, $byId) : null;
        $options = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $groupId = self::logicalGroupId($id, $byId);
            if ($groupId !== $id
                || !empty($row['is_home'])
                || (string) ($row['slug'] ?? '') === 'home'
                || ($excludedGroup !== null && $groupId === $excludedGroup)) {
                continue;
            }

            $path = [];
            $visited = [];
            $cursor = $row;
            $invalid = false;
            while ($cursor) {
                $cursorId = (int) $cursor['id'];
                $cursorGroup = self::logicalGroupId($cursorId, $byId);
                if (isset($visited[$cursorGroup])) {
                    $invalid = true;
                    break;
                }
                if ($excludedGroup !== null && $cursorGroup === $excludedGroup) {
                    $invalid = true;
                    break;
                }
                $visited[$cursorGroup] = true;
                array_unshift($path, (string) $cursor['title']);
                $parentId = (int) ($cursor['parent_id'] ?? 0);
                $cursor = $parentId > 0 ? ($byId[$parentId] ?? null) : null;
            }
            if ($invalid) {
                continue;
            }

            $options[] = [
                'id' => $id,
                'label' => implode(' → ', $path),
                'depth' => max(0, count($path) - 1),
            ];
        }

        usort($options, static fn (array $a, array $b): int => strnatcasecmp($a['label'], $b['label']));

        return $options;
    }

    /**
     * Проверяет существование родителя и отсутствие циклов в дереве.
     * Возвращает текст ошибки или null.
     */
    public static function validateParent(?int $parentId, ?int $pageId = null, bool $lockRows = false): ?string
    {
        if ($parentId === null || $parentId <= 0) {
            return null;
        }

        $parent = self::hierarchyRow($parentId, $lockRows);
        if (!$parent || !empty($parent['deleted_at'])) {
            return 'Выбранная родительская страница не существует или находится в корзине.';
        }
        if (!empty($parent['is_home']) || (string) ($parent['slug'] ?? '') === 'home') {
            return 'Главную страницу не нужно выбирать родителем: она уже является началом навигации.';
        }

        $currentGroup = $pageId !== null ? self::logicalGroupId($pageId) : null;
        $visited = [];
        $cursor = $parent;
        for ($depth = 0; $depth < 100; $depth++) {
            $cursorGroup = (int) (($cursor['translation_group_id'] ?? null) ?: $cursor['id']);
            if ($currentGroup !== null && $cursorGroup === $currentGroup) {
                return 'Страница не может быть родителем самой себе или своего родительского раздела.';
            }
            if (isset($visited[$cursorGroup])) {
                return 'В иерархии страниц обнаружен цикл.';
            }
            $visited[$cursorGroup] = true;

            $nextId = (int) ($cursor['parent_id'] ?? 0);
            if ($nextId <= 0) {
                return null;
            }
            $next = self::hierarchyRow($nextId, $lockRows);
            if (!$next) {
                return 'Цепочка родительских страниц повреждена.';
            }
            $cursor = $next;
        }

        return 'Превышена допустимая глубина иерархии страниц.';
    }

    /** @return array<string, mixed>|null */
    private static function hierarchyRow(int $id, bool $lockRow): ?array
    {
        $sql = 'SELECT * FROM pages WHERE id = :id AND deleted_at IS NULL LIMIT 1';
        if ($lockRow && Database::pdo()->inTransaction()) {
            $sql .= ' FOR UPDATE';
        }
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Родительские страницы от корневой к непосредственному родителю.
     * Заголовки локализуются, а URL остаются плоскими.
     *
     * @param array<string, mixed> $page
     * @return list<array<string,mixed>>
     */
    public static function ancestorTrail(array $page, string $lang): array
    {
        $trail = [];
        $visited = [];
        $parentId = (int) ($page['parent_id'] ?? 0);

        for ($depth = 0; $parentId > 0 && $depth < 100; $depth++) {
            if (isset($visited[$parentId])) {
                break;
            }
            $visited[$parentId] = true;

            $parent = Page::findById($parentId);
            if (!$parent || !empty($parent['deleted_at'])) {
                break;
            }

            $display = self::hierarchyNodeForLanguage($parent, $lang);
            array_unshift($trail, $display);
            $parentId = (int) ($parent['parent_id'] ?? 0);
        }

        return $trail;
    }

    /** @param array<int,array<string,mixed>>|null $rowsById */
    private static function logicalGroupId(int $id, ?array $rowsById = null): int
    {
        $row = $rowsById[$id] ?? null;
        if ($row === null) {
            $row = Page::findById($id);
        }
        if (!$row) {
            return $id;
        }

        return (int) (($row['translation_group_id'] ?? null) ?: $row['id']);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function hierarchyNodeForLanguage(array $row, string $lang): array
    {
        $groupId = (int) (($row['translation_group_id'] ?? null) ?: $row['id']);
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM pages
             WHERE (id = :group_id OR translation_group_id = :translation_group_id)
               AND lang = :lang AND deleted_at IS NULL
             ORDER BY status = 'published' DESC, id ASC
             LIMIT 1"
        );
        $stmt->execute([
            ':group_id' => $groupId,
            ':translation_group_id' => $groupId,
            ':lang' => $lang,
        ]);
        $localizedRow = $stmt->fetch();

        return $localizedRow ?: Page::localize($row, $lang);
    }
}
