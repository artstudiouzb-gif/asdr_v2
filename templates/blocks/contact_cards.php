<?php
/** @var array $data */
$title = $data['title'] ?? '';
$items = $data['items'] ?? [];

// Фоллбек иконки по заголовку карточки
$getFallbackIcon = static function (string $cardTitle): ?string {
    $t = mb_strtolower(trim($cardTitle));
    if (str_contains($t, 'адрес') || str_contains($t, 'manzil') || str_contains($t, 'address') || str_contains($t, 'где')) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="22" height="22"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 1 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>';
    }
    if (str_contains($t, 'телефон') || str_contains($t, 'telefon') || str_contains($t, 'phone') || str_contains($t, 'связь') || str_contains($t, 'call') || str_contains($t, 'номер')) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="22" height="22"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>';
    }
    if (str_contains($t, 'mail') || str_contains($t, 'почта') || str_contains($t, 'e-mail') || str_contains($t, 'pochta') || str_contains($t, 'написать')) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="22" height="22"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>';
    }
    if (str_contains($t, 'часы') || str_contains($t, 'время') || str_contains($t, 'график') || str_contains($t, 'vaqt') || str_contains($t, 'hour') || str_contains($t, 'приём') || str_contains($t, 'режим')) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="22" height="22"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
    }
    return null;
};
?>
<div class="block-contact-cards">
    <?php if ($title !== ''): ?>
        <h2 class="block-contact-cards__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2>
    <?php endif; ?>
    <?php if (empty($items)): ?>
        <p class="block-contact-cards__empty">Контактные карточки ещё не добавлены.</p>
    <?php else: ?>
        <div class="contact-cards">
            <?php foreach ($items as $item): ?>
                <?php $iconSvg = !empty($item['icon_svg']) ? (string) $item['icon_svg'] : $getFallbackIcon((string) ($item['title'] ?? '')); ?>
                <div class="contact-card">
                    <?php if ($iconSvg !== null && $iconSvg !== ''): ?>
                        <span class="contact-card__icon" aria-hidden="true"><?= $iconSvg ?></span>
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
