<?php
/** @var array $data */
$title = $data['title'] ?? '';
$items = $data['items'] ?? [];
$cardBg = preg_match('/^#[0-9a-f]{6}$/i', (string) ($data['card_bg'] ?? '')) ? $data['card_bg'] : '';
$textColor = preg_match('/^#[0-9a-f]{6}$/i', (string) ($data['text_color'] ?? '')) ? $data['text_color'] : '';
$cstyle = ($cardBg !== '' ? '--counters-bg:' . $cardBg . ';' : '') . ($textColor !== '' ? '--counters-text:' . $textColor . ';' : '');
?>
<div class="block-counters"<?= $cstyle !== '' ? ' style="' . $cstyle . '"' : '' ?>>
    <?php if ($title !== ''): ?><h2 class="block-counters__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2><?php endif; ?>
    <div class="block-counters__grid">
        <?php foreach ($items as $item):
            $value = (int) ($item['value'] ?? 0);
        ?>
            <div class="counter">
                <?php if (!empty($item['icon_svg'])): ?>
                    <span class="counter__icon" aria-hidden="true"><?= $item['icon_svg'] ?></span>
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
