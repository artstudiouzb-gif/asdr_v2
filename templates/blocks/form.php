<?php
/** @var array $data */
/** @var int $blockId */
use App\Core\Csrf;

$form = $data['form'] ?? null;

// Умные фоллбек векторные SVG-иконки для полей публичных форм
$getFieldIcon = static function (string $fieldType, string $fieldName, string $fieldLabel): ?string {
    $t = mb_strtolower(trim($fieldName . ' ' . $fieldLabel));
    if (str_contains($t, 'имя') || str_contains($t, 'name') || str_contains($t, 'fio') || str_contains($t, 'фио') || str_contains($t, 'ism') || str_contains($t, 'familiya') || str_contains($t, 'пользователь')) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="18" height="18"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
    }
    if (str_contains($t, 'mail') || str_contains($t, 'почта') || str_contains($t, 'e-mail') || str_contains($t, 'pochta')) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="18" height="18"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>';
    }
    if (str_contains($t, 'телефон') || str_contains($t, 'telefon') || str_contains($t, 'phone') || str_contains($t, 'tel') || str_contains($t, 'номер')) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="18" height="18"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>';
    }
    if (str_contains($t, 'тема') || str_contains($t, 'subject') || str_contains($t, 'mavzu') || str_contains($t, 'вопрос') || str_contains($t, 'заголовок') || str_contains($t, 'тип')) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="18" height="18"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>';
    }
    if (str_contains($t, 'сообщение') || str_contains($t, 'хабар') || str_contains($t, 'message') || str_contains($t, 'текст') || str_contains($t, 'комментарий') || str_contains($t, 'обращение') || str_contains($t, 'описание')) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="18" height="18"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>';
    }
    if ($fieldType === 'file' || str_contains($t, 'файл') || str_contains($t, 'fayl') || str_contains($t, 'документ')) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="18" height="18"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>';
    }
    if ($fieldType === 'date' || str_contains($t, 'дата') || str_contains($t, 'sana') || str_contains($t, 'date') || str_contains($t, 'день')) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="18" height="18"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
    }
    if ($fieldType === 'select') {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="18" height="18"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>';
    }
    return null;
};
?>
<div class="block-form">
    <?php if ($form === null): ?>
        <p class="block-form__missing">Форма не найдена или ещё не выбрана в настройках блока.</p>
    <?php else: ?>
        <?php if (!empty($form['name'])): ?><h2><?= htmlspecialchars($form['name'], ENT_QUOTES) ?></h2><?php endif; ?>
        <?php $hasFile = false; foreach ($form['fields'] as $f) { if (($f['type'] ?? '') === 'file') { $hasFile = true; break; } } ?>
        <?php 
        $formLayoutClass = ($data['layout'] ?? '1col') === '2col' ? ' block-form__form--2col' : '';
        ?>
        <form method="post" action="/forms/<?= htmlspecialchars($form['slug'], ENT_QUOTES) ?>/submit" class="block-form__form<?= $formLayoutClass ?>"<?= $hasFile ? ' enctype="multipart/form-data"' : '' ?>>
            <?= Csrf::field() ?>
            <?= Csrf::honeypotField() ?>
            <?php foreach ($form['fields'] as $field): ?>
                <?php
                $fieldName = htmlspecialchars($field['name'] ?? '', ENT_QUOTES);
                $fieldLabel = htmlspecialchars($field['label'] ?? '', ENT_QUOTES);
                $fieldType = $field['type'] ?? 'text';
                $required = !empty($field['required']) ? 'required' : '';
                $inputId = 'field-' . $fieldName . '-' . (int) $blockId;
                $iconSvg = $getFieldIcon($fieldType, (string) ($field['name'] ?? ''), (string) ($field['label'] ?? ''));
                // Условная логика (задача 135): поле с условием стартует скрытым,
                // JS показывает его при совпадении значения триггера.
                $cond = $field['condition'] ?? null;
                $condAttrs = '';
                $hiddenStyle = '';
                if (is_array($cond) && !empty($cond['field'])) {
                    $condAttrs = ' data-cond-field="' . htmlspecialchars((string) $cond['field'], ENT_QUOTES)
                        . '" data-cond-value="' . htmlspecialchars((string) ($cond['value'] ?? ''), ENT_QUOTES) . '"';
                    $hiddenStyle = ' style="display:none"';
                }

                $isFullWidth = in_array($fieldType, ['textarea', 'file', 'checkbox_group', 'checkbox'], true);
                $fieldClass = 'block-form__field' . ($isFullWidth ? ' block-form__field--full' : '');
                ?>
                <div class="<?= $fieldClass ?>"<?= $condAttrs ?><?= $hiddenStyle ?>>
                    <?php if ($fieldType !== 'checkbox'): ?>
                        <label for="<?= $inputId ?>"><?= $fieldLabel ?></label>
                    <?php endif; ?>

                    <?php 
                    $lowerInfo = mb_strtolower($fieldName . ' ' . $fieldLabel);
                    $isPhone = ($fieldType === 'tel') || str_contains($lowerInfo, 'телефон') || str_contains($lowerInfo, 'telefon') || str_contains($lowerInfo, 'phone') || str_contains($lowerInfo, 'tel');
                    $isEmail = ($fieldType === 'email') || str_contains($lowerInfo, 'email') || str_contains($lowerInfo, 'mail') || str_contains($lowerInfo, 'почта');
                    ?>

                    <?php if ($fieldType === 'textarea'): ?>
                        <div class="block-form__input-wrapper block-form__input-wrapper--textarea<?= $iconSvg !== null ? ' has-icon' : '' ?>">
                            <?php if ($iconSvg !== null): ?><span class="block-form__input-icon" aria-hidden="true"><?= $iconSvg ?></span><?php endif; ?>
                            <textarea id="<?= $inputId ?>" name="<?= $fieldName ?>" maxlength="2000" data-maxlength="2000" <?= $required ?>></textarea>
                        </div>
                        <div class="block-form__char-counter" style="font-size:12px; color:var(--text-muted, #718096); text-align:right; margin-top:4px;">
                            <span class="char-count">0</span> / 2000 <?= htmlspecialchars(t('символов'), ENT_QUOTES) ?>
                        </div>
                    <?php elseif ($fieldType === 'file'): ?>
                        <div class="block-form__input-wrapper<?= $iconSvg !== null ? ' has-icon' : '' ?>">
                            <?php if ($iconSvg !== null): ?><span class="block-form__input-icon" aria-hidden="true"><?= $iconSvg ?></span><?php endif; ?>
                            <input type="file" id="<?= $inputId ?>" name="<?= $fieldName ?>"
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" <?= $required ?>>
                        </div>
                        <span class="form-hint">PDF, DOC, DOCX, JPG или PNG; до 20 МБ.</span>
                    <?php elseif ($fieldType === 'select'): ?>
                        <?php $opts = array_map('trim', explode(',', (string) ($field['options'] ?? ''))); ?>
                        <div class="block-form__input-wrapper<?= $iconSvg !== null ? ' has-icon' : '' ?>">
                            <?php if ($iconSvg !== null): ?><span class="block-form__input-icon" aria-hidden="true"><?= $iconSvg ?></span><?php endif; ?>
                            <select id="<?= $inputId ?>" name="<?= $fieldName ?>" <?= $required ?>>
                                <option value="">Выберите...</option>
                                <?php foreach ($opts as $opt): ?>
                                    <?php if ($opt !== ''): ?>
                                        <option value="<?= htmlspecialchars($opt, ENT_QUOTES) ?>"><?= htmlspecialchars($opt, ENT_QUOTES) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php elseif ($fieldType === 'radio'): ?>
                        <?php $opts = array_map('trim', explode(',', (string) ($field['options'] ?? ''))); ?>
                        <div class="block-form__radio-group" style="display:flex; flex-direction:column; gap:8px; margin-top:6px;">
                            <?php foreach ($opts as $opt): ?>
                                <?php if ($opt !== ''): ?>
                                    <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-weight:normal;">
                                        <input type="radio" name="<?= $fieldName ?>" value="<?= htmlspecialchars($opt, ENT_QUOTES) ?>" <?= $required ?>>
                                        <?= htmlspecialchars($opt, ENT_QUOTES) ?>
                                    </label>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($fieldType === 'checkbox_group'): ?>
                        <?php $opts = array_map('trim', explode(',', (string) ($field['options'] ?? ''))); ?>
                        <div class="block-form__checkbox-group" style="display:flex; flex-direction:column; gap:8px; margin-top:6px;">
                            <?php foreach ($opts as $opt): ?>
                                <?php if ($opt !== ''): ?>
                                    <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-weight:normal;">
                                        <input type="checkbox" name="<?= $fieldName ?>[]" value="<?= htmlspecialchars($opt, ENT_QUOTES) ?>">
                                        <?= htmlspecialchars($opt, ENT_QUOTES) ?>
                                    </label>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($fieldType === 'checkbox'): ?>
                        <div class="block-form__checkbox-single" style="display:flex; align-items:center; gap:8px; margin-top:6px;">
                            <input type="checkbox" id="<?= $inputId ?>" name="<?= $fieldName ?>" value="1" <?= $required ?>>
                            <label for="<?= $inputId ?>" style="display:inline; cursor:pointer; font-weight:normal;"><?= $fieldLabel ?></label>
                        </div>
                    <?php elseif ($fieldType === 'date'): ?>
                        <div class="block-form__input-wrapper<?= $iconSvg !== null ? ' has-icon' : '' ?>">
                            <?php if ($iconSvg !== null): ?><span class="block-form__input-icon" aria-hidden="true"><?= $iconSvg ?></span><?php endif; ?>
                            <input type="date" id="<?= $inputId ?>" name="<?= $fieldName ?>" <?= $required ?>>
                        </div>
                    <?php elseif ($isPhone): ?>
                        <div class="block-form__input-wrapper<?= $iconSvg !== null ? ' has-icon' : '' ?>">
                            <?php if ($iconSvg !== null): ?><span class="block-form__input-icon" aria-hidden="true"><?= $iconSvg ?></span><?php endif; ?>
                            <input type="tel" id="<?= $inputId ?>" name="<?= $fieldName ?>" data-input-type="phone" inputmode="tel" autocomplete="tel" pattern="^[\+0-9\s\-\(\)]{7,25}$" placeholder="+998 71 123 45 67" <?= $required ?>>
                        </div>
                    <?php elseif ($isEmail): ?>
                        <div class="block-form__input-wrapper<?= $iconSvg !== null ? ' has-icon' : '' ?>">
                            <?php if ($iconSvg !== null): ?><span class="block-form__input-icon" aria-hidden="true"><?= $iconSvg ?></span><?php endif; ?>
                            <input type="email" id="<?= $inputId ?>" name="<?= $fieldName ?>" data-input-type="email" inputmode="email" autocomplete="email" placeholder="example@domain.uz" <?= $required ?>>
                        </div>
                    <?php else: ?>
                        <div class="block-form__input-wrapper<?= $iconSvg !== null ? ' has-icon' : '' ?>">
                            <?php if ($iconSvg !== null): ?><span class="block-form__input-icon" aria-hidden="true"><?= $iconSvg ?></span><?php endif; ?>
                            <input type="<?= htmlspecialchars($fieldType, ENT_QUOTES) ?>" id="<?= $inputId ?>" name="<?= $fieldName ?>" <?= $required ?>>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php if (\App\Core\Captcha::isEnabled()): ?>
                <div class="block-form__captcha">
                    <?= \App\Core\Captcha::field('captcha-' . (int) $blockId) ?>
                </div>
            <?php endif; ?>
            <?php
            // Согласие на обработку персональных данных (глобальная настройка).
            $consentOn = \App\Models\Setting::get('form_consent_enabled', '0') === '1';
            if ($consentOn):
                $consentText = (string) \App\Models\Setting::get('form_consent_text', 'Я согласен на обработку персональных данных');
                // Ссылка на политику конфиденциальности, если задана страница.
                $ppId = (int) \App\Models\Setting::get('privacy_policy_page_id', '');
                $ppUrl = '';
                if ($ppId > 0) {
                    $pp = \App\Models\Page::findById($ppId);
                    if ($pp && ($pp['status'] ?? '') === 'published') {
                        $ppUrl = \App\Core\Locale::url($pp['slug']);
                    }
                }
                $consentId = 'consent-' . (int) $blockId;
                ?>
                <div class="block-form__consent">
                    <input type="checkbox" id="<?= $consentId ?>" name="_consent" value="1" required>
                    <label for="<?= $consentId ?>">
                        <?= htmlspecialchars($consentText, ENT_QUOTES) ?><?php if ($ppUrl !== ''): ?>
                            (<a href="<?= htmlspecialchars($ppUrl, ENT_QUOTES) ?>" target="_blank" rel="noopener">политика конфиденциальности</a>)
                        <?php endif; ?>
                    </label>
                </div>
            <?php endif; ?>
            <button type="submit" class="block-form__submit"><?= htmlspecialchars(t('Отправить'), ENT_QUOTES) ?></button>
        </form>
    <?php endif; ?>
</div>
