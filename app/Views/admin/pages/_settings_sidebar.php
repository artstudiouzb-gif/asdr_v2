<?php

/** @var array|null $page */
/** @var array $languages */
/** @var bool $isEdit */
/** @var string $blockLang */
?>
<div class="form-card page-settings-sidebar" style="border:1px solid var(--admin-border,#e2e8f0);border-radius:12px;padding:20px;margin-bottom:20px;background:var(--admin-card-bg,#ffffff);box-shadow:0 1px 3px rgba(0,0,0,0.05);">
    <h3 style="margin-top:0;font-size:1.1rem;font-weight:700;">Параметры страницы</h3>

    <div class="form-grid">
        <div class="form-field">
            <label for="status" style="font-weight:600;">Статус</label>
            <select id="status" name="status">
                <option value="draft" <?= ($page['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Черновик</option>
                <option value="published" <?= ($page['status'] ?? '') === 'published' ? 'selected' : '' ?>>Опубликовано</option>
            </select>
        </div>

        <div class="form-field">
            <label for="slug" style="font-weight:600;">ЧПУ (slug)</label>
            <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($page['slug'] ?? '', ENT_QUOTES) ?>" placeholder="оставьте пустым для автогенерации">
            <span class="form-hint">Адрес страницы на сайте.</span>
        </div>

        <div class="form-field">
            <label for="layout_type" style="font-weight:600;">Макет страницы</label>
            <select id="layout_type" name="layout_type">
                <option value="no_sidebar" <?= ($page['layout_type'] ?? 'no_sidebar') === 'no_sidebar' ? 'selected' : '' ?>>Без сайдбара</option>
                <option value="left_sidebar" <?= ($page['layout_type'] ?? '') === 'left_sidebar' ? 'selected' : '' ?>>Левый сайдбар</option>
                <option value="right_sidebar" <?= ($page['layout_type'] ?? '') === 'right_sidebar' ? 'selected' : '' ?>>Правый сайдбар</option>
            </select>
            <span class="form-hint">Содержимое сайдбара из раздела «Виджеты».</span>
        </div>

        <div class="page-settings-sidebar__options" style="display:flex;flex-direction:column;gap:10px;margin-top:6px;">
            <div class="form-field form-field--checkbox">
                <input type="checkbox" id="is_home" name="is_home" value="1" <?= (\App\Models\Page::isHomePage($page ?? [])) ? 'checked' : '' ?>>
                <label for="is_home" style="font-size:0.92rem;font-weight:600;">Сделать главной страницей</label>
            </div>

            <div class="form-field form-field--checkbox">
                <input type="checkbox" id="hide_chrome" name="hide_chrome" value="1" <?= !empty($page['hide_chrome']) ? 'checked' : '' ?>>
                <label for="hide_chrome" style="font-size:0.92rem;font-weight:600;">Скрыть шапку и футер</label>
            </div>

            <div class="form-field form-field--checkbox">
                <input type="checkbox" id="transparent_header" name="transparent_header" value="1" <?= !empty($page['transparent_header']) ? 'checked' : '' ?>>
                <label for="transparent_header" style="font-size:0.92rem;font-weight:600;">Прозрачная шапка</label>
            </div>
            <p class="form-hint" style="margin:0;">Для прозрачной шапки нужен hero первым блоком.</p>
        </div>
    </div>
</div>
