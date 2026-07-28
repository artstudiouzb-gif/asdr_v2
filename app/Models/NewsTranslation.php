<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class NewsTranslation
{
    /**
     * @return array<string, array<string, mixed>> переводы по коду языка
     */
    public static function forNews(int $newsId): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM news_translations WHERE news_id = :id');
        $stmt->execute([':id' => $newsId]);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(string) $row['lang']] = $row;
        }

        return $result;
    }

    public static function find(int $newsId, string $lang): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM news_translations WHERE news_id = :id AND lang = :lang LIMIT 1'
        );
        $stmt->execute([':id' => $newsId, ':lang' => $lang]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Пакетная загрузка одного перевода для списка новостей — устраняет N+1.
     * @param list<int> $newsIds
     * @return array<int, array<string, mixed>>
     */
    public static function forNewsIds(array $newsIds, string $lang): array
    {
        $newsIds = array_values(array_unique(array_filter(array_map('intval', $newsIds), static fn (int $id): bool => $id > 0)));
        if ($newsIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($newsIds), '?'));
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM news_translations WHERE news_id IN ({$placeholders}) AND lang = ?"
        );
        $stmt->execute([...$newsIds, $lang]);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(int) $row['news_id']] = $row;
        }
        return $result;
    }

    private static bool $schemaEnsured = false;

    public static function ensureSchema(): void
    {
        if (self::$schemaEnsured || !Database::isConnected()) {
            return;
        }

        $cols = [
            'key_points' => 'TEXT NULL',
            'event_meta' => 'TEXT NULL',
            'timeline_json' => 'JSON NULL',
            'docs' => 'TEXT NULL',
            'poll_question' => 'VARCHAR(255) NULL',
            'poll_options_json' => 'JSON NULL',
        ];

        try {
            $existing = Database::pdo()->query("SHOW COLUMNS FROM news_translations")->fetchAll(\PDO::FETCH_COLUMN) ?: [];
            foreach ($cols as $col => $type) {
                if (!in_array($col, $existing, true)) {
                    Database::pdo()->exec("ALTER TABLE news_translations ADD COLUMN {$col} {$type}");
                }
            }
        } catch (\Throwable) {}

        self::$schemaEnsured = true;
    }

    public static function upsert(int $newsId, string $lang, array $data): void
    {
        self::ensureSchema();

        $timelineJson = null;
        if (isset($data['timeline_json'])) {
            $timelineJson = is_array($data['timeline_json']) ? json_encode($data['timeline_json'], JSON_UNESCAPED_UNICODE) : $data['timeline_json'];
        }

        $docsJson = null;
        if (isset($data['docs'])) {
            $docsJson = is_array($data['docs']) ? json_encode($data['docs'], JSON_UNESCAPED_UNICODE) : $data['docs'];
        }

        $pollOptionsJson = null;
        if (isset($data['poll_options_json'])) {
            $pollOptionsJson = is_array($data['poll_options_json']) ? json_encode($data['poll_options_json'], JSON_UNESCAPED_UNICODE) : $data['poll_options_json'];
        }

        $stmt = Database::pdo()->prepare(
            'INSERT INTO news_translations (news_id, lang, title, badge, excerpt, content, hashtags, key_points, event_meta, timeline_json, docs, poll_question, poll_options_json, meta_title, meta_description)
             VALUES (:news_id, :lang, :title, :badge, :excerpt, :content, :hashtags, :key_points, :event_meta, :timeline_json, :docs, :poll_question, :poll_options_json, :meta_title, :meta_description)
             ON DUPLICATE KEY UPDATE title = VALUES(title), badge = VALUES(badge), excerpt = VALUES(excerpt),
                content = VALUES(content), hashtags = VALUES(hashtags), key_points = VALUES(key_points), event_meta = VALUES(event_meta),
                timeline_json = VALUES(timeline_json), docs = VALUES(docs), poll_question = VALUES(poll_question), poll_options_json = VALUES(poll_options_json),
                meta_title = VALUES(meta_title), meta_description = VALUES(meta_description)'
        );
        $stmt->execute([
            ':news_id' => $newsId,
            ':lang' => $lang,
            ':title' => $data['title'] ?? null,
            ':badge' => $data['badge'] ?? null,
            ':excerpt' => $data['excerpt'] ?? null,
            ':content' => $data['content'] ?? null,
            ':hashtags' => \App\Models\News::cleanHashtags($data['hashtags'] ?? null),
            ':key_points' => $data['key_points'] ?? null,
            ':event_meta' => $data['event_meta'] ?? null,
            ':timeline_json' => $timelineJson,
            ':docs' => $docsJson,
            ':poll_question' => $data['poll_question'] ?? null,
            ':poll_options_json' => $pollOptionsJson,
            ':meta_title' => $data['meta_title'] ?? null,
            ':meta_description' => $data['meta_description'] ?? null,
        ]);
    }
}
