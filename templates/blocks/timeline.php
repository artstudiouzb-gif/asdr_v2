<?php
/** @var array $data */
$title = $data['title'] ?? '';
$items = $data['items'] ?? [];
$btnText = trim((string) ($data['button_text'] ?? ''));
$btnUrl = trim((string) ($data['button_url'] ?? ''));
$ctaTitle = trim((string) ($data['cta_title'] ?? ''));
$hasCta = $ctaTitle !== '';
$ctaImage = trim((string) ($data['cta_image'] ?? ''));
$ctaBtnText = trim((string) ($data['cta_button_text'] ?? ''));
$ctaBtnUrl = trim((string) ($data['cta_button_url'] ?? ''));
?>
<div class="block-timeline<?= $hasCta ? ' block-timeline--with-cta' : '' ?>">
    <div class="timeline-card">
        <?php if ($title !== ''): ?><h2 class="timeline-card__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
        <?php if (empty($items)): ?>
            <p class="block-timeline__empty">События ещё не добавлены.</p>
        <?php else: ?>
            <ol class="timeline-list">
                <?php foreach ($items as $item): ?>
                    <li class="timeline-item">
                        <span class="timeline-item__year"><?= htmlspecialchars((string) ($item['year'] ?? ''), ENT_QUOTES) ?></span>
                        <span class="timeline-item__text"><?= htmlspecialchars((string) ($item['text'] ?? ''), ENT_QUOTES) ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
        <?php if ($btnText !== '' && $btnUrl !== ''): ?>
            <a class="timeline-card__button" href="<?= htmlspecialchars($btnUrl, ENT_QUOTES) ?>"><?= htmlspecialchars($btnText, ENT_QUOTES) ?></a>
        <?php endif; ?>
    </div>
    <?php if ($hasCta): ?>
        <div class="timeline-cta"<?= $ctaImage !== '' ? ' style="background-image:url(\'' . htmlspecialchars($ctaImage, ENT_QUOTES) . '\')"' : '' ?>>
            <span class="timeline-cta__overlay"></span>
            <div class="timeline-cta__body">
                <h3 class="timeline-cta__title"><?= htmlspecialchars($ctaTitle, ENT_QUOTES) ?></h3>
                <?php if (!empty($data['cta_text'])): ?><p class="timeline-cta__text"><?= htmlspecialchars((string) $data['cta_text'], ENT_QUOTES) ?></p><?php endif; ?>
                <?php if ($ctaBtnText !== '' && $ctaBtnUrl !== ''): ?>
                    <a class="timeline-cta__button" href="<?= htmlspecialchars($ctaBtnUrl, ENT_QUOTES) ?>"><?= htmlspecialchars($ctaBtnText, ENT_QUOTES) ?> →</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
