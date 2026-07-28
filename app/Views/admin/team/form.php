<?php

use App\Core\Csrf;
use App\Models\Language;

$isEdit = !empty($member['id']);
$pageTitle = $isEdit ? 'Редактирование сотрудника' : 'Новый сотрудник';
$activeNav = 'team';
require __DIR__ . '/../layout/header.php';

/** @var array|null $member */
/** @var array $translations */
/** @var string|null $error */

$action = $isEdit ? '/admin/team/' . (int) $member['id'] . '/edit' : '/admin/team/create';
$socials = $member['socials'] ?? [];
$defaultCode = Language::defaultCode();
$translationLangs = array_values(array_filter(
    Language::active(),
    static fn (array $l): bool => (string) $l['code'] !== $defaultCode
));
?>
<?php if ($error): ?><div class="alert alert--error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div><?php endif; ?>

<form method="post" action="<?= $action ?>" enctype="multipart/form-data">
    <?= Csrf::field() ?>

    <div class="entry-grid">
        <div class="entry-main">
            <!-- Блок 1: Основная информация сотрудника -->
            <div class="form-card" style="border:1px solid var(--admin-border,#e2e8f0);border-radius:12px;padding:24px;margin-bottom:24px;background:var(--admin-card-bg,#ffffff);box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <?= \App\Core\AdminUi::cardHeader('1. Основная информация о сотруднике', 'users', 'var(--admin-primary,#2563eb)') ?>

                <div class="form-field" style="margin-bottom:16px;">
                    <label style="font-weight:600;margin-bottom:6px;display:block;">ФИО / Имя сотрудника <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($member['name'] ?? '', ENT_QUOTES) ?>" required placeholder="например: Алишер Каримов" style="font-size:1.05rem;font-weight:600;">
                </div>

                <div class="form-field" style="margin-bottom:16px;">
                    <label style="font-weight:600;margin-bottom:6px;display:block;">Должность (на основном языке)</label>
                    <input type="text" id="position" name="position" value="<?= htmlspecialchars($member['position'] ?? '', ENT_QUOTES) ?>" placeholder="например: Главный специалист / Руководитель отдела">
                </div>

                <div style="margin-top:16px;">
                    <?= \App\Core\AdminUi::imageField('photo_url', $member['photo'] ?? '', [
                        'label' => 'Фотография сотрудника',
                        'file' => 'photo_file',
                        'hint' => 'Рекомендуемый размер: портрет 400x500px.',
                    ]) ?>
                </div>
            </div>

            <!-- Блок 2: Контакты и социальные сети -->
            <div class="form-card" style="border:1px solid var(--admin-border,#e2e8f0);border-radius:12px;padding:24px;margin-bottom:24px;background:var(--admin-card-bg,#ffffff);box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <?= \App\Core\AdminUi::cardHeader('2. Контакты и социальные сети', 'send', '#0d9488') ?>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;" class="form-grid-2col">
                    <div class="form-field">
                        <label style="font-weight:600;">Электронная почта (Email)</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($member['email'] ?? '', ENT_QUOTES) ?>" placeholder="karimov@organization.uz">
                    </div>
                    <div class="form-field">
                        <label style="font-weight:600;">Телефон</label>
                        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($member['phone'] ?? '', ENT_QUOTES) ?>" placeholder="+998 90 123-45-67">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="form-grid-2col">
                    <?php foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'telegram' => 'Telegram', 'linkedin' => 'LinkedIn', 'whatsapp' => 'WhatsApp'] as $key => $label): ?>
                        <div class="form-field">
                            <label style="font-weight:600;"><?= $label ?></label>
                            <input type="text" id="social_<?= $key ?>" name="social_<?= $key ?>" value="<?= htmlspecialchars($socials[$key] ?? '', ENT_QUOTES) ?>" placeholder="Ссылка или username">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Блок 3: Переводы на другие языки сайта -->
            <?php if (!empty($translationLangs)): ?>
                <div class="form-card" style="border:1px solid var(--admin-border,#e2e8f0);border-radius:12px;padding:24px;margin-bottom:24px;background:var(--admin-card-bg,#ffffff);box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <?= \App\Core\AdminUi::cardHeader('3. Переводы на другие языки', 'globe', 'var(--admin-violet)') ?>

                    <?php foreach ($translationLangs as $lang): ?>
                        <?php
                        $code = (string) $lang['code'];
                        $tr = $translations[$code] ?? [];
                        ?>
                        <div style="border:1px solid var(--admin-border,#e2e8f0);border-radius:10px;padding:18px;margin-bottom:14px;background:var(--admin-surface-soft,#f8fafc);">
                            <h4 style="margin:0 0 14px;font-size:1.05rem;font-weight:700;color:var(--admin-accent,#2563eb);display:flex;align-items:center;gap:8px;">
                                <?= \App\Core\AdminUi::icon('globe') ?> <?= htmlspecialchars($lang['name'], ENT_QUOTES) ?> (<?= strtoupper($code) ?>)
                            </h4>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;" class="form-grid-2col">
                                <div class="form-field">
                                    <label style="font-weight:600;">ФИО / Имя (<?= strtoupper($code) ?>)</label>
                                    <input type="text" name="translations[<?= $code ?>][name]" value="<?= htmlspecialchars($tr['name'] ?? '', ENT_QUOTES) ?>" placeholder="Перевод имени на <?= htmlspecialchars($lang['name'], ENT_QUOTES) ?>">
                                </div>
                                <div class="form-field">
                                    <label style="font-weight:600;">Должность (<?= strtoupper($code) ?>)</label>
                                    <input type="text" name="translations[<?= $code ?>][position]" value="<?= htmlspecialchars($tr['position'] ?? '', ENT_QUOTES) ?>" placeholder="Перевод должности на <?= htmlspecialchars($lang['name'], ENT_QUOTES) ?>">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Правая колонка параметров публикации -->
        <aside class="entry-side">
            <div class="form-card" style="border:1px solid var(--admin-border,#e2e8f0);border-radius:12px;padding:20px;margin-bottom:20px;background:var(--admin-card-bg,#ffffff);box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="margin-top:0;font-size:1.1rem;font-weight:700;">Параметры публикации</h3>
                <div class="form-grid">
                    <div class="form-field">
                        <label for="status" style="font-weight:600;">Статус</label>
                        <select id="status" name="status">
                            <option value="published" <?= ($member['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Опубликовано</option>
                            <option value="draft" <?= ($member['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Черновик</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="sort_order" style="font-weight:600;">Порядок сортировки</label>
                        <input type="number" id="sort_order" name="sort_order" value="<?= (int) ($member['sort_order'] ?? 0) ?>">
                        <span class="form-hint">Чем меньше число, тем выше в списке.</span>
                    </div>
                </div>

                <div class="form-actions form-actions--sticky" style="margin-top:18px;">
                    <button type="submit" class="btn btn--primary"><?= \App\Core\AdminUi::icon('save') ?>Сохранить</button>
                    <a href="/admin/team" class="btn">Отмена</a>
                </div>
            </div>
        </aside>
    </div>
</form>
<?php require __DIR__ . '/../layout/footer.php'; ?>
