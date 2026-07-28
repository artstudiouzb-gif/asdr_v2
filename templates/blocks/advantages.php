<?php
/** @var array $data */
$title = $data['title'] ?? '';
$items = $data['items'] ?? [];
?>
<div class="block-advantages">
    <?php if ($title !== ''): ?><h2 class="block-advantages__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
    <div class="block-advantages__grid">
        <?php foreach ($items as $item): ?>
            <div class="block-advantages__item">
                <?php if (!empty($item['icon_svg'])): ?>
                    <?php // SVG уже очищен санитайзером при сохранении (группа 4.3). ?>
                    <div class="block-advantages__icon block-advantages__icon--svg"><?= $item['icon_svg'] ?></div>
                <?php elseif (!empty($item['icon'])): ?>
                    <div class="block-advantages__icon"><?= htmlspecialchars($item['icon'], ENT_QUOTES) ?></div>
                <?php endif; ?>
                <h3><?= htmlspecialchars($item['title'] ?? '', ENT_QUOTES) ?></h3>
                <p><?= htmlspecialchars($item['text'] ?? '', ENT_QUOTES) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>
