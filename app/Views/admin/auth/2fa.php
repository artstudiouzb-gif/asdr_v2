<?php

use App\Core\Csrf;

/** @var string|null $error */
/** @var string|null $notice */
?>
<!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Код подтверждения — <?= htmlspecialchars(\App\Core\AdminBrand::name(), ENT_QUOTES) ?></title>
<link rel="stylesheet" href="<?= htmlspecialchars(\App\Core\Asset::url('/assets/css/admin.css'), ENT_QUOTES) ?>">
<?= \App\Core\AdminBrand::styleTag() ?>
<?= \App\Core\AdminBrand::faviconHtml() ?>
</head>
<body class="auth-page">
<div class="auth-decor">
    <div class="auth-decor__blob auth-decor__blob--1"></div>
    <div class="auth-decor__blob auth-decor__blob--2"></div>
    <div class="auth-decor__grid"></div>
</div>

<a href="/admin/login" class="auth-site-link">
    <?= \App\Core\AdminUi::icon('arrow-left', 16) ?>
    Вернуться к авторизации
</a>

<div class="auth-card">
    <div class="auth-brand">
        <?= \App\Core\AdminBrand::badgeHtml('auth-brand__logoimg', 'auth-brand__logo') ?>
        <span class="auth-brand__name"><?= htmlspecialchars(\App\Core\AdminBrand::name(), ENT_QUOTES) ?></span>
    </div>

    <div class="auth-head">
        <h1>Код из Telegram</h1>
        <p class="auth-sub">Мы отправили 6-значный код безопасности в Telegram от канала <strong>Verification&nbsp;Codes</strong>.</p>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert alert--success"><?= htmlspecialchars($notice, ENT_QUOTES) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert--error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
    <?php endif; ?>

    <form method="post" action="/admin/login/2fa" class="auth-form">
        <?= Csrf::field() ?>
        <div class="auth-field">
            <label for="code">Код подтверждения</label>
            <div class="auth-input-wrap">
                <?= \App\Core\AdminUi::icon('lock', 18, 'auth-input-icon') ?>
                <input type="text" id="code" name="code" inputmode="numeric" pattern="[0-9 ]*" maxlength="7" placeholder="123456" autocomplete="one-time-code" autocapitalize="off" spellcheck="false" required autofocus>
            </div>
        </div>

        <button type="submit" class="auth-submit-btn">
            <span>Подтвердить вход</span>
            <?= \App\Core\AdminUi::icon('arrow-right', 18) ?>
        </button>
    </form>

    <form class="u-inline-3f5e202c68" method="post" action="/admin/login/2fa/resend">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn--small u-inline-8233e9f287">Отправить код повторно</button>
    </form>
</div>
</body>
</html>
