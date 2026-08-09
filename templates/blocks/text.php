<?php
use App\Core\Icon;

/** @var array $data */
$title = $data['title'] ?? '';
$content = \App\Core\HtmlSanitizer::sanitizeText((string) ($data['content'] ?? ''));
$variant = in_array($data['variant'] ?? 'default', ['default', 'section', 'intro', 'system', 'spotlight'], true)
    ? (string) $data['variant']
    : 'default';
$asideTitle = trim((string) ($data['aside_title'] ?? ''));
$items = is_array($data['items'] ?? null) ? array_values($data['items']) : [];
$quote = trim((string) ($data['quote'] ?? ''));
?>
<div class="block-text block-text--<?= htmlspecialchars($variant, ENT_QUOTES) ?>">
    <?php if ($title !== ''): ?>
        <h2 class="block-text__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2>
    <?php endif; ?>
    <div class="block-text__layout">
        <div class="block-text__content rich-content"><?= $content ?></div>

        <?php if ($variant === 'intro' && $items !== []): ?>
            <ol class="block-text__principles">
                <?php foreach ($items as $item): ?>
                    <li class="block-text__principle">
                        <?php if (!empty($item['icon_svg'])): ?><span class="block-text__principle-icon" aria-hidden="true"><?= Icon::render((string) $item['icon_svg'], 22) ?></span><?php endif; ?>
                        <span><?= htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES) ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php elseif ($variant === 'system' && ($asideTitle !== '' || $items !== [])): ?>
            <aside class="block-text__system"<?= $asideTitle !== '' ? ' aria-labelledby="block-text-system-' . $blockId . '"' : '' ?>>
                <?php if ($asideTitle !== ''): ?><h3 id="block-text-system-<?= $blockId ?>" class="block-text__system-title"><?= htmlspecialchars($asideTitle, ENT_QUOTES) ?></h3><?php endif; ?>
                <?php if ($items !== []): ?>
                    <ul class="block-text__system-list">
                        <?php foreach ($items as $item): ?>
                            <li class="block-text__system-item">
                                <?php if (!empty($item['icon_svg'])): ?><span class="block-text__system-icon" aria-hidden="true"><?= Icon::render((string) $item['icon_svg'], 24) ?></span><?php endif; ?>
                                <span><?= htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </aside>
        <?php elseif ($variant === 'spotlight' && $quote !== ''): ?>
            <blockquote class="block-text__quote"><p><?= nl2br(htmlspecialchars($quote, ENT_QUOTES)) ?></p></blockquote>
        <?php endif; ?>
    </div>
</div>
