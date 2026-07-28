<?php

use App\Core\ContentFields;
use App\Core\Csrf;
use App\Models\Language;

/** @var array $type */
/** @var array $fields */
/** @var array|null $entry */
/** @var array $translations */
/** @var string|null $error */

$isEdit = $entry !== null;
$pageTitle = ($isEdit ? 'Редактирование' : 'Новая запись') . ': ' . $type['name'];
$activeNav = 'content:' . $type['slug'];
require __DIR__ . '/../layout/header.php';

$data = $entry['data'] ?? [];
$action = $isEdit
    ? '/admin/content/' . $type['slug'] . '/' . (int) $entry['id'] . '/edit'
    : '/admin/content/' . $type['slug'] . '/create';
$hasFile = false;
foreach ($fields as $f) { if ($f['field_type'] === 'file') { $hasFile = true; break; } }
$hasTr = (int) $type['has_translations'] === 1;
?>
<div style="margin-bottom:16px;">
    <a href="/admin/content/<?= htmlspecialchars((string) $type['slug'], ENT_QUOTES) ?>" class="btn btn--small">&larr; Все записи: <?= htmlspecialchars((string) $type['name'], ENT_QUOTES) ?></a>
</div>

<?php if ($error): ?><div class="alert alert--error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div><?php endif; ?>

<form method="post" action="<?= $action ?>"<?= $hasFile ? ' enctype="multipart/form-data"' : '' ?>>
    <?= Csrf::field() ?>

    <div class="entry-grid">
        <div class="entry-main">
            <!-- Блок 1: Основная информация -->
            <div class="form-card" style="border:1px solid var(--admin-border,#e2e8f0);border-radius:12px;padding:24px;margin-bottom:24px;background:var(--admin-card-bg,#ffffff);box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <?= \App\Core\AdminUi::cardHeader('1. Основная информация (' . htmlspecialchars((string) $type['name'], ENT_QUOTES) . ')', 'document', 'var(--admin-primary,#2563eb)') ?>

                <div class="form-field" style="margin-bottom:16px;">
                    <label style="font-weight:600;margin-bottom:6px;display:block;">Заголовок / Название <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars((string) ($entry['title'] ?? ''), ENT_QUOTES) ?>" required placeholder="Введите заголовок записи" style="font-size:1.05rem;font-weight:600;">
                </div>

                <?php foreach ($fields as $field): ?>
                    <div style="margin-bottom:16px;">
                        <?= ContentFields::renderInput($field, $data[$field['name']] ?? '', 'f_') ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Блок 2: Переводы на другие языки сайта -->
            <?php if ($hasTr): ?>
                <div class="form-card" style="border:1px solid var(--admin-border,#e2e8f0);border-radius:12px;padding:24px;margin-bottom:24px;background:var(--admin-card-bg,#ffffff);box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <?= \App\Core\AdminUi::cardHeader('2. Переводы и языковые версии', 'globe', 'var(--admin-violet)') ?>

                    <?php foreach (Language::active() as $lang): ?>
                        <?php
                        $code = (string) $lang['code'];
                        if ($code === Language::defaultCode()) { continue; }
                        $tr = $translations[$code] ?? ['title' => '', 'data' => []];
                        ?>
                        <div style="border:1px solid var(--admin-border,#e2e8f0);border-radius:10px;padding:18px;margin-bottom:16px;background:var(--admin-surface-soft,#f8fafc);">
                            <h4 style="margin:0 0 14px;font-size:1.05rem;font-weight:700;color:var(--admin-accent,#2563eb);display:flex;align-items:center;gap:8px;">
                                <?= \App\Core\AdminUi::icon('globe') ?> <?= htmlspecialchars((string) $lang['name'], ENT_QUOTES) ?> (<?= strtoupper($code) ?>)
                            </h4>
                            <div class="form-field" style="margin-bottom:14px;">
                                <label style="font-weight:600;">Заголовок (<?= htmlspecialchars($code, ENT_QUOTES) ?>)</label>
                                <input type="text" name="title_<?= $code ?>" value="<?= htmlspecialchars((string) ($tr['title'] ?? ''), ENT_QUOTES) ?>" placeholder="Перевод заголовка на <?= htmlspecialchars((string) $lang['name'], ENT_QUOTES) ?>">
                            </div>

                            <?php foreach ($fields as $field): ?>
                                <?php if ($field['field_type'] === 'file') { continue; /* файлы не переводим */ } ?>
                                <div style="margin-bottom:14px;">
                                    <?= ContentFields::renderInput($field, $tr['data'][$field['name']] ?? '', 't_' . $code . '_') ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Правая колонка настройки публикации -->
        <aside class="entry-side">
            <div class="form-card" style="border:1px solid var(--admin-border,#e2e8f0);border-radius:12px;padding:20px;margin-bottom:20px;background:var(--admin-card-bg,#ffffff);box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="margin-top:0;font-size:1.1rem;font-weight:700;">Параметры публикации</h3>
                <div class="form-grid">
                    <div class="form-field">
                        <label for="status" style="font-weight:600;">Статус</label>
                        <select id="status" name="status">
                            <option value="draft" <?= ($entry['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Черновик</option>
                            <option value="published" <?= ($entry['status'] ?? '') === 'published' ? 'selected' : '' ?>>Опубликовано</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="slug" style="font-weight:600;">Адрес (slug)</label>
                        <input type="text" id="slug" name="slug" value="<?= htmlspecialchars((string) ($entry['slug'] ?? ''), ENT_QUOTES) ?>" placeholder="оставьте пустым для автогенерации">
                        <span class="form-hint">Уникальный веб-адрес для отображения на сайте.</span>
                    </div>
                </div>

                <div class="form-actions form-actions--sticky" style="margin-top:18px;">
                    <button type="submit" class="btn btn--primary"><?= \App\Core\AdminUi::icon('save') ?>Сохранить</button>
                    <a href="/admin/content/<?= htmlspecialchars((string) $type['slug'], ENT_QUOTES) ?>" class="btn">Отмена</a>
                </div>
            </div>
        </aside>
    </div>
</form>
<?php require __DIR__ . '/../layout/footer.php'; ?>
