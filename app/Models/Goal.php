<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\UrlGuard;

/**
 * «Цель» (Maqsad) — набор снимков без текста наружу. Публичного адреса у цели
 * нет: на сайте она показывается только каруселью в виджете, поэтому ни slug,
 * ни SEO, ни таблицы переводов ей не нужны. Имя служебное — оно существует,
 * чтобы отличать записи в списке из сотен штук.
 */
final class Goal
{
    /**
     * Страница списка админки. Записей сотни, поэтому список постраничный и с
     * поиском по имени — иначе нужная цель ищется прокруткой.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function page(int $limit, int $offset, string $search = ''): array
    {
        $where = '';
        $params = [];
        if ($search !== '') {
            $where = 'WHERE g.name LIKE ?';
            $params[] = '%' . $search . '%';
        }

        // Число снимков — одним запросом вместе со списком: отдельный SELECT на
        // каждую строку дал бы N+1 на странице из полусотни целей.
        $sql = 'SELECT g.*, COUNT(i.id) AS image_count
                  FROM goals g
                  LEFT JOIN goal_images i ON i.goal_id = g.id
                ' . $where . '
                 GROUP BY g.id
                 ORDER BY g.id DESC
                 LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function countAll(string $search = ''): int
    {
        if ($search !== '') {
            $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM goals WHERE name LIKE ?');
            $stmt->execute(['%' . $search . '%']);
        } else {
            $stmt = Database::pdo()->query('SELECT COUNT(*) FROM goals');
        }

        return (int) $stmt->fetchColumn();
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM goals WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /** @return array<int, array<string, mixed>> */
    public static function images(int $goalId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM goal_images WHERE goal_id = ? ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$goalId]);

        return $stmt->fetchAll();
    }

    /**
     * Случайная цель со снимками — то, что показывает виджет.
     *
     * Идентификаторы выбираются отдельным запросом, а не `ORDER BY RAND()`:
     * так выбор не зависит от того, сколько снимков у целей, и остаётся
     * одинаково дешёвым, когда целей станет вдвое больше. Цель без снимков
     * пропускается — пустая карусель хуже, чем соседняя цель.
     *
     * @return array{goal: array<string, mixed>, images: array<int, array<string, mixed>>}|null
     */
    public static function random(): ?array
    {
        $ids = Database::pdo()
            ->query('SELECT DISTINCT g.id FROM goals g
                       JOIN goal_images i ON i.goal_id = g.id
                      WHERE g.is_active = 1')
            ->fetchAll(\PDO::FETCH_COLUMN);
        if ($ids === []) {
            return null;
        }

        $id = (int) $ids[random_int(0, count($ids) - 1)];
        $goal = self::find($id);
        if ($goal === null) {
            return null;
        }

        return ['goal' => $goal, 'images' => self::images($id)];
    }

    public static function create(string $name, bool $isActive): int
    {
        $stmt = Database::pdo()->prepare('INSERT INTO goals (name, is_active) VALUES (?, ?)');
        $stmt->execute([$name, $isActive ? 1 : 0]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, string $name, bool $isActive): void
    {
        $stmt = Database::pdo()->prepare('UPDATE goals SET name = ?, is_active = ? WHERE id = ?');
        $stmt->execute([$name, $isActive ? 1 : 0, $id]);
    }

    public static function delete(int $id): void
    {
        // Снимки уходят вместе с целью по внешнему ключу: строки без владельца
        // никому не видны и просто копились бы.
        $stmt = Database::pdo()->prepare('DELETE FROM goals WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * Снимки цели переписываются целиком: форма присылает готовый порядок, и
     * сверять её со старым списком построчно незачем.
     *
     * @param array<int, array{image: string, alt: string}> $images
     */
    public static function replaceImages(int $goalId, array $images): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM goal_images WHERE goal_id = ?')->execute([$goalId]);

        $insert = $pdo->prepare(
            'INSERT INTO goal_images (goal_id, image, alt, sort_order) VALUES (?, ?, ?, ?)'
        );
        $order = 0;
        foreach ($images as $image) {
            $src = trim((string) ($image['image'] ?? ''));
            if ($src === '' || !UrlGuard::isSafeMedia($src)) {
                continue;
            }
            $insert->execute([
                $goalId,
                $src,
                mb_substr(trim((string) ($image['alt'] ?? '')), 0, 200),
                $order++,
            ]);
        }
    }
}
