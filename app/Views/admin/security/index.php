<?php

use App\Core\AdminUi;
use App\Models\AuditLog;

/** @var array<int, array<string, mixed>> $authEvents */
/** @var array{total:int,successful:int,failed:int,locked:int,delivery_failed:int} $authSummary */
/** @var array<int, string> $technicalEvents */
/** @var array<string, mixed>|null $currentUser */
/** @var int $activeSessions */
/** @var bool $botLinked */
/** @var bool $gatewayLinked */

$pageTitle = 'Безопасность';
$activeNav = 'security';
require __DIR__ . '/../layout/header.php';

$twoFactorActive = $botLinked || $gatewayLinked;
$channels = [];
if ($botLinked) {
    $channels[] = 'Telegram-бот';
}
if ($gatewayLinked) {
    $channels[] = 'Telegram Gateway';
}
$riskEvents = (int) $authSummary['failed'] + (int) $authSummary['locked'];
?>

<div class="u-inline-f94566b02a">
    <a class="btn btn--small btn--primary" href="/admin/security">Центр безопасности</a>
    <a class="btn btn--small" href="/admin/audit">Действия администраторов</a>
    <a class="btn btn--small" href="/admin/audit/errors">Ошибки сайта</a>
</div>

<p class="admin-subtitle">Состояние защиты входа, активные сессии и понятная история событий аутентификации.</p>

<div class="admin-grid-auto admin-status-grid">
    <div class="admin-status-card">
        <h3 class="admin-status-card__label">Защита входа</h3>
        <div class="admin-status-card__value">
            <?php if ($twoFactorActive): ?>
                <span class="admin-status--success"><?= AdminUi::icon('lock', 20) ?> 2FA включена</span>
            <?php else: ?>
                <span class="admin-status--warning"><?= AdminUi::icon('warning', 20) ?> Требуется настройка</span>
            <?php endif; ?>
        </div>
        <p class="form-hint u-inline-1da9facb4d">
            <?= $twoFactorActive
                ? 'Канал: ' . htmlspecialchars(implode(' + ', $channels), ENT_QUOTES)
                : 'Нет рабочего канала доставки одноразового кода.' ?>
        </p>
    </div>

    <div class="admin-status-card">
        <h3 class="admin-status-card__label">Активные сессии</h3>
        <div class="admin-status-card__value"><?= (int) $activeSessions ?></div>
        <p class="form-hint u-inline-1da9facb4d">Устройства текущего администратора</p>
    </div>

    <div class="admin-status-card">
        <h3 class="admin-status-card__label">Неудачные попытки за 24 часа</h3>
        <div class="admin-status-card__value">
            <span class="<?= $riskEvents > 0 ? 'admin-status--warning' : 'admin-status--success' ?>">
                <?= (int) $riskEvents ?>
            </span>
        </div>
        <p class="form-hint u-inline-1da9facb4d">
            Ошибок кода: <?= (int) $authSummary['failed'] ?> · блокировок: <?= (int) $authSummary['locked'] ?>
        </p>
    </div>

    <div class="admin-status-card">
        <h3 class="admin-status-card__label">Последний успешный вход</h3>
        <div class="admin-status-card__value">
            <?= !empty($currentUser['last_login_at'])
                ? htmlspecialchars((string) $currentUser['last_login_at'], ENT_QUOTES)
                : 'Нет данных' ?>
        </div>
        <p class="form-hint u-inline-1da9facb4d">
            <?= htmlspecialchars((string) ($currentUser['username'] ?? '—'), ENT_QUOTES) ?>
            · IP <?= htmlspecialchars((string) ($_SERVER['REMOTE_ADDR'] ?? '—'), ENT_QUOTES) ?>
        </p>
    </div>
</div>

<div class="form-card u-inline-1e1a9b09bf">
    <div class="admin-card-header">
        <div class="admin-card-header__title">
            <span class="admin-card-header__icon"><?= AdminUi::icon('shield', 20) ?></span>
            <h3>Управление защитой</h3>
        </div>
    </div>
    <p class="form-hint">В профиле можно подключить канал кодов входа, сменить пароль, проверить устройства и завершить другие сессии.</p>
    <div class="form-actions">
        <a href="/admin/profile" class="btn btn--primary">Профиль и сессии</a>
        <a href="/admin/telegram" class="btn">Настройки Telegram</a>
    </div>
</div>

<div class="form-card u-inline-1e1a9b09bf">
    <div class="admin-card-header">
        <div class="admin-card-header__title">
            <span class="admin-card-header__icon"><?= AdminUi::icon('list', 20) ?></span>
            <h3>Последние события входа</h3>
        </div>
        <a class="btn btn--small" href="/admin/audit?method=AUTH">Открыть весь журнал</a>
    </div>

    <p class="form-hint">
        За 24 часа: успешных подтверждений — <strong><?= (int) $authSummary['successful'] ?></strong>,
        неудачных — <strong><?= (int) $authSummary['failed'] ?></strong>,
        ошибок доставки кода — <strong><?= (int) $authSummary['delivery_failed'] ?></strong>.
    </p>

    <?php if ($authEvents === []): ?>
        <p class="admin-empty">Событий входа пока нет.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Когда</th><th>Событие</th><th>Пользователь</th><th>IP</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($authEvents as $event): ?>
                        <?php $meta = AuditLog::authEventMeta((string) ($event['path'] ?? '')); ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($event['created_at'] ?? ''), ENT_QUOTES) ?></td>
                            <td>
                                <span class="status-badge status-badge--<?= htmlspecialchars($meta['tone'], ENT_QUOTES) ?>">
                                    <?= htmlspecialchars($meta['label'], ENT_QUOTES) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars((string) ($event['username'] ?: 'Не определён'), ENT_QUOTES) ?></td>
                            <td><?= htmlspecialchars((string) ($event['ip'] ?: '—'), ENT_QUOTES) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="form-card">
    <details>
        <summary><strong>Технический security.log</strong></summary>
        <p class="form-hint">Служебные сообщения защиты. Для ежедневной работы используйте таблицу событий выше.</p>
        <div class="admin-code-panel">
            <?php if ($technicalEvents === []): ?>
                <span class="admin-code-panel__empty">Технических сообщений пока нет.</span>
            <?php else: ?>
                <?php foreach ($technicalEvents as $event): ?>
                    <div class="admin-code-panel__row"><?= htmlspecialchars($event, ENT_QUOTES) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </details>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
