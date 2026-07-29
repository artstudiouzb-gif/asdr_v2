<?php
/** @var array $data */
/** @var int $blockId */
$slides = $data['slides'] ?? [];
?>
<div class="block-slider" data-block-id="<?= (int) $blockId ?>" role="region" aria-roledescription="<?= htmlspecialchars(t('Карусель'), ENT_QUOTES) ?>" aria-label="<?= htmlspecialchars(t('Слайдер изображений'), ENT_QUOTES) ?>" tabindex="0">
    <div class="block-slider__track">
        <?php foreach ($slides as $index => $slide): ?>
            <div class="block-slider__slide<?= $index === 0 ? ' is-active' : '' ?>" role="group" aria-roledescription="<?= htmlspecialchars(t('Слайд'), ENT_QUOTES) ?>" aria-label="<?= ($index + 1) . ' ' . htmlspecialchars(t('из'), ENT_QUOTES) . ' ' . count($slides) ?>" aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>">
                <?php if (!empty($slide['image'])): ?>
                    <?= \App\Core\Media::picture((string) $slide['image'], (string) ($slide['alt'] ?? ''), null, null, '', $index !== 0, '100vw') ?>
                <?php endif; ?>
                <?php if (!empty($slide['caption'])): ?>
                    <div class="block-slider__caption"><?= htmlspecialchars($slide['caption'], ENT_QUOTES) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if (count($slides) > 1): ?>
        <div class="block-slider__nav">
            <button type="button" class="block-slider__prev" aria-label="<?= htmlspecialchars(t('Предыдущий слайд'), ENT_QUOTES) ?>"><?= \App\Core\Icon::render('chevron-left', 22) ?></button>
            <button type="button" class="block-slider__next" aria-label="<?= htmlspecialchars(t('Следующий слайд'), ENT_QUOTES) ?>"><?= \App\Core\Icon::render('chevron-right', 22) ?></button>
        </div>
        <div class="block-slider__dots" role="group" aria-label="<?= htmlspecialchars(t('Выбор слайда'), ENT_QUOTES) ?>">
            <?php foreach ($slides as $index => $_slide): ?>
                <button type="button" class="block-slider__dot<?= $index === 0 ? ' is-active' : '' ?>" data-slide-index="<?= $index ?>" aria-label="<?= htmlspecialchars(t('Перейти к слайду'), ENT_QUOTES) . ' ' . ($index + 1) ?>" aria-current="<?= $index === 0 ? 'true' : 'false' ?>"></button>
            <?php endforeach; ?>
        </div>
        <span class="visually-hidden" data-slider-status aria-live="polite"></span>
    <?php endif; ?>
</div>
