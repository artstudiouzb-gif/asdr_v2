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
        <a class="u-inline-da0ab63f64" href="/admin/<?= urlencode($module) ?>/<?= $targetId ?>/edit"
           title="Редактировать перевод на язык <?= htmlspecialchars(strtoupper($code), ENT_QUOTES) ?> (#<?= $targetId ?>)"
          ><?= htmlspecialchars($code, ENT_QUOTES) ?></a>
    <?php else: ?>
        <a class="u-inline-7fca5bade1" href="<?= $origId > 0 ? '/admin/' . urlencode($module) . '/' . $origId . '/create-translation?target_lang=' . urlencode($code) : '#' ?>"
           title="Создать перевод на язык <?= htmlspecialchars(strtoupper($code), ENT_QUOTES) ?>"
          >+<?= htmlspecialchars($code, ENT_QUOTES) ?></a>
    <?php endif; ?>
<?php endforeach; ?>