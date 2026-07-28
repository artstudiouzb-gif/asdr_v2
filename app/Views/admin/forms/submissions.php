<?php

use App\Core\Csrf;

/** @var array $form */
/** @var array $submissions */
$form = $form ?? [];
$submissions = $submissions ?? [];
$pageTitle = 'Заявки: ' . $form['name'];
$activeNav = 'forms';
require __DIR__ . '/../layout/header.php';
?>
<a href="/admin/forms" class="btn btn--small" style="margin-bottom:16px;">&larr; Все формы</a>

<table class="data-table">
    <thead>
        <tr>
            <th>Дата</th>
            <th>Данные</th>
            <th>IP</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($submissions)): ?>
            <tr><td colspan="4" class="data-table__empty">Заявок пока нет.</td></tr>
        <?php endif; ?>
        <?php foreach ($submissions as $submission): ?>
            <tr>
                <td><?= htmlspecialchars($submission['created_at'], ENT_QUOTES) ?></td>
                <td>
                    <?php foreach ($submission['data'] as $key => $value): ?>
                        <div><strong><?= htmlspecialchars((string) $key, ENT_QUOTES) ?>:</strong> <?= htmlspecialchars((string) $value, ENT_QUOTES) ?></div>
                    <?php endforeach; ?>
                </td>
                <td><?= htmlspecialchars($submission['ip_address'] ?? '', ENT_QUOTES) ?></td>
                <td class="data-table__actions">
                    <form method="post" action="/admin/forms/submissions/<?= (int) $submission['id'] ?>/delete" data-confirm="Удалить заявку?">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn--small btn--danger"><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../layout/footer.php'; ?>
