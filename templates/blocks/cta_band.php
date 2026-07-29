<?php
/** @var array $data */
$title = trim((string) ($data['title'] ?? ''));
$text = trim((string) ($data['text'] ?? ''));
$iconSvg = trim((string) ($data['icon_svg'] ?? ''));
$btnText = trim((string) ($data['button_text'] ?? ''));
$btnUrl = trim((string) ($data['button_url'] ?? ''));
$cvar = static function (string $key, string $var) use ($data): string {
    $v = (string) ($data[$key] ?? '');
    return preg_match('/^#[0-9a-f]{6}$/i', $v) ? $var . ':' . $v . ';' : '';
};
$style = $cvar('bg_color', '--ctaband-bg') . $cvar('text_color', '--ctaband-text') . $cvar('button_color', '--ctaband-btn');
$templateCss = $style !== '' ? '#block-' . $blockId . ' .block-ctaband{' . $style . '}' : '';
?>
<div class="block-ctaband">
    <div class="ctaband__lead">
        <span class="ctaband__icon">
            <?php if ($iconSvg !== ''): ?>
                <?= \App\Core\Icon::render($iconSvg, 40) ?>
            <?php else: ?>
                <?= \App\Core\Icon::render('mail', 40, 'cta-band__icon-svg', 1.5) ?>
            <?php endif; ?>
        </span>
        <span class="ctaband__body">
            <?php if ($title !== ''): ?><span class="ctaband__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></span><?php endif; ?>
            <?php if ($text !== ''): ?><span class="ctaband__text"><?= htmlspecialchars($text, ENT_QUOTES) ?></span><?php endif; ?>
        </span>
    </div>
    <?php if ($btnText !== '' && $btnUrl !== ''): ?>
        <a class="ctaband__button" href="<?= htmlspecialchars($btnUrl, ENT_QUOTES) ?>"><?= htmlspecialchars($btnText, ENT_QUOTES) ?> →</a>
    <?php endif; ?>
</div>
