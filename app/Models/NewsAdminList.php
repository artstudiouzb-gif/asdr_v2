<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Список новостей в административной панели: фильтры, сортировка, страница.
 *
 * Выделено из News: модель отвечает за запись и чтение одной сущности, а
 * фильтры, сортировка и постраничный вывод списка в админке живут здесь.
 */
final class NewsAdminList
{
    /**
     * Фильтрованный и постраничный список для административной панели.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public static function items(array $filters): array
    {
        [$from, $params] = self::from($filters);
        $orders = [
            'newest' => 'n.created_at DESC, n.id DESC',
            'oldest' => 'n.created_at ASC, n.id ASC',
            'title_asc' => 'n.title ASC, n.id ASC',
            'title_desc' => 'n.title DESC, n.id DESC',
            'published_desc' => 'n.published_at DESC, n.id DESC',
        ];
        $order = $orders[$filters['sort'] ?? 'newest'] ?? $orders['newest'];
        $sql = "SELECT n.* {$from} ORDER BY {$order} LIMIT :limit OFFSET :offset";
        $stmt = Database::pdo()->prepare($sql);
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
                "SELECT news_id, title FROM news_translations WHERE news_id IN ({$placeholders}) AND lang = ? AND TRIM(COALESCE(title, '')) <> ''"
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
     * @return array{0:string,1:array<string,string|int>}
     */
    private static function from(array $filters): array
    {
        $from = 'FROM news n';
        $params = [];
        $where = ['n.deleted_at IS NULL'];

        $langFilter = (string) ($filters['lang'] ?? '');
        if ($langFilter !== '' && $langFilter !== 'all') {
            $where[] = '(n.lang = :lang'
                . ' OR (n.lang <> :lang_neq'
                . '     AND EXISTS (SELECT 1 FROM news_translations nt WHERE nt.news_id = n.id AND nt.lang = :lang_nt AND TRIM(COALESCE(nt.title, \'\')) <> \'\')'
                . '     AND NOT EXISTS (SELECT 1 FROM news n2 WHERE (n2.translation_group_id = COALESCE(NULLIF(n.translation_group_id, 0), n.id) OR n2.id = COALESCE(NULLIF(n.translation_group_id, 0), n.id)) AND n2.lang = :lang_n2 AND n2.deleted_at IS NULL AND n2.id <> n.id)))';
            $params[':lang'] = $langFilter;
            $params[':lang_neq'] = $langFilter;
            $params[':lang_nt'] = $langFilter;
            $params[':lang_n2'] = $langFilter;
        }

        if (in_array($filters['status'] ?? '', ['published', 'draft'], true)) {
            $where[] = 'n.status = :status';
            $params[':status'] = (string) $filters['status'];
        }
        $categoryFilter = (string) ($filters['category'] ?? '');
        if ($categoryFilter === 'none') {
            $where[] = 'n.category_id IS NULL';
        } elseif ((int) $categoryFilter > 0) {
            $where[] = 'n.category_id = :category';
            $params[':category'] = (int) $categoryFilter;
        }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(n.title LIKE :q_title OR n.slug LIKE :q_slug'
                . ' OR EXISTS (SELECT 1 FROM news_translations nqs WHERE nqs.news_id = n.id AND nqs.title LIKE :q_translation))';
            $like = '%' . (string) $filters['q'] . '%';
            $params[':q_title'] = $like;
            $params[':q_slug'] = $like;
            $params[':q_translation'] = $like;
        }
        if (($filters['from'] ?? '') !== '') {
            $where[] = 'n.published_at >= :date_from';
            $params[':date_from'] = (string) $filters['from'] . ' 00:00:00';
        }
        if (($filters['to'] ?? '') !== '') {
            $where[] = 'n.published_at < DATE_ADD(:date_to, INTERVAL 1 DAY)';
            $params[':date_to'] = (string) $filters['to'] . ' 00:00:00';
        }

        $from .= ' WHERE ' . implode(' AND ', $where);

        return [$from, $params];
    }
}
