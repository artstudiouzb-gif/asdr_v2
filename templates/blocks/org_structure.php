<?php
/**
 * Структура организации: оргсхема с коллегиальным органом над руководителем,
 * советниками сбоку, ветками заместителей и их подразделениями.
 * Чистый CSS без JS; на мобильных ветки складываются в столбец.
 *
 * Разметка списков (подразделения, органы при руководителе, примечания):
 *   Название               — обычный пункт
 *   Название | /url        — пункт-ссылка
 *   * Название             — акцентный пункт (проектный офис)
 *   - Название             — вложенная группа внутри предыдущего пункта
 *
 * @var array $data
 */

use App\Core\Icon;
use App\Core\UrlGuard;

/**
 * Разбор построчного поля в дерево пунктов: label/url/accent/children.
 *
 * @return list<array{label: string, url: string, accent: bool, children: list<array{label: string, url: string}>}>
 */
$orgParseLines = static function (string $raw): array {
    $items = [];
    foreach (explode("\n", $raw) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        // Маркер вложенности: пункт уходит в подгруппу предыдущего.
        $nested = false;
        if (preg_match('/^[-–—•]\s*(.+)$/u', $line, $m) === 1) {
            $nested = true;
            $line = trim($m[1]);
        }

        // Маркер акцента (проектный офис и т.п.).
        $accent = false;
        if (!$nested && preg_match('/^\*\s*(.+)$/u', $line, $m) === 1) {
            $accent = true;
            $line = trim($m[1]);
        }

        $url = '';
        if (str_contains($line, '|')) {
            [$line, $url] = array_map('trim', explode('|', $line, 2));
        }
        if ($line === '') {
            continue;
        }
        if ($url !== '' && !UrlGuard::isSafeLink($url)) {
            $url = '';
        }

        if ($nested && $items !== []) {
            $items[count($items) - 1]['children'][] = ['label' => $line, 'url' => $url];
            continue;
        }

        $items[] = ['label' => $line, 'url' => $url, 'accent' => $accent, 'children' => []];
    }

    return $items;
};

/** Пункт списка: ссылка, если задан безопасный URL, иначе текст. */
$orgItemInner = static function (array $item): string {
    $label = htmlspecialchars((string) $item['label'], ENT_QUOTES);

    return ($item['url'] ?? '') !== ''
        ? '<a class="orgstruct__link" href="' . htmlspecialchars((string) $item['url'], ENT_QUOTES) . '">' . $label . '</a>'
        : $label;
};

$title = trim((string) ($data['title'] ?? ''));
$layout = ($data['layout'] ?? 'tree') === 'spine' ? 'spine' : 'tree';
$columns = (int) ($data['columns'] ?? 4);
$columns = max(2, min(4, $columns));
$council = $orgParseLines((string) ($data['council'] ?? ''));
$headTitle = trim((string) ($data['head_title'] ?? ''));
$headName = trim((string) ($data['head_name'] ?? ''));
$headUrl = trim((string) ($data['head_url'] ?? ''));
if ($headUrl !== '' && !UrlGuard::isSafeLink($headUrl)) {
    $headUrl = '';
}
$sideItems = $orgParseLines((string) ($data['side_items'] ?? ''));
$notes = $orgParseLines((string) ($data['notes'] ?? ''));
$footnote = trim((string) ($data['footnote'] ?? ''));
$collapsible = !empty($data['collapsible']);
$headTag = $headUrl !== '' ? 'a' : 'div';

// Ветки нормализуем заранее: пустые отбрасываем, чтобы ряды считались верно.
$branches = [];
foreach ((is_array($data['branches'] ?? null) ? $data['branches'] : []) as $branch) {
    $bTitle = trim((string) ($branch['title'] ?? ''));
    $bName = trim((string) ($branch['name'] ?? ''));
    $bUrl = trim((string) ($branch['url'] ?? ''));
    $units = $orgParseLines((string) ($branch['units'] ?? ''));
    if ($bTitle === '' && $bName === '' && $units === []) {
        continue;
    }
    if ($bUrl !== '' && !UrlGuard::isSafeLink($bUrl)) {
        $bUrl = '';
    }
    $branches[] = ['title' => $bTitle, 'name' => $bName, 'url' => $bUrl, 'units' => $units];
}

// Ряды по <= $columns веток: перекладина-соединитель считается внутри ряда,
// поэтому схема не ломается при пяти и более заместителях. Компактный макет
// идёт одним рядом — там колонок нет.
$rows = [];
if ($branches !== []) {
    if ($layout === 'tree') {
        // Ряды выравниваем по длине: пять веток при четырёх колонках дают
        // 3+2, а не 4+1 с одинокой карточкой во всю ширину.
        $rowCount = (int) ceil(count($branches) / $columns);
        $rows = array_chunk($branches, (int) ceil(count($branches) / $rowCount));
    } else {
        $rows = [$branches];
    }
}

