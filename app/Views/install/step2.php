<?php

use App\Core\Csrf;

/** @var string|null $error */
/** @var array $data */
$step = '2';
require __DIR__ . '/_header.php';
?>
<div class="install-header">
    <h1 class="install-header__title">Подключение к базе данных</h1>
    <p class="install-header__desc">Укажите параметры подключения к вашей СУБД MySQL / MariaDB.</p>
</div>

<div class="install-callout">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
    <div>
        Установщик автоматически проверит подключение и создаст структуру таблиц. Если база данных ещё не создана на сервере, мастер попробует создать её самостоятельно.
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert--error" style="border-radius:12px; margin-bottom:24px; font-weight:500;">
        <?= htmlspecialchars($error, ENT_QUOTES) ?>
    </div>
<?php endif; ?>

<form method="post" action="/install/step2" class="form-grid">
    <?= Csrf::field() ?>

    <div style="display:grid; grid-template-columns: 2fr 1fr; gap: 20px;" class="form-grid-2col">
        <div class="form-field">
            <label for="db_host" style="font-weight:600; margin-bottom:8px; display:block;">Сервер MySQL (Host) <span style="color:#ef4444;">*</span></label>
            <div class="install-input-wrap">
                <svg class="install-input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>
                <input type="text" id="db_host" name="db_host" value="<?= htmlspecialchars($data['host'] ?? 'localhost', ENT_QUOTES) ?>" required placeholder="localhost или 127.0.0.1">
            </div>
        </div>
        <div class="form-field">
            <label for="db_port" style="font-weight:600; margin-bottom:8px; display:block;">Порт <span style="color:#ef4444;">*</span></label>
            <div class="install-input-wrap">
                <svg class="install-input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/></svg>
                <input type="text" id="db_port" name="db_port" value="<?= htmlspecialchars($data['port'] ?? '3306', ENT_QUOTES) ?>" required placeholder="3306">
            </div>
        </div>
    </div>

    <div class="form-field" style="margin-top:20px;">
        <label for="db_name" style="font-weight:600; margin-bottom:8px; display:block;">Имя базы данных <span style="color:#ef4444;">*</span></label>
        <div class="install-input-wrap">
            <svg class="install-input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
            <input type="text" id="db_name" name="db_name" value="<?= htmlspecialchars($data['database'] ?? 'asdr_cms', ENT_QUOTES) ?>" required placeholder="asdr_cms">
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top:20px;" class="form-grid-2col">
        <div class="form-field">
            <label for="db_user" style="font-weight:600; margin-bottom:8px; display:block;">Пользователь БД <span style="color:#ef4444;">*</span></label>
            <div class="install-input-wrap">
                <svg class="install-input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <input type="text" id="db_user" name="db_user" value="<?= htmlspecialchars($data['username'] ?? '', ENT_QUOTES) ?>" required placeholder="root или db_user">
            </div>
        </div>
        <div class="form-field">
            <label for="db_pass" style="font-weight:600; margin-bottom:8px; display:block;">Пароль пользователя БД</label>
            <div class="install-input-wrap">
                <svg class="install-input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input type="password" id="db_pass" name="db_pass" value="" placeholder="Пароль базы данных" style="padding-right:44px !important;">
                <button type="button" id="toggle_db_pass" class="auth-eye-btn" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:none; border:none; color:#94a3b8; cursor:pointer; padding:6px; display:flex; align-items:center; justify-content:center;" title="Показать пароль" aria-label="Показать пароль">
                    <svg class="auth-eye-icon auth-eye-icon--show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="auth-eye-icon auth-eye-icon--hide" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary">
            Проверить подключение и продолжить
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
    </div>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
