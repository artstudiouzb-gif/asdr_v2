<?php
/** @var array $data */
$title = $data['title'] ?? '';
$allText = trim((string) ($data['all_text'] ?? ''));
$allUrl = trim((string) ($data['all_url'] ?? ''));
$cols = (int) ($data['columns'] ?? 5);
$items = $data['items'] ?? [];
$cvar = static function (string $key, string $var) use ($data): string {
    $v = (string) ($data[$key] ?? '');
    return preg_match('/^#[0-9a-f]{6}$/i', $v) ? $var . ':' . $v . ';' : '';
};
$cardStyle = $cvar('card_bg', '--card-bg') . $cvar('text_color', '--cards-text');
?>
<div class="block-cards" style="--cards-cols: <?= max(2, min(5, $cols)) ?>;<?= $cardStyle ?>">
    <?php if ($title !== '' || ($allText !== '' && $allUrl !== '')): ?>
        <div class="section-head">
            <?php if ($title !== ''): ?><h2 class="section-head__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
            <?php if ($allText !== '' && $allUrl !== ''): ?><a class="section-head__all" href="<?= htmlspecialchars($allUrl, ENT_QUOTES) ?>"><?= htmlspecialchars($allText, ENT_QUOTES) ?> →</a><?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if (empty($items)): ?>
        <p class="block-cards__empty">Пункты ещё не добавлены.</p>
    <?php else: ?>
        <div class="cards-grid">
            <?php foreach ($items as $item): ?>
                <?php $url = trim((string) ($item['url'] ?? '')); $tag = $url !== '' ? 'a' : 'div'; ?>
                <<?= $tag ?> class="feature-card"<?= $url !== '' ? ' href="' . htmlspecialchars($url, ENT_QUOTES) . '"' : '' ?>>
                    <?php if (!empty($item['icon_svg'])): ?><span class="feature-card__icon" aria-hidden="true"><?= $item['icon_svg'] ?></span><?php endif; ?>
                    <span class="feature-card__title"><?= htmlspecialchars((string) $item['title'], ENT_QUOTES) ?></span>
                    <?php if (!empty($item['text'])): ?><span class="feature-card__text"><?= htmlspecialchars((string) $item['text'], ENT_QUOTES) ?></span><?php endif; ?>
                </<?= $tag ?>>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
