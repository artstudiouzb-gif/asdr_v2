<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Цели пунктов меню: опубликованная страница или проект нужного языка по
 * адресу пункта, пакетная выборка для всего меню и адрес, которым пункт
 * хранится (`projects/<slug>` у проекта).
 *
 * Выделено из Page: модель отвечает за саму страницу, а разрешение адресов
 * меню со своей памятью в пределах запроса живёт здесь. Page сбрасывает эту
 * память при создании, правке и удалении страницы (forget()).
 */
final class PageMenuTarget
{
    /**
     * Память ответов в пределах запроса: адрес пункта → цель (null тоже
     * запоминается). Шапка спрашивает одно и то же дважды — сперва разрешая
     * пункты дерева, потом собирая ссылку каждого из них, — и на меню из
     * 46 пунктов это давало 36 одинаковых запросов на каждой странице.
     *
     * @var array<string, array<string,mixed>|null>
     */
    private static array $memo = [];

    /**
     * Находит именно опубликованную запись страницы нужного языка для меню.
     *
     * В отличие от findBySlug() этот метод не возвращает локализованный
     * контент основной записи как fallback: меню языка должно вести только
     * на самостоятельную опубликованную версию этого языка.
     *
     * Цель пункта меню по адресу из формы.
     *
     * Проект адресуется как `projects/<slug>` — тем же значением, каким пункт
     * меню и хранится, поэтому публичная ссылка собирается без особых случаев.
     *
     * @return array<string, mixed>|null
     */
    public static function find(string $slug, string $lang): ?array
    {
        $memoKey = $lang . '|' . ltrim(trim($slug), '/');
        if (array_key_exists($memoKey, self::$memo)) {
            return self::$memo[$memoKey];
        }

        return self::$memo[$memoKey] = self::resolve($slug, $lang);
    }

    /** Сбрасывает память целей меню: адреса страниц изменились. */
    public static function forget(): void
    {
        self::$memo = [];
    }

    /** @return array<string,mixed>|null */
    private static function resolve(string $slug, string $lang): ?array
    {
        $slug = ltrim(trim($slug), '/');
        $entityType = 'page';
        if (str_starts_with($slug, 'projects/')) {
            $entityType = 'project';
            $slug = substr($slug, strlen('projects/'));
        }
        if ($slug === '' || !Language::isActive($lang)) {
            return null;
        }

        $pdo = Database::pdo();
        $stmtExact = $pdo->prepare(
            "SELECT * FROM pages
             WHERE slug = :slug AND lang = :lang AND entity_type = :entity_type
               AND status = 'published' AND deleted_at IS NULL
             LIMIT 1"
        );
        $stmtExact->execute([':slug' => $slug, ':lang' => $lang, ':entity_type' => $entityType]);
        $exact = $stmtExact->fetch();
        if ($exact) {
            return $exact;
        }

        $stmtSource = $pdo->prepare(
            "SELECT * FROM pages
             WHERE slug = :slug AND entity_type = :entity_type
               AND status = 'published' AND deleted_at IS NULL
             ORDER BY (lang = :default_lang) DESC, id ASC
             LIMIT 1"
        );
        $stmtSource->execute([
            ':slug' => $slug,
            ':entity_type' => $entityType,
            ':default_lang' => Language::defaultCode(),
        ]);
        $source = $stmtSource->fetch();
        if (!$source) {
            return null;
        }

        $groupId = (int) ($source['translation_group_id'] ?: $source['id']);
        $stmtTarget = $pdo->prepare(
            "SELECT * FROM pages
             WHERE (id = :group_id OR translation_group_id = :translation_group_id)
               AND lang = :lang AND entity_type = :entity_type
               AND status = 'published' AND deleted_at IS NULL
             ORDER BY id ASC
             LIMIT 1"
        );
        $stmtTarget->execute([
            ':group_id' => $groupId,
            ':translation_group_id' => $groupId,
            ':lang' => $lang,
            ':entity_type' => $entityType,
        ]);
        $target = $stmtTarget->fetch();

        return $target ?: null;
    }

    /**
     * Цели пунктов меню сразу для набора адресов: значение пункта → запись.
     *
     * Шапка разрешала каждый пункт отдельным запросом, и меню из 46 пунктов
     * давало 36 обращений к базе на **каждой** странице сайта. Здесь обычный
     * случай — точное совпадание языка — берётся одним запросом на тип
     * записи. Редкий остаток (страница есть только на другом языке) дорешает
     * поштучный find(): своя копия его логики про группы
     * переводов разъехалась бы с ним при первой правке.
     *
     * @param array<array-key, mixed> $urlValues
     * @return array<string, array<string,mixed>> адрес пункта → строка страницы
     */
    public static function findMany(array $urlValues, string $lang): array
    {
        if (!Language::isActive($lang)) {
            return [];
        }

        $wanted = [];   // тип записи → slug → список исходных значений
        foreach ($urlValues as $value) {
            $value = (string) $value;
            $slug = ltrim(trim($value), '/');
            $entityType = 'page';
            if (str_starts_with($slug, 'projects/')) {
                $entityType = 'project';
                $slug = substr($slug, strlen('projects/'));
            }
            if ($slug !== '') {
                $wanted[$entityType][$slug][] = $value;
            }
        }
        if ($wanted === []) {
            return [];
        }

        $found = [];
        $pdo = Database::pdo();
        foreach ($wanted as $entityType => $bySlug) {
            foreach (array_chunk(array_keys($bySlug), 500) as $chunk) {
                $marks = implode(', ', array_fill(0, count($chunk), '?'));
                $stmt = $pdo->prepare(
                    "SELECT * FROM pages
                     WHERE slug IN ({$marks}) AND lang = ? AND entity_type = ?
                       AND status = 'published' AND deleted_at IS NULL"
                );
                $stmt->execute(array_merge($chunk, [$lang, $entityType]));
                foreach ($stmt->fetchAll() as $row) {
                    foreach ($bySlug[(string) $row['slug']] ?? [] as $value) {
                        $found[$value] = $row;
                    }
                }
            }
        }

        foreach ($urlValues as $value) {
            $value = (string) $value;
            if (!array_key_exists($value, $found)) {
                $target = self::find($value, $lang);
                if ($target !== null) {
                    $found[$value] = $target;
                }
                continue;
            }
            // Найденное кладём в общую память: ту же цель спросит resolveUrl,
            // когда будет собирать ссылку этого пункта.
            self::$memo[$lang . '|' . ltrim(trim($value), '/')] = $found[$value];
        }

        return $found;
    }

    /**
     * Значение пункта меню для найденной цели: у проекта адрес с префиксом.
     * @param array<string, mixed> $target
     */
    public static function value(array $target): string
    {
        $slug = (string) ($target['slug'] ?? '');

        return (string) ($target['entity_type'] ?? 'page') === 'project'
            ? 'projects/' . $slug
            : $slug;
    }
}
