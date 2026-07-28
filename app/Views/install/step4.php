<?php

use App\Core\Csrf;

/** @var string|null $error */
/** @var array $data */
$step = '4';
require __DIR__ . '/_header.php';
?>
<div class="install-header">
    <h1 class="install-header__title">Создание главного администратора</h1>
    <p class="install-header__desc">Укажите учётные данные суперпользователя для доступа к панели управления.</p>
</div>

<div class="install-callout">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
    <div>
        <strong>Безопасность системы:</strong> При первом входе в панель управления система автоматически предложит настроить двухфакторную аутентификацию (2FA) для полной защиты вашей системы.
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert--error u-inline-d7a4b27f07">
        <?= htmlspecialchars($error, ENT_QUOTES) ?>
    </div>
<?php endif; ?>

<form method="post" action="/install/step4" class="form-grid">
    <?= Csrf::field() ?>

    <div class="form-grid-2col u-inline-d4be37b0b9">
        <div class="form-field">
            <label class="u-inline-aa1820469b" for="username">Логин администратора <span class="u-inline-9dd1207e58">*</span></label>
            <div class="install-input-wrap">
                <svg class="install-input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($data['username'] ?? '', ENT_QUOTES) ?>" required autocomplete="username" placeholder="admin">
            </div>
        </div>
        <div class="form-field">
            <label class="u-inline-aa1820469b" for="email">E-mail <span class="u-inline-9dd1207e58">*</span></label>
            <div class="install-input-wrap">
                <svg class="install-input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($data['email'] ?? '', ENT_QUOTES) ?>" required placeholder="admin@example.com">
            </div>
        </div>
    </div>

    <div class="form-field u-inline-9eb125f52f">
        <label class="u-inline-aa1820469b" for="password">Пароль (минимум 10 символов) <span class="u-inline-9dd1207e58">*</span></label>
        <div class="install-input-wrap">
            <svg class="install-input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <input class="u-inline-614afdfeb3" type="password" id="password" name="password" required autocomplete="new-password" placeholder="Надёжный пароль">
            <button type="button" id="toggle_pass" class="auth-eye-btn u-inline-761ba8828b" title="Показать пароль" aria-label="Показать пароль">
                <svg class="auth-eye-icon auth-eye-icon--show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="auth-eye-icon auth-eye-icon--hide u-inline-c8be1ccba6" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
        </div>

        <div class="u-inline-d8a81eac84">
            <div class="u-inline-af9c75294f">
                <div class="u-inline-821081bbf8" id="pass_strength_bar"></div>
            </div>
            <div class="u-inline-3f6331b9d1" id="pass_strength_text">
                Введённый пароль
            </div>
        </div>


    <div class="form-actions u-inline-9eb125f52f">
        <button type="submit" class="btn btn--primary u-inline-5b9236194b">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            Завершить установку
        </button>
    </div>
</form>
<?php require __DIR__ . '/_footer.php'; ?>
