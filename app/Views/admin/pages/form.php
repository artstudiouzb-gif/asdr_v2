<?php

use App\Core\Csrf;
use App\Core\BlockTypeRegistry;
use App\Models\Language;

$isEdit = !empty($page['id']);
$pageTitle = $isEdit ? 'Редактирование страницы' : 'Новая страница';
$activeNav = 'pages';
require __DIR__ . '/../layout/header.php';

/** @var array|null $page */
/** @var array $translations */
/** @var string|null $error */
/** @var array $blocks */
$blocks = $blocks ?? [];
$blockLang = $blockLang ?? Language::defaultCode();

$action = $isEdit ? '/admin/pages/' . (int) $page['id'] . '/edit' : '/admin/pages/create';
$defaultCode = Language::defaultCode();
$languages = Language::active();

$blockTypeLabels = BlockTypeRegistry::editorLabels();

// Дочерние блоки колонок (группа 4.1): подгружаем детей каждого columns-блока.
$columnsChildren = [];
foreach ($blocks as $b) {
    if ($b['type'] === 'columns') {
        $columnsChildren[(int) $b['id']] = \App\Models\Block::childrenOf((int) $b['id']);
    }
}
?>
<?php if ($error): ?><div class="alert alert--error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div><?php endif; ?>
<?php if ($isEdit): ?>
    <div style="margin-bottom:16px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <a class="btn btn--small" href="/admin/revisions/page/<?= (int) $page['id'] ?>">История версий</a>
        <?php if (\App\Models\Page::isHomePage($page)): ?>
            <span class="badge badge--success" style="font-size:0.88rem;padding:6px 12px;">★ Эта страница назначена Главной страницей сайта</span>
        <?php else: ?>
            <form method="post" action="/admin/pages/<?= (int) $page['id'] ?>/make-home" style="display:inline;">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn--small btn--warning">🏠 Назначить этой странице статус «Главная»</button>
            </form>
        <?php endif; ?>
    </div>
<?php endif; ?>
<form method="post" action="<?= $action ?>" id="page_edit_form" data-content-draft="page:<?= $isEdit ? (int) $page['id'] : 'new' ?>" data-record-updated="<?= htmlspecialchars((string) ($page['updated_at'] ?? ''), ENT_QUOTES) ?>">
        <?= Csrf::field() ?>
        <input type="hidden" name="block_lang" value="<?= htmlspecialchars($blockLang, ENT_QUOTES) ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="expected_updated_at" value="<?= htmlspecialchars((string) $page['updated_at'], ENT_QUOTES) ?>">
            <input type="hidden" name="expected_lock_version" value="<?= (int) ($page['lock_version'] ?? 1) ?>">
        <?php endif; ?>

    <div class="entry-grid">
        <div class="entry-main">
            <!-- Блок 1: Основная информация -->
            <div class="form-card">
                <?= \App\Core\AdminUi::cardHeader('1. Основная информация', 'document') ?>

                <div class="form-field" style="margin-bottom:16px;">
                    <label style="font-weight:600;margin-bottom:6px;display:block;">Заголовок страницы <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="title" value="<?= htmlspecialchars($page['title'] ?? '', ENT_QUOTES) ?>" placeholder="Введите название страницы" required style="font-size:1.05rem;font-weight:600;">
                </div>

                <div class="form-field">
                    <label style="font-weight:600;margin-bottom:6px;display:block;">Описание / лид (подзаголовок)</label>
                    <textarea name="lead" rows="2" placeholder="Короткое описание страницы"><?= htmlspecialchars($page['lead'] ?? '', ENT_QUOTES) ?></textarea>
                    <span class="form-hint">Показывается в начале страницы под заголовком (если нет Hero-блока).</span>
                </div>
            </div>

            <!-- Блок 2: SEO Оптимизация -->
            <div class="form-card">
                <?= \App\Core\AdminUi::cardHeader('2. SEO Оптимизация', 'seo', 'var(--admin-success)') ?>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="form-grid-2col">
                    <div class="form-field">
                        <label style="font-weight:600;margin-bottom:6px;display:block;">SEO: Meta Title</label>
                        <input type="text" name="meta_title" value="<?= htmlspecialchars($page['meta_title'] ?? '', ENT_QUOTES) ?>" placeholder="SEO Заголовок для поисковиков">
                    </div>
                    <div class="form-field">
                        <label style="font-weight:600;margin-bottom:6px;display:block;">SEO: Meta Description</label>
                        <textarea name="meta_description" rows="2" placeholder="SEO Краткое описание для поисковиков"><?= htmlspecialchars($page['meta_description'] ?? '', ENT_QUOTES) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <aside class="entry-side">
            <?= \App\Core\TranslationGroupHelper::renderSidebarMetaBox('pages', $page ?? []) ?>
            <?php require __DIR__ . '/_settings_sidebar.php'; ?>
        </aside>
    </div>
