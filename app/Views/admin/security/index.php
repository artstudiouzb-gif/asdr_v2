<?php
use App\Core\AdminUi;

$pageTitle = 'Безопасность';
$activeNav = 'security';
require __DIR__ . '/../layout/header.php';
?>

<p class="admin-subtitle">Мониторинг попыток входа, аудита администраторов и протоколов защиты</p>

<div class="admin-grid-auto admin-status-grid">
    <div class="admin-status-card">
        <h3 class="admin-status-card__label">Двухфакторная аутентификация</h3>
        <div class="admin-status-card__value">
            <?php if (!empty($currentUser["two_factor_secret"]) || !empty($currentUser["telegram_chat_id"])): ?>
                <span class="admin-status--success"><?= AdminUi::icon('lock', 20) ?> Включена (2FA Active)</span>
            <?php else: ?>
                <span class="admin-status--warning"><?= AdminUi::icon('warning', 20) ?> Отключена</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="admin-status-card">
        <h3 class="admin-status-card__label">Текущая сессия</h3>
        <div class="admin-status-card__value">
            Пользователь: <strong><?= htmlspecialchars((string) ($currentUser["username"] ?? "admin"), ENT_QUOTES) ?></strong> (IP: <?= htmlspecialchars($_SERVER["REMOTE_ADDR"] ?? "127.0.0.1", ENT_QUOTES) ?>)
        </div>
    </div>
</div>

<div class="form-card u-inline-1e1a9b09bf">
    <h2>Журнал аудита действий (Audit Logs)</h2>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Время</th>
                    <th>Пользователь ID</th>
                    <th>Действие / Метод</th>
                    <th>URL</th>
                    <th>IP адрес</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($auditLogs)): ?>
                    <tr><td colspan="5" class="admin-empty">Записей пока нет</td></tr>
                <?php else: ?>
                    <?php foreach ($auditLogs as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($log["created_at"] ?? ""), ENT_QUOTES) ?></td>
                            <td>#<?= (int) ($log["user_id"] ?? 0) ?></td>
                            <td><span class="badge badge--info"><?= htmlspecialchars((string) ($log["method"] ?? "POST"), ENT_QUOTES) ?></span></td>
                            <td><code><?= htmlspecialchars((string) ($log["path"] ?? ""), ENT_QUOTES) ?></code></td>
                            <td><?= htmlspecialchars((string) ($log["ip_address"] ?? ""), ENT_QUOTES) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="form-card">
    <h2>События безопасности (Security Log)</h2>
    <div class="admin-code-panel">
        <?php if (empty($securityEvents)): ?>
            <span class="admin-code-panel__empty">Подозрительных инцидентов не зафиксировано.</span>
        <?php else: ?>
            <?php foreach ($securityEvents as $event): ?>
                <div class="admin-code-panel__row"><?= htmlspecialchars($event, ENT_QUOTES) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . "/../layout/footer.php"; ?>
