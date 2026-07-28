<?php

$pageTitle = t('Дашборд');
$activeNav = 'dashboard';
require __DIR__ . '/layout/header.php';

/** @var array $user */
/** @var array $counts */
/** @var array<string, int> $chartData */
/** @var array<int, array<string, mixed>> $recentLogs */
/** @var array<int, array<string, mixed>> $recentItems */
/** @var array<int, array<string, mixed>> $recentSubmissions */
/** @var array<string, mixed> $systemHealth */
?>
<section class="admin-welcome" aria-labelledby="admin-welcome-title">
    <div>
        <h2 id="admin-welcome-title"><?= htmlspecialchars(t('Добро пожаловать'), ENT_QUOTES) ?>, <?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES) ?></h2>
        <p><?= htmlspecialchars(t('Управляйте содержимым сайта и быстро переходите к основным действиям.'), ENT_QUOTES) ?></p>
    </div>
    <div class="admin-welcome__actions">
        <a href="/admin/news/create" class="btn btn--primary"><?= \App\Core\AdminUi::icon('plus') ?> <?= htmlspecialchars(t('Добавить новость'), ENT_QUOTES) ?></a>
        <a href="/admin/pages/create" class="btn"><?= htmlspecialchars(t('Добавить страницу'), ENT_QUOTES) ?></a>
        <a href="/" target="_blank" rel="noopener" class="btn"><?= htmlspecialchars(t('Открыть сайт'), ENT_QUOTES) ?> ↗</a>
    </div>
</section>

<div class="stat-grid">
    <a href="/admin/news" class="stat-card">
        <span class="stat-card__value"><?= (int) $counts['news'] ?></span>
        <span class="stat-card__label"><?= htmlspecialchars(t('Новости'), ENT_QUOTES) ?></span>
        <?php if (!empty($counts['news_drafts'])): ?>
            <span class="form-hint" style="margin-top:4px;"><?= (int) $counts['news_drafts'] ?> <?= htmlspecialchars(t('черновиков'), ENT_QUOTES) ?></span>
        <?php endif; ?>
    </a>
    <a href="/admin/pages" class="stat-card">
        <span class="stat-card__value"><?= (int) $counts['pages'] ?></span>
        <span class="stat-card__label"><?= htmlspecialchars(t('Страницы'), ENT_QUOTES) ?></span>
    </a>
    <a href="/admin/projects" class="stat-card">
        <span class="stat-card__value"><?= (int) $counts['projects'] ?></span>
        <span class="stat-card__label"><?= htmlspecialchars(t('Проекты'), ENT_QUOTES) ?></span>
    </a>
    <a href="/admin/team" class="stat-card">
        <span class="stat-card__value"><?= (int) $counts['team'] ?></span>
        <span class="stat-card__label"><?= htmlspecialchars(t('Сотрудники'), ENT_QUOTES) ?></span>
    </a>
    <a href="/admin/forms" class="stat-card">
        <span class="stat-card__value"><?= (int) $counts['forms'] ?></span>
        <span class="stat-card__label"><?= htmlspecialchars(t('Формы'), ENT_QUOTES) ?></span>
    </a>
    <a href="/admin/forms" class="stat-card<?= $counts['submissions_unread'] > 0 ? ' stat-card--highlight' : '' ?>">
        <span class="stat-card__value"><?= (int) $counts['submissions_unread'] ?></span>
        <span class="stat-card__label"><?= htmlspecialchars(t('Непрочитанные заявки'), ENT_QUOTES) ?></span>
    </a>
    <a href="/admin/files" class="stat-card">
        <span class="stat-card__value"><?= (int) $counts['files'] ?></span>
        <span class="stat-card__label"><?= htmlspecialchars(t('Медиафайлы'), ENT_QUOTES) ?></span>
    </a>
    <a href="/admin/languages" class="stat-card">
        <span class="stat-card__value"><?= (int) ($systemHealth['active_langs_count'] ?? 1) ?></span>
        <span class="stat-card__label"><?= htmlspecialchars(t('Активные языки'), ENT_QUOTES) ?></span>
    </a>
</div>