</form>

<?php if ($isEdit): ?>
    <h2 style="margin-top:40px;">Блоки страницы</h2>
    <p class="form-hint">У каждого языка свой независимый стек блоков. Если стек языка пуст, на сайте показывается стек основного языка.</p>

    <?php if (!empty($usingFallback)): ?>
        <div class="alert alert--info" style="margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <span>ℹ️ На языке <strong><?= strtoupper(htmlspecialchars($blockLang, ENT_QUOTES)) ?></strong> ещё нет собственных блоков. Ниже пока отображаются блоки основного языка (<strong><?= strtoupper(htmlspecialchars($defaultCode, ENT_QUOTES)) ?></strong>).</span>
            <form method="post" action="/admin/pages/<?= (int) $page['id'] ?>/copy-language-blocks" style="display:inline;">
                <?= Csrf::field() ?>
                <input type="hidden" name="from_lang" value="<?= htmlspecialchars($defaultCode, ENT_QUOTES) ?>">
                <input type="hidden" name="to_lang" value="<?= htmlspecialchars($blockLang, ENT_QUOTES) ?>">
                <button type="submit" class="btn btn--small btn--primary">📋 Скопировать блоки из <?= strtoupper(htmlspecialchars($defaultCode, ENT_QUOTES)) ?> для перевода на <?= strtoupper(htmlspecialchars($blockLang, ENT_QUOTES)) ?></button>
            </form>
        </div>
    <?php endif; ?>

    <?php if (empty($blocks)): ?>
        <p class="form-hint">На этом языке блоков пока нет.</p>
    <?php endif; ?>

    <?php if (!empty($blocks)): ?>
        <p class="form-hint">Перетаскивайте блоки за значок ⠿ для изменения порядка (сохраняется автоматически).</p>
    <?php endif; ?>
    <div class="block-list" data-block-sortable
         data-page-id="<?= (int) $page['id'] ?>"
         data-block-lang="<?= htmlspecialchars($blockLang, ENT_QUOTES) ?>"
         data-csrf="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
    <?php foreach ($blocks as $index => $block): ?>
        <?php $blockActive = (int) ($block['is_active'] ?? 1) === 1; ?>
        <div class="block-list-item<?= $blockActive ? '' : ' block-list-item--off' ?>" draggable="true" data-block-id="<?= (int) $block['id'] ?>">
            <span class="block-list-item__handle" title="Зажмите и перетащите для изменения порядка" aria-hidden="true">⠿</span>
            <div class="block-list-item__icon" title="Тип: <?= htmlspecialchars($blockTypeLabels[$block['type']] ?? $block['type'], ENT_QUOTES) ?>">
                <?= \App\Core\AdminUi::blockIcon($block['type']) ?>
            </div>
            <div class="block-list-item__meta">
                <div class="block-list-item__head">
                    <strong><?= htmlspecialchars($block['title'] ?: ('Блок #' . $block['id']), ENT_QUOTES) ?></strong>
                    <span class="block-list-item__num">#<?= (int) $block['id'] ?></span>
                </div>
                <div class="block-list-item__sub">
                    <span class="block-list-item__type"><?= htmlspecialchars($blockTypeLabels[$block['type']] ?? $block['type'], ENT_QUOTES) ?></span>
                    <?php if (!$blockActive): ?><span class="block-list-item__badge block-list-item__badge--off">Скрыт</span><?php endif; ?>
                    <?php
                    // Условия показа: редактор должен видеть, почему блока нет на
                    // сайте, не заходя внутрь блока.
                    $blockData = json_decode((string) ($block['data'] ?? '{}'), true) ?: [];
                    $visLabel = \App\Core\BlockVisibility::label($blockData);
                    ?>
                    <?php if ($visLabel !== ''): ?><span class="block-list-item__badge block-list-item__badge--vis" title="Условия показа"><?= htmlspecialchars($visLabel, ENT_QUOTES) ?></span><?php endif; ?>
                </div>
            </div>
            <div class="block-list-item__actions">
                <form method="post" action="/admin/blocks/<?= (int) $block['id'] ?>/move">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="direction" value="up">
                    <button type="submit" class="btn btn--small btn--icon-only" title="Поднять выше" <?= $index === 0 ? 'disabled' : '' ?>><?= \App\Core\AdminUi::icon('arrow-up') ?></button>
                </form>
                <form method="post" action="/admin/blocks/<?= (int) $block['id'] ?>/move">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="direction" value="down">
                    <button type="submit" class="btn btn--small btn--icon-only" title="Опустить ниже" <?= $index === count($blocks) - 1 ? 'disabled' : '' ?>><?= \App\Core\AdminUi::icon('arrow-down') ?></button>
                </form>
                <form method="post" action="/admin/blocks/<?= (int) $block['id'] ?>/toggle" title="<?= $blockActive ? 'Отключить (скрыть на сайте)' : 'Включить (показать на сайте)' ?>">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn--small<?= $blockActive ? '' : ' btn--warning' ?>"><?= $blockActive ? \App\Core\AdminUi::icon('eye-off') . 'Скрыть' : \App\Core\AdminUi::icon('eye') . 'Показать' ?></button>
                </form>
                <a class="btn btn--small btn--secondary" href="/admin/blocks/<?= (int) $block['id'] ?>/edit"><?= \App\Core\AdminUi::icon('edit') ?>Редактировать</a>
                <form method="post" action="/admin/blocks/<?= (int) $block['id'] ?>/delete" data-confirm="Удалить блок «<?= htmlspecialchars($block['title'] ?: ('Блок #' . $block['id']), ENT_QUOTES) ?>»?">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn--small btn--danger"><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                </form>
            </div>
        </div>
        <?php if ($block['type'] === 'columns'):
            $cdata = json_decode((string) $block['data'], true) ?: [];
            $colCount = (int) ($cdata['columns'] ?? 2);
            if ($colCount < 2 || $colCount > 4) { $colCount = 2; }
            $kids = $columnsChildren[(int) $block['id']] ?? [];
        ?>
        <div class="columns-editor" style="margin:4px 0 16px 32px;">
            <div class="columns-editor__grid columns-editor__grid--<?= $colCount ?>">
                <?php for ($ci = 0; $ci < $colCount; $ci++): ?>
                    <div class="columns-editor__col">
                        <div class="columns-editor__col-title">Колонка <?= $ci + 1 ?></div>
                        <?php foreach ($kids as $kid): if ((int) $kid['column_index'] !== $ci) { continue; } ?>
                            <div class="columns-editor__child">
                                <span><?= htmlspecialchars($kid['title'] ?: ($blockTypeLabels[$kid['type']] ?? $kid['type']), ENT_QUOTES) ?></span>
                                <span class="columns-editor__child-actions">
                                    <a class="btn btn--small" href="/admin/blocks/<?= (int) $kid['id'] ?>/edit" title="Редактировать" aria-label="Редактировать"><?= \App\Core\AdminUi::icon('edit') ?></a>
                                    <form method="post" action="/admin/blocks/<?= (int) $kid['id'] ?>/delete" data-confirm="Удалить вложенный блок?">
                                        <?= Csrf::field() ?><button class="btn btn--small btn--danger" title="Удалить" aria-label="Удалить"><?= \App\Core\AdminUi::icon('trash') ?></button>
                                    </form>
                                </span>
                            </div>
                        <?php endforeach; ?>
                        <form method="post" action="/admin/pages/<?= (int) $page['id'] ?>/blocks/add" class="columns-editor__add">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="block_lang" value="<?= htmlspecialchars($blockLang, ENT_QUOTES) ?>">
                            <input type="hidden" name="parent_block_id" value="<?= (int) $block['id'] ?>">
                            <input type="hidden" name="column_index" value="<?= $ci ?>">
                            <select name="type" aria-label="Тип вложенного блока">
                                <?php foreach ($blockTypeLabels as $t => $lbl):
                                    if ($t === 'columns') { continue; } // без columns-в-columns
                                    if ($t === 'html' && !\App\Core\Auth::isSuperAdmin()) { continue; }
                                ?>
                                    <option value="<?= htmlspecialchars($t, ENT_QUOTES) ?>"><?= htmlspecialchars($lbl, ENT_QUOTES) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn--small"><?= \App\Core\AdminUi::icon('plus') ?>блок</button>
                        </form>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>
    </div>
