<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Language;
use PDO;

final class TranslationGroupHelper
{
    private static bool $schemaEnsured = false;

    public static function ensureSchema(): void
    {
        if (self::$schemaEnsured || !Database::isConnected()) {
            return;
        }

        $tables = ['news', 'pages', 'projects'];
        $pdo = Database::pdo();

        foreach ($tables as $table) {
            try {
                $cols = $pdo->query("SHOW COLUMNS FROM {$table}")->fetchAll(PDO::FETCH_COLUMN) ?: [];
                if (!in_array('lang', $cols, true)) {
                    $pdo->exec("ALTER TABLE {$table} ADD COLUMN lang VARCHAR(8) NOT NULL DEFAULT 'ru'");
                }
                if (!in_array('translation_group_id', $cols, true)) {
                    $pdo->exec("ALTER TABLE {$table} ADD COLUMN translation_group_id INT UNSIGNED NULL");
                    $pdo->exec("UPDATE {$table} SET translation_group_id = id WHERE translation_group_id IS NULL");
                    $pdo->exec("CREATE INDEX idx_{$table}_lang_group ON {$table} (translation_group_id, lang)");
                }

                // Переводим уникальный индекс со slug на (slug, lang)
                $indexes = $pdo->query("SHOW INDEX FROM {$table}")->fetchAll() ?: [];
                $indexNames = array_map(static fn($idx) => (string) ($idx['Key_name'] ?? ''), $indexes);
                if (in_array("uq_{$table}_slug", $indexNames, true)) {
                    $pdo->exec("ALTER TABLE {$table} DROP INDEX uq_{$table}_slug");
                }
                if (!in_array("uq_{$table}_slug_lang", $indexNames, true)) {
                    $pdo->exec("CREATE UNIQUE INDEX uq_{$table}_slug_lang ON {$table} (slug, lang)");
                }
            } catch (\Throwable) {}
        }

        self::$schemaEnsured = true;
    }

    /**
     * Автоматически связывает неручные/разрозненные записи (например "Главная (UZ)" или slug "home-uz")
     * с их родительским материалом на основном языке, если они ещё не привязаны.
     */
    public static function autoLinkStandaloneTranslations(): void
    {
        self::ensureSchema();

        $defaultLang = Language::defaultCode();
        $tables = ['pages', 'news', 'projects'];
        $pdo = Database::pdo();

        foreach ($tables as $table) {
            try {
                // Ищем все записи, где lang != defaultLang и (translation_group_id IS NULL или translation_group_id = id)
                $stmt = $pdo->prepare("SELECT id, title, slug, lang" . ($table === 'pages' ? ', is_home' : '') . " FROM {$table} WHERE lang != :default_lang AND (translation_group_id IS NULL OR translation_group_id = 0 OR translation_group_id = id) AND deleted_at IS NULL");
                $stmt->execute([':default_lang' => $defaultLang]);
                $unlinked = $stmt->fetchAll();

                foreach ($unlinked as $row) {
                    $id = (int) $row['id'];
                    $title = (string) ($row['title'] ?? '');
                    $slug = (string) ($row['slug'] ?? '');
                    $isHome = !empty($row['is_home']);

                    // Очищаем заголовок и slug от языковых суффиксов "(UZ)", "-uz", "_uz", "(EN)", "-en"
                    $cleanTitle = trim(preg_replace('/\s*\((?:uz|ru|en)\)\s*$/i', '', $title));
                    $cleanSlug = trim(preg_replace('/[_\-](?:uz|ru|en)\d*$/i', '', $slug));

                    // Ищем основной элемент на дефолтном языке с похожим заголовком или чистым slug
                    $stmtMatch = $pdo->prepare(
                        "SELECT id FROM {$table} 
                         WHERE lang = :default_lang 
                           AND deleted_at IS NULL 
                           AND (title = :clean_title OR slug = :clean_slug OR title = :orig_title OR slug = :orig_slug)
                         ORDER BY id ASC LIMIT 1"
                    );
                    $stmtMatch->execute([
                        ':default_lang' => $defaultLang,
                        ':clean_title' => $cleanTitle,
                        ':clean_slug' => $cleanSlug,
                        ':orig_title' => $title,
                        ':orig_slug' => $slug,
                    ]);
                    $parentId = $stmtMatch->fetchColumn();

                    // Для страниц — специальный фолбэк для главных страниц ("Главная", "Bosh sahifa", "home", is_home = 1)
                    if (!$parentId && $table === 'pages' && (
                        $isHome ||
                        mb_stripos($title, 'главная') !== false ||
                        mb_stripos($title, 'bosh sahifa') !== false ||
                        mb_stripos($slug, 'home') !== false ||
                        mb_stripos($slug, 'bosh-sahifa') !== false
                    )) {
                        $parentId = $pdo->query("SELECT id FROM pages WHERE (is_home = 1 OR slug = 'home' OR lang = '{$defaultLang}') AND deleted_at IS NULL ORDER BY is_home DESC, id ASC LIMIT 1")->fetchColumn();
                    }

                    if ($parentId !== false && (int) $parentId > 0 && (int) $parentId !== $id) {
                        $parentSlug = (string) $pdo->query("SELECT slug FROM {$table} WHERE id = " . (int) $parentId)->fetchColumn();
                        if ($table !== 'news' && $parentSlug !== '' && $parentSlug !== $slug && !$isHome && $slug !== 'bosh-sahifa' && $slug !== 'home') {
                            $pdo->prepare("UPDATE {$table} SET translation_group_id = :parent_id, slug = :parent_slug WHERE id = :id")
                                ->execute([':parent_id' => (int) $parentId, ':parent_slug' => $parentSlug, ':id' => $id]);
                        } else {
                            $pdo->prepare("UPDATE {$table} SET translation_group_id = :parent_id WHERE id = :id")
                                ->execute([':parent_id' => (int) $parentId, ':id' => $id]);
                        }
                    }
                }
            } catch (\Throwable) {}
        }
    }

