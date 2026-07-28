<?php
/** @var array $data */
$title = trim((string) ($data['title'] ?? ''));
$image = trim((string) ($data['image'] ?? ''));
$embedUrl = trim((string) ($data['embed_url'] ?? ''));
$cardTitle = trim((string) ($data['card_title'] ?? ''));
$address = trim((string) ($data['address'] ?? ''));
$btnText = trim((string) ($data['button_text'] ?? ''));
$btnUrl = trim((string) ($data['button_url'] ?? ''));

if (preg_match('/src=["\'](https:\/\/[^"\']+)["\']/i', $embedUrl, $m)) {
    $embedUrl = $m[1];
}

if ($embedUrl !== '' && (str_contains($embedUrl, 'google.com/maps') || str_contains($embedUrl, 'maps.google.com'))) {
    if (str_contains($embedUrl, '/maps/place/')) {
        $lat = null;
        $lng = null;
        if (preg_match('/!3d(-?\d+\.\d+)!4d(-?\d+\.\d+)/', $embedUrl, $m)) {
            $lat = $m[1];
            $lng = $m[2];
        } elseif (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $embedUrl, $m)) {
            $lat = $m[1];
            $lng = $m[2];
        }
        if ($lat !== null && $lng !== null) {
            $embedUrl = 'https://maps.google.com/maps?ll=' . $lat . ',' . $lng . '&z=18&output=embed';
        }
    }
    if (!str_contains($embedUrl, 'output=embed') && !str_contains($embedUrl, '/embed')) {
        $embedUrl .= (str_contains($embedUrl, '?') ? '&' : '?') . 'output=embed';
    }
    if (!str_contains($embedUrl, 'iwloc=')) {
        $embedUrl .= (str_contains($embedUrl, '?') ? '&' : '?') . 'iwloc=near';
    }
}
?>
<div class="block-map">
    <?php if ($title !== ''): ?><h2 class="block-map__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
    <div class="block-map__canvas">
        <?php if ($embedUrl !== ''): ?>
            <iframe class="block-map__frame" src="<?= htmlspecialchars($embedUrl, ENT_QUOTES) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="<?= htmlspecialchars($title !== '' ? $title : 'Карта', ENT_QUOTES) ?>"></iframe>
        <?php elseif ($image !== ''): ?>
            <span class="block-map__image" style="--block-map-image:url('<?= htmlspecialchars($image, ENT_QUOTES) ?>')"></span>
        <?php else: ?>
            <span class="block-map__image block-map__image--empty"></span>
        <?php endif; ?>
        <span class="block-map__pin" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="currentColor" width="52" height="52" class="block-map__pin-svg">
                <path d="M12 22s-7-6-7-11.5a7 7 0 1 1 14 0C19 16 12 22 12 22z"/>
                <circle cx="12" cy="10.3" r="3.5" fill="#fff" class="block-map__pin-dot"/>
            </svg>
            <span class="block-map__pin-ripple"></span>
        </span>
        <?php if ($cardTitle !== '' || $address !== ''): ?>
            <div class="block-map__card">
                <?php if ($cardTitle !== ''): ?><span class="block-map__card-title"><?= htmlspecialchars($cardTitle, ENT_QUOTES) ?></span><?php endif; ?>
                <?php if ($address !== ''): ?><span class="block-map__card-address"><?= nl2br(htmlspecialchars($address, ENT_QUOTES)) ?></span><?php endif; ?>
                <?php if ($btnText !== '' && $btnUrl !== ''): ?>
                    <a class="block-map__card-link" href="<?= htmlspecialchars($btnUrl, ENT_QUOTES) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($btnText, ENT_QUOTES) ?> →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
