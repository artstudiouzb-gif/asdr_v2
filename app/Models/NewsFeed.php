<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Публичное чтение новостей: лента с рубриками и счётом, новость по слагу,
 * соседние, похожие и популярные. Все выборки учитывают язык: своя запись
 * языка важнее перевода из legacy-таблицы.
 *
 * Выделено из News: модель отвечает за запись и админские операции, а
 * выборки для посетителей со своей памятью в пределах запроса живут здесь.
 * News сбрасывает эту память при любом изменении новостей (forget()).
 */
final class NewsFeed
{
    /** @var array<string, array<int, array<string, mixed>>> */
    private static array $publishedRequestCache = [];

    /**
     * Строит общую часть публичной выборки для одного языка.
     *
     * Независимая запись нужного языка всегда имеет приоритет. Перевод из
     * legacy-таблицы используется только пока для этой группы вообще не
     * создана самостоятельная запись языка назначения. Поэтому черновик новой
     * архитектуры нельзя случайно обойти старым опубликованным переводом.
     *
     * @return array{join:string, where:string, params:array<string, string>, translation_alias:?string}
     */
    private static function publicLanguageParts(string $lang, string $alias, string $prefix): array
    {
        $default = Language::defaultCode();
        if ($lang === $default) {
            return [
                'join' => '',
                'where' => "{$alias}.lang = :{$prefix}_exact_lang",
                'params' => ["{$prefix}_exact_lang" => $lang],
                'translation_alias' => null,
            ];
        }

        $translationAlias = $prefix . '_translation';
        $shadowAlias = $prefix . '_shadow';

        return [
            // «Есть ли у группы собственная запись нужного языка» спрашивается
            // один раз производной таблицей, а не коррелированным NOT EXISTS
            // на каждую строку. Прежний вид сравнивал выражение
            // COALESCE(NULLIF(tgid,0), id) с таким же выражением, поэтому под
            // него не подходил никакой индекс: EXPLAIN показывал DEPENDENT
            // SUBQUERY с type=ALL, то есть полный проход по news для каждой
            // строки-кандидата. Замерено на 409 новостях: страница /uz/news
            // отвечала 155 мс против 12 мс у русской, и рост был квадратичным.
            'join' => " LEFT JOIN news_translations {$translationAlias}
                        ON {$translationAlias}.news_id = {$alias}.id
                       AND {$translationAlias}.lang = :{$prefix}_legacy_lang
                        LEFT JOIN (
                            SELECT DISTINCT COALESCE(NULLIF(translation_group_id, 0), id) AS group_key
                            FROM news
                            WHERE deleted_at IS NULL AND lang = :{$prefix}_shadow_lang
                        ) {$shadowAlias}
                        ON {$shadowAlias}.group_key
                           = COALESCE(NULLIF({$alias}.translation_group_id, 0), {$alias}.id)",
            'where' => "(
                {$alias}.lang = :{$prefix}_exact_lang
                OR (
                    {$alias}.lang = :{$prefix}_default_lang
                    AND {$translationAlias}.id IS NOT NULL
                    AND (
                        TRIM(COALESCE({$translationAlias}.title, '')) <> ''
                        OR TRIM(COALESCE({$translationAlias}.content, '')) <> ''
                    )
                    AND {$shadowAlias}.group_key IS NULL
                )
            )",
            'params' => [
                "{$prefix}_legacy_lang" => $lang,
                "{$prefix}_exact_lang" => $lang,
                "{$prefix}_default_lang" => $default,
                "{$prefix}_shadow_lang" => $lang,
            ],
            'translation_alias' => $translationAlias,
        ];
    }

    /**
     * Накладывает legacy-перевод только на резервные базовые строки. Поля
     * самостоятельной языковой записи никогда не заменяются legacy-данными.
     *
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
        $translations = NewsTranslation::forNewsIds($fallbackIds, $lang);

        foreach ($rows as &$row) {
            $id = (int) $row['id'];
            if ((string) ($row['lang'] ?? '') !== $lang && isset($translations[$id])) {
                $row = News::applyTranslation($row, $translations[$id]);
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * Опубликованные новости, локализованные под указанный язык.
     *
     * Фильтр по категории — сравнение идентификатора, а не текста: рубрика
     * одна на все языковые версии новости, переводится только её название.
     * Прежний фильтр по бейджу сравнивал строки и требовал разбора перевода
     * прямо в SQL.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function published(int $limit = 20, int $offset = 0, ?string $lang = null, ?int $categoryId = null): array
    {
        $lang = $lang ?? Language::defaultCode();
        $cacheKey = implode('|', [$limit, $offset, $lang, $categoryId ?? 0]);
        if (isset(self::$publishedRequestCache[$cacheKey])) {
            return self::$publishedRequestCache[$cacheKey];
        }

        $parts = self::publicLanguageParts($lang, 'n', 'published');
        $params = $parts['params'];
        $where = "n.status = 'published' AND n.published_at <= NOW() AND n.deleted_at IS NULL
                  AND {$parts['where']}";
        if ($categoryId !== null && $categoryId > 0) {
            $where .= ' AND n.category_id = :category';
            $params['category'] = $categoryId;
        }
        $stmt = Database::pdo()->prepare(
            "SELECT n.*,
                    (SELECT ni.path FROM news_images ni WHERE ni.news_id = n.id
                     ORDER BY ni.sort_order ASC, ni.id ASC LIMIT 1) AS first_gallery_image
             FROM news n{$parts['join']}
             WHERE {$where}
             ORDER BY n.published_at DESC, n.id DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return self::$publishedRequestCache[$cacheKey] = self::localizePublicRows($stmt->fetchAll(), $lang);
    }

    /** Количество опубликованных новостей одного языка, опционально по категории. */
    public static function publishedCount(?int $categoryId = null, ?string $lang = null): int
    {
        $lang = $lang ?? Language::defaultCode();
        $parts = self::publicLanguageParts($lang, 'n', 'counted');
        $params = $parts['params'];
        $where = "n.status = 'published' AND n.published_at <= NOW() AND n.deleted_at IS NULL
                  AND {$parts['where']}";
        if ($categoryId !== null && $categoryId > 0) {
            $where .= ' AND n.category_id = :category';
            $params[':category'] = $categoryId;
        }

        $stmt = Database::pdo()->prepare(
            "SELECT COUNT(*) FROM news n{$parts['join']} WHERE {$where}"
        );
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Категории, у которых есть опубликованные новости на этом языке —
     * рубрикатор страницы «Новости».
     *
     * Считается по языковой выборке, а не по всей таблице: иначе на узбекской
     * версии предлагалась бы рубрика, в которой на этом языке нет ни одной
     * новости, и фильтр приводил бы к пустой ленте.
     *
     * @return list<array<string, mixed>>
     */
    public static function publishedCategories(?string $lang = null): array
    {
        $lang = $lang ?? Language::defaultCode();
        $parts = self::publicLanguageParts($lang, 'n', 'cats');
        $stmt = Database::pdo()->prepare(
            "SELECT DISTINCT n.category_id
             FROM news n{$parts['join']}
             WHERE n.status = 'published' AND n.published_at <= NOW() AND n.deleted_at IS NULL
               AND n.category_id IS NOT NULL
               AND {$parts['where']}"
        );
        $stmt->execute($parts['params']);
        $ids = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
        if ($ids === []) {
            return [];
        }

        return array_values(array_filter(
            NewsCategory::all($lang, true),
            static fn (array $category): bool => in_array((int) $category['id'], $ids, true)
        ));
    }

    /**
     * Ищет опубликованную новость по слагу и локализует под язык.
     *
     * @return array<string, mixed>|null
     */
    public static function findPublishedBySlug(string $slug, ?string $lang = null): ?array
    {
        $lang = $lang ?? Language::defaultCode();

        // 1. Самостоятельная опубликованная запись нужного языка.
        $stmtExact = Database::pdo()->prepare(
            "SELECT * FROM news WHERE slug = :slug AND lang = :lang AND status = 'published' AND published_at <= NOW() AND deleted_at IS NULL LIMIT 1"
        );
        $stmtExact->execute([':slug' => $slug, ':lang' => $lang]);
        $exactRow = $stmtExact->fetch();
        if ($exactRow) {
            return $exactRow;
        }

        // 2. Находим группу по slug другой языковой версии.
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM news WHERE slug = :slug AND status = 'published' AND published_at <= NOW() AND deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $groupId = (int) ($row['translation_group_id'] ?: $row['id']);
        $stmtTrans = Database::pdo()->prepare(
            "SELECT * FROM news
             WHERE COALESCE(NULLIF(translation_group_id, 0), id) = :group_id
               AND lang = :lang
               AND status = 'published' AND published_at <= NOW() AND deleted_at IS NULL
             LIMIT 1"
        );
        $stmtTrans->execute([':group_id' => $groupId, ':lang' => $lang]);
        $transRow = $stmtTrans->fetch();
        if ($transRow) {
            return $transRow;
        }

        // Самостоятельный черновик подавляет старый перевод: иначе его можно
        // было бы обойти публичным legacy-контентом.
        $stmtIndependent = Database::pdo()->prepare(
            "SELECT COUNT(*) FROM news
             WHERE COALESCE(NULLIF(translation_group_id, 0), id) = :group_id
               AND lang = :lang AND deleted_at IS NULL"
        );
        $stmtIndependent->execute([':group_id' => $groupId, ':lang' => $lang]);
        if ((int) $stmtIndependent->fetchColumn() > 0) {
            return $row;
        }

        // Legacy-перевод допустим только у опубликованной базовой записи.
        $default = Language::defaultCode();
        $stmtBase = Database::pdo()->prepare(
            "SELECT * FROM news
             WHERE COALESCE(NULLIF(translation_group_id, 0), id) = :group_id
               AND lang = :default_lang
               AND status = 'published' AND published_at <= NOW() AND deleted_at IS NULL
             LIMIT 1"
        );
        $stmtBase->execute([':group_id' => $groupId, ':default_lang' => $default]);
        $base = $stmtBase->fetch();
        if ($base && $lang !== $default) {
            $legacy = NewsTranslation::find((int) $base['id'], $lang);
            if ($legacy !== null && (
                trim((string) ($legacy['title'] ?? '')) !== ''
                || trim((string) ($legacy['content'] ?? '')) !== ''
            )) {
                return News::applyTranslation($base, $legacy);
            }
        }

        // Контроллер покажет штатное уведомление об отсутствующем переводе.
        return $base ?: $row;
    }

    /**
     * Соседние опубликованные новости по дате публикации (для «предыдущая/следующая»).
     *
     * @param array<string, mixed> $news
     * @return array{prev: array<string, mixed>|null, next: array<string, mixed>|null}
     */
    public static function adjacent(array $news, ?string $lang = null): array
    {
        $lang = $lang ?? Language::defaultCode();
        $pub = (string) ($news['published_at'] ?? '');
        $id = (int) $news['id'];
        $pick = static function (string $op, string $order, string $prefix) use ($pub, $id, $lang): ?array {
            $parts = self::publicLanguageParts($lang, 'n', $prefix);
            $params = $parts['params'];
            $params[':adjacent_published'] = $pub;
            $params[':adjacent_published_equal'] = $pub;
            $params[':adjacent_id'] = $id;
            $stmt = Database::pdo()->prepare(
                "SELECT n.* FROM news n{$parts['join']}
                 WHERE n.status = 'published' AND n.published_at <= NOW() AND n.deleted_at IS NULL
                   AND {$parts['where']}
                   AND (n.published_at {$op} :adjacent_published
                        OR (n.published_at = :adjacent_published_equal AND n.id {$op} :adjacent_id))
                 ORDER BY n.published_at {$order}, n.id {$order}
                 LIMIT 1"
            );
            $stmt->execute($params);
            $row = $stmt->fetch();
            if (!$row) {
                return null;
            }

            return self::localizePublicRows([$row], $lang)[0] ?? null;
        };

        return [
            'prev' => $pick('<', 'DESC', 'adjacent_prev'),
            'next' => $pick('>', 'ASC', 'adjacent_next'),
        ];
    }

    /**
     * Похожие новости: последние опубликованные, исключая текущую.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function related(int $excludeId, int $limit = 4, ?string $lang = null): array
    {
        $lang = $lang ?? Language::defaultCode();
        $limit = max(1, min(12, $limit));
        $parts = self::publicLanguageParts($lang, 'n', 'related');
        $params = $parts['params'];
        $params[':excluded_id'] = $excludeId;
        $stmt = Database::pdo()->prepare(
            "SELECT n.*,
                    (SELECT ni.path FROM news_images ni WHERE ni.news_id = n.id
                     ORDER BY ni.sort_order ASC, ni.id ASC LIMIT 1) AS first_gallery_image
             FROM news n{$parts['join']}
             WHERE n.status = 'published' AND n.published_at <= NOW() AND n.deleted_at IS NULL
               AND {$parts['where']} AND n.id <> :excluded_id
             ORDER BY n.published_at DESC, n.id DESC LIMIT {$limit}"
        );
        $stmt->execute($params);

        return self::localizePublicRows($stmt->fetchAll() ?: [], $lang);
    }

    /**
     * Самые читаемые новости за период (days = 0 — за всё время).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function mostViewed(int $days = 30, int $limit = 5, ?string $lang = null): array
    {
        $lang = $lang ?? Language::defaultCode();
        $limit = max(1, min(20, $limit));
        try {
            $pdo = Database::pdo();
            $parts = self::publicLanguageParts($lang, 'n', 'viewed');
            if ($days <= 0) {
                $stmt = $pdo->prepare(
                    "SELECT n.id, n.title, n.slug, n.image, n.lang, n.translation_group_id,
                            n.views AS period_views, n.published_at
                     FROM news n{$parts['join']}
                     WHERE n.status = 'published' AND n.published_at <= NOW() AND n.deleted_at IS NULL
                       AND {$parts['where']}
                     ORDER BY n.views DESC, n.id DESC LIMIT {$limit}"
                );
                $stmt->execute($parts['params']);
            } else {
                $stmt = $pdo->prepare(
                    "SELECT n.id, n.title, n.slug, n.image, n.lang, n.translation_group_id,
                            n.published_at, SUM(nv.views_count) AS period_views
                     FROM news_views nv
                     JOIN news n ON nv.news_id = n.id
                     {$parts['join']}
                     WHERE nv.view_date >= DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY)
                       AND n.status = 'published' AND n.published_at <= NOW() AND n.deleted_at IS NULL
                       AND {$parts['where']}
                     GROUP BY n.id, n.title, n.slug, n.image, n.lang, n.translation_group_id, n.published_at
                     ORDER BY period_views DESC, n.id DESC LIMIT {$limit}"
                );
                foreach ($parts['params'] as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->bindValue(':days', $days, \PDO::PARAM_INT);
                $stmt->execute();
            }
            $rows = $stmt->fetchAll() ?: [];
            return self::localizePublicRows($rows, $lang);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Сбрасывает память ленты в пределах запроса: новости изменились. */
    public static function forget(): void
    {
        self::$publishedRequestCache = [];
    }
}
