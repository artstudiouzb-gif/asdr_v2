<?php
$step = '4';
/** @var string $email */
/** @var string $username */
/** @var bool $emailSent */
require __DIR__ . '/_header.php';
?>
<div style="text-align:center; padding: 12px 0;">
    <div style="width:76px; height:76px; background:#dcfce7; color:#10b981; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; margin-bottom:20px; box-shadow:0 0 0 8px rgba(16, 185, 129, 0.12);">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    </div>

    <h1 class="install-header__title" style="font-size:1.65rem; color:#0f172a; margin-bottom:8px;">Установка успешно завершена!</h1>

    <?php if (!empty($emailSent)): ?>
        <p style="font-size:0.95rem; color:#64748b; margin:0 0 28px 0; max-width:520px; margin-left:auto; margin-right:auto; line-height:1.5;">
            Ваша CMS полностью настроена и готова к работе. Уведомление с параметрами входа успешно отправлено на E-mail: <strong style="color:#0f172a;"><?= htmlspecialchars((string) ($email ?? ''), ENT_QUOTES) ?></strong>
        </p>
    <?php else: ?>
        <p style="font-size:0.95rem; color:#64748b; margin:0 0 24px 0; max-width:540px; margin-left:auto; margin-right:auto; line-height:1.5;">
            Ваша CMS полностью настроена и готова к работе! Ниже указаны параметры созданной учётной записи администратора.
        </p>
    <?php endif; ?>

    <div style="background:#ffffff; border:1px solid #cbd5e1; border-radius:16px; padding:20px 24px; max-width:500px; margin:0 auto 24px auto; text-align:left; box-shadow:0 4px 12px rgba(0,0,0,0.03);">
        <div style="font-size:0.85rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:12px;">
            Параметры входа в систему
        </div>
        <div style="font-size:0.92rem; color:#334155; line-height:1.8;">
            <div><strong>Логин:</strong> <code><?= htmlspecialchars((string) ($username ?? 'admin'), ENT_QUOTES) ?></code></div>
            <div><strong>E-mail:</strong> <code><?= htmlspecialchars((string) ($email ?? ''), ENT_QUOTES) ?></code></div>
            <div><strong>Панель управления:</strong> <code>/admin/login</code></div>
        </div>
        <?php if (empty($emailSent)): ?>
            <div style="margin-top:14px; padding-top:12px; border-top:1px solid #f1f5f9; font-size:0.82rem; color:#64748b; line-height:1.4;">
                💡 <em>Поскольку SMTP-сервер еще не настроен, сообщение не отправлялось на почту. Настроить отправку писем или подключить Telegram-бота можно в панели управления.</em>
            </div>
        <?php endif; ?>
    </div>

    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:16px; padding:16px 20px; max-width:500px; margin:0 auto 32px auto; text-align:left;">
        <div style="display:flex; align-items:flex-start; gap:12px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" style="flex-shrink:0; margin-top:2px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <div style="font-size:0.85rem; color:#475569; line-height:1.5;">
                <strong style="color:#1e293b; display:block; margin-bottom:2px;">Защита установщика:</strong>
                Установщик заблокирован файлом <code>storage/installed.lock</code> и закрыт для повторного использования (403 Forbidden).
            </div>
        </div>
    </div>

    <a href="/admin/login" class="btn btn--primary" style="padding:14px 36px; font-size:1.02rem; border-radius:12px; display:inline-flex; align-items:center; gap:10px;">
        Войти в панель управления
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
    </a>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
