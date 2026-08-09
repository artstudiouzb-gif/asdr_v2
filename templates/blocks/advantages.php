<?php
/** @var array $data */
$title = $data['title'] ?? '';
$items = $data['items'] ?? [];
$variant = in_array($data['variant'] ?? 'grid', ['grid', 'indexed', 'band'], true) ? (string) $data['variant'] : 'grid';

if ($variant !== 'band') {
    // До пяти карточек — один ряд: пять направлений идут пятёркой, как на
    // макете. Дальше колонки подбираются так, чтобы в последнем ряду не
    // осталась одинокая карточка, а карточки хвоста растягиваются на всю
    // ширину — иначе справа зияет пустая ячейка.
    $count = count($items);
    $columns = $count <= 5 ? $count : \App\Core\GridBalance::columnsFor($count);
    $templateCss = \App\Core\GridBalance::css(
        $blockId,
        '.block-advantages__grid',
        '.block-advantages__item',
        $count,
        $columns
    );
}
?>
<?php if ($variant === 'band'): ?>
<div class="block-featband">
    <?php if ($title !== ''): ?><h2 class="block-featband__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
    <?php if (empty($items)): ?>
        <p class="block-featband__empty"><?= htmlspecialchars(t('Элементы ещё не добавлены.'), ENT_QUOTES) ?></p>
    <?php else: ?>
        <div class="featband">
            <?php foreach ($items as $item): ?>
                <div class="featband__item">
                    <?php if (!empty($item['icon_svg'])): ?><span class="featband__icon" aria-hidden="true"><?= \App\Core\Icon::render($item['icon_svg'], 28) ?></span><?php endif; ?>
                    <span class="featband__name"><?= htmlspecialchars((string) ($item['title'] ?? ''), ENT_QUOTES) ?></span>
                    <?php if (!empty($item['text'])): ?><span class="featband__text"><?= htmlspecialchars((string) $item['text'], ENT_QUOTES) ?></span><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="block-advantages block-advantages--<?= htmlspecialchars($variant, ENT_QUOTES) ?>">
    <?php if ($title !== ''): ?><h2 class="block-advantages__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
    <div class="block-advantages__grid">
        <?php foreach ($items as $index => $item): ?>
            <div class="block-advantages__item">
                <?php if ($variant === 'indexed'): ?><span class="block-advantages__index" aria-hidden="true"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><?php endif; ?>
                <?php if (!empty($item['icon_svg'])): ?>
                    <div class="block-advantages__icon block-advantages__icon--svg"><?= \App\Core\Icon::render($item['icon_svg'], 32) ?></div>
                <?php endif; ?>
                <h3><?= htmlspecialchars($item['title'] ?? '', ENT_QUOTES) ?></h3>
                <p><?= htmlspecialchars($item['text'] ?? '', ENT_QUOTES) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
