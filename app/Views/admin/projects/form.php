<?php

use App\Core\Csrf;
use App\Models\Language;

$isEdit = !empty($project['id']);
$pageTitle = $isEdit ? 'Редактирование проекта' : 'Новый проект';
$activeNav = 'projects';
require __DIR__ . '/../layout/header.php';

/** @var array|null $project */
/** @var array $images */
/** @var array $fields */
/** @var array $translations */
/** @var string|null $error */

$defaultCode = Language::defaultCode();
$action = $isEdit ? '/admin/projects/' . (int) $project['id'] . '/edit' : '/admin/projects/create';

// Группировка переводов полей
$groupedFields = [];
foreach ($fields as $f) {
    $k = (string) ($f['field_key'] ?? '');
    if (str_ends_with($k, ' [uz]')) {
        $baseKey = substr($k, 0, -5);
        if (isset($groupedFields[$baseKey])) {
            $groupedFields[$baseKey]['field_value_uz'] = $f['field_value'] ?? '';
            continue;
        }
    }
    $groupedFields[$k] = $f;
    $groupedFields[$k]['field_value_uz'] = '';
}

// Разделение полей по категориям (Команда, Вакансии, Документы, Мероприятия, Тендеры, Обычные)
$teamFields = [];
$vacancyFields = [];
$docFields = [];
$eventFields = [];
$tenderFields = [];
$customFields = [];

foreach ($groupedFields as $f) {
    $k = (string) ($f['field_key'] ?? '');
    if (str_starts_with($k, 'Команда:') || str_starts_with($k, 'team:')) {
        $teamFields[] = $f;
    } elseif (str_starts_with($k, 'Вакансия:') || str_starts_with($k, 'vacancy:')) {
        $vacancyFields[] = $f;
    } elseif (str_starts_with($k, 'Документ:') || str_starts_with($k, 'doc:')) {
        $docFields[] = $f;
    } elseif (str_starts_with($k, 'Мероприятие:') || str_starts_with($k, 'event:')) {
        $eventFields[] = $f;
    } elseif (str_starts_with($k, 'Тендер:') || str_starts_with($k, 'tender:')) {
        $tenderFields[] = $f;
    } else {
        $customFields[] = $f;
    }
}
?>
<?php if ($error): ?><div class="alert alert--error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div><?php endif; ?>
<?php if ($isEdit): ?>
    <div style="margin-bottom:16px;"><a class="btn btn--small" href="/admin/revisions/project/<?= (int) $project['id'] ?>">История версий</a></div>
<?php endif; ?>

