<?php
/** @var array $data */
$title = $data['title'] ?? '';
$text = $data['text'] ?? '';
$buttonText = $data['button_text'] ?? '';
$buttonUrl = $data['button_url'] ?? '#';
$cvar = static function (string $key, string $var) use ($data): string {
    $v = (string) ($data[$key] ?? '');
    return preg_match('/^#[0-9a-f]{6}$/i', $v) ? $var . ':' . $v . ';' : '';
};
$style = $cvar('bg_color', '--cta-bg') . $cvar('text_color', '--cta-text') . $cvar('button_color', '--cta-btn');
?>
<div class="block-cta"<?= $style !== '' ? ' style="' . $style . '"' : '' ?>>
    <?php if ($title !== ''): ?><h2><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
    <?php if ($text !== ''): ?><p><?= htmlspecialchars($text, ENT_QUOTES) ?></p><?php endif; ?>
    <?php if ($buttonText !== ''): ?>
        <a class="block-cta__button" href="<?= htmlspecialchars($buttonUrl, ENT_QUOTES) ?>"><?= htmlspecialchars($buttonText, ENT_QUOTES) ?></a>
    <?php endif; ?>
</div>
