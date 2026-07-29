<?php

use App\Core\Csrf;

/** @var string|null $error */
/** @var array $languages */
/** @var array $timezones */
$step = '3';
require __DIR__ . '/_header.php';
?>
<div class="install-header">
    <h1 class="install-header__title">Настройки веб-портала</h1>
    <p class="install-header__desc">Укажите основную информацию о вашей организации и языковые параметры.</p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert--error u-inline-d7a4b27f07">
        <?= htmlspecialchars($error, ENT_QUOTES) ?>
    </div>
<?php endif; ?>

<form method="post" action="/install/step3" class="form-grid">
    <?= Csrf::field() ?>

    <div class="form-field">
        <label class="u-inline-aa1820469b" for="site_name">Название организации / портала <span class="u-inline-9dd1207e58">*</span></label>
        <div class="install-input-wrap">
            <svg class="install-input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 7v14M21 7v14M6 21V11m4 10V11m4 10V11m4 10V11M12 3L2 7h20l-10-4z"/></svg>
            <input class="u-inline-1cef6a84e1" type="text" id="site_name" name="site_name" value="Агентство стратегического развития и реформ" required>
        </div>
    </div>

    <div class="form-field u-inline-9eb125f52f">
        <label class="u-inline-aa1820469b" for="site_description">Краткое описание (SEO мета-описание)</label>
        <div class="install-input-wrap">
            <svg class="install-input-icon u-inline-3c3e97944f" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            <textarea id="site_description" name="site_description" rows="2" placeholder="Официальный веб-портал государственного органа"></textarea>
        </div>
    </div>

    <div class="form-grid-2col u-inline-e6e3fab9a5">
        <div class="form-field">
            <label class="u-inline-aa1820469b" for="timezone">Часовой пояс</label>
            <div class="install-input-wrap">
                <svg class="install-input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <select id="timezone" name="timezone">
                    <?php foreach ($timezones as $tz): ?>
                        <option value="<?= htmlspecialchars($tz, ENT_QUOTES) ?>" <?= $tz === 'Asia/Tashkent' ? 'selected' : '' ?>><?= htmlspecialchars($tz, ENT_QUOTES) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-field">
            <label class="u-inline-aa1820469b" for="default_language">Основной язык сайта</label>
            <div class="install-input-wrap">
                <svg class="install-input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                <select id="default_language" name="default_language">
                    <?php foreach ($languages as $lang): ?>
                        <option value="<?= htmlspecialchars($lang['code'], ENT_QUOTES) ?>" <?= $lang['is_default'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($lang['name'], ENT_QUOTES) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary">
            Сохранить и продолжить
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
    </div>
</form>
<?php require __DIR__ . '/_footer.php'; ?>