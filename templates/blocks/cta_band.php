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
?>
<div class="block-ctaband"<?= $style !== '' ? ' style="' . $style . '"' : '' ?>>
    <div class="ctaband__lead">
        <span class="ctaband__icon">
            <?php if ($iconSvg !== ''): ?>
                <?= $iconSvg /* очищено при сохранении */ ?>
            <?php else: ?>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="40" height="40"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg>
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