<form method="post" action="<?= $action ?>" id="project_edit_form" enctype="multipart/form-data" data-content-draft="project:<?= $isEdit ? (int) $project['id'] : 'new' ?>" data-record-updated="<?= htmlspecialchars((string) ($project['updated_at'] ?? ''), ENT_QUOTES) ?>">
    <?= Csrf::field() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="expected_updated_at" value="<?= htmlspecialchars((string) $project['updated_at'], ENT_QUOTES) ?>">
        <input type="hidden" name="expected_lock_version" value="<?= (int) ($project['lock_version'] ?? 1) ?>">
    <?php endif; ?>

    <div class="entry-grid">
        <div class="entry-main">
            <!-- Блок 1: Основная информация -->
            <div class="form-card" style="border:1px solid var(--admin-border,#e2e8f0);border-radius:12px;padding:24px;margin-bottom:24px;background:var(--admin-card-bg,#ffffff);box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--admin-border,#e2e8f0);">
                    <span class="admin-section-icon"><?= \App\Core\AdminUi::icon('projects', 22) ?></span>
                    <h3 style="margin:0;font-size:1.15rem;font-weight:700;">1. Основная информация о проекте</h3>
                </div>

                <div class="form-field" style="margin-bottom:16px;">
                    <label style="font-weight:600;margin-bottom:6px;display:block;">Название проекта <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($project['title'] ?? '', ENT_QUOTES) ?>" placeholder="Введите название проекта" required style="font-size:1.05rem;font-weight:600;">
                </div>

                <div class="form-field">
                    <label style="font-weight:600;margin-bottom:6px;display:block;">Подробное описание проекта (Визуальный редактор)</label>
                    <textarea id="description" name="description" data-wysiwyg style="min-height:260px;"><?= htmlspecialchars($project['description'] ?? '', ENT_QUOTES) ?></textarea>
                </div>
            </div>

            <!-- Блок 2: Медиа и галерея -->
            <div class="form-card" style="border:1px solid var(--admin-border,#e2e8f0);border-radius:12px;padding:24px;margin-bottom:24px;background:var(--admin-card-bg,#ffffff);box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--admin-border,#e2e8f0);">
                    <span class="admin-section-icon admin-section-icon--info"><?= \App\Core\AdminUi::icon('media', 22) ?></span>
                    <h3 style="margin:0;font-size:1.15rem;font-weight:700;">2. Медиа и галерея проекта</h3>
                </div>

                <div class="form-grid" style="margin-bottom:20px;">
                    <?= \App\Core\AdminUi::imageField('cover_image_url', $project['cover_image'] ?? '', [
                        'label' => 'Главная обложка проекта',
                        'file' => 'cover_image_file',
                        'hint' => 'Выберите из медиабиблиотеки или загрузите файл.',
                    ]) ?>
                </div>

                <div>
                    <label style="font-weight:600;margin-bottom:8px;display:block;">Галерея фотографий проекта</label>
                    <div data-repeater="gallery">
                        <?php foreach ($images as $i => $image): ?>
                            <div class="repeater-row" style="display:grid;grid-template-columns:2fr 2fr 36px;gap:10px;align-items:center;margin-bottom:10px;">
                                <div class="form-field" style="margin:0;">
                                    <div style="display:flex;gap:6px;">
                                        <input type="text" name="gallery[<?= $i ?>][file_path]" value="<?= htmlspecialchars($image['file_path'] ?? '', ENT_QUOTES) ?>" placeholder="/uploads/... или https://">
                                        <button type="button" class="btn btn--small btn--secondary" data-media-pick data-media-target="[name='gallery[<?= $i ?>][file_path]']">Выбрать</button>
                                    </div>
                                </div>
                                <div class="form-field" style="margin:0;">
                                    <input type="text" name="gallery[<?= $i ?>][caption]" value="<?= htmlspecialchars($image['caption'] ?? '', ENT_QUOTES) ?>" placeholder="Подпись к фото">
                                </div>
                                <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove style="padding:6px 10px;">✕</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <template data-repeater-template="gallery">
                        <div class="repeater-row" style="display:grid;grid-template-columns:2fr 2fr 36px;gap:10px;align-items:center;margin-bottom:10px;">
                            <div class="form-field" style="margin:0;">
                                <div style="display:flex;gap:6px;">
                                    <input type="text" name="gallery[__INDEX__][file_path]" placeholder="/uploads/... или https://">
                                    <button type="button" class="btn btn--small btn--secondary" data-media-pick data-media-target="[name='gallery[__INDEX__][file_path]']">Выбрать</button>
                                </div>
                            </div>
                            <div class="form-field" style="margin:0;">
                                <input type="text" name="gallery[__INDEX__][caption]" placeholder="Подпись к фото">
                            </div>
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove style="padding:6px 10px;">✕</button>
                        </div>
                    </template>
                    <div class="repeater-actions" style="margin-top:10px;">
                        <button type="button" class="btn btn--small btn--secondary" data-repeater-add="gallery"><?= \App\Core\AdminUi::icon('plus') ?>Добавить фото в галерею</button>
                    </div>
                </div>
            </div>

            <!-- Блок 3: Команда проекта -->
            <div class="form-card" style="border:1px solid var(--admin-border,#e2e8f0);border-radius:12px;padding:24px;margin-bottom:24px;background:var(--admin-card-bg,#ffffff);box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span class="admin-section-icon admin-section-icon--success"><?= \App\Core\AdminUi::icon('users', 22) ?></span>
                        <h3 style="margin:0;font-size:1.15rem;font-weight:700;">3. Команда проекта (с переводом)</h3>
                    </div>
                    <button type="button" class="btn btn--small btn--secondary" data-repeater-add="team_fields">+ Добавить участника</button>
                </div>
                <div data-repeater="team_fields">
                    <?php foreach ($teamFields as $i => $tf): ?>
                        <div class="repeater-row" style="display:grid;grid-template-columns:1.2fr 1.4fr 1.4fr 36px;gap:10px;align-items:center;margin-bottom:10px;">
                            <input type="text" name="custom_fields[team_<?= $i ?>][key]" value="<?= htmlspecialchars($tf['field_key'] ?? '', ENT_QUOTES) ?>" placeholder="Должность">
                            <input type="text" name="custom_fields[team_<?= $i ?>][value]" value="<?= htmlspecialchars($tf['field_value'] ?? '', ENT_QUOTES) ?>" placeholder="ФИО (RU)">
                            <input type="text" name="custom_fields[team_<?= $i ?>][value_uz]" value="<?= htmlspecialchars($tf['field_value_uz'] ?? '', ENT_QUOTES) ?>" placeholder="ФИО (UZ Перевод)">
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove style="padding:6px 10px;">✕</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="team_fields">
                    <div class="repeater-row" style="display:grid;grid-template-columns:1.2fr 1.4fr 1.4fr 36px;gap:10px;align-items:center;margin-bottom:10px;">
                        <input type="text" name="custom_fields[team___INDEX__][key]" value="Команда: " placeholder="Должность">
                        <input type="text" name="custom_fields[team___INDEX__][value]" placeholder="ФИО участника (RU)">
                        <input type="text" name="custom_fields[team___INDEX__][value_uz]" placeholder="Перевод (UZ)">
                        <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove style="padding:6px 10px;">✕</button>
                    </div>
                </template>
                <span class="form-hint">Укажите ключевых специалистов и их переводы.</span>
            </div>

            <!-- Блок 4: Вакансии проекта -->
            <div class="form-card" style="border:1px solid var(--admin-border,#e2e8f0);border-radius:12px;padding:24px;margin-bottom:24px;background:var(--admin-card-bg,#ffffff);box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span class="admin-section-icon admin-section-icon--violet"><?= \App\Core\AdminUi::icon('briefcase', 22) ?></span>
                        <h3 style="margin:0;font-size:1.15rem;font-weight:700;">4. Вакансии проекта (с переводом)</h3>
                    </div>
                    <button type="button" class="btn btn--small btn--secondary" data-repeater-add="vacancy_fields">+ Добавить вакансию</button>
                </div>
                <div data-repeater="vacancy_fields">
                    <?php foreach ($vacancyFields as $i => $vf): ?>
                        <div class="repeater-row" style="display:grid;grid-template-columns:1.2fr 1.4fr 1.4fr 36px;gap:10px;align-items:center;margin-bottom:10px;">
                            <input type="text" name="custom_fields[vac_<?= $i ?>][key]" value="<?= htmlspecialchars($vf['field_key'] ?? '', ENT_QUOTES) ?>" placeholder="Название вакансии">
                            <input type="text" name="custom_fields[vac_<?= $i ?>][value]" value="<?= htmlspecialchars($vf['field_value'] ?? '', ENT_QUOTES) ?>" placeholder="Условия (RU)">
                            <input type="text" name="custom_fields[vac_<?= $i ?>][value_uz]" value="<?= htmlspecialchars($vf['field_value_uz'] ?? '', ENT_QUOTES) ?>" placeholder="Условия (UZ Перевод)">
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove style="padding:6px 10px;">✕</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="vacancy_fields">
                    <div class="repeater-row" style="display:grid;grid-template-columns:1.2fr 1.4fr 1.4fr 36px;gap:10px;align-items:center;margin-bottom:10px;">
                        <input type="text" name="custom_fields[vac___INDEX__][key]" value="Вакансия: " placeholder="Вакансия">
                        <input type="text" name="custom_fields[vac___INDEX__][value]" placeholder="Требования (RU)">
                        <input type="text" name="custom_fields[vac___INDEX__][value_uz]" placeholder="Перевод (UZ)">
                        <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove style="padding:6px 10px;">✕</button>
                    </div>
                </template>
                <span class="form-hint">Открытые вакансии проекта.</span>
            </div>

            <!-- Блок 5: Документы проекта -->
            <div class="form-card" style="border:1px solid var(--admin-border,#e2e8f0);border-radius:12px;padding:24px;margin-bottom:24px;background:var(--admin-card-bg,#ffffff);box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span class="admin-section-icon admin-section-icon--info"><?= \App\Core\AdminUi::icon('document', 22) ?></span>
                        <h3 style="margin:0;font-size:1.15rem;font-weight:700;">5. Документы проекта (с переводом)</h3>
                    </div>
                    <button type="button" class="btn btn--small btn--secondary" data-repeater-add="doc_fields">+ Добавить документ</button>
                </div>
                <div data-repeater="doc_fields">
                    <?php foreach ($docFields as $i => $df): ?>
                        <div class="repeater-row" style="display:grid;grid-template-columns:1.2fr 1.4fr 1.4fr 36px;gap:10px;align-items:center;margin-bottom:10px;">
                            <input type="text" name="custom_fields[doc_<?= $i ?>][key]" value="<?= htmlspecialchars($df['field_key'] ?? '', ENT_QUOTES) ?>" placeholder="Название документа">
                            <div style="display:flex;gap:6px;">
                                <input type="text" name="custom_fields[doc_<?= $i ?>][value]" value="<?= htmlspecialchars($df['field_value'] ?? '', ENT_QUOTES) ?>" placeholder="Ссылка (RU)">
                                <button type="button" class="btn btn--small btn--secondary" data-media-pick data-media-target="[name='custom_fields[doc_<?= $i ?>][value]']" data-media-type="all_files">Файл</button>
                            </div>
                            <input type="text" name="custom_fields[doc_<?= $i ?>][value_uz]" value="<?= htmlspecialchars($df['field_value_uz'] ?? '', ENT_QUOTES) ?>" placeholder="Ссылка (UZ)">
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove style="padding:6px 10px;">✕</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="doc_fields">
                    <div class="repeater-row" style="display:grid;grid-template-columns:1.2fr 1.4fr 1.4fr 36px;gap:10px;align-items:center;margin-bottom:10px;">
                        <input type="text" name="custom_fields[doc___INDEX__][key]" value="Документ: " placeholder="Название">
                        <div style="display:flex;gap:6px;">
                            <input type="text" name="custom_fields[doc___INDEX__][value]" placeholder="Ссылка (RU)">
                            <button type="button" class="btn btn--small btn--secondary" data-media-pick data-media-target="[name='custom_fields[doc___INDEX__][value]']" data-media-type="all_files">Файл</button>
                        </div>
                        <input type="text" name="custom_fields[doc___INDEX__][value_uz]" placeholder="Ссылка / Название (UZ)">
                        <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove style="padding:6px 10px;">✕</button>
                    </div>
                </template>
                <span class="form-hint">Прикрепляемые нормативные акты, справки и паспорта.</span>
            </div>

            <!-- Блок 6: Мероприятия проекта -->
            <div class="form-card" style="border:1px solid var(--admin-border,#e2e8f0);border-radius:12px;padding:24px;margin-bottom:24px;background:var(--admin-card-bg,#ffffff);box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span class="admin-section-icon admin-section-icon--violet"><?= \App\Core\AdminUi::icon('calendar', 22) ?></span>
                        <h3 style="margin:0;font-size:1.15rem;font-weight:700;">6. Мероприятия и презентации (с переводом)</h3>
                    </div>
                    <button type="button" class="btn btn--small btn--secondary" data-repeater-add="event_fields">+ Добавить мероприятие</button>
                </div>
                <div data-repeater="event_fields">
                    <?php foreach ($eventFields as $i => $ef): ?>
                        <div class="repeater-row" style="display:grid;grid-template-columns:1.2fr 1.4fr 1.4fr 36px;gap:10px;align-items:center;margin-bottom:10px;">
                            <input type="text" name="custom_fields[evt_<?= $i ?>][key]" value="<?= htmlspecialchars($ef['field_key'] ?? '', ENT_QUOTES) ?>" placeholder="Название">
                            <input type="text" name="custom_fields[evt_<?= $i ?>][value]" value="<?= htmlspecialchars($ef['field_value'] ?? '', ENT_QUOTES) ?>" placeholder="Дата и место (RU)">
                            <input type="text" name="custom_fields[evt_<?= $i ?>][value_uz]" value="<?= htmlspecialchars($ef['field_value_uz'] ?? '', ENT_QUOTES) ?>" placeholder="Перевод (UZ)">
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove style="padding:6px 10px;">✕</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="event_fields">
                    <div class="repeater-row" style="display:grid;grid-template-columns:1.2fr 1.4fr 1.4fr 36px;gap:10px;align-items:center;margin-bottom:10px;">
                        <input type="text" name="custom_fields[evt___INDEX__][key]" value="Мероприятие: " placeholder="Название события">
                        <input type="text" name="custom_fields[evt___INDEX__][value]" placeholder="Дата и место (RU)">
                        <input type="text" name="custom_fields[evt___INDEX__][value_uz]" placeholder="Перевод (UZ)">
                        <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove style="padding:6px 10px;">✕</button>
                    </div>
                </template>
                <span class="form-hint">Запланированные форумы и открытия.</span>
            </div>

            <!-- Блок 7: Тендеры и закупки -->
            <div class="form-card" style="border:1px solid var(--admin-border,#e2e8f0);border-radius:12px;padding:24px;margin-bottom:24px;background:var(--admin-card-bg,#ffffff);box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span class="admin-section-icon admin-section-icon--warning"><?= \App\Core\AdminUi::icon('tender', 22) ?></span>
                        <h3 style="margin:0;font-size:1.15rem;font-weight:700;">7. Тендеры и конкурсы (с переводом)</h3>
                    </div>
                    <button type="button" class="btn btn--small btn--secondary" data-repeater-add="tender_fields">+ Добавить тендер</button>
                </div>
                <div data-repeater="tender_fields">
                    <?php foreach ($tenderFields as $i => $tnd): ?>
                        <div class="repeater-row" style="display:grid;grid-template-columns:1.2fr 1.4fr 1.4fr 36px;gap:10px;align-items:center;margin-bottom:10px;">
                            <input type="text" name="custom_fields[tnd_<?= $i ?>][key]" value="<?= htmlspecialchars($tnd['field_key'] ?? '', ENT_QUOTES) ?>" placeholder="Название тендера">
                            <input type="text" name="custom_fields[tnd_<?= $i ?>][value]" value="<?= htmlspecialchars($tnd['field_value'] ?? '', ENT_QUOTES) ?>" placeholder="Условия (RU)">
                            <input type="text" name="custom_fields[tnd_<?= $i ?>][value_uz]" value="<?= htmlspecialchars($tnd['field_value_uz'] ?? '', ENT_QUOTES) ?>" placeholder="Перевод (UZ)">
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove style="padding:6px 10px;">✕</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="tender_fields">
                    <div class="repeater-row" style="display:grid;grid-template-columns:1.2fr 1.4fr 1.4fr 36px;gap:10px;align-items:center;margin-bottom:10px;">
                        <input type="text" name="custom_fields[tnd___INDEX__][key]" value="Тендер: " placeholder="Номер тендера">
                        <input type="text" name="custom_fields[tnd___INDEX__][value]" placeholder="Бюджет и сроки (RU)">
                        <input type="text" name="custom_fields[tnd___INDEX__][value_uz]" placeholder="Перевод (UZ)">
                        <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove style="padding:6px 10px;">✕</button>
                    </div>
                </template>
                <span class="form-hint">Закупки и подряды проекта.</span>
            </div>

            <!-- Блок 8: Дополнительные характеристики -->
            <div class="form-card" style="border:1px solid var(--admin-border,#e2e8f0);border-radius:12px;padding:24px;margin-bottom:24px;background:var(--admin-card-bg,#ffffff);box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span class="admin-section-icon admin-section-icon--neutral"><?= \App\Core\AdminUi::icon('sliders', 22) ?></span>
                        <h3 style="margin:0;font-size:1.15rem;font-weight:700;">8. Дополнительные характеристики (с переводом)</h3>
                    </div>
                    <button type="button" class="btn btn--small btn--secondary" data-repeater-add="custom_fields">+ Добавить поле</button>
                </div>
                <div data-repeater="custom_fields">
                    <?php foreach ($customFields as $i => $cf): ?>
                        <div class="repeater-row" style="display:grid;grid-template-columns:1.2fr 1.4fr 1.4fr 36px;gap:10px;align-items:center;margin-bottom:10px;">
                            <input type="text" name="custom_fields[cf_<?= $i ?>][key]" value="<?= htmlspecialchars($cf['field_key'] ?? '', ENT_QUOTES) ?>" placeholder="Ключ">
                            <input type="text" name="custom_fields[cf_<?= $i ?>][value]" value="<?= htmlspecialchars($cf['field_value'] ?? '', ENT_QUOTES) ?>" placeholder="Значение (RU)">
                            <input type="text" name="custom_fields[cf_<?= $i ?>][value_uz]" value="<?= htmlspecialchars($cf['field_value_uz'] ?? '', ENT_QUOTES) ?>" placeholder="Значение (UZ)">
                            <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove style="padding:6px 10px;">✕</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <template data-repeater-template="custom_fields">
                    <div class="repeater-row" style="display:grid;grid-template-columns:1.2fr 1.4fr 1.4fr 36px;gap:10px;align-items:center;margin-bottom:10px;">
                        <input type="text" name="custom_fields[cf___INDEX__][key]" placeholder="Название поля">
                        <input type="text" name="custom_fields[cf___INDEX__][value]" placeholder="Значение (RU)">
                        <input type="text" name="custom_fields[cf___INDEX__][value_uz]" placeholder="Перевод (UZ)">
                        <button type="button" class="btn btn--small btn--danger repeater-row__remove" data-repeater-remove style="padding:6px 10px;">✕</button>
                    </div>
                </template>
                <span class="form-hint">Заказчик, год реализации, площадь объекта и др.</span>
            </div>
        </div>

        <!-- Правая колонка настройки публикации -->
        <aside class="entry-side">
            <?= \App\Core\TranslationGroupHelper::renderSidebarMetaBox('projects', $project ?? []) ?>
            <div class="form-card" style="border:1px solid var(--admin-border,#e2e8f0);border-radius:12px;padding:20px;margin-bottom:20px;background:var(--admin-card-bg,#ffffff);box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="margin-top:0;font-size:1.1rem;font-weight:700;">Параметры публикаций</h3>
                <div class="form-grid">
                    <div class="form-field">
                        <label for="status" style="font-weight:600;">Статус</label>
                        <select id="status" name="status">
                            <option value="draft" <?= ($project['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Черновик</option>
                            <option value="published" <?= ($project['status'] ?? '') === 'published' ? 'selected' : '' ?>>Опубликовано</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="slug" style="font-weight:600;">ЧПУ (slug)</label>
                        <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($project['slug'] ?? '', ENT_QUOTES) ?>" placeholder="оставьте пустым для автогенерации">
                        <span class="form-hint">Общий адрес для всех языковых версий.</span>
                    </div>

                    <div class="form-field">
                        <label for="sort_order" style="font-weight:600;">Порядок сортировки</label>
                        <input type="number" id="sort_order" name="sort_order" value="<?= (int) ($project['sort_order'] ?? 0) ?>">
                    </div>

                    <div class="form-field form-field--checkbox" style="margin-top:8px;">
                        <input type="checkbox" id="is_featured" name="is_featured" value="1" <?= !empty($project['is_featured']) ? 'checked' : '' ?>>
                        <label for="is_featured" style="font-size:0.92rem;font-weight:600;">Показать на главной</label>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</form>

<div class="form-actions form-actions--sticky">
    <?php if (($project['status'] ?? 'draft') !== 'published'): ?>
        <button type="submit" name="publish_action" value="1" form="project_edit_form" class="btn btn--primary"><?= \App\Core\AdminUi::icon('check') ?>Опубликовать</button>
        <button type="submit" form="project_edit_form" class="btn"><?= \App\Core\AdminUi::icon('save') ?>Сохранить черновик</button>
    <?php else: ?>
        <button type="submit" form="project_edit_form" class="btn btn--primary"><?= \App\Core\AdminUi::icon('save') ?>Сохранить изменения</button>
    <?php endif; ?>
    <a href="/admin/projects" class="btn">Отмена</a>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
