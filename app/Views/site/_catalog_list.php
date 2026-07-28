<?php

use App\Core\ContentFields;
use App\Core\Locale;

/**
 * Область результатов каталога: счётчик, карточки записей и пагинация.
 * Подключается и целой страницей (content_index.php), и отдельно — как
 * фрагмент для AJAX-фильтрации (ContentController::index), поэтому все
 * производные значения считаются здесь.
 *
 * @var array $type
 * @var array $fields
 * @var array $entries
 * @var string $q
 * @var string $sort
 * @var int $page
 * @var int $pages
 * @var int $total
 * @var bool $hasDeadline
 */
$shortFields = array_values(array_filter($fields, static fn ($f) => in_array($f['field_type'], ['text', 'number', 'date'], true)));
$longFields = array_values(array_filter($fields, static fn ($f) => $f['field_type'] === 'textarea'));
$fileFields = array_values(array_filter($fields, static fn ($f) => $f['field_type'] === 'file'));
// Типы с датой проведения (мероприятия) получают карточку с датой-плиткой.
$isEvents = array_filter($fields, static fn ($f) => $f['name'] === 'event_date' && $f['field_type'] === 'date') !== [];
$months = match (Locale::current()) {
    'uz' => ['YAN', 'FEV', 'MAR', 'APR', 'MAY', 'IYN', 'IYL', 'AVG', 'SEN', 'OKT', 'NOY', 'DEK'],
    'en' => ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'],
    default => ['ЯНВ', 'ФЕВ', 'МАР', 'АПР', 'МАЙ', 'ИЮН', 'ИЮЛ', 'АВГ', 'СЕН', 'ОКТ', 'НОЯ', 'ДЕК'],
};

$baseUrl = Locale::url('catalog/' . $type['slug']);
$qs = static function (array $overrides) use ($q, $sort): string {
    $params = array_filter(array_merge(['q' => $q, 'sort' => $sort === 'new' ? '' : $sort], $overrides), static fn ($v) => $v !== '' && $v !== null);
    return $params === [] ? '' : '?' . http_build_query($params);
};
?>
<?php if (empty($entries)): ?>
    <p class="listing__empty">
        <?= $q !== '' ? t('По вашему запросу ничего не найдено.') : t('В этом разделе пока нет опубликованных записей.') ?>
    </p>
<?php else: ?>
    <p class="catlist-count"><?= htmlspecialchars(t('Найдено:'), ENT_QUOTES) ?> <b><?= (int) $total ?></b></p>
    <div class="catlist<?= $isEvents ? ' catlist--events' : '' ?>">
        <?php foreach ($entries as $entry): ?>
            <?php $url = Locale::url('catalog/' . $type['slug'] . '/' . $entry['slug']); ?>
            <article class="catcard<?= !empty($entry['is_archived']) ? ' catcard--archived' : '' ?>">
                <?php if ($isEvents && !empty($entry['data']['event_date'])): ?>
                    <?php $ts = (int) strtotime((string) $entry['data']['event_date']); ?>
                    <span class="catcard__datebox" aria-hidden="true">
                        <b><?= date('d', $ts) ?></b>
                        <i><?= $months[(int) date('n', $ts) - 1] ?></i>
                        <em><?= date('Y', $ts) ?></em>
                    </span>
                <?php endif; ?>
                <div class="catcard__main">
                    <div class="catcard__top">
                        <span class="catcard__doc-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        </span>
                        <?php if ($hasDeadline): ?>
                            <span class="catcard__status<?= !empty($entry['is_archived']) ? ' catcard__status--off' : '' ?>"><?= htmlspecialchars(t(!empty($entry['is_archived']) ? 'Архив' : 'Приём открыт'), ENT_QUOTES) ?></span>
                        <?php endif; ?>
                        <time class="catcard__created"><?= htmlspecialchars(date('d.m.Y', strtotime((string) $entry['created_at'])), ENT_QUOTES) ?></time>
                    </div>
                    <h2 class="catcard__title"><a href="<?= htmlspecialchars($url, ENT_QUOTES) ?>"><?= htmlspecialchars((string) $entry['title'], ENT_QUOTES) ?></a></h2>
                    <?php
                    $meta = [];
                    foreach ($shortFields as $f) {
                        if ($isEvents && $f['name'] === 'event_date') {
                            continue; // уже в плитке даты
                        }
                        $val = ContentFields::displayValue($f, $entry['data'][$f['name']] ?? null);
                        if ($val !== '') {
                            $meta[] = '<div class="catcard__meta-item"><i>' . htmlspecialchars(t((string) $f['label']), ENT_QUOTES) . '</i><span>' . $val . '</span></div>';
                        }
                    }
                    ?>
                    <?php if ($meta !== []): ?><div class="catcard__meta"><?= implode('', $meta) ?></div><?php endif; ?>
                    <?php foreach ($longFields as $f): ?>
                        <?php $val = ContentFields::displayValue($f, $entry['data'][$f['name']] ?? null); ?>
                        <?php if ($val !== ''): ?><p class="catcard__excerpt"><?= htmlspecialchars(mb_substr(trim(strip_tags((string) $val)), 0, 160), ENT_QUOTES) ?></p><?php break; endif; ?>
                    <?php endforeach; ?>
                    <div class="catcard__foot">
                        <?php foreach ($fileFields as $f): ?>
                            <?php if (!empty($entry['data'][$f['name']])): ?>
                                <span class="catcard__file">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="15" height="15" aria-hidden="true"><path d="M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8z"/><path d="M14 3v5h5"/><path d="M12 11v6"/><path d="m9 14 3 3 3-3"/></svg>
                                    <?= htmlspecialchars(t((string) $f['label']), ENT_QUOTES) ?>
                                </span>
                                <?php break; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <a class="catcard__more" href="<?= htmlspecialchars($url, ENT_QUOTES) ?>">
                            <span><?= htmlspecialchars(t('Подробнее'), ENT_QUOTES) ?></span>
                            <span class="catcard__arrow" aria-hidden="true">→</span>
                        </a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="listing-pager" aria-label="<?= htmlspecialchars(t('Страницы'), ENT_QUOTES) ?>">
            <?php for ($p = 1; $p <= $pages; $p++): ?>
                <?php if ($p === $page): ?>
                    <span class="listing-pager__item is-active" aria-current="page"><?= $p ?></span>
                <?php else: ?>
                    <a class="listing-pager__item" href="<?= htmlspecialchars($baseUrl . $qs(['page' => $p > 1 ? $p : null]), ENT_QUOTES) ?>"><?= $p ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
