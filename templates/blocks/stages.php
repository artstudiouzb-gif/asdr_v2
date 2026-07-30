<?php
use App\Core\Icon;

/** @var array $data */
$title = trim((string) ($data['title'] ?? ''));
$allText = trim((string) ($data['all_text'] ?? ''));
$allUrl = trim((string) ($data['all_url'] ?? ''));
$items = is_array($data['items'] ?? null) ? $data['items'] : [];
$carousel = count($items) > 1;
$desktopCarousel = count($items) > 5;
$statusLabels = ['done' => t('Завершён'), 'active' => t('В процессе'), 'planned' => t('Запланирован')];
?>
<div class="block-stages"<?= $carousel ? ' data-carousel' : '' ?>>
    <div class="section-head">
        <?php if ($title !== ''): ?><h2 class="section-head__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
        <div class="section-head__tools">
            <?php if ($allText !== '' && $allUrl !== ''): ?><a class="section-head__all" href="<?= htmlspecialchars($allUrl, ENT_QUOTES) ?>"><?= htmlspecialchars($allText, ENT_QUOTES) ?> →</a><?php endif; ?>
            <?php if ($carousel): ?>
                <span class="carousel-nav" data-carousel-nav hidden>
                    <button type="button" class="carousel-nav__btn" data-carousel-prev aria-label="<?= htmlspecialchars(t('Назад'), ENT_QUOTES) ?>"><?= Icon::render('chevron-left', 18) ?></button>
                    <span class="carousel-nav__dots" data-carousel-dots role="group" aria-label="<?= htmlspecialchars(t('Выбор слайда'), ENT_QUOTES) ?>"></span>
                    <button type="button" class="carousel-nav__btn" data-carousel-next aria-label="<?= htmlspecialchars(t('Вперёд'), ENT_QUOTES) ?>"><?= Icon::render('chevron-right', 18) ?></button>
                </span>
            <?php endif; ?>
        </div>
    </div>
    <?php if (empty($items)): ?>
        <p class="block-stages__empty"><?= htmlspecialchars(t('Этапы ещё не добавлены.'), ENT_QUOTES) ?></p>
    <?php else: ?>
        <ol class="stages<?= $desktopCarousel ? ' stages--carousel' : '' ?>"<?= $carousel ? ' data-carousel-track tabindex="0" role="group" aria-label="' . htmlspecialchars(t('Этапы — прокрутка вбок'), ENT_QUOTES) . '"' : '' ?>>
            <?php foreach ($items as $item): ?>
                <?php $status = in_array($item['status'] ?? '', ['done', 'active', 'planned'], true) ? $item['status'] : 'planned'; ?>
                <li class="stage stage--<?= $status ?>"<?= $carousel ? ' data-carousel-item' : '' ?>>
                    <span class="stage__dot"></span>
                    <span class="stage__year"><?= htmlspecialchars((string) ($item['year'] ?? ''), ENT_QUOTES) ?></span>
                    <?php if (!empty($item['stage'])): ?><span class="stage__label"><?= htmlspecialchars((string) $item['stage'], ENT_QUOTES) ?></span><?php endif; ?>
                    <?php if (!empty($item['title'])): ?><span class="stage__title"><?= htmlspecialchars((string) $item['title'], ENT_QUOTES) ?></span><?php endif; ?>
                    <?php if (!empty($item['text'])): ?><span class="stage__text"><?= htmlspecialchars((string) $item['text'], ENT_QUOTES) ?></span><?php endif; ?>
                    <span class="stage__status"><?= htmlspecialchars((string) ($item['status_text'] ?? '') !== '' ? (string) $item['status_text'] : $statusLabels[$status], ENT_QUOTES) ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</div>
