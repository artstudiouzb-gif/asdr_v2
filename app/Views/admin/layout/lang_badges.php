<?php

// Индикаторы языков контента для строки списка.
// $siteLangs — активные языки сайта (коды); $has — языки, где контент есть (массив кодов или [langCode => targetId]).
/** @var array<int, string> $siteLangs */
$siteLangs = $siteLangs ?? [];
/** @var array<string, int>|array<int, string> $has */
$has = $has ?? [];
$module = $module ?? 'pages';
$origId = (int) ($origId ?? 0);

foreach ($siteLangs as $code):
    $targetId = null;
    if (isset($has[$code])) {
        $targetId = is_numeric($has[$code]) ? (int) $has[$code] : $origId;
    } elseif (in_array($code, $has, true)) {
        $targetId = $origId;
    }

    $on = ($targetId !== null);
    ?>
    <?php if ($on): ?>
        <a href="/admin/<?= urlencode($module) ?>/<?= $targetId ?>/edit"
           title="Редактировать перевод на язык <?= htmlspecialchars(strtoupper($code), ENT_QUOTES) ?> (#<?= $targetId ?>)"
           style="display:inline-block;margin-right:4px;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;text-transform:uppercase;text-decoration:none;background:#e6f4ea;color:#1e7e34;border:1px solid #a8dab5;transition:all .15s ease;"><?= htmlspecialchars($code, ENT_QUOTES) ?></a>
    <?php else: ?>
        <a href="<?= $origId > 0 ? '/admin/' . urlencode($module) . '/' . $origId . '/create-translation?target_lang=' . urlencode($code) : '#' ?>"
           title="Создать перевод на язык <?= htmlspecialchars(strtoupper($code), ENT_QUOTES) ?>"
           style="display:inline-block;margin-right:4px;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;text-transform:uppercase;text-decoration:none;background:#f1f2f4;color:#9aa0a6;border:1px solid #dadce0;transition:all .15s ease;">+<?= htmlspecialchars($code, ENT_QUOTES) ?></a>
    <?php endif; ?>
<?php endforeach; ?>
