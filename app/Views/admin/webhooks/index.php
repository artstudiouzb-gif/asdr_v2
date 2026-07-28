<?php

use App\Core\Csrf;

$pageTitle = 'Вебхуки';
$activeNav = 'webhooks';
require __DIR__ . '/../layout/header.php';

/** @var array $items */
/** @var array $deliveries */
/** @var array $events */
?>
<div class="form-card">
    <h2 style="margin-top:0;">Добавить вебхук</h2>
    <p class="form-hint">Внешний URL получит <code>POST</code> с JSON события. Если задан секрет —
       тело подписывается заголовком <code>X-ArtStudio-Signature: sha256=…</code> (HMAC).
       Доставка асинхронна воркером с ретраями.</p>
    <form method="post" action="/admin/webhooks/create" class="form-grid">
        <?= Csrf::field() ?>
        <div class="form-field">
            <label for="event_type">Событие</label>
            <select id="event_type" name="event_type">
                <?php foreach ($events as $ev): ?>
                    <option value="<?= htmlspecialchars($ev, ENT_QUOTES) ?>"><?= htmlspecialchars($ev, ENT_QUOTES) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-field">
            <label for="url">URL получателя</label>
            <input type="url" id="url" name="url" placeholder="https://example.com/hook" required>
        </div>
        <div class="form-field">
            <label for="secret">Секрет (HMAC, необязательно)</label>
            <input type="text" id="secret" name="secret" autocomplete="off">
        </div>
        <div class="form-field form-field--checkbox">
            <input type="checkbox" id="is_active" name="is_active" value="1" checked>
            <label for="is_active">Активен</label>
        </div>
        <div class="form-actions"><button type="submit" class="btn btn--primary">Добавить</button></div>
    </form>
</div>

<table class="data-table" style="margin-top:20px;">
    <thead><tr><th>Событие</th><th>URL</th><th>Секрет</th><th>Активен</th><th></th></tr></thead>
    <tbody>
        <?php if (empty($items)): ?><tr><td colspan="5" class="data-table__empty">Вебхуков пока нет.</td></tr><?php endif; ?>
        <?php foreach ($items as $w): ?>
            <tr>
                <td><code><?= htmlspecialchars((string) $w['event_type'], ENT_QUOTES) ?></code></td>
                <td style="max-width:280px;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars((string) $w['url'], ENT_QUOTES) ?></td>
                <td><?= !empty($w['secret']) ? '✓' : '—' ?></td>
                <td><?= (int) $w['is_active'] === 1 ? 'да' : 'нет' ?></td>
                <td class="data-table__actions">
                    <form method="post" action="/admin/webhooks/<?= (int) $w['id'] ?>/delete" data-confirm="Удалить вебхук?">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn--small btn--danger"><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="form-card" style="margin-top:24px;">
    <h2 style="margin-top:0;">Журнал доставок</h2>
    <?php $sf = $statusFilter ?? ''; ?>
    <div class="filter-tabs" style="margin-bottom:12px;">
        <a class="btn btn--small <?= $sf === '' ? 'btn--primary' : '' ?>" href="/admin/webhooks">Все</a>
        <a class="btn btn--small <?= $sf === 'failed' ? 'btn--primary' : '' ?>" href="/admin/webhooks?status=failed">Проблемные (failed)</a>
        <a class="btn btn--small <?= $sf === 'pending' ? 'btn--primary' : '' ?>" href="/admin/webhooks?status=pending">В очереди</a>
        <a class="btn btn--small <?= $sf === 'sent' ? 'btn--primary' : '' ?>" href="/admin/webhooks?status=sent">Доставленные</a>
    </div>
    <table class="data-table">
        <thead><tr><th>Время</th><th>Событие</th><th>URL</th><th>Статус</th><th>HTTP</th><th>Попыток</th><th>Ошибка</th></tr></thead>
        <tbody>
            <?php if (empty($deliveries)): ?><tr><td colspan="7" class="data-table__empty">Доставок пока нет.</td></tr><?php endif; ?>
            <?php foreach ($deliveries as $d): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $d['created_at'], ENT_QUOTES) ?></td>
                    <td><code><?= htmlspecialchars((string) $d['event_type'], ENT_QUOTES) ?></code></td>
                    <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars((string) ($d['webhook_url'] ?? ''), ENT_QUOTES) ?></td>
                    <td><span class="badge badge--<?= $d['status'] === 'sent' ? 'published' : 'draft' ?>"><?= htmlspecialchars((string) $d['status'], ENT_QUOTES) ?></span></td>
                    <td><?= $d['response_code'] !== null ? (int) $d['response_code'] : '—' ?></td>
                    <td><?= (int) $d['attempts'] ?></td>
                    <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars((string) ($d['last_error'] ?? ''), ENT_QUOTES) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../layout/footer.php'; ?>
