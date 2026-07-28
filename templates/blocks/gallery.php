<?php
/** @var array $data */
$title = $data['title'] ?? '';
$images = $data['images'] ?? [];
?>
<div class="block-gallery">
    <?php if ($title !== ''): ?><h2><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
    <div class="block-gallery__grid">
        <?php foreach ($images as $image): ?>
            <?php
            if (!is_array($image)) {
                continue;
            }
            $url = trim((string) ($image['url'] ?? ''));
            if (!\App\Core\UrlGuard::isSafeMedia($url)) {
                continue;
            }
            ?>
            <a class="block-gallery__item" href="<?= htmlspecialchars($url, ENT_QUOTES) ?>" target="_blank" rel="noopener">
                <?= \App\Core\Media::picture($url, (string) ($image['caption'] ?? ''), null, null, '', true, '(max-width: 600px) 100vw, 25vw') ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
