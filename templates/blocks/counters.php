<?php
/** @var array $data */
$title = $data['title'] ?? '';
$items = $data['items'] ?? [];
$cardBg = preg_match('/^#[0-9a-f]{6}$/i', (string) ($data['card_bg'] ?? '')) ? $data['card_bg'] : '';
$textColor = preg_match('/^#[0-9a-f]{6}$/i', (string) ($data['text_color'] ?? '')) ? $data['text_color'] : '';
$iconSize = max(16, min(64, (int) ($data['icon_size'] ?? 28)));
$iconBoxSize = max(42, $iconSize + 22);
$iconBackground = ($data['icon_bg'] ?? 'on') === 'off' ? 'off' : 'on';
$iconPositionRaw = (string) ($data['icon_position'] ?? 'left');
$iconPosition = in_array($iconPositionRaw, ['top', 'left', 'right', 'center'], true) ? $iconPositionRaw : 'left';
$textAlignRaw = (string) ($data['text_align'] ?? 'left');
$textAlign = in_array($textAlignRaw, ['left', 'center', 'right'], true) ? $textAlignRaw : 'left';
$cstyle = '--counter-icon-size:' . $iconSize . 'px;--counter-icon-box-size:' . $iconBoxSize . 'px;'
    . ($cardBg !== '' ? '--counters-bg:' . $cardBg . ';' : '')
    . ($textColor !== '' ? '--counters-text:' . $textColor . ';' : '');
$templateCss = '#block-' . $blockId . ' .block-counters{' . $cstyle . '}';
$blockClasses = ($iconBackground === 'off' ? ' block-counters--icons-no-bg' : '')
    . ' block-counters--icon-pos-' . $iconPosition
    . ' block-counters--text-align-' . $textAlign;
?>
<div class="block-counters<?= $blockClasses ?>">
    <?php if ($title !== ''): ?><h2 class="block-counters__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
    <div class="block-counters__grid">
        <?php foreach ($items as $item):
            $value = (int) ($item['value'] ?? 0);
        ?>
            <div class="counter">
                <?php if (!empty($item['icon_svg'])): ?>
                    <span class="counter__icon" aria-hidden="true"><?= \App\Core\Icon::render($item['icon_svg'], $iconSize) ?></span>
                <?php endif; ?>
                <div class="counter__body">
                    <div class="counter__num">
                        <span class="counter__value" data-counter-target="<?= $value ?>"><?= $value ?></span>
                        <?php if (!empty($item['suffix'])): ?><span class="counter__suffix"><?= htmlspecialchars($item['suffix'], ENT_QUOTES) ?></span><?php endif; ?>
                    </div>
                    <div class="counter__label"><?= htmlspecialchars($item['label'] ?? '', ENT_QUOTES) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
