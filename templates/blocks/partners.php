<?php
use App\Core\Icon;

/** @var array $data */
$title = (string) ($data['title'] ?? '');
$items = is_array($data['items'] ?? null) ? $data['items'] : [];
$carousel = count($items) > 1;
$desktopCarousel = count($items) > 6;
?>
<div class="block-partners"<?= $carousel ? ' data-carousel' : '' ?>>
    <div class="section-head">
        <?php if ($title !== ''): ?><h2 class="block-partners__title section-head__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
        <?php if ($carousel): ?>
            <span class="carousel-nav" data-carousel-nav hidden>
                <button type="button" class="carousel-nav__btn" data-carousel-prev aria-label="<?= htmlspecialchars(t('Назад'), ENT_QUOTES) ?>"><?= Icon::render('chevron-left', 18) ?></button>
                <span class="carousel-nav__dots" data-carousel-dots role="group" aria-label="<?= htmlspecialchars(t('Выбор слайда'), ENT_QUOTES) ?>"></span>
                <button type="button" class="carousel-nav__btn" data-carousel-next aria-label="<?= htmlspecialchars(t('Вперёд'), ENT_QUOTES) ?>"><?= Icon::render('chevron-right', 18) ?></button>
            </span>
        <?php endif; ?>
    </div>
    <?php if (!empty($items)): ?>
        <div class="block-partners__grid<?= $desktopCarousel ? ' block-partners__grid--carousel' : '' ?>"<?= $carousel ? ' data-carousel-track tabindex="0" role="group" aria-label="' . htmlspecialchars(t('Партнёры — прокрутка вбок'), ENT_QUOTES) . '"' : '' ?>>
            <?php foreach ($items as $p): ?>
                <?php
                $logo = trim((string) ($p['logo'] ?? ''));
                $nameRaw = (string) ($p['name'] ?? '');
                $name = htmlspecialchars($nameRaw, ENT_QUOTES);
                $url = (string) ($p['url'] ?? '');
                if ($url !== '' && !\App\Core\UrlGuard::isSafeLink($url)) {
                    $url = '';
                }
                // Без логотипа показываем название текстом: пустой <img src="">
                // — это битая картинка, а имя партнёра было видно только во
                // всплывающей подсказке.
                $img = $logo !== ''
                    ? \App\Core\Media::picture($logo, $nameRaw, null, null, 'block-partners__logo', true, '180px')
                    : '<span class="block-partners__name">' . ($name !== '' ? $name : htmlspecialchars(t('Партнёр'), ENT_QUOTES)) . '</span>';
                ?>
                <?php if ($url !== ''): ?>
                    <a class="block-partners__item"<?= $carousel ? ' data-carousel-item' : '' ?> href="<?= htmlspecialchars($url, ENT_QUOTES) ?>" target="_blank" rel="noopener" title="<?= $name ?>"><?= $img ?></a>
                <?php else: ?>
                    <span class="block-partners__item"<?= $carousel ? ' data-carousel-item' : '' ?> title="<?= $name ?>"><?= $img ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