<div class="dashboard-grid" style="margin-bottom:24px;">
    <!-- Виджет: Статус и безопасность системы -->
    <div class="form-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <h3 style="margin:0;"><?= htmlspecialchars(t('Статус системы'), ENT_QUOTES) ?></h3>
            <a href="/admin/security" class="btn btn--small" style="font-size:12px;"><?= htmlspecialchars(t('Безопасность'), ENT_QUOTES) ?> →</a>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(140px, 1fr));gap:12px;margin-top:8px;">
            <div style="padding:10px 12px;background:var(--admin-bg);border-radius:8px;border:1px solid var(--admin-border);">
                <div class="form-hint" style="margin:0 0 4px 0;font-size:11px;"><?= htmlspecialchars(t('Версия PHP'), ENT_QUOTES) ?></div>
                <strong style="font-size:14px;color:var(--admin-text);"><?= htmlspecialchars((string) ($systemHealth['php_version'] ?? PHP_VERSION), ENT_QUOTES) ?></strong>
            </div>
            <div style="padding:10px 12px;background:var(--admin-bg);border-radius:8px;border:1px solid var(--admin-border);">
                <div class="form-hint" style="margin:0 0 4px 0;font-size:11px;"><?= htmlspecialchars(t('База данных'), ENT_QUOTES) ?></div>
                <span class="badge badge--published" style="font-size:11px;">✓ <?= htmlspecialchars(t('Подключена'), ENT_QUOTES) ?></span>
            </div>
            <div style="padding:10px 12px;background:var(--admin-bg);border-radius:8px;border:1px solid var(--admin-border);">
                <div class="form-hint" style="margin:0 0 4px 0;font-size:11px;"><?= htmlspecialchars(t('Защита 2FA / Telegram'), ENT_QUOTES) ?></div>
                <?php if (!empty($systemHealth['telegram_linked'])): ?>
                    <span class="badge badge--published" style="font-size:11px;">✓ <?= htmlspecialchars(t('Активна'), ENT_QUOTES) ?></span>
                <?php else: ?>
                    <span class="badge badge--draft" style="font-size:11px;"><?= htmlspecialchars(t('Не настроена'), ENT_QUOTES) ?></span>
                <?php endif; ?>
            </div>
            <div style="padding:10px 12px;background:var(--admin-bg);border-radius:8px;border:1px solid var(--admin-border);">
                <div class="form-hint" style="margin:0 0 4px 0;font-size:11px;"><?= htmlspecialchars(t('Обслуживание'), ENT_QUOTES) ?></div>
                <?php if (!empty($systemHealth['maintenance'])): ?>
                    <span class="badge badge--draft" style="font-size:11px;"><?= htmlspecialchars(t('Включён'), ENT_QUOTES) ?></span>
                <?php else: ?>
                    <span class="badge badge--published" style="font-size:11px;"><?= htmlspecialchars(t('Выключен'), ENT_QUOTES) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Виджет: Последние поступившие заявки с сайта -->
    <div class="form-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <h3 style="margin:0;"><?= htmlspecialchars(t('Последние заявки'), ENT_QUOTES) ?></h3>
            <a href="/admin/forms" class="btn btn--small" style="font-size:12px;"><?= htmlspecialchars(t('Все заявки'), ENT_QUOTES) ?> →</a>
        </div>
        <?php if (empty($recentSubmissions)): ?>
            <p class="form-hint" style="margin:8px 0 0 0;"><?= htmlspecialchars(t('Заявок пока не поступало.'), ENT_QUOTES) ?></p>
        <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:8px;margin-top:8px;">
                <?php foreach ($recentSubmissions as $sub): ?>
                    <?php
                    $isUnread = (int) ($sub['is_read'] ?? 0) === 0;
                    $data = json_decode((string) ($sub['data_json'] ?? '{}'), true) ?: [];
                    $previewText = implode(' • ', array_slice(array_values($data), 0, 2));
                    ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:var(--admin-bg);border-radius:6px;border:1px solid var(--admin-border);">
                        <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:70%;">
                            <strong style="font-size:13px;display:block;"><?= htmlspecialchars((string) ($sub['form_title'] ?? t('Форма')), ENT_QUOTES) ?></strong>
                            <span class="form-hint" style="font-size:11px;margin:0;"><?= htmlspecialchars($previewText !== '' ? $previewText : '—', ENT_QUOTES) ?></span>
                        </div>
                        <div style="text-align:right;">
                            <?php if ($isUnread): ?>
                                <span class="badge badge--draft" style="font-size:10px;"><?= htmlspecialchars(t('Новая'), ENT_QUOTES) ?></span>
                            <?php endif; ?>
                            <span class="form-hint" style="font-size:10px;display:block;margin-top:2px;"><?= date('d.m H:i', strtotime((string) $sub['created_at'])) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Виджет: Поисковые запросы по сайту за последние 30 дней -->
