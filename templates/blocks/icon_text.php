<?php

use App\Core\Icon;

/**
 * «Иконка и текст»: карточки с иконкой и парами «подпись → значение».
 * Типовое применение — полезные телефоны и короткие реквизиты.
 *
 * Строки задаются по одной на пару: «Подпись | Значение». Строка без черты
 * выводится одной подписью — так карточка не ломается на неполном вводе.
 *
 * @var array $data
 * @var int $blockId
 */
$title = trim((string) ($data['title'] ?? ''));
$description = trim((string) ($data['description'] ?? ''));
$columns = max(1, min(4, (int) ($data['columns'] ?? 3)));
$items = array_values(array_filter(
    (array) ($data['items'] ?? []),
    static fn (array $item): bool => trim((string) ($item['rows'] ?? '')) !== ''
        || trim((string) ($item['icon_svg'] ?? '')) !== ''
));
$esc = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES);

// Число колонок уходит в scoped CSS: инлайн-стили в блоках запрещены тестами.
$templateCss = '';
if ($columns !== 3) {
    $templateCss = '@media (min-width:721px){#block-' . (int) $blockId
        . ' .icon-text__grid{grid-template-columns:repeat(' . $columns . ',minmax(0,1fr));}}';
}
?>
<div class="block-icon-text">
    <?php if ($title !== '' || $description !== ''): ?>
        <div class="section-head section-head--stacked">
            <?php if ($title !== ''): ?><h2 class="section-head__title"><?= $esc($title) ?></h2><?php endif; ?>
            <?php if ($description !== ''): ?><p class="section-head__description"><?= $esc($description) ?></p><?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($items === []): ?>
        <p class="block-icon-text__empty"><?= $esc(t('Карточки ещё не добавлены.')) ?></p>
    <?php else: ?>
        <div class="icon-text__grid">
            <?php foreach ($items as $index => $item): ?>
                <?php
                $icon = trim((string) ($item['icon_svg'] ?? ''));
                $color = \App\Core\NewsBadge::normalizeColor($item['icon_color'] ?? '');
                $rows = preg_split('/\R/u', (string) ($item['rows'] ?? '')) ?: [];
                // Цвет иконки — переменной в scoped CSS блока, а не в атрибуте
                // style: инлайн-стили в блоках стережёт тест.
                if ($color !== '') {
                    $templateCss .= '#block-' . (int) $blockId . ' .icon-text__card--c' . $index
                        . '{--icon-text-accent:' . $color . ';}';
                }
                ?>
                <div class="icon-text__card<?= $color !== '' ? ' icon-text__card--c' . $index : '' ?>">
                    <?php if ($icon !== ''): ?>
                        <span class="icon-text__icon" aria-hidden="true"><?= Icon::render($icon, 26) ?></span>
                    <?php endif; ?>
                    <div class="icon-text__body">
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $row = trim((string) $row);
                            if ($row === '') {
                                continue;
                            }
                            $parts = array_map('trim', explode('|', $row, 2));
                            ?>
                            <p class="icon-text__label"><?= $esc($parts[0]) ?></p>
                            <?php if (($parts[1] ?? '') !== ''): ?>
                                <p class="icon-text__value"><?= $esc($parts[1]) ?></p>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
