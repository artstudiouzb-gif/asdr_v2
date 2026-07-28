<?php
/** @var array $requirements */
/** @var array $permissions */
/** @var bool $allPassed */
$step = '1';
require __DIR__ . '/_header.php';
?>
<div class="install-header">
    <h1 class="install-header__title">Проверка системного окружения</h1>
    <p class="install-header__desc">Убедитесь, что ваш сервер полностью соответствует всем техническим требованиями для стабильной работы ASDR CMS.</p>
</div>

<div class="check-grid-2col">
    <div class="check-card-group">
        <div class="check-card-group__title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>
            Параметры PHP и модули
        </div>
        <?php foreach ($requirements as $check): ?>
            <div class="check-item">
                <div class="check-item__info">
                    <div class="check-item__icon <?= $check['ok'] ? 'ok' : 'fail' ?>">
                        <?= $check['ok'] ? '✓' : '✕' ?>
                    </div>
                    <div>
                        <div class="check-item__label"><?= htmlspecialchars($check['label'], ENT_QUOTES) ?></div>
                        <?php if (!$check['ok'] && !empty($check['hint'])): ?>
                            <div class="check-item__hint"><?= htmlspecialchars($check['hint'], ENT_QUOTES) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="badge badge--<?= $check['ok'] ? 'success' : 'danger' ?> u-inline-07bbf13a37">
                    <?= $check['ok'] ? 'Соответствует' : 'Ошибка' ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="check-card-group">
        <div class="check-card-group__title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            Права доступа к директориям
        </div>
        <?php foreach ($permissions as $check): ?>
            <div class="check-item">
                <div class="check-item__info">
                    <div class="check-item__icon <?= $check['ok'] ? 'ok' : 'fail' ?>">
                        <?= $check['ok'] ? '✓' : '✕' ?>
                    </div>
                    <div>
                        <div class="check-item__label"><?= htmlspecialchars($check['label'], ENT_QUOTES) ?></div>
                        <?php if (!$check['ok'] && !empty($check['hint'])): ?>
                            <div class="check-item__hint"><?= htmlspecialchars($check['hint'], ENT_QUOTES) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="badge badge--<?= $check['ok'] ? 'success' : 'danger' ?> u-inline-07bbf13a37">
                    <?= $check['ok'] ? 'Запись разрешена' : 'Нет прав' ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="form-actions">
    <?php if ($allPassed): ?>
        <a href="/install/step2" class="btn btn--primary">
            Продолжить к настройке БД
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
    <?php else: ?>
        <a href="/install" class="btn btn--secondary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
            Проверить снова
        </a>
        <span class="form-hint u-inline-30739594c7">Исправьте ошибки выше для продолжения.</span>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
