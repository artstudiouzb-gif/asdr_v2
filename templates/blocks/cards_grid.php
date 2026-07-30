<?php

use App\Core\Icon;
use App\Core\Media;
use App\Core\MediaPosition;

/** @var array $data */
$title = trim((string) ($data['title'] ?? ''));
$allText = trim((string) ($data['all_text'] ?? ''));
$allUrl = trim((string) ($data['all_url'] ?? ''));
$items = is_array($data['items'] ?? null) ? $data['items'] : [];
$variant = in_array($data['variant'] ?? 'icon', ['icon', 'compact', 'image'], true) ? (string) $data['variant'] : 'icon';
$columns = max(2, min(5, (int) ($data['columns'] ?? 5)));
$mediaClasses = MediaPosition::classes($data['image_position'] ?? null, $data['image_position_mobile'] ?? null);
$cardBg = preg_match('/^#[0-9a-f]{6}$/i', (string) ($data['card_bg'] ?? '')) ? (string) $data['card_bg'] : '';
$textColor = preg_match('/^#[0-9a-f]{6}$/i', (string) ($data['text_color'] ?? '')) ? (string) $data['text_color'] : '';
$cardStyle = ($cardBg !== '' ? '--card-bg:' . $cardBg . ';' : '') . ($textColor !== '' ? '--cards-text:' . $textColor . ';' : '');
$templateCss = '#block-' . (int) $blockId . ' .block-cards{--cards-cols:' . $columns . ';' . $cardStyle . '}';
$cardClasses = ($cardBg !== '' ? ' block-cards--custom-bg' : '') . ($textColor !== '' ? ' block-cards--custom-text' : '');
?>
<?php if ($variant === 'image'): ?>
    <?php $carousel = count($items) > 4; ?>
    <div class="block-imgcards" data-carousel>
        <div class="section-head">
            <?php if ($title !== ''): ?><h2 class="section-head__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
            <div class="section-head__tools">
                <?php if ($allText !== '' && $allUrl !== ''): ?><a class="section-head__all" href="<?= htmlspecialchars($allUrl, ENT_QUOTES) ?>"><?= htmlspecialchars($allText, ENT_QUOTES) ?> →</a><?php endif; ?>
                <?php if ($carousel): ?>
                    <span class="carousel-nav">
                        <button type="button" class="carousel-nav__btn" data-carousel-prev aria-label="<?= htmlspecialchars(t('Назад'), ENT_QUOTES) ?>"><?= Icon::render('chevron-left', 18) ?></button>
                        <button type="button" class="carousel-nav__btn" data-carousel-next aria-label="<?= htmlspecialchars(t('Вперёд'), ENT_QUOTES) ?>"><?= Icon::render('chevron-right', 18) ?></button>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($items === []): ?>
            <p class="block-imgcards__empty"><?= htmlspecialchars(t('Карточки ещё не добавлены.'), ENT_QUOTES) ?></p>
        <?php else: ?>
            <div class="imgcards-grid<?= $carousel ? ' imgcards-grid--carousel' : '' ?>" data-carousel-track<?= $carousel ? ' tabindex="0" role="group" aria-label="' . htmlspecialchars(t('Карточки — прокрутка вбок'), ENT_QUOTES) . '"' : '' ?>>
                <?php foreach ($items as $item): ?>
                    <?php $url = trim((string) ($item['url'] ?? '')); $image = trim((string) ($item['image'] ?? '')); $tag = $url !== '' ? 'a' : 'div'; ?>
                    <<?= $tag ?> class="imgcard"<?= $url !== '' ? ' href="' . htmlspecialchars($url, ENT_QUOTES) . '"' : '' ?>>
                        <?php if ($image !== ''): ?>
                            <?= Media::picture($image, (string) ($item['title'] ?? ''), null, null, 'imgcard__media ' . $mediaClasses, true, '(max-width: 700px) 100vw, 25vw') ?>
                        <?php else: ?>
                            <span class="imgcard__media" aria-hidden="true"></span>
                        <?php endif; ?>
                        <span class="imgcard__overlay"></span>
                        <span class="imgcard__body">
                            <span class="imgcard__title"><?= htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES) ?></span>
                            <?php if (!empty($item['text'])): ?><span class="imgcard__text"><?= htmlspecialchars((string) $item['text'], ENT_QUOTES) ?></span><?php endif; ?>
                            <?php if ($url !== ''): ?><span class="imgcard__more"><?= htmlspecialchars(t('Подробнее'), ENT_QUOTES) ?> →</span><?php endif; ?>
                        </span>
                    </<?= $tag ?>>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php elseif ($variant === 'compact'): ?>
    <div class="block-categories">
        <?php if ($title !== ''): ?><h2 class="block-categories__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
        <?php if ($items === []): ?>
            <p class="block-categories__empty"><?= htmlspecialchars(t('Категории ещё не добавлены.'), ENT_QUOTES) ?></p>
        <?php else: ?>
            <div class="cat-grid">
                <?php foreach ($items as $index => $item): ?>
                    <?php $url = trim((string) ($item['url'] ?? '')); $tag = $url !== '' ? 'a' : 'span'; ?>
                    <<?= $tag ?> class="cat-tile<?= $index === 0 ? ' is-active' : '' ?>"<?= $url !== '' ? ' href="' . htmlspecialchars($url, ENT_QUOTES) . '"' : '' ?>>
                        <?php if (!empty($item['icon_svg'])): ?><span class="cat-tile__icon" aria-hidden="true"><?= Icon::render($item['icon_svg'], 28) ?></span><?php endif; ?>
                        <span class="cat-tile__label"><?= htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES) ?></span>
                    </<?= $tag ?>>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="block-cards<?= $cardClasses ?>">
        <?php if ($title !== '' || ($allText !== '' && $allUrl !== '')): ?>
            <div class="section-head">
                <?php if ($title !== ''): ?><h2 class="section-head__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
                <?php if ($allText !== '' && $allUrl !== ''): ?><a class="section-head__all" href="<?= htmlspecialchars($allUrl, ENT_QUOTES) ?>"><?= htmlspecialchars($allText, ENT_QUOTES) ?> →</a><?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if ($items === []): ?>
            <p class="block-cards__empty"><?= htmlspecialchars(t('Пункты ещё не добавлены.'), ENT_QUOTES) ?></p>
        <?php else: ?>
            <div class="cards-grid">
                <?php foreach ($items as $item): ?>
                    <?php $url = trim((string) ($item['url'] ?? '')); $tag = $url !== '' ? 'a' : 'article'; ?>
                    <<?= $tag ?> class="feature-card"<?= $url !== '' ? ' href="' . htmlspecialchars($url, ENT_QUOTES) . '"' : '' ?>>
                        <?php if (!empty($item['icon_svg'])): ?><span class="feature-card__icon" aria-hidden="true"><?= Icon::render($item['icon_svg'], 32) ?></span><?php endif; ?>
                        <h3 class="feature-card__title"><?= htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES) ?></h3>
                        <?php if (!empty($item['text'])): ?><p class="feature-card__text"><?= htmlspecialchars((string) $item['text'], ENT_QUOTES) ?></p><?php endif; ?>
                    </<?= $tag ?>>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