<?php if (!empty($popularSearches)): ?>
<div class="form-card" style="margin-bottom:24px;">
    <h3 style="margin-top:0;"><?= htmlspecialchars(t('Популярные поиски на сайте'), ENT_QUOTES) ?></h3>
    <p class="form-hint"><?= htmlspecialchars(t('Что посетители чаще всего ищут через внутренний поиск за 30 дней.'), ENT_QUOTES) ?></p>
    <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:12px;">
        <?php foreach ($popularSearches as $s): ?>
            <?php
            $q = (string) $s['query'];
            $cnt = (int) $s['searches_count'];
            $resCnt = (int) $s['last_results_count'];
            ?>
            <div style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;background:var(--admin-bg);border:1px solid var(--admin-border);border-radius:20px;font-size:13px;">
                <?= \App\Core\AdminUi::icon('search', 13, 'btn__icon', 2.5) ?>
                <strong>«<?= htmlspecialchars($q, ENT_QUOTES) ?>»</strong>
                <span class="badge" style="font-size:11px;padding:2px 6px;"><?= $cnt ?> <?= htmlspecialchars(t('запросов'), ENT_QUOTES) ?></span>
                <?php if ($resCnt === 0): ?>
                    <span class="badge badge--draft" style="font-size:10px;"><?= htmlspecialchars(t('0 результатов'), ENT_QUOTES) ?></span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Виджет: Самые читаемые новости за период -->
<?php if (!empty($topReadNews)): ?>
<div class="form-card" style="margin-bottom:24px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
        <div>
            <h3 style="margin:0;"><?= htmlspecialchars(t('Самые читаемые новости'), ENT_QUOTES) ?></h3>
            <p class="form-hint" style="margin:4px 0 0 0;"><?= htmlspecialchars(t('Наибольшее число просмотров среди читателей за последние 30 дней.'), ENT_QUOTES) ?></p>
        </div>
        <a href="/admin/news" class="btn btn--small" style="font-size:12px;"><?= htmlspecialchars(t('Все новости'), ENT_QUOTES) ?> →</a>
    </div>
    <div style="display:flex;flex-direction:column;gap:8px;margin-top:8px;">
        <?php foreach ($topReadNews as $idx => $n): ?>
            <a href="/admin/news/<?= (int) $n['id'] ?>/edit" style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:var(--admin-bg);border:1px solid var(--admin-border);border-radius:8px;text-decoration:none;color:inherit;">
                <div style="display:flex;align-items:center;gap:12px;overflow:hidden;max-width:75%;">
                    <span style="font-weight:800;font-size:16px;color:var(--admin-accent);min-width:20px;">#<?= $idx + 1 ?></span>
                    <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <strong style="font-size:13.5px;display:block;"><?= htmlspecialchars((string) $n['title'], ENT_QUOTES) ?></strong>
                        <span class="form-hint" style="font-size:11px;margin:0;"><?= date('d.m.Y', strtotime((string) ($n['published_at'] ?? 'now'))) ?></span>
                    </div>
                </div>
                <div>
                    <span class="badge badge--published" style="font-size:12px;padding:4px 8px;">
                        👁️ <?= number_format((int) ($n['period_views'] ?? 0), 0, '.', ' ') ?> <?= htmlspecialchars(t('просмотров'), ENT_QUOTES) ?>
                    </span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($recentItems)): ?>
<div class="form-card continue-card" style="margin-bottom:24px;">
    <h3 style="margin-top:0;"><?= htmlspecialchars(t('Продолжить работу'), ENT_QUOTES) ?></h3>
    <p class="form-hint"><?= htmlspecialchars(t('Последние материалы, которые редактировались.'), ENT_QUOTES) ?></p>
    <div class="continue-list">
        <?php foreach ($recentItems as $item): ?>
            <?php
            $isNews = ($item['kind'] ?? '') === 'news';
            $editUrl = ($isNews ? '/admin/news/' : '/admin/pages/') . (int) $item['id'] . '/edit';
            $isDraft = ($item['status'] ?? '') === 'draft';
            ?>
            <a href="<?= htmlspecialchars($editUrl, ENT_QUOTES) ?>" class="continue-item">
                <span class="continue-item__kind"><?= $isNews ? htmlspecialchars(t('Новость'), ENT_QUOTES) : htmlspecialchars(t('Страница'), ENT_QUOTES) ?></span>
                <span class="continue-item__title"><?= htmlspecialchars((string) $item['title'], ENT_QUOTES) ?></span>
                <span class="badge <?= $isDraft ? 'badge--draft' : 'badge--published' ?>"><?= $isDraft ? htmlspecialchars(t('Черновик'), ENT_QUOTES) : htmlspecialchars(t('Опубликовано'), ENT_QUOTES) ?></span>
                <span class="continue-item__time"><?= date('d.m.Y H:i', strtotime((string) $item['updated_at'])) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php
$maxVal = max(1, ...array_values($chartData));
$width = 500;
$height = 220;
$padding = 30;
$chartWidth = $width - 2 * $padding;
$chartHeight = $height - 2 * $padding;