</form>

    <?php
    // Готовые сборки: страница собирается одним нажатием, с уже расставленными
    // фонами и отступами. Когда блоков ещё нет — это основной сценарий старта,
    // поэтому карточки раскрыты; на заполненной странице секция свёрнута.
    $presets = \App\Core\PagePresets::all();
    ?>
    <details class="form-card preset-picker" style="margin-top:16px;"<?= empty($blocks) ? ' open' : '' ?>>
        <summary><strong>Собрать страницу из готовой сборки</strong>
            <span class="form-hint">— <?= count($presets) ?> вариантов с готовой вёрсткой</span>
        </summary>
        <p class="form-hint" style="margin:10px 0 14px;">
            Блоки добавятся с расставленными отступами, фонами и текстами-заготовками —
            останется заменить содержимое своим. Оформление берётся из настроек дизайна сайта.
            Режим «Заменить всё» перед удалением сам сохранит текущие блоки в шаблон
            «Автокопия: …» — вернуть страницу можно через список шаблонов ниже.
        </p>
        <div class="preset-grid">
            <?php foreach ($presets as $presetId => $preset): ?>
                <form method="post" action="/admin/pages/<?= (int) $page['id'] ?>/presets/apply" class="preset-card"
                      data-confirm="Применить сборку «<?= htmlspecialchars($preset['name'], ENT_QUOTES) ?>» к этой странице?">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="block_lang" value="<?= htmlspecialchars($blockLang, ENT_QUOTES) ?>">
                    <input type="hidden" name="preset" value="<?= htmlspecialchars((string) $presetId, ENT_QUOTES) ?>">
                    <h4 class="preset-card__title"><?= htmlspecialchars($preset['name'], ENT_QUOTES) ?></h4>
                    <p class="preset-card__desc"><?= htmlspecialchars($preset['description'], ENT_QUOTES) ?></p>
                    <ol class="preset-card__outline">
                        <?php foreach ($preset['outline'] as $section): ?>
                            <li><?= htmlspecialchars($section, ENT_QUOTES) ?></li>
                        <?php endforeach; ?>
                    </ol>
                    <div class="preset-card__foot">
                        <select name="mode" aria-label="Как применить сборку">
                            <option value="append">Добавить к текущим</option>
                            <option value="replace">Заменить всё</option>
                        </select>
                        <button type="submit" class="btn btn--small btn--primary">Применить</button>
                    </div>
                </form>
            <?php endforeach; ?>
        </div>
    </details>

    <?php
    // Библиотека шаблонов: если миграция block_snippets ещё не накатана,
    // не роняем весь редактор страницы 500-й — скрываем секцию и подсказываем
    // (тот же паттерн, что у ContentType::all() в layout/header.php).
    try {
        $snippets = \App\Models\BlockSnippet::all();
    } catch (\Throwable $snippetError) {
        $snippets = null;
    }
    ?>
    <?php if ($snippets === null): ?>
    <div class="form-card" style="margin-top:16px;">
        <h3 style="margin-top:0;">Шаблоны страницы</h3>
        <p class="form-hint">Раздел недоступен: не применена миграция базы данных. Выполните <code>php database/migrate.php</code> на сервере.</p>
    </div>
    <?php else: ?>
    <div class="form-card" style="margin-top:16px;">
        <h3 style="margin-top:0;">Шаблоны страницы</h3>
        <p class="form-hint">Шаблон сохраняет все блоки этого языка, включая содержимое колонок. Его можно применить к любой странице: добавить к текущим блокам или полностью заменить их. Перед заменой прежние блоки автоматически сохраняются как «Автокопия: …» — хранятся последние 5.</p>
        <div class="snippet-tools">
            <form method="post" action="/admin/pages/<?= (int) $page['id'] ?>/snippets/save" class="snippet-tools__row">
                <?= Csrf::field() ?>
                <input type="hidden" name="block_lang" value="<?= htmlspecialchars($blockLang, ENT_QUOTES) ?>">
                <input type="text" name="snippet_name" placeholder="Название шаблона" required>
                <button type="submit" class="btn btn--small"><?= \App\Core\AdminUi::icon('save') ?>Сохранить страницу как шаблон</button>
            </form>
            <?php if (!empty($snippets)): ?>
                <form method="post" action="/admin/pages/<?= (int) $page['id'] ?>/snippets/insert" class="snippet-tools__row" data-snippet-insert>
                    <?= Csrf::field() ?>
                    <input type="hidden" name="block_lang" value="<?= htmlspecialchars($blockLang, ENT_QUOTES) ?>">
                    <select name="snippet_id" required>
                        <option value="">— выберите шаблон —</option>
                        <?php foreach ($snippets as $s): ?>
                            <?php // Показываем состав: по одному названию не понять, что применится. ?>
                            <option value="<?= (int) $s['id'] ?>"><?= htmlspecialchars((string) $s['name'], ENT_QUOTES) ?><?= ($s['summary'] ?? '') !== '' ? ' — ' . htmlspecialchars((string) $s['summary'], ENT_QUOTES) : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="mode">
                        <option value="append">Добавить к текущим</option>
                        <option value="replace">Заменить текущие блоки</option>
                    </select>
                    <button type="submit" class="btn btn--small"><?= \App\Core\AdminUi::icon('layout') ?>Применить шаблон</button>
                </form>
            <?php else: ?>
                <p class="form-hint">Пока нет сохранённых шаблонов.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="form-card" style="margin-top:20px;">
        <form method="post" action="/admin/pages/<?= (int) $page['id'] ?>/blocks/add" class="form-grid">
            <?= Csrf::field() ?>
            <input type="hidden" name="block_lang" value="<?= htmlspecialchars($blockLang, ENT_QUOTES) ?>">
            <div class="form-field">
                <label for="type">Добавить блок (язык: <?= htmlspecialchars($blockLang, ENT_QUOTES) ?>)</label>
                <select id="type" name="type">
                    <?php foreach ($blockTypeLabels as $type => $label): ?>
                        <?php // Блок сырого HTML доступен только супер-администратору. ?>
                        <?php if ($type === 'html' && !\App\Core\Auth::isSuperAdmin()) { continue; } ?>
                        <option value="<?= $type ?>"><?= htmlspecialchars($label, ENT_QUOTES) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label for="block_title">Внутреннее название блока (необязательно)</label>
                <input type="text" id="block_title" name="title" placeholder="например: Слайдер на главной">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn--primary"><?= \App\Core\AdminUi::icon('plus') ?>Добавить блок</button>
            </div>
        </form>
    </div>
