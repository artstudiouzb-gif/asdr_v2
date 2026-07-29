<?php
/** @var array $data */
$items = $data['items'] ?? [];
?>
<?php if (!empty($items)): ?>
<nav class="block-anchornav" aria-label="<?= htmlspecialchars(t('Разделы страницы'), ENT_QUOTES) ?>">
    <?php foreach ($items as $item): ?>
        <a class="block-anchornav__link" href="<?= htmlspecialchars((string) ($item['url'] ?? '#'), ENT_QUOTES) ?>"><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES) ?></a>
    <?php endforeach; ?>
</nav>
<?php endif; ?>
