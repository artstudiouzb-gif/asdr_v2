<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Список страниц в административной панели: фильтры, сортировка, страница.
 *
 * Выделено из Page: модель отвечает за запись и чтение одной сущности, а
 * фильтры, сортировка и постраничный вывод списка в админке живут здесь.
 */
final class PageAdminList
{
    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public static function items(array $filters): array
    {
        [$from, $params] = self::from($filters);
        $orders = [
            'newest' => 'p.created_at DESC, p.id DESC',
            'oldest' => 'p.created_at ASC, p.id ASC',
            'title_asc' => 'p.title ASC, p.id ASC',
            'title_desc' => 'p.title DESC, p.id DESC',
        ];
        $order = $orders[$filters['sort'] ?? 'newest'] ?? $orders['newest'];
        $stmt = Database::pdo()->prepare(
            "SELECT p.*,
                    (SELECT parent.title FROM pages parent WHERE parent.id = p.parent_id AND parent.deleted_at IS NULL LIMIT 1) AS parent_title,
                    (SELECT parent.slug FROM pages parent WHERE parent.id = p.parent_id AND parent.deleted_at IS NULL LIMIT 1) AS parent_slug
             {$from}
             ORDER BY {$order}
             LIMIT :limit OFFSET :offset"
        );
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
                "SELECT page_id, title FROM page_translations WHERE page_id IN ({$placeholders}) AND lang = ? AND TRIM(COALESCE(title, '')) <> ''"
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

    /** @param array<string, mixed> $filters */
    public static function count(array $filters): int
    {
        [$from, $params] = self::from($filters);
        $stmt = Database::pdo()->prepare("SELECT COUNT(*) {$from}");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{0:string,1:array<string,string>}
     */
    private static function from(array $filters): array
    {
        $from = 'FROM pages p';
        $params = [];
        // Проекты живут в этой же таблице, но у них свой раздел админки.
        $where = ['p.deleted_at IS NULL', "p.entity_type = 'page'"];

        $langFilter = (string) ($filters['lang'] ?? '');
        if ($langFilter !== '' && $langFilter !== 'all') {
            $where[] = '(p.lang = :lang'
                . ' OR (p.lang <> :lang_neq'
                . '     AND (EXISTS (SELECT 1 FROM page_translations pt WHERE pt.page_id = p.id AND pt.lang = :lang_pt AND TRIM(COALESCE(pt.title, \'\')) <> \'\')'
                . '          OR EXISTS (SELECT 1 FROM blocks b WHERE b.page_id = p.id AND b.lang = :lang_b))'
                . '     AND NOT EXISTS (SELECT 1 FROM pages p2 WHERE (p2.translation_group_id = COALESCE(NULLIF(p.translation_group_id, 0), p.id) OR p2.id = COALESCE(NULLIF(p.translation_group_id, 0), p.id)) AND p2.lang = :lang_p2 AND p2.deleted_at IS NULL AND p2.id <> p.id)))';
            $params[':lang'] = $langFilter;
            $params[':lang_neq'] = $langFilter;
            $params[':lang_pt'] = $langFilter;
            $params[':lang_b'] = $langFilter;
            $params[':lang_p2'] = $langFilter;
        }

        if (in_array($filters['status'] ?? '', ['published', 'draft'], true)) {
            $where[] = 'p.status = :status';
            $params[':status'] = (string) $filters['status'];
        }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(p.title LIKE :q_title OR p.slug LIKE :q_slug'
                . ' OR EXISTS (SELECT 1 FROM page_translations pqs WHERE pqs.page_id = p.id AND pqs.title LIKE :q_translation))';
            $like = '%' . (string) $filters['q'] . '%';
            $params[':q_title'] = $like;
            $params[':q_slug'] = $like;
            $params[':q_translation'] = $like;
        }

        $from .= ' WHERE ' . implode(' AND ', $where);

        return [$from, $params];
    }
}
