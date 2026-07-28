<?php
/** @var array $data */
$title = $data['title'] ?? '';
$text = $data['text'] ?? '';
$image = (string) ($data['image'] ?? '');
$btnText = (string) ($data['button_text'] ?? '');
$btnUrl = (string) ($data['button_url'] ?? '');
if ($btnUrl !== '' && !\App\Core\UrlGuard::isSafeLink($btnUrl)) {
    $btnUrl = '';
}
// Стиль: dark — тёмная подложка поверх фото (по умолчанию),
// light — светлый сплит: текст слева, фото справа с растворением.
$variant = ($data['style'] ?? 'dark') === 'light' ? 'light' : 'dark';
$cvar = static function (string $key, string $var) use ($data): string {
    $v = (string) ($data[$key] ?? '');
    return preg_match('/^#[0-9a-f]{6}$/i', $v) ? $var . ':' . $v . ';' : '';
};
$colorVars = $cvar('bg_color', '--banner-bg') . $cvar('text_color', '--banner-text') . $cvar('button_color', '--banner-btn');
?>
<?php if ($variant === 'light'): ?>
<div class="block-banner block-banner--light"<?= $colorVars !== '' ? ' style="' . $colorVars . '"' : '' ?>>
    <div class="block-banner__inner">
        <?php // Баннер — рекламная врезка, а не заголовок страницы: h2 в обоих
              // вариантах оформления (в тёмном так было и раньше). ?>
        <?php if ($title !== ''): ?><h2 class="block-banner__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
        <?php if ($text !== ''): ?><p class="block-banner__text"><?= htmlspecialchars($text, ENT_QUOTES) ?></p><?php endif; ?>
        <?php if ($btnText !== '' && $btnUrl !== ''): ?>
            <a class="block-banner__button" href="<?= htmlspecialchars($btnUrl, ENT_QUOTES) ?>"><?= htmlspecialchars($btnText, ENT_QUOTES) ?></a>
        <?php endif; ?>
    </div>
    <?php if ($image !== ''): ?><span class="block-banner__photo" style="background-image:url('<?= htmlspecialchars($image, ENT_QUOTES) ?>')"></span><?php endif; ?>
</div>
<?php else: ?>
<?php
$bgImg = $image !== '' ? 'background-image:linear-gradient(rgba(15,23,42,.55),rgba(15,23,42,.55)),url(' . htmlspecialchars($image, ENT_QUOTES) . ');' : '';
$style = ($bgImg . $colorVars) !== '' ? ' style="' . $bgImg . $colorVars . '"' : '';
?>
<div class="block-banner<?= $image !== '' ? ' block-banner--image' : '' ?>"<?= $style ?>>
    <div class="block-banner__inner">
        <?php if ($title !== ''): ?><h2 class="block-banner__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
        <?php if ($text !== ''): ?><p class="block-banner__text"><?= htmlspecialchars($text, ENT_QUOTES) ?></p><?php endif; ?>
        <?php if ($btnText !== '' && $btnUrl !== ''): ?>
            <a class="block-banner__button" href="<?= htmlspecialchars($btnUrl, ENT_QUOTES) ?>"><?= htmlspecialchars($btnText, ENT_QUOTES) ?></a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
