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
    <?php elseif ($origId > 0): ?>
        <form method="post" action="/admin/<?= rawurlencode($module) ?>/<?= $origId ?>/create-translation" class="translation-create-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Core\Csrf::token(), ENT_QUOTES) ?>">
            <input type="hidden" name="target_lang" value="<?= htmlspecialchars($code, ENT_QUOTES) ?>">
            <button type="submit" class="u-inline-7fca5bade1"
                    title="Создать перевод на язык <?= htmlspecialchars(strtoupper($code), ENT_QUOTES) ?>"
                   >+<?= htmlspecialchars($code, ENT_QUOTES) ?></button>
        </form>
    <?php else: ?>
        <span class="u-inline-7fca5bade1" title="Сначала сохраните запись">+<?= htmlspecialchars($code, ENT_QUOTES) ?></span>
    <?php endif; ?>
<?php endforeach; ?>
