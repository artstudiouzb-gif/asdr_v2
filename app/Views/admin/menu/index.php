<?php

use App\Core\Csrf;

$pageTitle = 'Меню';
$activeNav = 'menu';
$pageActions = '<a href="#menu-add" class="btn btn--primary">' . \App\Core\AdminUi::icon('plus') . 'Добавить пункт</a>';
require __DIR__ . '/../layout/header.php';

/** @var array $tree */
/** @var array $items */
/** @var array $pages */
/** @var array $languages */

$urlTypeLabels = ['page' => 'Страница', 'news_index' => 'Раздел новостей', 'custom' => 'Произвольный URL'];
$parentCandidates = array_values(array_filter(
    $items,
    static fn (array $row): bool => $row['parent_id'] === null && empty($row['is_divider'])
));

/** Общие поля формы создания и редактирования пункта. */
$renderFields = static function (?array $item) use ($pages, $parentCandidates): string {
    $item ??= [];
    $id = isset($item['id']) ? (int) $item['id'] : 0;
    $prefix = $id > 0 ? 'menu_' . $id : 'menu_new';
    $urlType = (string) ($item['url_type'] ?? 'page');
    $urlValue = (string) ($item['url_value'] ?? '');
    $langCode = (string) ($item['lang'] ?? '');
    $parentId = isset($item['parent_id']) ? (int) $item['parent_id'] : 0;
    $isDivider = !empty($item['is_divider']);
    ob_start();
    ?>
    <div class="menu-form-fields" data-menu-link-form>
        <div class="form-field">
            <label for="<?= $prefix ?>_title">Название</label>
            <input type="text" id="<?= $prefix ?>_title" name="title"
                   value="<?= htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES) ?>"
                   placeholder="Например: О компании">
        </div>

        <?php $selectedLang = $langCode !== '' ? $langCode : \App\Models\Language::defaultCode(); ?>
        <input type="hidden" name="lang" value="<?= htmlspecialchars($selectedLang, ENT_QUOTES) ?>" data-menu-lang-select>

        <div class="form-field" data-menu-link-only>
            <label for="<?= $prefix ?>_type">Тип ссылки</label>
            <select id="<?= $prefix ?>_type" name="url_type" data-menu-url-type>
                <option value="page"<?= $urlType === 'page' ? ' selected' : '' ?>>Страница сайта</option>
                <option value="news_index"<?= $urlType === 'news_index' ? ' selected' : '' ?>>Раздел новостей</option>
                <option value="custom"<?= $urlType === 'custom' ? ' selected' : '' ?>>Произвольный URL</option>
            </select>
        </div>

        <div class="form-field" data-menu-url-field="page">
            <label for="<?= $prefix ?>_page">Страница</label>
            <select id="<?= $prefix ?>_page" name="page_slug" data-menu-page-select>
                <option value="">— выберите страницу —</option>
                <?php foreach ($pages as $page): ?>
                    <option value="<?= htmlspecialchars((string) $page['slug'], ENT_QUOTES) ?>"
                            data-title="<?= htmlspecialchars((string) $page['title'], ENT_QUOTES) ?>"
                            data-lang="<?= htmlspecialchars((string) ($page['lang'] ?? \App\Models\Language::defaultCode()), ENT_QUOTES) ?>"
                            <?= $urlType === 'page'
                                && $urlValue === (string) $page['slug']
                                && (string) ($page['lang'] ?? \App\Models\Language::defaultCode()) === $selectedLang
                                ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $page['title'], ENT_QUOTES) ?>
                        [<?= htmlspecialchars(strtoupper((string) ($page['lang'] ?? \App\Models\Language::defaultCode())), ENT_QUOTES) ?>]
                        (/<?= htmlspecialchars((string) $page['slug'], ENT_QUOTES) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="form-hint">Показываются только опубликованные страницы выбранного языка.</span>
        </div>

        <div class="form-field" data-menu-url-field="custom">
            <label for="<?= $prefix ?>_url">URL</label>
            <input type="text" id="<?= $prefix ?>_url" name="custom_url"
                   value="<?= $urlType === 'custom' ? htmlspecialchars($urlValue, ENT_QUOTES) : '' ?>"
                   placeholder="/contacts или https://example.com">
        </div>

        <div class="form-field" data-menu-parent-field>
            <label for="<?= $prefix ?>_parent">Родительский пункт</label>
            <select id="<?= $prefix ?>_parent" name="parent_id" data-menu-parent-select>
                <option value="">— верхний уровень —</option>
                <?php foreach ($parentCandidates as $candidate): ?>
                    <?php if ($id > 0 && (int) $candidate['id'] === $id) { continue; } ?>
                    <option value="<?= (int) $candidate['id'] ?>"
                            data-lang="<?= htmlspecialchars((string) $candidate['lang'], ENT_QUOTES) ?>"
                            <?= $parentId === (int) $candidate['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $candidate['title'], ENT_QUOTES) ?>
                        <?= $candidate['lang'] !== '' ? '(' . htmlspecialchars((string) $candidate['lang'], ENT_QUOTES) . ')' : '(все языки)' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="form-hint">Вложенность ограничена одним уровнем; язык родителя должен совпадать.</span>
        </div>

        <?php // Мега-меню задаётся только у пункта верхнего уровня. ?>
        <?php $mega = (int) ($item['mega_columns'] ?? 0); ?>
        <div class="form-field">
            <label for="<?= $prefix ?>_mega">Вид подменю</label>
            <select id="<?= $prefix ?>_mega" name="mega_columns">
                <option value="0" <?= $mega === 0 ? 'selected' : '' ?>>Обычное — один столбец</option>
                <option value="2" <?= $mega === 2 ? 'selected' : '' ?>>Мега-меню, 2 колонки</option>
                <option value="3" <?= $mega === 3 ? 'selected' : '' ?>>Мега-меню, 3 колонки</option>
                <option value="4" <?= $mega === 4 ? 'selected' : '' ?>>Мега-меню, 4 колонки</option>
            </select>
            <span class="form-hint">Широкая панель во всю ширину контента — удобно, когда вложенных пунктов много. Работает только у пункта верхнего уровня.</span>
        </div>

        <?php
        $iconValue = trim((string) ($item['icon_svg'] ?? ''));
        $iconCatalog = \App\Core\AdminUi::iconCatalog();
        $selectedIconKey = isset($iconCatalog[$iconValue]) ? $iconValue : '';
        ?>
        <div class="form-field menu-icon-picker-field">
            <label for="<?= $prefix ?>_icon_select">Иконка пункта <span class="form-hint">(выберите из AdminUI или укажите свой SVG)</span></label>
            <div class="u-inline-7a9664ce54">
                <select id="<?= $prefix ?>_icon_select" class="form-control" data-icon-select="<?= $prefix ?>_icon">
                    <option value="">— Без иконки —</option>
                    <optgroup label="Иконки AdminUI">
                        <?php foreach ($iconCatalog as $iconKey => $iconLabel): ?>
                            <option value="<?= htmlspecialchars($iconKey, ENT_QUOTES) ?>" <?= $selectedIconKey === $iconKey ? 'selected' : '' ?>>
                                <?= htmlspecialchars($iconLabel, ENT_QUOTES) ?> (<?= htmlspecialchars($iconKey, ENT_QUOTES) ?>)
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <option value="custom" <?= $iconValue !== '' && $selectedIconKey === '' ? 'selected' : '' ?>>Свой SVG-код…</option>
                </select>
                <div class="menu-icon-preview u-inline-893519dbf3" id="<?= $prefix ?>_icon_preview" title="Превью иконки">
                    <?= $iconValue !== '' ? ($selectedIconKey !== '' ? \App\Core\AdminUi::icon($selectedIconKey, 20) : (str_contains($iconValue, '<svg') ? $iconValue : \App\Core\AdminUi::icon($iconValue, 20))) : '<span class="u-inline-081bc4f452">нет</span>' ?>
                </div>
            </div>
            <div class="menu-icon-custom-box<?= $selectedIconKey !== '' || $iconValue === '' ? ' is-hidden' : '' ?>" id="<?= $prefix ?>_icon_custom_box">
                <textarea id="<?= $prefix ?>_icon" name="icon_svg" rows="3" placeholder="<svg viewBox=&quot;0 0 24 24&quot;>…</svg>"><?= htmlspecialchars($iconValue, ENT_QUOTES) ?></textarea>
                <span class="form-hint">Векторный SVG-код или имя иконки из AdminUi. Скрипты и события удаляются автоматически.</span>
        </div>

        <div class="form-field form-field--checkbox">
            <input type="checkbox" id="<?= $prefix ?>_hide_title" name="hide_title" value="1"<?= !empty($item['hide_title']) ? ' checked' : '' ?>>
            <label for="<?= $prefix ?>_hide_title">Скрыть текст названия (показывать только иконку)</label>
            <span class="form-hint u-inline-6f8ae3477e">Работает, когда у пункта выведена иконка. Текст остаётся для скринридеров и подсказки.</span>
        </div>

        <?php $badgeColor = (string) ($item['badge_color'] ?? 'red'); ?>
        <div class="form-field">
            <label for="<?= $prefix ?>_badge_text">Текст плашки / бейджа <span class="form-hint">(напр. АКТУАЛЬНО!, NEW, TOP)</span></label>
            <input type="text" id="<?= $prefix ?>_badge_text" name="badge_text" value="<?= htmlspecialchars((string) ($item['badge_text'] ?? ''), ENT_QUOTES) ?>" placeholder="АКТУАЛЬНО!">
        </div>
        <?php $badgePos = (string) ($item['badge_pos'] ?? 'right'); ?>
        <div class="form-field">
            <label for="<?= $prefix ?>_badge_color">Цвет плашки</label>
            <select id="<?= $prefix ?>_badge_color" name="badge_color">
                <option value="red" <?= $badgeColor === 'red' ? 'selected' : '' ?>>Красный</option>
                <option value="green" <?= $badgeColor === 'green' ? 'selected' : '' ?>>Зелёный</option>
                <option value="blue" <?= $badgeColor === 'blue' ? 'selected' : '' ?>>Синий</option>
                <option value="orange" <?= $badgeColor === 'orange' ? 'selected' : '' ?>>Оранжевый</option>
                <option value="purple" <?= $badgeColor === 'purple' ? 'selected' : '' ?>>Фиолетовый</option>
            </select>
        </div>
        <div class="form-field">
            <label for="<?= $prefix ?>_badge_pos">Позиция плашки</label>
            <select id="<?= $prefix ?>_badge_pos" name="badge_pos">
                <option value="right" <?= $badgePos === 'right' ? 'selected' : '' ?>>Справа (по умолчанию)</option>
                <option value="center" <?= $badgePos === 'center' ? 'selected' : '' ?>>По центру</option>
                <option value="left" <?= $badgePos === 'left' ? 'selected' : '' ?>>Слева</option>
            </select>
        </div>

        <div class="form-field form-field--checkbox">
            <input type="checkbox" id="<?= $prefix ?>_divider" name="is_divider" value="1" data-menu-divider<?= $isDivider ? ' checked' : '' ?>>
            <label for="<?= $prefix ?>_divider">Разделитель без ссылки</label>
        </div>
        <div class="form-field form-field--checkbox">
            <input type="checkbox" id="<?= $prefix ?>_active" name="is_active" value="1"<?= !isset($item['is_active']) || !empty($item['is_active']) ? ' checked' : '' ?>>
            <label for="<?= $prefix ?>_active">Показывать на сайте</label>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
};

/** Строка структуры меню с полноширинной панелью редактирования. */
$renderNode = static function (array $item) use ($urlTypeLabels, $renderFields): string {
    $id = (int) $item['id'];
    $isDivider = !empty($item['is_divider']);
    $title = $isDivider ? 'Разделитель' : (string) $item['title'];
    $destination = $isDivider
        ? 'Без ссылки'
        : ($urlTypeLabels[$item['url_type']] ?? (string) $item['url_type']) . ': ' . ((string) ($item['url_value'] ?? '') ?: '/news');
    $editorId = 'menu-editor-' . $id;
    ob_start();
    ?>
    <div class="menu-node__row">
        <span class="menu-node__handle" draggable="true" title="Перетащите для сортировки" aria-hidden="true">⠿</span>
        <?php if (trim((string) ($item['icon_svg'] ?? '')) !== ''): ?>
            <?php
            $rawIcon = trim((string) $item['icon_svg']);
            $nodeIconSvg = !str_contains($rawIcon, '<svg') ? \App\Core\AdminUi::icon($rawIcon, 18) : $rawIcon;
            ?>
            <span class="menu-node__icon" aria-hidden="true"><?= $nodeIconSvg ?></span>
        <?php endif; ?>
        <span class="menu-node__content">
            <strong class="menu-node__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></strong>
            <span class="menu-node__meta"><?= htmlspecialchars($destination, ENT_QUOTES) ?></span>
        </span>
        <?php if (empty($item['is_active'])): ?><span class="badge badge--draft">Скрыт</span><?php endif; ?>
        <div class="menu-node__actions">
            <form method="post" action="/admin/menu/<?= $id ?>/move">
                <?= Csrf::field() ?><input type="hidden" name="direction" value="up">
                <button type="submit" class="btn btn--small btn--icon menu-node__move" aria-label="Переместить вверх" title="Переместить вверх"><?= \App\Core\AdminUi::icon('arrow-up') ?></button>
            </form>
            <form method="post" action="/admin/menu/<?= $id ?>/move">
                <?= Csrf::field() ?><input type="hidden" name="direction" value="down">
                <button type="submit" class="btn btn--small btn--icon menu-node__move" aria-label="Переместить вниз" title="Переместить вниз"><?= \App\Core\AdminUi::icon('arrow-down') ?></button>
            </form>
            <button type="button" class="btn btn--small btn--icon" data-menu-edit-toggle aria-controls="<?= $editorId ?>" aria-expanded="false" title="Изменить" aria-label="Изменить"><?= \App\Core\AdminUi::icon('edit') ?></button>
            <form method="post" action="/admin/menu/<?= $id ?>/delete" data-confirm="Удалить пункт «<?= htmlspecialchars($title, ENT_QUOTES) ?>»<?= !empty($item['children']) ? ' вместе с вложенными пунктами' : '' ?>?">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn--small btn--icon btn--danger" aria-label="Удалить <?= htmlspecialchars($title, ENT_QUOTES) ?>" title="Удалить"><?= \App\Core\AdminUi::icon('trash') ?></button>
            </form>
        </div>
    </div>
    <div class="menu-node__edit" id="<?= $editorId ?>" data-menu-edit-panel hidden>
        <form method="post" action="/admin/menu/<?= $id ?>/edit" class="form-grid">
            <?= Csrf::field() ?>
            <?= $renderFields($item) ?>
            <div class="form-actions">
                <button type="submit" class="btn btn--primary"><?= \App\Core\AdminUi::icon('save') ?>Сохранить изменения</button>
                <button type="button" class="btn" data-menu-edit-close>Отмена</button>
            </div>
        </form>
    </div>
    <?php
    return (string) ob_get_clean();
};

$groups = [];
foreach ($languages as $language) {
    $groups[] = ['code' => (string) $language['code'], 'name' => (string) $language['name']];
}
$defaultLangCode = \App\Models\Language::defaultCode();
$syncTargetCode = '';
foreach ($groups as $group) {
    if ($group['code'] !== $defaultLangCode) {
        $syncTargetCode = $group['code'];
        break;
    }
}
?>

<p class="admin-hint">
    Структура разделена по языкам. Перетаскивайте пункт только за маркер ⠿ и нажмите
    «Сохранить» в появившейся панели. На телефоне используйте стрелки ↑/↓, а родителя выбирайте в редактировании.
</p>

<div class="menu-workspace">
    <aside class="form-card menu-add-panel" id="menu-add">
        <h2>Добавить пункт</h2>
        <form method="post" action="/admin/menu/create" class="form-grid">
            <?= Csrf::field() ?>
            <?= $renderFields(null) ?>
            <div class="form-actions"><button type="submit" class="btn btn--primary">Добавить в меню</button></div>
        </form>
    </aside>

    <section class="menu-structure" aria-labelledby="menu-structure-title">
        <div class="menu-structure__head">
            <div>
                <h2 id="menu-structure-title">Структура меню</h2>
                <p class="form-hint">Вложенность поддерживает один уровень.</p>
            </div>
            <span class="badge"><?= count($items) ?> пунктов</span>
        </div>

        <div class="form-card menu-sync-card">
            <h3>Синхронизация меню</h3>
            <p class="form-hint">
                Копирует структуру из одного языка в другой и заменяет меню назначения.
                Ссылки автоматически привязываются к опубликованным переводам страниц;
                пункты без перевода пропускаются.
            </p>
            <form method="post" action="/admin/menu/synchronize" class="form-grid"
                  data-confirm="Заменить меню выбранного языка синхронизированной копией?">
                <?= Csrf::field() ?>
                <div class="form-field">
                    <label for="menu_sync_source">Копировать из</label>
                    <select id="menu_sync_source" name="source_lang">
                        <?php foreach ($groups as $group): ?>
                            <option value="<?= htmlspecialchars($group['code'], ENT_QUOTES) ?>"
                                    <?= $group['code'] === $defaultLangCode ? 'selected' : '' ?>>
                                <?= htmlspecialchars($group['name'], ENT_QUOTES) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field">
                    <label for="menu_sync_target">Копировать в</label>
                    <select id="menu_sync_target" name="target_lang">
                        <?php foreach ($groups as $group): ?>
                            <option value="<?= htmlspecialchars($group['code'], ENT_QUOTES) ?>"
                                    <?= $group['code'] === $syncTargetCode ? 'selected' : '' ?>>
                                <?= htmlspecialchars($group['name'], ENT_QUOTES) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn--primary"<?= count($groups) < 2 ? ' disabled' : '' ?>>
                        Синхронизировать
                    </button>
                </div>
            </form>
        </div>

        <div class="menu-lang-tabs" role="tablist" aria-label="Язык меню">
            <?php foreach ($groups as $index => $group): ?>
                <button type="button" role="tab" class="menu-lang-tab<?= $index === 0 ? ' is-active' : '' ?>"
                        data-menu-lang-tab="<?= htmlspecialchars($group['code'], ENT_QUOTES) ?>"
                        aria-selected="<?= $index === 0 ? 'true' : 'false' ?>">
                    <?= htmlspecialchars($group['name'], ENT_QUOTES) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <?php foreach ($groups as $index => $group): ?>
            <?php $groupNodes = array_values(array_filter($tree, static fn (array $node): bool => (string) $node['lang'] === $group['code'])); ?>
            <div class="menu-lang-panel" data-menu-lang-panel="<?= htmlspecialchars($group['code'], ENT_QUOTES) ?>"<?= $index === 0 ? '' : ' hidden' ?>>
                <ul class="menu-tree" data-menu-sortable data-menu-lang="<?= htmlspecialchars($group['code'], ENT_QUOTES) ?>" data-csrf="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
                    <?php if ($groupNodes === []): ?>
                        <li class="menu-tree__empty">В этом языковом разделе пунктов пока нет.</li>
                    <?php endif; ?>
                    <?php foreach ($groupNodes as $node): ?>
                        <li class="menu-node<?= !empty($node['is_divider']) ? ' menu-node--divider' : '' ?>" data-menu-id="<?= (int) $node['id'] ?>" data-menu-lang="<?= htmlspecialchars((string) $node['lang'], ENT_QUOTES) ?>">
                            <?= $renderNode($node) ?>
                            <?php if (empty($node['is_divider'])): ?>
                                <ul class="menu-node__children" data-menu-children aria-label="Вложенные пункты <?= htmlspecialchars((string) $node['title'], ENT_QUOTES) ?>">
                                    <?php foreach ($node['children'] ?? [] as $child): ?>
                                        <li class="menu-node menu-node--child<?= (string) $child['lang'] !== (string) $node['lang'] ? ' menu-node--language-error' : '' ?>"
                                            data-menu-id="<?= (int) $child['id'] ?>" data-menu-lang="<?= htmlspecialchars((string) $child['lang'], ENT_QUOTES) ?>">
                                            <?= $renderNode($child) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </section>
</div>

<script nonce="<?= \App\Core\SecurityHeaders::nonce() ?>">
document.addEventListener('change', function(e) {
    if (e.target && e.target.matches('[data-icon-select]')) {
        const targetId = e.target.getAttribute('data-icon-select');
        const textarea = document.getElementById(targetId);
        const prefix = targetId.replace('_icon', '');
        const customBox = document.getElementById(prefix + '_icon_custom_box');
        const preview = document.getElementById(prefix + '_icon_preview');
        const val = e.target.value;

        if (val === 'custom') {
            if (customBox) customBox.style.display = '';
        } else {
            if (customBox) customBox.style.display = 'none';
            if (textarea) textarea.value = val;
            if (preview) {
                if (!val) {
                    preview.innerHTML = '<span class="u-inline-081bc4f452">нет</span>';
                } else {
                    preview.innerHTML = '<span class="u-inline-cc19ec9cf7">✓</span>';
                }
            }
        }
    }
});
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
