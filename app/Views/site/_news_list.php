<?php

use App\Core\DateFormatter;
use App\Core\Locale;
use App\Models\News;

/**
 * Область результатов списка новостей: крупная новость, сетка и пагинация.
 * Подключается и целой страницей (news_index.php), и отдельно — как фрагмент
 * для AJAX-фильтрации (NewsController::index). Поэтому всё, что нужно для
 * вывода, считается здесь, а не в родительском шаблоне.
 *
 * @var array $items
 * @var int $page
 * @var int $pages
 * @var string $category slug выбранной рубрики ('' — все)
 */
$page = $page ?? 1;
$pages = $pages ?? 1;
$category = $category ?? '';

$lang = Locale::current();
// Дата — единым числовым форматом на всех языках: 19.07.2026.
$fmt = static fn (string $d): string => DateFormatter::short($d);
$pageUrl = static fn (int $p): string => Locale::url('news')
    . (($p > 1 || $category !== '') ? '?' . http_build_query(array_filter(['category' => $category, 'page' => $p > 1 ? $p : null])) : '');

// Названия рубрик для всех карточек одним запросом.
$categoryNames = \App\Models\NewsCategory::namesForIds(
    array_map(static fn (array $item): int => (int) ($item['category_id'] ?? 0), $items),
    $lang
);
$categoryOf = static function (array $item) use ($categoryNames): string {
    $id = (int) ($item['category_id'] ?? 0);

    return $id > 0 ? (string) ($categoryNames[$id] ?? '') : '';
};
?>
<?php if (empty($items)): ?>
    <p class="listing__empty"><?= htmlspecialchars(t('Пока нет опубликованных новостей.'), ENT_QUOTES) ?></p>
<?php else: ?>
    <div class="newslist-grid">
        <?php foreach ($items as $index => $item): ?>
            <?php
            // В 4-строчной сетке (всего 14 новостей):
            // Индекс 0: Строка 1, крупный лид слева (колонки 1-2)
            // Индексы 1, 2: Строка 1, 2 карточки справа (колонки 3, 4)
            // Индексы 3, 4, 5, 6: Строка 2, 4 карточки (колонки 1, 2, 3, 4)
            // Индексы 7, 8: Строка 3, 2 карточки слева (колонки 1, 2)
            // Индекс 9: Строка 3, крупный лид справа (колонки 3-4)
            // Индексы 10, 11, 12, 13: Строка 4, 4 карточки (колонки 1, 2, 3, 4)
            $isLead = ($category === '' && ($index % 14 === 0 || $index % 14 === 9));
            $isLeadRight = ($category === '' && $index % 14 === 9);
            $c = News::getCoverImage($item);
            ?>
            <?php if ($isLead): ?>
                <a class="newslist-lead<?= $isLeadRight ? ' newslist-lead--right' : '' ?>" href="<?= htmlspecialchars(Locale::url('news/' . $item['slug']), ENT_QUOTES) ?>">
                    <span class="news-cover">
                        <?php if ($c !== null): ?>
                            <?= \App\Core\Media::picture($c, (string) $item['title'], null, null, 'newslist-lead__img', false, '(max-width: 900px) 100vw, 50vw', false, 'newslist-lead__media') ?>
                        <?php else: ?>
                            <span class="newslist-lead__media newslist-lead__media--empty" aria-hidden="true"></span>
                        <?php endif; ?>
                        <?= \App\Core\NewsBadge::renderOverlay($item['badge'] ?? '', $item['badge_color'] ?? null) ?>
                    </span>
                    <span class="newslist-lead__body">
                        <span class="news-meta">
                            <?php if (!empty($item['published_at'])): ?><time class="newslist__date"><?= htmlspecialchars($fmt((string) $item['published_at']), ENT_QUOTES) ?></time><?php endif; ?>
                            <?php if ($categoryOf($item) !== ''): ?><span class="news-category"><?= htmlspecialchars($categoryOf($item), ENT_QUOTES) ?></span><?php endif; ?>
                        </span>
                        <h2 class="newslist-lead__title"><?= htmlspecialchars((string) $item['title'], ENT_QUOTES) ?></h2>
                        <?php if (!empty($item['excerpt'])): ?><span class="newslist-lead__excerpt"><?= htmlspecialchars(excerpt((string) $item['excerpt'], 160), ENT_QUOTES) ?></span><?php endif; ?>
                        <span class="card-more"><?= htmlspecialchars(t('Читать подробнее'), ENT_QUOTES) ?><span class="card-more__arrow" aria-hidden="true">→</span></span>
                    </span>
                </a>
            <?php else: ?>
                <a class="relnews-card" href="<?= htmlspecialchars(Locale::url('news/' . $item['slug']), ENT_QUOTES) ?>">
                    <span class="news-cover">
                        <?php if ($c !== null): ?>
                            <?= \App\Core\Media::picture($c, (string) $item['title'], null, null, 'relnews-card__img', true, '(max-width: 700px) 100vw, 25vw', false, 'relnews-card__media') ?>
                        <?php else: ?>
                            <span class="relnews-card__media relnews-card__media--empty" aria-hidden="true"></span>
                        <?php endif; ?>
                        <?= \App\Core\NewsBadge::renderOverlay($item['badge'] ?? '', $item['badge_color'] ?? null) ?>
                    </span>
                    <span class="news-meta">
                        <?php if (!empty($item['published_at'])): ?><time class="relnews-card__date"><?= htmlspecialchars($fmt((string) $item['published_at']), ENT_QUOTES) ?></time><?php endif; ?>
                        <?php if ($categoryOf($item) !== ''): ?><span class="news-category"><?= htmlspecialchars($categoryOf($item), ENT_QUOTES) ?></span><?php endif; ?>
                    </span>
                    <h3 class="relnews-card__title"><?= htmlspecialchars((string) $item['title'], ENT_QUOTES) ?></h3>
                    <span class="card-more"><?= htmlspecialchars(t('Читать подробнее'), ENT_QUOTES) ?><span class="card-more__arrow" aria-hidden="true">→</span></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <?php require __DIR__ . '/_pager.php'; ?>
<?php endif; ?>
