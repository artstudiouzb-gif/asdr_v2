<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\ConcurrencyException;
use App\Core\Database;
use App\Core\Logger;

final class Project
{
    public static function all(): array
    {
        $stmt = Database::pdo()->query('SELECT * FROM projects WHERE deleted_at IS NULL ORDER BY sort_order ASC, created_at DESC');

        return $stmt->fetchAll();
    }

    public static function trashed(): array
    {
        $stmt = Database::pdo()->query('SELECT * FROM projects WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC');

        return $stmt->fetchAll();
    }

    /** Список с фильтром по статусу (задача 91). */
    public static function filter(?string $status = null, ?string $lang = null): array
    {
        $sql = 'SELECT * FROM projects WHERE deleted_at IS NULL';
        $params = [];
        if ($status === 'published' || $status === 'draft') {
            $sql .= ' AND status = :status';
            $params[':status'] = $status;
        }
        $sql .= ' ORDER BY sort_order ASC, created_at DESC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function adminList(array $filters): array
    {
        [$where, $params] = self::adminListWhere($filters);
        $orders = [
            'manual' => 'p.sort_order ASC, p.created_at DESC, p.id DESC',
            'newest' => 'p.created_at DESC, p.id DESC',
            'oldest' => 'p.created_at ASC, p.id ASC',
            'title_asc' => 'p.title ASC, p.id ASC',
            'title_desc' => 'p.title DESC, p.id DESC',
        ];
        $order = $orders[$filters['sort'] ?? 'manual'] ?? $orders['manual'];

        $stmt = Database::pdo()->prepare("SELECT p.* FROM projects p {$where} ORDER BY {$order} LIMIT :limit OFFSET :offset");
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int) $filters['per_page'], \PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $filters['offset'], \PDO::PARAM_INT);
        $stmt->execute();

        $items = $stmt->fetchAll();
        $langFilter = (string) ($filters['lang'] ?? '');
        if ($langFilter !== '' && $langFilter !== 'all' && $items !== []) {
            $ids = array_map(static fn ($item): int => (int) $item['id'], $items);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $tStmt = Database::pdo()->prepare(
                "SELECT project_id, title FROM project_translations WHERE project_id IN ({$placeholders}) AND lang = ? AND TRIM(COALESCE(title, '')) <> ''"
            );
            $tStmt->execute([...$ids, $langFilter]);
            $transMap = $tStmt->fetchAll(\PDO::FETCH_KEY_PAIR);
            foreach ($items as &$item) {
                if (!empty($transMap[(int) $item['id']])) {
                    $item['title'] = (string) $transMap[(int) $item['id']];
                }
            }
            unset($item);
        }

