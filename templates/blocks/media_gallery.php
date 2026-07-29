<?php
/** @var array $data */
$title = $data['title'] ?? '';
$allText = trim((string) ($data['all_text'] ?? ''));
$allUrl = trim((string) ($data['all_url'] ?? ''));
$items = $data['items'] ?? [];

// Разделяем на видео/фото для переключателей.
$hasVideo = false;
$hasPhoto = false;
foreach ($items as $it) {
    if (($it['kind'] ?? 'video') === 'photo') { $hasPhoto = true; } else { $hasVideo = true; }
}
$showTabs = $hasVideo && $hasPhoto;
?>
<div class="block-mediagallery" data-media-gallery>
    <div class="section-head">
        <?php if ($title !== ''): ?><h2 class="section-head__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
        <?php if ($showTabs): ?>
            <div class="media-tabs" role="group" aria-label="Фильтр медиа">
                <button type="button" class="media-tabs__tab is-active" data-media-tab="video" aria-pressed="true">Видео</button>
                <button type="button" class="media-tabs__tab" data-media-tab="photo" aria-pressed="false">Фото</button>
            </div>
        <?php endif; ?>
        <?php if ($allText !== '' && $allUrl !== ''): ?><a class="section-head__all" href="<?= htmlspecialchars($allUrl, ENT_QUOTES) ?>"><?= htmlspecialchars($allText, ENT_QUOTES) ?> →</a><?php endif; ?>
    </div>
    <?php if (empty($items)): ?>
        <p class="block-mediagallery__empty">Материалы ещё не добавлены.</p>
    <?php else: ?>
        <div class="mediagallery-grid">
            <?php foreach ($items as $item): ?>
                <?php
                $url = trim((string) ($item['url'] ?? ''));
                $img = trim((string) ($item['image'] ?? ''));
                $tag = $url !== '' ? 'a' : 'div';
                $duration = trim((string) ($item['meta'] ?? ''));
                $kind = ($item['kind'] ?? 'video') === 'photo' ? 'photo' : 'video';
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
