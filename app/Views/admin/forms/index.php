<?php

use App\Core\Csrf;

$pageTitle = 'Формы';
$activeNav = 'forms';
$pageActions = '<a href="/admin/forms/create" class="btn btn--primary">' . \App\Core\AdminUi::icon('plus') . 'Добавить форму</a>';
require __DIR__ . '/../layout/header.php';

/** @var array $items */
?>

<table class="data-table">
    <thead>
        <tr>
            <th>Название</th>
            <th>Slug</th>
            <th>Заявки</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($items)): ?>
            <tr><td colspan="4" class="data-table__empty">Форм пока нет.<br><a href="/admin/forms/create" class="btn btn--small">+ Создать первую форму</a></td></tr>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['name'], ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($item['slug'], ENT_QUOTES) ?></td>
                <td>
                    <a href="/admin/forms/<?= (int) $item['id'] ?>/submissions">
                        Просмотреть<?= $item['unread'] > 0 ? ' (' . (int) $item['unread'] . ' новых)' : '' ?>
                    </a>
                </td>
                <td class="data-table__actions">
                    <a class="btn btn--small" href="/admin/forms/<?= (int) $item['id'] ?>/edit"><?= \App\Core\AdminUi::icon('edit') ?>Редактировать</a>
                    <form method="post" action="/admin/forms/<?= (int) $item['id'] ?>/delete" data-confirm="Удалить форму «<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>» вместе со всеми заявками?">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn--small btn--danger"><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../layout/footer.php'; ?>
