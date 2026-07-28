<?php

use App\Core\Csrf;

$pageTitle = 'Репозиторий: категории';
$activeNav = 'repository';
require __DIR__ . '/../layout/header.php';

/** @var list<array> $tree */

// Строка таблицы: имя (с отступом у подкатегории), число файлов, действия.
$row = static function (array $cat, bool $isChild): void {
    ?>
    <tr>
        <td>
            <?= $isChild ? '<span class="u-inline-99a68ab617">└</span> ' : '' ?>
            <?= $isChild ? '' : '<strong>' ?><?= htmlspecialchars((string) $cat['name'], ENT_QUOTES) ?><?= $isChild ? '' : '</strong>' ?>
        </td>
        <td><?= (int) $cat['files_count'] ?></td>
        <td class="data-table__actions">
            <details class="u-inline-39e79eb52f">
                <summary class="btn btn--small u-inline-5e798cd9db">Переименовать</summary>
                <form method="post" action="/admin/repository/categories/<?= (int) $cat['id'] ?>/rename" class="form-card u-inline-d7a10ee612">
                    <?= Csrf::field() ?>
                    <div class="form-field">
                        <label>Новое название</label>
                        <input type="text" name="name" required maxlength="120" value="<?= htmlspecialchars((string) $cat['name'], ENT_QUOTES) ?>">
                    </div>
                    <button type="submit" class="btn btn--small btn--primary">Сохранить</button>
                </form>
            </details>
            <form method="post" action="/admin/repository/categories/<?= (int) $cat['id'] ?>/delete" data-confirm="Удалить категорию «<?= htmlspecialchars((string) $cat['name'], ENT_QUOTES) ?>»?<?= !$isChild ? ' Её подкатегории тоже удалятся.' : '' ?> Файлы останутся без категории.">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn--small btn--danger"><?= \App\Core\AdminUi::icon('trash') ?>Удалить</button>
            </form>
        </td>
    </tr>
    <?php
};
?>
<div class="u-inline-f1b7a56d35">
    <a href="/admin/repository" class="btn btn--small">Файлы</a>
    <a href="/admin/repository/categories" class="btn btn--small btn--primary">Категории</a>
    <a href="/admin/repository/users" class="btn btn--small">Пользователи портала</a>
</div>
<p class="form-hint">Категории файлового хранилища. Один уровень вложенности: категория → подкатегории. На портале фильтр по корневой категории показывает и файлы её подкатегорий.</p>

<div class="form-card u-inline-2d22144f96">
    <h2 class="u-inline-291b7bbb01">Добавить категорию</h2>
    <form method="post" action="/admin/repository/categories/create" class="form-grid">
        <?= Csrf::field() ?>
        <div class="form-field">
            <label for="name">Название</label>
            <input type="text" id="name" name="name" required maxlength="120" placeholder="напр. Приказы">
        </div>
        <div class="form-field">
            <label for="parent_id">Родительская категория</label>
            <select id="parent_id" name="parent_id">
                <option value="0">— Нет (корневая категория) —</option>
                <?php foreach ($tree as $root): ?>
                    <option value="<?= (int) $root['id'] ?>"><?= htmlspecialchars((string) $root['name'], ENT_QUOTES) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-actions"><button type="submit" class="btn btn--primary">Добавить</button></div>
    </form>
</div>

<table class="data-table">
    <thead>
        <tr><th>Категория</th><th>Файлов</th><th></th></tr>
    </thead>
    <tbody>
        <?php if (empty($tree)): ?>
            <tr><td class="u-inline-0e883e39e4" colspan="3">Категорий пока нет.</td></tr>
        <?php else: ?>
            <?php foreach ($tree as $root): ?>
                <?php $row($root, false); ?>
                <?php foreach ($root['children'] as $child): ?>
                    <?php $row($child, true); ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../layout/footer.php'; ?>
