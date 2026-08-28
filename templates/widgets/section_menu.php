<?php

/**
 * Меню текущего раздела: страницы, соседние открытой.
 *
 * Пустой ветки не бывает: страница вне разделов оставляет виджет без вывода
 * вовсе (см. WidgetRenderer::render) — пустая рамка с заголовком хуже, чем её
 * отсутствие.
 *
 * @var array $data
 */
$branch = is_array($data['branch'] ?? null) ? $data['branch'] : null;
if ($branch === null) {
    return;
}
?>
<nav class="widget-secmenu" aria-label="<?= htmlspecialchars($branch['title'], ENT_QUOTES) ?>">
    <a class="widget-secmenu__parent<?= $branch['active'] ? ' is-active' : '' ?>" href="<?= htmlspecialchars($branch['url'], ENT_QUOTES) ?>"<?= $branch['active'] ? ' aria-current="page"' : '' ?>>
        <?= htmlspecialchars($branch['title'], ENT_QUOTES) ?>
    </a>
    <ul class="widget-secmenu__list">
        <?php foreach ($branch['items'] as $item): ?>
            <li>
                <a class="widget-secmenu__link<?= $item['active'] ? ' is-active' : '' ?>" href="<?= htmlspecialchars($item['url'], ENT_QUOTES) ?>"<?= $item['active'] ? ' aria-current="page"' : '' ?>>
                    <?= htmlspecialchars($item['title'], ENT_QUOTES) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