// Число колонок ряда и размер шрифта уходят в scoped CSS блока.
$templateCss = '';
$fontSize = (int) ($data['font_size'] ?? 0);
if ($fontSize >= 10 && $fontSize <= 24) {
    $templateCss .= '#block-' . $blockId . ' .orgstruct{--org-font-size:' . $fontSize . 'px}';
}
foreach ($rows as $rowIndex => $row) {
    $templateCss .= '#block-' . $blockId . ' .orgstruct__row:nth-child(' . ($rowIndex + 1) . ')'
        . '{--orgstruct-cols:' . count($row) . '}';
}
$branchIndex = 0;
?>
<div class="block-orgstruct">
    <?php if ($title !== ''): ?>
        <div class="section-head"><h2 class="section-head__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></h2></div>
    <?php endif; ?>

    <div class="orgstruct orgstruct--<?= $layout ?><?= $collapsible ? ' orgstruct--collapsible' : '' ?>"<?= $collapsible ? ' data-org-collapsible' : '' ?>>
        <?php if ($council !== []): ?>
            <ul class="orgstruct__council" role="list">
                <?php foreach ($council as $item): ?>
                    <li class="orgstruct__council-card"><?= $orgItemInner($item) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="orgstruct__top">
            <div class="orgstruct__head-row">
                <<?= $headTag ?><?= $headUrl !== '' ? ' href="' . htmlspecialchars($headUrl, ENT_QUOTES) . '"' : '' ?> class="orgstruct__head">
                    <?php if ($headTitle !== ''): ?><span class="orgstruct__head-role"><?= htmlspecialchars($headTitle, ENT_QUOTES) ?></span><?php endif; ?>
                    <?php if ($headName !== ''): ?><span class="orgstruct__head-name"><?= htmlspecialchars($headName, ENT_QUOTES) ?></span><?php endif; ?>
                </<?= $headTag ?>>
                <?php if ($sideItems !== []): ?>
                    <ul class="orgstruct__aside" role="list">
                        <?php foreach ($sideItems as $side): ?>
                            <li class="orgstruct__aside-item"><?= $orgItemInner($side) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($branches !== []): ?>
            <div class="orgstruct__branches">
                <?php foreach ($rows as $row): ?>
                    <ul class="orgstruct__row" role="list">
                        <?php foreach ($row as $branch): ?>
                            <?php
                            $branchIndex++;
                            $branchId = 'org-branch-' . (int) $blockId . '-' . $branchIndex;
                            $hasHead = $branch['title'] !== '' || $branch['name'] !== '';
                            ?>
                            <li class="orgstruct__branch<?= $hasHead ? '' : ' orgstruct__branch--headless' ?>" data-org-branch>
                                <?php if ($hasHead): ?>
                                    <div class="orgstruct__deputy">
                                        <?php if ($branch['url'] !== ''): ?><a class="orgstruct__deputy-link" href="<?= htmlspecialchars($branch['url'], ENT_QUOTES) ?>"><?php endif; ?>
                                            <?php if ($branch['title'] !== ''): ?><span class="orgstruct__deputy-role"><?= htmlspecialchars($branch['title'], ENT_QUOTES) ?></span><?php endif; ?>
                                            <?php if ($branch['name'] !== ''): ?><span class="orgstruct__deputy-name"><?= htmlspecialchars($branch['name'], ENT_QUOTES) ?></span><?php endif; ?>
                                        <?php if ($branch['url'] !== ''): ?></a><?php endif; ?>
                                        <?php if ($branch['units'] !== []): ?>
                                            <button type="button" class="orgstruct__toggle" data-org-toggle aria-expanded="true" aria-controls="<?= $branchId ?>">
                                                <span class="visually-hidden"><?= htmlspecialchars(t('Показать подразделения'), ENT_QUOTES) ?></span>
                                                <?= Icon::render('chevron-down', 18, '', 2) ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($branch['units'] !== []): ?>
                                    <ul class="orgstruct__units" id="<?= $branchId ?>" data-org-units role="list"<?= $hasHead ? ' aria-label="' . htmlspecialchars(trim($branch['title'] . ' ' . $branch['name']), ENT_QUOTES) . '"' : '' ?>>
                                        <?php foreach ($branch['units'] as $unit): ?>
                                            <li class="orgstruct__unit<?= $unit['accent'] ? ' orgstruct__unit--accent' : '' ?>">
                                                <?= $orgItemInner($unit) ?>
                                                <?php if ($unit['children'] !== []): ?>
                                                    <ul class="orgstruct__subunits" role="list">
                                                        <?php foreach ($unit['children'] as $child): ?>
                                                            <li class="orgstruct__subunit"><?= $orgItemInner($child) ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($notes !== []): ?>
        <ul class="orgstruct__notes" role="list">
            <?php foreach ($notes as $note): ?>
                <li class="orgstruct__note"><?= $orgItemInner($note) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($footnote !== ''): ?>
        <p class="orgstruct__footnote"><?= htmlspecialchars($footnote, ENT_QUOTES) ?></p>
    <?php endif; ?>
</div>
