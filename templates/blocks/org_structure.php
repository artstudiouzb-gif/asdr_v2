<?php
/**
 * Структура организации: оргсхема с руководителем, боковыми органами
 * (совет/советник), ветками заместителей и их подразделениями.
 * Чистый CSS без JS; на мобильных ветки складываются в столбец.
 *
 * @var array $data
 */
$title = trim((string) ($data['title'] ?? ''));
$headTitle = trim((string) ($data['head_title'] ?? ''));
$headName = trim((string) ($data['head_name'] ?? ''));
$headUrl = trim((string) ($data['head_url'] ?? ''));
$sideItems = array_values(array_filter(array_map('trim', explode("\n", (string) ($data['side_items'] ?? '')))));
$branches = is_array($data['branches'] ?? null) ? $data['branches'] : [];
$footnote = trim((string) ($data['footnote'] ?? ''));
$headTag = $headUrl !== '' ? 'a' : 'div';
?>
<div class="block-orgstruct">
    <?php if ($title !== ''): ?>
        <div class="section-head"><h2 class="section-head__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2></div>
    <?php endif; ?>

    <div class="orgstruct">
        <div class="orgstruct__top">
            <?php if ($sideItems !== []): ?>
                <ul class="orgstruct__aside" role="list">
                    <?php foreach ($sideItems as $side): ?>
                        <li class="orgstruct__aside-item"><?= htmlspecialchars($side, ENT_QUOTES) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <<?= $headTag ?><?= $headUrl !== '' ? ' href="' . htmlspecialchars($headUrl, ENT_QUOTES) . '"' : '' ?> class="orgstruct__head">
                <?php if ($headTitle !== ''): ?><span class="orgstruct__head-role"><?= htmlspecialchars($headTitle, ENT_QUOTES) ?></span><?php endif; ?>
                <?php if ($headName !== ''): ?><span class="orgstruct__head-name"><?= htmlspecialchars($headName, ENT_QUOTES) ?></span><?php endif; ?>
            </<?= $headTag ?>>
        </div>

        <?php if ($branches !== []): ?>
            <div class="orgstruct__branches" style="--orgstruct-branches: <?= count($branches) ?>;">
                <?php foreach ($branches as $branch): ?>
                    <?php
                    $bTitle = trim((string) ($branch['title'] ?? ''));
                    $bName = trim((string) ($branch['name'] ?? ''));
                    $units = array_values(array_filter(array_map('trim', explode("\n", (string) ($branch['units'] ?? '')))));
                    if ($bTitle === '' && $bName === '' && $units === []) {
                        continue;
                    }
                    ?>
                    <div class="orgstruct__branch">
                        <div class="orgstruct__deputy">
                            <?php if ($bTitle !== ''): ?><span class="orgstruct__deputy-role"><?= htmlspecialchars($bTitle, ENT_QUOTES) ?></span><?php endif; ?>
                            <?php if ($bName !== ''): ?><span class="orgstruct__deputy-name"><?= htmlspecialchars($bName, ENT_QUOTES) ?></span><?php endif; ?>
                        </div>
                        <?php if ($units !== []): ?>
                            <ul class="orgstruct__units" role="list">
                                <?php foreach ($units as $unit): ?>
                                    <li class="orgstruct__unit"><?= htmlspecialchars($unit, ENT_QUOTES) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($footnote !== ''): ?>
        <p class="orgstruct__footnote"><?= htmlspecialchars($footnote, ENT_QUOTES) ?></p>
    <?php endif; ?>
</div>
