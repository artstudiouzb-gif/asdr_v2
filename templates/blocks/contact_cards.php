<?php
/** @var array $data */
$title = $data['title'] ?? '';
$items = $data['items'] ?? [];

// Фоллбек иконки по заголовку карточки
$getFallbackIcon = static function (string $cardTitle): ?string {
    $t = mb_strtolower(trim($cardTitle));
    if (str_contains($t, 'адрес') || str_contains($t, 'manzil') || str_contains($t, 'address') || str_contains($t, 'где')) {
        return 'map-pin';
    }
    if (str_contains($t, 'телефон') || str_contains($t, 'telefon') || str_contains($t, 'phone') || str_contains($t, 'связь') || str_contains($t, 'call') || str_contains($t, 'номер')) {
        return 'phone';
    }
    if (str_contains($t, 'mail') || str_contains($t, 'почта') || str_contains($t, 'e-mail') || str_contains($t, 'pochta') || str_contains($t, 'написать')) {
        return 'mail';
    }
    if (str_contains($t, 'часы') || str_contains($t, 'время') || str_contains($t, 'график') || str_contains($t, 'vaqt') || str_contains($t, 'hour') || str_contains($t, 'приём') || str_contains($t, 'режим')) {
        return 'clock';
    }
    return null;
};
?>
<div class="block-contact-cards">
    <?php if ($title !== ''): ?>
        <h2 class="block-contact-cards__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2>
    <?php endif; ?>
    <?php if (empty($items)): ?>
        <p class="block-contact-cards__empty"><?= htmlspecialchars(t('Контактные карточки ещё не добавлены.'), ENT_QUOTES) ?></p>
    <?php else: ?>
        <div class="contact-cards">
            <?php foreach ($items as $item): ?>
                <?php $iconSvg = !empty($item['icon_svg']) ? (string) $item['icon_svg'] : $getFallbackIcon((string) ($item['title'] ?? '')); ?>
                <div class="contact-card">
                    <?php if ($iconSvg !== null && $iconSvg !== ''): ?>
                        <span class="contact-card__icon" aria-hidden="true"><?= \App\Core\Icon::render($iconSvg, 22, 'contact-card__icon-svg', 1.8) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($item['title'])): ?>
                        <div class="contact-card__title"><?= htmlspecialchars((string) $item['title'], ENT_QUOTES) ?></div>
                    <?php endif; ?>
                    <?php foreach (preg_split('/\R/', (string) ($item['lines'] ?? '')) ?: [] as $line): ?>
                        <?php $line = trim($line); if ($line === '') { continue; } ?>
                        <p class="contact-card__line"><?= htmlspecialchars($line, ENT_QUOTES) ?></p>
                    <?php endforeach; ?>
                    <?php $linkUrl = trim((string) ($item['link_url'] ?? '')); ?>
                    <?php if ($linkUrl !== '' && \App\Core\UrlGuard::isSafeLink($linkUrl) && !empty($item['link_text'])): ?>
                        <a class="contact-card__link" href="<?= htmlspecialchars($linkUrl, ENT_QUOTES) ?>"><?= htmlspecialchars((string) $item['link_text'], ENT_QUOTES) ?> →</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
