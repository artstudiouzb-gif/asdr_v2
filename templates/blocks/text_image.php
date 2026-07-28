<?php
/** @var array $data */
$title = trim((string) ($data['title'] ?? ''));
$text = trim((string) ($data['text'] ?? ''));
$image = trim((string) ($data['image'] ?? ''));
$items = $data['items'] ?? [];
?>
<div class="block-textimage<?= $image !== '' ? '' : ' block-textimage--no-image' ?>">
    <div class="textimage__info">
        <?php if ($title !== ''): ?><h2 class="textimage__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
        <?php if ($text !== ''): ?><div class="textimage__text"><?= nl2br(htmlspecialchars($text, ENT_QUOTES)) ?></div><?php endif; ?>
        <?php if (!empty($items)): ?>
            <div class="textimage__features">
                <?php foreach ($items as $item): ?>
                    <span class="textimage__feature">
                        <?php if (!empty($item['icon_svg'])): ?><span class="textimage__feature-icon"><?= $item['icon_svg'] /* очищено при сохранении */ ?></span><?php endif; ?>
                        <span class="textimage__feature-label"><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES) ?></span>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($image !== ''): ?>
        <?= \App\Core\Media::picture($image, $title, null, null, 'textimage__media', true, '(max-width: 800px) 100vw, 50vw') ?>
    <?php endif; ?>
</div>
