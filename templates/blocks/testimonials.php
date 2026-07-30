<?php
use App\Core\Icon;

/** @var array $data */
$title = (string) ($data['title'] ?? '');
$items = is_array($data['items'] ?? null) ? $data['items'] : [];
$carousel = count($items) > 1;
?>
<div class="block-testimonials"<?= $carousel ? ' data-carousel' : '' ?>>
    <div class="section-head">
        <?php if ($title !== ''): ?><h2 class="block-testimonials__title section-head__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
        <?php if ($carousel): ?>
            <span class="carousel-nav" data-carousel-nav hidden>
                <button type="button" class="carousel-nav__btn" data-carousel-prev aria-label="<?= htmlspecialchars(t('Назад'), ENT_QUOTES) ?>"><?= Icon::render('chevron-left', 18) ?></button>
                <span class="carousel-nav__dots" data-carousel-dots role="group" aria-label="<?= htmlspecialchars(t('Выбор слайда'), ENT_QUOTES) ?>"></span>
                <button type="button" class="carousel-nav__btn" data-carousel-next aria-label="<?= htmlspecialchars(t('Вперёд'), ENT_QUOTES) ?>"><?= Icon::render('chevron-right', 18) ?></button>
            </span>
        <?php endif; ?>
    </div>
    <?php // Полоса прокручивается вбок: с клавиатуры до неё нужно добраться
          // и пролистать стрелками, поэтому область фокусируемая и подписана. ?>
    <div class="block-testimonials__track"<?= $carousel ? ' data-carousel-track' : '' ?> tabindex="0" role="group"
         aria-label="<?= htmlspecialchars(t('Отзывы — прокрутка вбок'), ENT_QUOTES) ?>">
        <?php foreach ($items as $item): ?>
            <figure class="testimonial"<?= $carousel ? ' data-carousel-item' : '' ?>>
                <?php if (!empty($item['photo'])): ?>
                    <?= \App\Core\Media::picture((string) $item['photo'], (string) ($item['name'] ?? ''), null, null, 'testimonial__photo', true, '72px') ?>
                <?php endif; ?>
                <blockquote class="testimonial__quote"><?= htmlspecialchars($item['quote'] ?? '', ENT_QUOTES) ?></blockquote>
                <figcaption class="testimonial__author">
                    <span class="testimonial__name"><?= htmlspecialchars($item['name'] ?? '', ENT_QUOTES) ?></span>
                    <?php if (!empty($item['company'])): ?>
                        <span class="testimonial__company"><?= htmlspecialchars($item['company'], ENT_QUOTES) ?></span>
                    <?php endif; ?>
                </figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
</div>
