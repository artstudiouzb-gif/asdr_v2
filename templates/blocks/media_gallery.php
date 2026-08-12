<?php
/** @var array $data */
$title = $data['title'] ?? '';
$allText = trim((string) ($data['all_text'] ?? ''));
$allUrl = trim((string) ($data['all_url'] ?? ''));
$items = $data['items'] ?? [];

// Разделяем на видео/фото для переключателей.
$videoCount = 0;
$photoCount = 0;
foreach ($items as $it) {
    if (($it['kind'] ?? 'video') === 'photo') { $photoCount++; } else { $videoCount++; }
}
$hasVideo = $videoCount > 0;
$hasPhoto = $photoCount > 0;
$showTabs = $hasVideo && $hasPhoto;
$initialCount = $hasVideo ? $videoCount : $photoCount;
$initialColumns = max(1, min(4, $initialCount));
?>
<div class="block-mediagallery" data-media-gallery>
    <div class="section-head block-mediagallery__head">
        <?php if ($title !== ''): ?><h2 class="section-head__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
        <?php if ($showTabs): ?>
            <div class="media-tabs" role="group" aria-label="<?= htmlspecialchars(t('Фильтр медиа'), ENT_QUOTES) ?>">
                <button type="button" class="media-tabs__tab is-active" data-media-tab="video" aria-pressed="true"><span class="media-tabs__tab-text"><?= htmlspecialchars(t('Видео'), ENT_QUOTES) ?></span></button>
                <button type="button" class="media-tabs__tab" data-media-tab="photo" aria-pressed="false"><span class="media-tabs__tab-text"><?= htmlspecialchars(t('Фото'), ENT_QUOTES) ?></span></button>
            </div>
        <?php endif; ?>
        <?php if ($allText !== '' && $allUrl !== ''): ?><a class="section-head__all" href="<?= htmlspecialchars($allUrl, ENT_QUOTES) ?>"><?= htmlspecialchars($allText, ENT_QUOTES) ?> →</a><?php endif; ?>
    </div>
    <?php if (empty($items)): ?>
        <p class="block-mediagallery__empty"><?= htmlspecialchars(t('Материалы ещё не добавлены.'), ENT_QUOTES) ?></p>
    <?php else: ?>
        <div class="mediagallery-grid mediagallery-grid--cols-<?= $initialColumns ?>" data-media-grid>
            <?php foreach ($items as $item): ?>
                <?php
                $url = trim((string) ($item['url'] ?? ''));
                $img = trim((string) ($item['image'] ?? ''));
                $duration = trim((string) ($item['meta'] ?? ''));
                $kind = ($item['kind'] ?? 'video') === 'photo' ? 'photo' : 'video';
                if ($img !== '' && !\App\Core\UrlGuard::isSafeMedia($img)) {
                    $img = '';
                }
                if ($url !== '' && !\App\Core\UrlGuard::isSafeLink($url)) {
                    $url = '';
                }
                // Ручная фотография без отдельной ссылки открывает сам файл
                // в общем lightbox — отдельный тип «Галерея» больше не нужен.
                if ($kind === 'photo' && $url === '' && $img !== '') {
                    $url = $img;
                }
                $tag = $url !== '' ? 'a' : 'div';
                ?>
                <<?= $tag ?> class="mediacard mediacard--<?= $kind ?>" data-media-kind="<?= $kind ?>"<?= $url !== '' ? ' href="' . htmlspecialchars($url, ENT_QUOTES) . '"' : '' ?>>
                    <span class="mediacard__media">
                        <?php if ($img !== ''): ?><?= \App\Core\Media::picture($img, (string) $item['title'], null, null, 'mediacard__img', true, '(max-width: 700px) 100vw, 33vw') ?><?php endif; ?>
                        <span class="mediacard__play mediacard__play--<?= $kind ?>" aria-hidden="true">
                            <?php if ($kind === 'photo'): ?>
                                <?= \App\Core\Icon::render('photo', 24) ?>
                            <?php else: ?>
                                <?= \App\Core\Icon::render('player-play', 24) ?>
                            <?php endif; ?>
                        </span>
                        <?php if ($duration !== ''): ?><span class="mediacard__duration"><?= htmlspecialchars($duration, ENT_QUOTES) ?></span><?php endif; ?>
                    </span>
                    <span class="mediacard__title"><?= htmlspecialchars((string) $item['title'], ENT_QUOTES) ?></span>
                    <?php if (!empty($item['text'])): ?><span class="mediacard__date"><?= htmlspecialchars((string) $item['text'], ENT_QUOTES) ?></span><?php endif; ?>
                </<?= $tag ?>>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
