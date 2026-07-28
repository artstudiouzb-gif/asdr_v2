<?php

use App\Core\AssetCollector;
use App\Core\Locale;

/** @var array $items */
/** @var int $page */
/** @var int $pages */
/** @var list<string> $badges */
/** @var string $badge */
$page = $page ?? 1;
$pages = $pages ?? 1;
$badges = $badges ?? [];
$badge = $badge ?? '';

$metaTitle = 'Новости';
$metaDescription = 'Официальные новости и аналитические материалы Агентства.';
AssetCollector::requireJs('news');
require __DIR__ . '/_header.php';

$crumbs = [
    ['label' => t('Главная'), 'url' => Locale::url('/')],
    ['label' => t('Новости')],
];
require __DIR__ . '/_crumbs.php';

?>
<div class="listing" data-listing>
    <div class="listing__head">
        <h1 class="listing__title"><?= htmlspecialchars(t('Новости и аналитика'), ENT_QUOTES) ?></h1>
        <p class="listing__lead"><?= htmlspecialchars(t('Официальные сообщения, события и аналитические материалы Агентства.'), ENT_QUOTES) ?></p>
    </div>

    <?php if ($badges !== []): ?>
        <nav class="listing-filter" aria-label="<?= htmlspecialchars(t('Рубрики'), ENT_QUOTES) ?>">
            <a class="listing-filter__item<?= $badge === '' ? ' is-active' : '' ?>" href="<?= htmlspecialchars(Locale::url('news'), ENT_QUOTES) ?>"><?= htmlspecialchars(t('Все материалы'), ENT_QUOTES) ?></a>
            <?php foreach ($badges as $b): ?>
                <a class="listing-filter__item<?= $b === $badge ? ' is-active' : '' ?>" href="<?= htmlspecialchars(Locale::url('news') . '?badge=' . rawurlencode($b), ENT_QUOTES) ?>"><?= htmlspecialchars($b, ENT_QUOTES) ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>


    <?php // Область результатов: её же отдаёт контроллер как фрагмент при
          // AJAX-фильтрации, поэтому разметка живёт в одном партиале. ?>
    <div class="listing__results" data-listing-results>
        <?= \App\Core\View::renderPartial('site/_news_list', compact('items', 'page', 'pages', 'badge')) ?>
    </div>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
