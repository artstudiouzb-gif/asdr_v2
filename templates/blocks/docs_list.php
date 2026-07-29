<?php
/** @var array $data */
$title = trim((string) ($data['title'] ?? ''));
$allText = trim((string) ($data['all_text'] ?? ''));
$allUrl = trim((string) ($data['all_url'] ?? ''));
$items = $data['items'] ?? [];
$cols = max(1, min(4, (int) ($data['columns'] ?? 4)));
$templateCss = '#block-' . $blockId . ' .docslist-grid{--docs-cols:' . $cols . '}';
?>
<div class="block-docslist">
    <div class="section-head">
        <?php if ($title !== ''): ?><h2 class="section-head__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
        <?php if ($allText !== '' && $allUrl !== ''): ?><a class="section-head__all" href="<?= htmlspecialchars($allUrl, ENT_QUOTES) ?>"><?= htmlspecialchars($allText, ENT_QUOTES) ?> →</a><?php endif; ?>
    </div>
    <?php if (empty($items)): ?>
        <p class="block-docslist__empty"><?= htmlspecialchars(t('Документы ещё не добавлены.'), ENT_QUOTES) ?></p>
    <?php else: ?>
        <div class="docslist-grid">
            <?php foreach ($items as $doc): ?>
                <?php $url = trim((string) ($doc['url'] ?? '')); $tag = $url !== '' ? 'a' : 'div'; ?>
                <<?= $tag ?> class="doc-card"<?= $url !== '' ? ' href="' . htmlspecialchars($url, ENT_QUOTES) . '" download' : '' ?>>
                    <span class="doc-card__icon">
                        <?= \App\Core\Icon::render('file-text', 24, 'doc-card__icon-svg', 1.5) ?>
                    </span>
                    <span class="doc-card__body">
                        <span class="doc-card__title"><?= htmlspecialchars((string) ($doc['title'] ?? ''), ENT_QUOTES) ?></span>
                        <?php if (!empty($doc['meta'])): ?><span class="doc-card__meta"><?= htmlspecialchars((string) $doc['meta'], ENT_QUOTES) ?></span><?php endif; ?>
                    </span>
                    <?php if ($url !== ''): ?>
                        <span class="doc-card__dl">
                            <?= \App\Core\Icon::render('download', 18, 'doc-card__download-icon', 1.8) ?>
                        </span>
                    <?php endif; ?>
                </<?= $tag ?>>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