    /**
     * @return array<string, array<string, mixed>> переводы с ключом — кодом языка (например, 'ru' => [...], 'uz' => [...])
     */
    public static function getTranslations(string $table, int $recordId): array
    {
        self::ensureSchema();

        $stmt = Database::pdo()->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $recordId]);
        $row = $stmt->fetch();
        if (!$row) {
            return [];
        }

        $groupId = (int) ($row['translation_group_id'] ?? $recordId);
        $stmtGroup = Database::pdo()->prepare(
            "SELECT * FROM {$table} WHERE (translation_group_id = :gid OR id = :gid2) AND deleted_at IS NULL"
        );
        $stmtGroup->execute([':gid' => $groupId, ':gid2' => $groupId]);

        $translations = [];
        foreach ($stmtGroup->fetchAll() as $t) {
            $langCode = (string) ($t['lang'] ?? Language::defaultCode());
            $translations[$langCode] = $t;
        }

        return $translations;
    }

    /**
     * Создаёт новую отдельную запись-перевод для выбранного языка.
     */
    public static function createTranslation(string $table, int $originalId, string $targetLang): int
    {
        self::ensureSchema();

        if (!in_array($table, ['news', 'pages', 'projects'], true)) {
            throw new \InvalidArgumentException("Неподдерживаемая таблица {$table}");
        }

        $pdo = Database::pdo();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $groupStmt = $pdo->prepare(
                "SELECT COALESCE(NULLIF(translation_group_id, 0), id)
                 FROM {$table}
                 WHERE id = :id AND deleted_at IS NULL
                 LIMIT 1"
            );
            $groupStmt->execute([':id' => $originalId]);
            $groupId = $groupStmt->fetchColumn();
            if ($groupId === false) {
                throw new \InvalidArgumentException("Запись #{$originalId} не найдена в таблице {$table}");
            }
            $groupId = (int) $groupId;

            // Все запросы одной языковой группы блокируют один и тот же набор
            // строк в стабильном порядке. Это сериализует создание перевода,
            // даже если запросы пришли с разных языковых версий или старый
            // числовой идентификатор корня больше не существует.
            $groupLock = $pdo->prepare(
                "SELECT id
                 FROM {$table}
                 WHERE id = :id OR translation_group_id = :id2
                 ORDER BY id
                 FOR UPDATE"
            );
            $groupLock->execute([':id' => $groupId, ':id2' => $groupId]);
            if ($groupLock->fetchAll(PDO::FETCH_COLUMN) === []) {
                throw new \RuntimeException("Не удалось заблокировать группу перевода #{$groupId}");
            }

            $stmt = $pdo->prepare(
                "SELECT *
                 FROM {$table}
                 WHERE id = :id AND deleted_at IS NULL
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmt->execute([':id' => $originalId]);
            $orig = $stmt->fetch();
            if (!$orig) {
                throw new \InvalidArgumentException("Запись #{$originalId} не найдена в таблице {$table}");
            }

            if ((int) ($orig['translation_group_id'] ?? 0) !== $groupId) {
                $pdo->prepare("UPDATE {$table} SET translation_group_id = :gid WHERE id = :id")
                    ->execute([':gid' => $groupId, ':id' => $originalId]);
            }

            // После блокировки корня повторно проверяем наличие целевого языка.
            $stmtExist = $pdo->prepare(
                "SELECT id
                 FROM {$table}
                 WHERE translation_group_id = :gid
                   AND lang = :lang
                   AND deleted_at IS NULL
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmtExist->execute([':gid' => $groupId, ':lang' => $targetLang]);
            $existingId = $stmtExist->fetchColumn();
            if ($existingId !== false) {
                if ($ownsTransaction) {
                    $pdo->commit();
                }

                return (int) $existingId;
            }

            $newSlug = Slug::unique(
                (string) ($orig['slug'] ?? 'item') . '-' . $targetLang,
                static function (string $candidate, ?int $_excludeId) use ($pdo, $table, $targetLang): bool {
                    $check = $pdo->prepare(
                        "SELECT COUNT(*) FROM {$table} WHERE slug = :slug AND lang = :lang AND deleted_at IS NULL"
                    );
                    $check->execute([':slug' => $candidate, ':lang' => $targetLang]);
                    return (int) $check->fetchColumn() > 0;
                }
            );
            if ($table === 'news') {
                $ins = $pdo->prepare(
                    "INSERT INTO news (title, slug, excerpt, badge, content, image, video_url, audio_url, audio_title, hashtags, press_release_url, key_points, event_meta, timeline_json, docs, source_note, layout_type, sidebar_layout, focal_x, focal_y, meta_title, meta_description, status, published_at, author_id, lang, translation_group_id, created_at)
                     VALUES (:t, :s, :e, :b, :c, :img, :v, :a, :at, :h, :pr, :kp, :em, :tj, :dc, :sn, :lt, :sl, :fx, :fy, :mt, :md, 'draft', NOW(), :auth, :lang, :gid, NOW())"
                );
                $ins->execute([
                    ':t' => ($orig['title'] ?? '') . ' (' . strtoupper($targetLang) . ')',
                    ':s' => $newSlug,
                    ':e' => $orig['excerpt'] ?? null,
                    ':b' => $orig['badge'] ?? null,
                    ':c' => $orig['content'] ?? null,
                    ':img' => $orig['image'] ?? null,
                    ':v' => $orig['video_url'] ?? null,
                    ':a' => $orig['audio_url'] ?? null,
                    ':at' => $orig['audio_title'] ?? null,
                    ':h' => $orig['hashtags'] ?? null,
                    ':pr' => $orig['press_release_url'] ?? null,
                    ':kp' => $orig['key_points'] ?? null,
                    ':em' => $orig['event_meta'] ?? null,
                    ':tj' => $orig['timeline_json'] ?? null,
                    ':dc' => $orig['docs'] ?? null,
                    ':sn' => $orig['source_note'] ?? null,
                    ':lt' => $orig['layout_type'] ?? 'standard',
                    ':sl' => $orig['sidebar_layout'] ?? 'right_sidebar',
                    ':fx' => $orig['focal_x'] ?? null,
                    ':fy' => $orig['focal_y'] ?? null,
                    ':mt' => $orig['meta_title'] ?? null,
                    ':md' => $orig['meta_description'] ?? null,
                    ':auth' => $orig['author_id'] ?? null,
                    ':lang' => $targetLang,
                    ':gid' => $groupId,
                ]);
            } elseif ($table === 'pages') {
                $ins = $pdo->prepare(
                    "INSERT INTO pages (title, slug, meta_title, meta_description, `lead`, status, is_home, layout_type, hide_chrome, transparent_header, lang, translation_group_id, parent_id, created_at)
                     VALUES (:t, :s, :mt, :md, :l, 'draft', 0, :lt, :hc, :th, :lang, :gid, :parent_id, NOW())"
                );
                $ins->execute([
                    ':t' => ($orig['title'] ?? '') . ' (' . strtoupper($targetLang) . ')',
                    ':s' => $newSlug,
                    ':mt' => $orig['meta_title'] ?? null,
                    ':md' => $orig['meta_description'] ?? null,
                    ':l' => $orig['lead'] ?? null,
                    ':lt' => $orig['layout_type'] ?? 'no_sidebar',
                    ':hc' => $orig['hide_chrome'] ?? 0,
                    ':th' => $orig['transparent_header'] ?? 0,
                    ':lang' => $targetLang,
                    ':gid' => $groupId,
                    ':parent_id' => !empty($orig['parent_id']) ? (int) $orig['parent_id'] : null,
                ]);
            } else {
                $ins = $pdo->prepare(
                    "INSERT INTO projects (title, slug, description, cover_image, status, is_featured, sort_order, lang, translation_group_id, created_at)
                     VALUES (:t, :s, :d, :ci, 'draft', :if, :so, :lang, :gid, NOW())"
                );
                $ins->execute([
                    ':t' => ($orig['title'] ?? '') . ' (' . strtoupper($targetLang) . ')',
                    ':s' => $newSlug,
                    ':d' => $orig['description'] ?? null,
                    ':ci' => $orig['cover_image'] ?? null,
                    ':if' => $orig['is_featured'] ?? 0,
                    ':so' => $orig['sort_order'] ?? 0,
                    ':lang' => $targetLang,
                    ':gid' => $groupId,
                ]);
            }

            $newId = (int) $pdo->lastInsertId();
            if ($table === 'pages' && $newId > 0) {
                self::copyBlocksForTranslation($pdo, $originalId, $newId, $targetLang);
            }

            if ($ownsTransaction) {
                $pdo->commit();
            }

            return $newId;
        } catch (\Throwable $e) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private static function copyBlocksForTranslation(
        PDO $pdo,
        int $origPageId,
        int $newPageId,
        string $targetLang
    ): void
    {
        $pageLangStmt = $pdo->prepare('SELECT lang FROM pages WHERE id = :id LIMIT 1');
        $pageLangStmt->execute([':id' => $origPageId]);
        $originalLang = (string) ($pageLangStmt->fetchColumn() ?: Language::defaultCode());

        // В старых данных RU и UZ могли лежать в одном page_id. Если там
        // уже есть стек целевого языка, переносим именно его; иначе берём
        // стек исходной страницы и назначаем копии новый язык.
        $sourceLang = null;
        $countByLang = $pdo->prepare(
            'SELECT COUNT(*) FROM blocks WHERE page_id = :page_id AND lang = :lang'
        );
        foreach (array_values(array_unique([$targetLang, $originalLang, ''])) as $candidateLang) {
            $countByLang->execute([
                ':page_id' => $origPageId,
                ':lang' => $candidateLang,
            ]);
            if ((int) $countByLang->fetchColumn() > 0) {
                $sourceLang = $candidateLang;
                break;
            }
        }
        if ($sourceLang === null) {
            return;
        }

        $stmt = $pdo->prepare(
            'SELECT *
             FROM blocks
             WHERE page_id = :pid
               AND lang = :source_lang
               AND parent_block_id IS NULL
             ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([':pid' => $origPageId, ':source_lang' => $sourceLang]);
        $topBlocks = $stmt->fetchAll();

        foreach ($topBlocks as $b) {
            $ins = $pdo->prepare(
                "INSERT INTO blocks (page_id, parent_block_id, column_index, lang, type, title, data, custom_css, sort_order, is_active, created_at)
                 VALUES (:pid, NULL, :ci, :lang, :type, :title, :data, :css, :so, :act, NOW())"
            );
            $ins->execute([
                ':pid' => $newPageId,
                ':ci' => $b['column_index'] ?? 0,
                ':lang' => $targetLang,
                ':type' => $b['type'],
                ':title' => $b['title'],
                ':data' => $b['data'],
                ':css' => $b['custom_css'] ?? null,
                ':so' => $b['sort_order'],
                ':act' => $b['is_active'] ?? 1,
            ]);
            $newParentId = (int) $pdo->lastInsertId();

            $stmtKids = $pdo->prepare(
                'SELECT *
                 FROM blocks
                 WHERE page_id = :page_id
                   AND parent_block_id = :parent_block_id
                   AND lang = :source_lang
                 ORDER BY sort_order ASC, id ASC'
            );
            $stmtKids->execute([
                ':page_id' => $origPageId,
                ':parent_block_id' => $b['id'],
                ':source_lang' => $sourceLang,
            ]);
            $kids = $stmtKids->fetchAll();

            foreach ($kids as $k) {
                $insKid = $pdo->prepare(
                    "INSERT INTO blocks (page_id, parent_block_id, column_index, lang, type, title, data, custom_css, sort_order, is_active, created_at)
                     VALUES (:pid, :pbid, :ci, :lang, :type, :title, :data, :css, :so, :act, NOW())"
                );
                $insKid->execute([
                    ':pid' => $newPageId,
                    ':pbid' => $newParentId,
                    ':ci' => $k['column_index'] ?? 0,
                    ':lang' => $targetLang,
                    ':type' => $k['type'],
                    ':title' => $k['title'],
                    ':data' => $k['data'],
                    ':css' => $k['custom_css'] ?? null,
                    ':so' => $k['sort_order'],
                    ':act' => $k['is_active'] ?? 1,
                ]);
            }
        }
    }

    /**
     * Рендерит боковой мета-бокс перевода записи.
     */
    public static function renderSidebarMetaBox(string $module, array $currentRecord): string
    {
        self::ensureSchema();

        $recordId = (int) ($currentRecord['id'] ?? 0);
        $currentLang = (string) ($currentRecord['lang'] ?? Language::defaultCode());
        $tableMap = ['news' => 'news', 'pages' => 'pages', 'projects' => 'projects'];
        $table = $tableMap[$module] ?? 'news';

        $translations = $recordId > 0 ? self::getTranslations($table, $recordId) : [];
        $languages = Language::active();
        $defaultCode = Language::defaultCode();

        $currentLangName = strtoupper($currentLang);
        foreach ($languages as $l) {
            if ((string) ($l['code'] ?? '') === $currentLang) {
                $currentLangName = (string) ($l['name'] ?? strtoupper($currentLang));
                break;
            }
        }

        ob_start();
        ?>
        <div class="form-card multilang-group-card u-inline-c18e1e5580">
            <input type="hidden" name="lang" value="<?= htmlspecialchars($currentLang, ENT_QUOTES) ?>">
            <div class="u-inline-342ce139a0">
                <h3 class="u-inline-7defd547d2">
                    <?= Icon::render('world', 19, 'u-inline-aa5e5b184d') ?>
                    Язык и переводы
                </h3>
            </div>

            <!-- Верхняя выделенная плашка с текущим редактируемым языком -->
            <div class="u-inline-8b8ad782a0">
                <div class="u-inline-e3f61041ef">
                    <span class="u-inline-7c3cfec592">Редактируется:</span>
                    <strong class="u-inline-0d175b7d93"><?= htmlspecialchars($currentLang, ENT_QUOTES) ?></strong>
                    <span class="u-inline-72088eb607">(<?= htmlspecialchars($currentLangName, ENT_QUOTES) ?>)</span>
                </div>
                <?php if ($currentLang === $defaultCode): ?>
                    <span class="u-inline-3159a647e8">Основной</span>
                <?php else: ?>
                    <span class="u-inline-4488ac4e31">Перевод</span>
                <?php endif; ?>
            </div>

            <div class="multilang-translation-list u-inline-f67b86e0fb">
                <?php foreach ($languages as $lang): ?>
                    <?php
                    $code = (string) $lang['code'];
                    $tRecord = $translations[$code] ?? null;
                    $isSelf = ($code === $currentLang);
                    ?>
                    <?php if ($isSelf): ?>
                        <div class="u-inline-9da576af61">
                            <div class="u-inline-e3f61041ef">
                                <span class="u-inline-26c741d857"></span>
                                <span class="u-inline-9ec672a78d"><?= htmlspecialchars($lang['name'], ENT_QUOTES) ?></span>
                                <?php if ($code === $defaultCode): ?>
                                    <span class="u-inline-c23eb2f888">(оригинал)</span>
                                <?php endif; ?>
                            </div>
                            <span class="u-inline-419b2840c7">
                                <?= Icon::render('check', 12, 'translation-current-icon', 3) ?>
                                Текущий пост
                            </span>
                        </div>
                    <?php else: ?>
                        <div class="u-inline-0fc66a2344">
                            <div class="u-inline-e3f61041ef">
                                <span class="u-inline-3b3157a26b"><?= htmlspecialchars($lang['name'], ENT_QUOTES) ?></span>
                                <?php if ($code === $defaultCode): ?>
                                    <span class="u-inline-71577fef0b">(оригинал)</span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php if ($tRecord !== null): ?>
                                    <a href="/admin/<?= $module ?>/<?= (int) $tRecord['id'] ?>/edit" class="btn btn--small btn--secondary u-inline-30b9f76a48">
                                        <?= Icon::render('edit', 14) ?> Редактировать (#<?= (int) $tRecord['id'] ?>)
                                    </a>
                                <?php elseif ($recordId > 0): ?>
                                    <button type="submit"
                                            name="target_lang"
                                            value="<?= htmlspecialchars($code, ENT_QUOTES) ?>"
                                            formaction="/admin/<?= rawurlencode($module) ?>/<?= $recordId ?>/create-translation"
                                            formmethod="post"
                                            formnovalidate
                                            class="btn btn--small btn--primary u-inline-64c12efe40">
                                        <?= Icon::render('plus', 14) ?> Создать перевод
                                    </button>
                                <?php else: ?>
                                    <span class="u-inline-d716a45428">Сначала сохраните запись</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }
}
