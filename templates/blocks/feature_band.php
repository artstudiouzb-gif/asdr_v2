<?php
/** @var array $data */
$title = trim((string) ($data['title'] ?? ''));
$items = $data['items'] ?? [];
?>
<div class="block-featband">
    <?php if ($title !== ''): ?><h2 class="block-featband__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
    <?php if (empty($items)): ?>
        <p class="block-featband__empty">Элементы ещё не добавлены.</p>
    <?php else: ?>
        <div class="featband">
            <?php foreach ($items as $item): ?>
                <div class="featband__item">
                    <?php if (!empty($item['icon_svg'])): ?>
                        <span class="featband__icon"><?= $item['icon_svg'] /* очищено при сохранении */ ?></span>
                    <?php endif; ?>
                    <span class="featband__name"><?= htmlspecialchars((string) $item['title'], ENT_QUOTES) ?></span>
                    <?php if (!empty($item['text'])): ?><span class="featband__text"><?= htmlspecialchars((string) $item['text'], ENT_QUOTES) ?></span><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
