<?php
/** @var array $data */
$title = trim((string) ($data['title'] ?? ''));
$rawText = trim((string) ($data['text'] ?? ''));
// Новые записи содержат безопасный HTML из WYSIWYG. Старые записи могли
// хранить обычный текст, поэтому для них сохраняем прежнее отображение
// переносов строк. В обоих случаях вывод проходит через allowlist.
$text = \App\Core\HtmlSanitizer::sanitizeText($rawText);
$textIsPlain = $rawText !== '' && $rawText === strip_tags($rawText);
if ($textIsPlain) {
    $text = nl2br($text);
}
$image = trim((string) ($data['image'] ?? ''));
$items = $data['items'] ?? [];
$imageSide = ($data['image_side'] ?? 'right') === 'left' ? 'left' : 'right';
$mediaClasses = \App\Core\MediaPosition::classes($data['image_position'] ?? null, $data['image_position_mobile'] ?? null);
?>
<div class="block-textimage block-textimage--image-<?= $imageSide ?><?= $image !== '' ? '' : ' block-textimage--no-image' ?>">
    <div class="textimage__info">
        <?php if ($title !== ''): ?><h2 class="textimage__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
        <?php if ($text !== ''): ?><div class="textimage__text rich-content"><?= $text ?></div><?php endif; ?>
        <?php if (!empty($items)): ?>
            <div class="textimage__features">
                <?php foreach ($items as $item): ?>
                    <span class="textimage__feature">
                        <?php if (!empty($item['icon_svg'])): ?><span class="textimage__feature-icon"><?= \App\Core\Icon::render($item['icon_svg'], 22) ?></span><?php endif; ?>
                        <span class="textimage__feature-label"><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES) ?></span>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($image !== ''): ?>
        <div class="textimage__visual">
            <?= \App\Core\Media::picture($image, $title, null, null, 'textimage__media ' . $mediaClasses, true, '(max-width: 800px) 100vw, 50vw') ?>
        </div>
    <?php endif; ?>
</div>
