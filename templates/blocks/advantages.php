<?php
/** @var array $data */
$title = $data['title'] ?? '';
$items = $data['items'] ?? [];
$variant = ($data['variant'] ?? 'grid') === 'band' ? 'band' : 'grid';

if ($variant === 'grid') {
    // Число колонок подбирается так, чтобы в последнем ряду не оставалась
    // одинокая карточка: пять преимуществ при четырёх колонках давали 4+1 —
    // пятая уезжала вниз в пустой ряд. Здесь пятёрка ложится как 3+2.
    $count = count($items);
    $columns = $count > 0 && $count <= 3 ? $count : 4;
    if ($count > 3) {
        foreach ([4, 3, 2] as $candidate) {
            if ($count % $candidate !== 1) {
                $columns = $candidate;
                break;
            }
        }
    }
    $templateCss = '#block-' . $blockId . ' .block-advantages__grid{--adv-cols:' . max(1, $columns) . '}';
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
<div class="block-advantages">
    <?php if ($title !== ''): ?><h2 class="block-advantages__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
    <div class="block-advantages__grid">
        <?php foreach ($items as $item): ?>
            <div class="block-advantages__item">
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
