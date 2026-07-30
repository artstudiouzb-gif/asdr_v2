<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Logger;

final class TeamMember
{
    public static function all(): array
    {
        $stmt = Database::pdo()->query('SELECT * FROM team_members ORDER BY sort_order ASC, id ASC');

        return $stmt->fetchAll();
    }

    public static function published(?string $lang = null): array
    {
        $lang = $lang ?? Language::defaultCode();
        if ($lang === Language::defaultCode()) {
            $stmt = Database::pdo()->query(
                "SELECT * FROM team_members WHERE status = 'published' ORDER BY sort_order ASC, id ASC"
            );

            return $stmt->fetchAll();
        }

        $stmt = Database::pdo()->prepare(
            "SELECT tm.* FROM team_members tm
             INNER JOIN team_member_translations tmt
                ON tmt.member_id = tm.id AND tmt.lang = :lang
               AND (
                    TRIM(COALESCE(tmt.name, '')) <> ''
                    OR TRIM(COALESCE(tmt.position, '')) <> ''
               )
             WHERE tm.status = 'published'
             ORDER BY tm.sort_order ASC, tm.id ASC"
        );
        $stmt->execute([':lang' => $lang]);
        $rows = $stmt->fetchAll();

        return self::localizeRows($rows, $lang);
    }

    /**
     * Накладывает перевод указанного языка на базовую строку. Пустые поля
     * перевода откатываются к значению основного языка (graceful fallback).
     */
    public static function localize(array $row, string $lang): array
    {
        return self::applyTranslation($row, TeamMemberTranslation::find((int) $row['id'], $lang));
    }

    /** @param array<int, array<string, mixed>> $rows @return array<int, array<string, mixed>> */
    private static function localizeRows(array $rows, string $lang): array
    {
        $translations = TeamMemberTranslation::forMemberIds(
            array_map(static fn (array $row): int => (int) $row['id'], $rows),
            $lang
        );
        return array_map(
            static fn (array $row): array => self::applyTranslation($row, $translations[(int) $row['id']] ?? null),
            $rows
        );
    }

    private static function applyTranslation(array $row, ?array $translation): array
    {
        if ($translation === null) {
            return $row;
        }
        foreach (['name', 'position'] as $field) {
            if (isset($translation[$field]) && trim((string) $translation[$field]) !== '') {
                $row[$field] = $translation[$field];
            }
        }

        return $row;
    }

    /**
     * Языки контента для набора сотрудников одним запросом (без N+1).
     * Контент на языке = непустой перевод имени или должности.
     *
     * @param array<int|string> $ids
     * @return array<int, array<int, string>>
     */
    public static function availableLangsForIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $map = [];
        foreach ($ids as $id) {
            $map[$id] = [];
        }
        if ($ids === []) {
            return $map;
        }

        $in = implode(',', array_fill(0, count($ids), '?'));
        $default = Language::defaultCode();

        try {
            $stmtBase = Database::pdo()->prepare(
                "SELECT id, name FROM team_members WHERE id IN ($in)"
            );
            $stmtBase->execute($ids);
            foreach ($stmtBase->fetchAll() as $row) {
                $id = (int) $row['id'];
                if (trim((string) ($row['name'] ?? '')) !== '' && isset($map[$id])) {
                    $map[$id][] = $default;
                }
            }
        } catch (\Throwable $e) {
            Logger::swallowed('TeamMember::availableLangsForIds: не удалось прочитать базовые записи', $e);
        }

        try {
            $stmtTrans = Database::pdo()->prepare(
                "SELECT member_id, lang FROM team_member_translations
                 WHERE member_id IN ($in)
                   AND (TRIM(COALESCE(name, '')) <> '' OR TRIM(COALESCE(position, '')) <> '')"
            );
            $stmtTrans->execute($ids);
            foreach ($stmtTrans->fetchAll() as $row) {
                $id = (int) $row['member_id'];
                $lang = (string) $row['lang'];
                if (isset($map[$id]) && !in_array($lang, $map[$id], true)) {
                    $map[$id][] = $lang;
                }
            }
        } catch (\Throwable $e) {
            Logger::swallowed('TeamMember::availableLangsForIds: не удалось прочитать team_member_translations', $e);
        }

        foreach ($ids as $id) {
            if (empty($map[$id])) {
                $map[$id] = [$default];
            }
        }

        return $map;
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM team_members WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO team_members (name, position, photo, email, phone, socials_json, status, sort_order, created_at)
             VALUES (:name, :position, :photo, :email, :phone, :socials_json, :status, :sort_order, NOW())'
        );
        $stmt->execute([
            ':name' => $data['name'],
            ':position' => $data['position'],
            ':photo' => $data['photo'],
            ':email' => $data['email'],
            ':phone' => $data['phone'],
            ':socials_json' => json_encode($data['socials'] ?? [], JSON_UNESCAPED_UNICODE),
            ':status' => $data['status'],
            ':sort_order' => $data['sort_order'] ?? 0,
        ]);

        // id читаем до сброса кэша: bustPageCache() делает запрос к settings,
        // который обнуляет lastInsertId() (см. Project::create).
        $id = (int) Database::pdo()->lastInsertId();
        self::bustPageCache();

        return $id;
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE team_members SET name = :name, position = :position, photo = :photo, email = :email,
             phone = :phone, socials_json = :socials_json, status = :status, sort_order = :sort_order WHERE id = :id'
        );
        $stmt->execute([
            ':name' => $data['name'],
            ':position' => $data['position'],
            ':photo' => $data['photo'],
            ':email' => $data['email'],
            ':phone' => $data['phone'],
            ':socials_json' => json_encode($data['socials'] ?? [], JSON_UNESCAPED_UNICODE),
            ':status' => $data['status'],
            ':sort_order' => $data['sort_order'] ?? 0,
            ':id' => $id,
        ]);
        self::bustPageCache();
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM team_members WHERE id = :id');
        $stmt->execute([':id' => $id]);
        self::bustPageCache();
    }

    private static function bustPageCache(): void
    {
        \App\Core\Cache::forgetPrefix('page:');
    }
}