        return $items;
    }

    public static function adminCount(array $filters): array|int
    {
        [$where, $params] = self::adminListWhere($filters);
        $stmt = Database::pdo()->prepare("SELECT COUNT(*) FROM projects p {$where}");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /** @return array{0:string,1:array<string,string>} */
    private static function adminListWhere(array $filters): array
    {
        $params = [];
        $where = ['deleted_at IS NULL'];

        $langFilter = (string) ($filters['lang'] ?? '');
        if ($langFilter !== '' && $langFilter !== 'all') {
            $where[] = '(p.lang = :lang'
                . ' OR (p.lang <> :lang_neq'
                . '     AND EXISTS (SELECT 1 FROM project_translations pt WHERE pt.project_id = p.id AND pt.lang = :lang_pt AND TRIM(COALESCE(pt.title, \'\')) <> \'\')'
                . '     AND NOT EXISTS (SELECT 1 FROM projects p2 WHERE (p2.translation_group_id = COALESCE(NULLIF(p.translation_group_id, 0), p.id) OR p2.id = COALESCE(NULLIF(p.translation_group_id, 0), p.id)) AND p2.lang = :lang_p2 AND p2.deleted_at IS NULL AND p2.id <> p.id)))';
            $params[':lang'] = $langFilter;
            $params[':lang_neq'] = $langFilter;
            $params[':lang_pt'] = $langFilter;
            $params[':lang_p2'] = $langFilter;
        }

        if (in_array($filters['status'] ?? '', ['published', 'draft'], true)) {
            $where[] = 'status = :status';
            $params[':status'] = (string) $filters['status'];
        }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(title LIKE :q_title OR slug LIKE :q_slug OR description LIKE :q_description)';
            $like = '%' . (string) $filters['q'] . '%';
            $params[':q_title'] = $like;
            $params[':q_slug'] = $like;
            $params[':q_description'] = $like;
        }

        return ['WHERE ' . implode(' AND ', $where), $params];
    }

    public static function setStatus(int $id, string $status): void
    {
        if (!in_array($status, ['draft', 'published'], true)) {
            return;
        }
        $stmt = Database::pdo()->prepare('UPDATE projects SET status = :s WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute([':s' => $status, ':id' => $id]);
        self::bustPageCache();
    }

    /** Полная копия проекта с изображениями и полями (черновик, slug -copy). */
    public static function duplicate(int $id): ?int
    {
        $project = self::findById($id);
        if (!$project) {
            return null;
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $newSlug = \App\Core\Duplicator::uniqueCopySlug(
                (string) $project['slug'],
                static fn (string $s) => self::slugExists($s)
            );
            $newId = \App\Core\Duplicator::copyRow('projects', $project, [
                'slug' => $newSlug,
                'status' => 'draft',
                'deleted_at' => null,
            ]);
            \App\Core\Duplicator::copyChildren('project_images', 'project_id', $id, $newId);
            \App\Core\Duplicator::copyChildren('project_fields', 'project_id', $id, $newId);
            \App\Core\Duplicator::copyChildren('project_translations', 'project_id', $id, $newId);

            $pdo->commit();

            return $newId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function restore(int $id): void
    {
        $stmt = Database::pdo()->prepare('UPDATE projects SET deleted_at = NULL WHERE id = :id');
        $stmt->execute([':id' => $id]);
        self::bustPageCache();
    }

    public static function forceDelete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM projects WHERE id = :id');
        $stmt->execute([':id' => $id]);
        ContentRevision::deleteForEntity('project', $id);
        self::bustPageCache();
    }

    /**
     * @return array{join:string, where:string, params:array<string, string>}
     */
    private static function publicLanguageParts(string $lang, string $alias, string $prefix): array
    {
        $default = Language::defaultCode();
        if ($lang === $default) {
            return [
                'join' => '',
                'where' => "{$alias}.lang = :{$prefix}_exact_lang",
                'params' => ["{$prefix}_exact_lang" => $lang],
            ];
        }

        $translationAlias = $prefix . '_translation';
        $shadowAlias = $prefix . '_shadow';

        return [
            'join' => " LEFT JOIN project_translations {$translationAlias}
                        ON {$translationAlias}.project_id = {$alias}.id
                       AND {$translationAlias}.lang = :{$prefix}_legacy_lang",
            'where' => "(
                {$alias}.lang = :{$prefix}_exact_lang
                OR (
                    {$alias}.lang = :{$prefix}_default_lang
                    AND {$translationAlias}.id IS NOT NULL
                    AND (
                        TRIM(COALESCE({$translationAlias}.title, '')) <> ''
                        OR TRIM(COALESCE({$translationAlias}.description, '')) <> ''
                    )
                    AND NOT EXISTS (
                        SELECT 1 FROM projects {$shadowAlias}
                        WHERE {$shadowAlias}.deleted_at IS NULL
                          AND {$shadowAlias}.lang = :{$prefix}_shadow_lang
                          AND COALESCE(NULLIF({$shadowAlias}.translation_group_id, 0), {$shadowAlias}.id)
                              = COALESCE(NULLIF({$alias}.translation_group_id, 0), {$alias}.id)
                    )
                )
            )",
            'params' => [
                "{$prefix}_legacy_lang" => $lang,
                "{$prefix}_exact_lang" => $lang,
                "{$prefix}_default_lang" => $default,
                "{$prefix}_shadow_lang" => $lang,
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private static function localizePublicRows(array $rows, string $lang): array
    {
        if ($rows === [] || $lang === Language::defaultCode()) {
            return $rows;
        }

        $fallbackIds = [];
        foreach ($rows as $row) {
            if ((string) ($row['lang'] ?? '') !== $lang) {
                $fallbackIds[] = (int) $row['id'];
            }
        }
        $translations = ProjectTranslation::forProjectIds($fallbackIds, $lang);
        foreach ($rows as &$row) {
            $id = (int) $row['id'];
            if ((string) ($row['lang'] ?? '') !== $lang && isset($translations[$id])) {
                $row = self::applyTranslation($row, $translations[$id]);
            }
        }
        unset($row);

        return $rows;
    }

    public static function published(?string $lang = null): array
    {
        $lang = $lang ?? Language::defaultCode();
        $parts = self::publicLanguageParts($lang, 'p', 'published');
        $stmt = Database::pdo()->prepare(
            "SELECT p.* FROM projects p{$parts['join']}
             WHERE p.status = 'published' AND p.deleted_at IS NULL
               AND {$parts['where']}
             ORDER BY p.sort_order ASC, p.created_at DESC, p.id DESC"
        );
        $stmt->execute($parts['params']);
        $rows = $stmt->fetchAll();

        return self::localizePublicRows($rows, $lang);
    }

    /**
     * Проекты для блока главной: только помеченные «показать на главном».
     * Если ни один не отмечен — откат на последние опубликованные (чтобы блок
     * не был пустым при первом включении источника).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forHome(int $limit = 6, ?string $lang = null): array
    {
        $lang = $lang ?? Language::defaultCode();
        $limit = max(1, min(24, $limit));
        $parts = self::publicLanguageParts($lang, 'p', 'featured');
        $stmt = Database::pdo()->prepare(
            "SELECT p.* FROM projects p{$parts['join']}
             WHERE p.status = 'published' AND p.deleted_at IS NULL AND p.is_featured = 1
               AND {$parts['where']}
             ORDER BY p.sort_order ASC, p.created_at DESC, p.id DESC LIMIT {$limit}"
        );
        $stmt->execute($parts['params']);
        $rows = $stmt->fetchAll();
        if (empty($rows)) {
            $parts = self::publicLanguageParts($lang, 'p', 'recent');
            $stmt = Database::pdo()->prepare(
                "SELECT p.* FROM projects p{$parts['join']}
                 WHERE p.status = 'published' AND p.deleted_at IS NULL
                   AND {$parts['where']}
                 ORDER BY p.sort_order ASC, p.created_at DESC, p.id DESC LIMIT {$limit}"
            );
            $stmt->execute($parts['params']);
            $rows = $stmt->fetchAll();
        }

        return self::localizePublicRows($rows, $lang);
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM projects WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function findPublishedBySlug(string $slug, ?string $lang = null): ?array
    {
        $lang = $lang ?? Language::defaultCode();

        $stmtExact = Database::pdo()->prepare(
            "SELECT * FROM projects WHERE slug = :slug AND lang = :lang AND status = 'published' AND deleted_at IS NULL LIMIT 1"
        );
        $stmtExact->execute([':slug' => $slug, ':lang' => $lang]);
        $exactRow = $stmtExact->fetch();
        if ($exactRow) {
            return $exactRow;
        }

        $stmt = Database::pdo()->prepare(
            "SELECT * FROM projects WHERE slug = :slug AND status = 'published' AND deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $groupId = (int) ($row['translation_group_id'] ?: $row['id']);
        $stmtTrans = Database::pdo()->prepare(
            "SELECT * FROM projects
             WHERE COALESCE(NULLIF(translation_group_id, 0), id) = :group_id
               AND lang = :lang AND status = 'published' AND deleted_at IS NULL
             LIMIT 1"
        );
        $stmtTrans->execute([':group_id' => $groupId, ':lang' => $lang]);
        $transRow = $stmtTrans->fetch();
        if ($transRow) {
            return $transRow;
        }

        $stmtIndependent = Database::pdo()->prepare(
            "SELECT COUNT(*) FROM projects
             WHERE COALESCE(NULLIF(translation_group_id, 0), id) = :group_id
               AND lang = :lang AND deleted_at IS NULL"
        );
        $stmtIndependent->execute([':group_id' => $groupId, ':lang' => $lang]);
        if ((int) $stmtIndependent->fetchColumn() > 0) {
            return $row;
        }

        $default = Language::defaultCode();
        $stmtBase = Database::pdo()->prepare(
            "SELECT * FROM projects
             WHERE COALESCE(NULLIF(translation_group_id, 0), id) = :group_id
               AND lang = :default_lang AND status = 'published' AND deleted_at IS NULL
             LIMIT 1"
        );
        $stmtBase->execute([':group_id' => $groupId, ':default_lang' => $default]);
        $base = $stmtBase->fetch();
        if ($base && $lang !== $default) {
            $legacy = ProjectTranslation::find((int) $base['id'], $lang);
            if ($legacy !== null && (
                trim((string) ($legacy['title'] ?? '')) !== ''
                || trim((string) ($legacy['description'] ?? '')) !== ''
            )) {
                return self::applyTranslation($base, $legacy);
            }
        }

        return $base ?: $row;
    }

    /**
     * Накладывает перевод указанного языка на базовую строку. Пустые поля
     * перевода откатываются к значению основного языка (graceful fallback).
     */
    public static function localize(array $row, string $lang): array
    {
        return self::applyTranslation($row, ProjectTranslation::find((int) $row['id'], $lang));
    }

    private static function applyTranslation(array $row, ?array $translation): array
    {
        if ($translation === null) {
            return $row;
        }
        foreach (['title', 'description'] as $field) {
            if (isset($translation[$field]) && trim((string) $translation[$field]) !== '') {
                $row[$field] = $translation[$field];
            }
        }

        return $row;
    }

    /**
     * Языки контента для набора проектов в виде списка кодов ['ru', 'uz'] (или с картой целевых постов при $withTargets = true).
     *
     * @param array<int|string> $ids
     * @return array<int, list<string>>|array<int, array<string, int>>
     */
    public static function availableLangsForIds(array $ids, bool $withTargets = false): array
    {
        $targets = self::availableLangTargetsForIds($ids);
        if ($withTargets) {
            return $targets;
        }

        $map = [];
        foreach ($targets as $id => $langs) {
            $map[$id] = array_values(array_unique(array_keys($langs)));
        }

        return $map;
    }

    /**
     * Языки опубликованных версий проекта для публичного переключателя.
     *
     * @return list<string>
     */
    public static function availableLangs(int $id): array
    {
        $pdo = Database::pdo();

        $stmtProject = $pdo->prepare(
            'SELECT COALESCE(NULLIF(translation_group_id, 0), id) FROM projects WHERE id = :id LIMIT 1'
        );
        $stmtProject->execute([':id' => $id]);
        $groupId = (int) ($stmtProject->fetchColumn() ?: $id);

        $stmtGroup = $pdo->prepare(
            "SELECT id, lang, status FROM projects
             WHERE COALESCE(NULLIF(translation_group_id, 0), id) = :group_id
               AND deleted_at IS NULL"
        );
        $stmtGroup->execute([':group_id' => $groupId]);
        $default = Language::defaultCode();
        $allIndependentLangs = [];
        $langs = [];
        $baseId = null;
        $baseIsPublished = false;
        foreach ($stmtGroup->fetchAll() as $record) {
            $recordLang = (string) ($record['lang'] ?? '');
            if ($recordLang === '') {
                continue;
            }
            $allIndependentLangs[$recordLang] = true;
            $isPublished = (string) ($record['status'] ?? '') === 'published';
            if ($isPublished) {
                $langs[] = $recordLang;
            }
            if ($recordLang === $default) {
                $baseId = (int) $record['id'];
                $baseIsPublished = $isPublished;
            }
        }

        if ($baseId !== null && $baseIsPublished) {
            $stmtLegacy = $pdo->prepare(
                "SELECT lang FROM project_translations
                 WHERE project_id = :id
                   AND (TRIM(COALESCE(title, '')) <> '' OR TRIM(COALESCE(description, '')) <> '')"
            );
            $stmtLegacy->execute([':id' => $baseId]);
            foreach ($stmtLegacy->fetchAll(\PDO::FETCH_COLUMN) as $legacyLang) {
                if (is_string($legacyLang) && $legacyLang !== '' && !isset($allIndependentLangs[$legacyLang])) {
                    $langs[] = $legacyLang;
                }
            }
        }

        return array_values(array_unique($langs));
    }

    /**
     * Карта языков контента с ID целевых записей для кликабельных баджей ['ru' => 15, 'uz' => 98].
     *
     * @param array<int|string> $ids
     * @return array<int, array<string, int>>
     */
    public static function availableLangTargetsForIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $default = Language::defaultCode();
        $map = [];
        foreach ($ids as $id) {
            $map[$id] = [];
        }
        if ($ids === []) {
            return $map;
        }

        $in = implode(',', array_fill(0, count($ids), '?'));
        $pdo = Database::pdo();

        try {
            $stmtGroup = $pdo->prepare(
                "SELECT p1.id AS ref_id, p2.lang, p2.id AS target_id, p2.title
                 FROM projects p1
                 JOIN projects p2 ON (p2.translation_group_id = COALESCE(NULLIF(p1.translation_group_id, 0), p1.id)
                                OR p2.id = COALESCE(NULLIF(p1.translation_group_id, 0), p1.id)
                                OR (p1.translation_group_id > 0 AND p2.translation_group_id = p1.translation_group_id))
                 WHERE p1.id IN ($in) AND p2.deleted_at IS NULL"
            );
            $stmtGroup->execute($ids);
            foreach ($stmtGroup->fetchAll() as $row) {
                $refId = (int) $row['ref_id'];
                $lang = (string) ($row['lang'] ?? $default);
                $targetId = (int) $row['target_id'];
                $title = trim((string) ($row['title'] ?? ''));
                if ($title !== '' && isset($map[$refId])) {
                    $map[$refId][$lang] = $targetId;
                }
            }
        } catch (\Throwable $e) {
            Logger::swallowed('Project::availableLangsForIds: не удалось прочитать группы переводов', $e);
        }

        try {
            $stmtLegacy = $pdo->prepare(
                "SELECT project_id, lang FROM project_translations
                 WHERE project_id IN ($in)
                   AND (TRIM(COALESCE(title, '')) <> '' OR TRIM(COALESCE(description, '')) <> '')"
            );
            $stmtLegacy->execute($ids);
            foreach ($stmtLegacy->fetchAll() as $row) {
                $id = (int) $row['project_id'];
                $lang = (string) $row['lang'];
                if (isset($map[$id]) && !isset($map[$id][$lang])) {
                    $map[$id][$lang] = $id;
                }
            }
        } catch (\Throwable $e) {
            Logger::swallowed('Project::availableLangsForIds: не удалось прочитать project_translations', $e);
        }

        foreach ($ids as $id) {
            if (empty($map[$id])) {
                $map[$id] = [$default => $id];
            }
        }

        return $map;
    }

    public static function slugExists(string $slug, ?int $excludeId = null, ?string $lang = null): bool
    {
        $lang = $lang ?? Language::defaultCode();
        $sql = 'SELECT COUNT(*) FROM projects WHERE slug = :slug AND lang = :lang AND deleted_at IS NULL';
        $params = [':slug' => $slug, ':lang' => $lang];

        if ($excludeId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $excludeId;
        }

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public static function langCounts(): array
    {
        $pdo = Database::pdo();
        $counts = [];
        $sum = 0;
        foreach (Language::active() as $l) {
            $code = (string) $l['code'];
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM projects p
                 WHERE p.deleted_at IS NULL
                   AND (p.lang = :code
                     OR (p.lang <> :code_neq
                         AND EXISTS (SELECT 1 FROM project_translations pt WHERE pt.project_id = p.id AND pt.lang = :code_pt AND TRIM(COALESCE(pt.title, '')) <> '')
                         AND NOT EXISTS (SELECT 1 FROM projects p2 WHERE (p2.translation_group_id = COALESCE(NULLIF(p.translation_group_id, 0), p.id) OR p2.id = COALESCE(NULLIF(p.translation_group_id, 0), p.id)) AND p2.lang = :code_p2 AND p2.deleted_at IS NULL AND p2.id <> p.id)))"
            );
            $stmt->execute([
                ':code' => $code,
                ':code_neq' => $code,
                ':code_pt' => $code,
                ':code_p2' => $code,
            ]);
            $c = (int) $stmt->fetchColumn();
            $counts[$code] = $c;
            $sum += $c;
        }
        $counts['all'] = $sum;

        return $counts;
    }

    public static function create(array $data): int
    {
        $lang = (string) ($data['lang'] ?? Language::defaultCode());
        $stmt = Database::pdo()->prepare(
            'INSERT INTO projects (title, slug, description, cover_image, status, is_featured, sort_order, lang, translation_group_id, created_at)
             VALUES (:title, :slug, :description, :cover_image, :status, :is_featured, :sort_order, :lang, NULL, NOW())'
        );
        $stmt->execute([
            ':title' => $data['title'],
            ':slug' => $data['slug'],
            ':description' => $data['description'],
            ':cover_image' => $data['cover_image'],
            ':status' => $data['status'],
            ':is_featured' => !empty($data['is_featured']) ? 1 : 0,
            ':sort_order' => $data['sort_order'] ?? 0,
            ':lang' => $lang,
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        Database::pdo()->prepare('UPDATE projects SET translation_group_id = id WHERE id = :id AND (translation_group_id IS NULL OR translation_group_id = 0)')->execute([':id' => $id]);
        self::bustPageCache();

        return $id;
    }

    public static function update(int $id, array $data, ?int $expectedLockVersion = null): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE projects SET title = :title, slug = :slug, description = :description,
             cover_image = :cover_image, status = :status, is_featured = :is_featured,
             sort_order = :sort_order, lock_version = lock_version + 1
             WHERE id = :id' . ($expectedLockVersion !== null ? ' AND lock_version = :expected_lock_version' : '')
        );
        $params = [
            ':title' => $data['title'],
            ':slug' => $data['slug'],
            ':description' => $data['description'],
            ':cover_image' => $data['cover_image'],
            ':status' => $data['status'],
            ':is_featured' => !empty($data['is_featured']) ? 1 : 0,
            ':sort_order' => $data['sort_order'] ?? 0,
            ':id' => $id,
        ];
        if ($expectedLockVersion !== null) {
            $params[':expected_lock_version'] = $expectedLockVersion;
        }
        $stmt->execute($params);
        if ($expectedLockVersion !== null && $stmt->rowCount() !== 1) {
            throw new ConcurrencyException('Проект был изменён другим пользователем.');
        }
        self::bustPageCache();
    }

    public static function delete(int $id): void
    {
        // Мягкое удаление: проект отправляется в корзину.
        $stmt = Database::pdo()->prepare('UPDATE projects SET deleted_at = NOW() WHERE id = :id');
        $stmt->execute([':id' => $id]);
        self::bustPageCache();
    }

    private static function bustPageCache(): void
    {
        \App\Core\Cache::forgetPrefix('page:');
    }
}
