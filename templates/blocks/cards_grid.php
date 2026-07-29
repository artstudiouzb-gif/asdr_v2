<?php
/** @var array $data */
$title = $data['title'] ?? '';
$allText = trim((string) ($data['all_text'] ?? ''));
$allUrl = trim((string) ($data['all_url'] ?? ''));
$cols = (int) ($data['columns'] ?? 5);
$items = $data['items'] ?? [];
$cardBg = preg_match('/^#[0-9a-f]{6}$/i', (string) ($data['card_bg'] ?? ''))
    ? (string) $data['card_bg']
    : '';
$cardsText = preg_match('/^#[0-9a-f]{6}$/i', (string) ($data['text_color'] ?? ''))
    ? (string) $data['text_color']
    : '';
$cardStyle = ($cardBg !== '' ? '--card-bg:' . $cardBg . ';' : '')
    . ($cardsText !== '' ? '--cards-text:' . $cardsText . ';' : '');
$cardsCols = max(2, min(5, $cols));
$templateCss = '#block-' . $blockId . ' .block-cards{--cards-cols:' . $cardsCols . ';' . $cardStyle . '}';
$cardClasses = $cardBg !== '' ? ' block-cards--custom-bg' : '';
$cardClasses .= $cardsText !== '' ? ' block-cards--custom-text' : '';
?>
<div class="block-cards<?= $cardClasses ?>">
    <?php if ($title !== '' || ($allText !== '' && $allUrl !== '')): ?>
        <div class="section-head">
            <?php if ($title !== ''): ?>
                <h2 class="section-head__title">
                    <?= htmlspecialchars($title, ENT_QUOTES) ?>
                </h2>
            <?php endif; ?>
            <?php if ($allText !== '' && $allUrl !== ''): ?>
                <a class="section-head__all" href="<?= htmlspecialchars($allUrl, ENT_QUOTES) ?>">
                    <?= htmlspecialchars($allText, ENT_QUOTES) ?> →
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if (empty($items)): ?>
        <p class="block-cards__empty"><?= htmlspecialchars(t('Пункты ещё не добавлены.'), ENT_QUOTES) ?></p>
    <?php else: ?>
        <div class="cards-grid">
            <?php foreach ($items as $item): ?>
                <?php $url = trim((string) ($item['url'] ?? '')); ?>
                <?php if ($url !== ''): ?>
                    <a class="feature-card" href="<?= htmlspecialchars($url, ENT_QUOTES) ?>">
                <?php else: ?>
                    <article class="feature-card">
                <?php endif; ?>
                    <?php if (!empty($item['icon_svg'])): ?>
                        <span class="feature-card__icon" aria-hidden="true">
                            <?= \App\Core\Icon::render($item['icon_svg'], 32) ?>
                        </span>
                    <?php endif; ?>
                    <h3 class="feature-card__title">
                        <?= htmlspecialchars((string) $item['title'], ENT_QUOTES) ?>
                    </h3>
                    <?php if (!empty($item['text'])): ?>
                        <p class="feature-card__text">
                            <?= htmlspecialchars((string) $item['text'], ENT_QUOTES) ?>
                        </p>
                    <?php endif; ?>
                <?php if ($url !== ''): ?>
                    </a>
                <?php else: ?>
                    </article>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