$points = [];
$xStep = count($chartData) > 1 ? $chartWidth / (count($chartData) - 1) : $chartWidth;
$i = 0;
foreach ($chartData as $date => $count) {
    $x = $padding + $i * $xStep;
    $y = $padding + $chartHeight - ($count / $maxVal) * $chartHeight;
    $points[] = "$x,$y";
    $i++;
}
$pointsStr = implode(' ', $points);
$fillPointsStr = "$padding," . ($height - $padding) . " $pointsStr " . ($width - $padding) . "," . ($height - $padding);
?>
<div class="dashboard-grid">
    <div class="form-card">
        <h3 style="margin-top:0;"><?= htmlspecialchars(t('Активность заявок'), ENT_QUOTES) ?></h3>
        <p class="form-hint"><?= htmlspecialchars(t('Число заполненных форм обратной связи за последние 7 дней.'), ENT_QUOTES) ?></p>
        <div style="max-width:100%;margin-top:12px;">
            <svg viewBox="0 0 500 220" width="100%" height="100%" style="overflow:visible;">
                <defs>
                    <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="var(--admin-accent)" stop-opacity="0.3"></stop>
                        <stop offset="100%" stop-color="var(--admin-accent)" stop-opacity="0"></stop>
                    </linearGradient>
                </defs>
                <!-- Grid Lines -->
                <?php for ($grid = 0; $grid <= 4; $grid++): ?>
                    <?php $gy = $padding + ($chartHeight / 4) * $grid; ?>
                    <line x1="<?= $padding ?>" y1="<?= $gy ?>" x2="<?= $width - $padding ?>" y2="<?= $gy ?>" stroke="var(--admin-border)" stroke-width="1" stroke-dasharray="4,4"></line>
                <?php endfor; ?>
                <!-- Filled Area -->
                <polygon points="<?= $fillPointsStr ?>" fill="url(#chartGrad)"></polygon>
                <!-- Line -->
                <polyline points="<?= $pointsStr ?>" fill="none" stroke="var(--admin-accent)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></polyline>
                <!-- Data Points -->
                <?php $i = 0; foreach ($chartData as $date => $count): ?>
                    <?php 
                    $parts = explode(',', $points[$i]); 
                    $cx = (float) $parts[0];
                    $cy = (float) $parts[1];
                    ?>
                    <circle cx="<?= $cx ?>" cy="<?= $cy ?>" r="5" fill="var(--admin-surface)" stroke="var(--admin-accent)" stroke-width="2"></circle>
                    <text x="<?= $cx ?>" y="<?= $cy - 10.0 ?>" font-size="10" font-weight="700" fill="var(--admin-text)" text-anchor="middle"><?= $count ?></text>
                <?php $i++; endforeach; ?>
                <!-- X Labels -->
                <?php $i = 0; foreach ($chartData as $date => $count): ?>
                    <?php 
                    $parts = explode(',', $points[$i]); 
                    $cx = $parts[0];
                    $label = date('d.m', strtotime($date));
                    ?>
                    <text x="<?= $cx ?>" y="<?= $height - $padding + 18 ?>" font-size="10" fill="var(--admin-muted)" text-anchor="middle"><?= $label ?></text>
                <?php $i++; endforeach; ?>
            </svg>
        </div>
    </div>

    <div class="form-card">
        <h3 style="margin-top:0;"><?= htmlspecialchars(t('Журнал действий'), ENT_QUOTES) ?></h3>
        <p class="form-hint"><?= htmlspecialchars(t('Последние действия администраторов в панели управления.'), ENT_QUOTES) ?></p>
        <div class="activity-feed" style="margin-top:16px;">
            <?php if (empty($recentLogs)): ?>
                <p class="form-hint" style="margin:0;"><?= htmlspecialchars(t('Действий пока нет.'), ENT_QUOTES) ?></p>
            <?php else: ?>
                <?php foreach ($recentLogs as $log): ?>
                    <div class="activity-item">
                        <div class="activity-item__meta">
                            <strong><?= htmlspecialchars((string) ($log['username'] ?? 'System'), ENT_QUOTES) ?></strong>
                            <span class="activity-item__time"><?= date('H:i d.m.Y', strtotime((string) $log['created_at'])) ?></span>
                        </div>
                        <div class="activity-item__desc">
                            <?php $m = strtoupper((string) ($log['method'] ?? '')); ?>
                            <span class="activity-item__badge activity-item__badge--<?= strtolower($m) ?>"><?= htmlspecialchars($m, ENT_QUOTES) ?></span>
                            <code><?= htmlspecialchars((string) ($log['path'] ?? ''), ENT_QUOTES) ?></code>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/layout/footer.php'; ?>