<?php endif; ?>

<div class="form-actions form-actions--sticky">
    <div class="form-actions-left">
        <?php $pStatus = $page['status'] ?? 'draft'; ?>
        <span class="badge badge--<?= $pStatus === 'published' ? 'success' : 'draft' ?>" style="font-size:0.84rem;padding:6px 12px;display:inline-flex;align-items:center;gap:6px;font-weight:600;">
            <span style="width:8px;height:8px;border-radius:50%;background:<?= $pStatus === 'published' ? '#10b981' : '#f59e0b' ?>;"></span>
            <?= $pStatus === 'published' ? 'Опубликовано' : 'Черновик' ?>
        </span>
        <span style="font-size:0.82rem;font-weight:600;color:var(--admin-text-muted,#64748b);display:inline-flex;align-items:center;gap:6px;background:var(--admin-surface-soft,#f8fafc);padding:5px 10px;border-radius:6px;border:1px solid var(--admin-border,#e2e8f0);">
            <?= \App\Core\AdminUi::icon('globe', 14) ?>
            Язык контента: <strong><?= strtoupper(htmlspecialchars($blockLang, ENT_QUOTES)) ?></strong>
        </span>
    </div>

    <div class="form-actions-right">
        <?php if ($pStatus !== 'published'): ?>
            <button type="submit" name="publish_action" value="1" form="page_edit_form" class="btn btn--primary"><?= \App\Core\AdminUi::icon('check') ?>Опубликовать</button>
            <button type="submit" form="page_edit_form" class="btn"><?= \App\Core\AdminUi::icon('save') ?>Сохранить черновик</button>
        <?php else: ?>
            <button type="submit" form="page_edit_form" class="btn btn--primary"><?= \App\Core\AdminUi::icon('save') ?>Сохранить изменения</button>
        <?php endif; ?>
        <a href="/admin/pages" class="btn">Отмена</a>
        <?php if ($isEdit): ?>
            <a href="/admin/pages/<?= (int) $page['id'] ?>/preview?block_lang=<?= urlencode($blockLang) ?>"
               class="btn" target="_blank" rel="noopener">Предпросмотр ↗</a>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
