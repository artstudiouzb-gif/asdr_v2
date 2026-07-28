<?php
/** @var array $data */
$title = $data['title'] ?? '';
$items = $data['items'] ?? [];
?>
<div class="block-testimonials">
    <?php if ($title !== ''): ?><h2 class="block-testimonials__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
    <?php // Полоса прокручивается вбок: с клавиатуры до неё нужно добраться
          // и пролистать стрелками, поэтому область фокусируемая и подписана. ?>
    <div class="block-testimonials__track" tabindex="0" role="group"
         aria-label="<?= htmlspecialchars(t('Отзывы — прокрутка вбок'), ENT_QUOTES) ?>">
        <?php foreach ($items as $item): ?>
            <figure class="testimonial">
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
