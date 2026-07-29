<?php
$step = '4';
/** @var string $email */
/** @var string $username */
/** @var bool $emailSent */
require __DIR__ . '/_header.php';
?>
<div class="u-inline-dc16df27e2">
    <div class="u-inline-c44cea58cf">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    </div>

    <h1 class="install-header__title u-inline-e40283b01f">Установка успешно завершена!</h1>

    <?php if (!empty($emailSent)): ?>
        <p class="u-inline-59f69369f1">
            Ваша CMS полностью настроена и готова к работе. Уведомление с параметрами входа успешно отправлено на E-mail: <strong class="u-inline-13daa00158"><?= htmlspecialchars((string) ($email ?? ''), ENT_QUOTES) ?></strong>
        </p>
    <?php else: ?>
        <p class="u-inline-ff1c0e35f5">
            Ваша CMS полностью настроена и готова к работе! Ниже указаны параметры созданной учётной записи администратора.
        </p>
    <?php endif; ?>

    <div class="u-inline-c0715b171a">
        <div class="u-inline-e1652ceeff">
            Параметры входа в систему
        </div>
        <div class="u-inline-6e20fb40dc">
            <div><strong>Логин:</strong> <code><?= htmlspecialchars((string) ($username ?? 'admin'), ENT_QUOTES) ?></code></div>
            <div><strong>E-mail:</strong> <code><?= htmlspecialchars((string) ($email ?? ''), ENT_QUOTES) ?></code></div>
            <div><strong>Панель управления:</strong> <code>/admin/login</code></div>
        </div>
        <?php if (empty($emailSent)): ?>
            <div class="u-inline-87ff6778ab">
                💡 <em>Поскольку SMTP-сервер еще не настроен, сообщение не отправлялось на почту. Настроить отправку писем или подключить Telegram-бота можно в панели управления.</em>
            </div>
        <?php endif; ?>
    </div>

    <div class="u-inline-fdc4c2f742">
        <div class="u-inline-d850ac3a7e">
            <svg class="u-inline-6abc078b20" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <div class="u-inline-c6e9746c81">
                <strong class="u-inline-c0811a5932">Защита установщика:</strong>
                Установщик заблокирован файлом <code>storage/installed.lock</code> и закрыт для повторного использования (403 Forbidden).
            </div>
        </div>
    </div>

    <a href="/admin/login" class="btn btn--primary u-inline-8ca649dc3b">
        Войти в панель управления
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
    </a>
</div>
<?php require __DIR__ . '/_footer.php'; ?>