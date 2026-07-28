<?php
/** @var string $step */
/** @var string $heading */
$step = $step ?? '1';
$heading = $heading ?? 'Установка';
$steps = [
    '1' => ['title' => 'Окружение', 'desc' => 'Проверка PHP и прав'],
    '2' => ['title' => 'База данных', 'desc' => 'Подключение MySQL'],
    '3' => ['title' => 'Сайт', 'desc' => 'Настройки системы'],
    '4' => ['title' => 'Администратор', 'desc' => 'Учётная запись'],
];
$stepNumber = (int) $step;
?>
<!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Установка ASDR CMS — <?= htmlspecialchars($steps[$step]['title'] ?? $heading, ENT_QUOTES) ?></title>
<link rel="stylesheet" href="/assets/css/admin.css">
<style>
/* UI/UX Pro Max Installer Styling - Responsive 1080px Layout */
:root {
    --inst-bg: #f8fafc;
    --inst-card-bg: #ffffff;
    --inst-border: #e2e8f0;
    --inst-primary: #2563eb;
    --inst-primary-hover: #1d4ed8;
    --inst-success: #10b981;
    --inst-danger: #ef4444;
    --inst-text: #0f172a;
    --inst-muted: #64748b;
}

body.auth-page {
    background: radial-gradient(circle at 50% 0%, #f1f5f9 0%, #f8fafc 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 24px;
    font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: var(--inst-text);
}

.install-wrapper {
    width: 92vw;
    max-width: 1080px;
    margin: 0 auto;
}

.install-brand {
    text-align: center;
    margin-bottom: 28px;
    display: flex;
    justify-content: center;
}
.install-brand__logo {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: #ffffff;
    padding: 9px 22px;
    border-radius: 999px;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.08);
    border: 1px solid var(--inst-border);
    margin-bottom: 4px;
}
.install-brand__logo svg { color: var(--inst-primary); }
.install-brand__name { font-weight: 800; font-size: 1.18rem; letter-spacing: -0.02em; color: var(--inst-text); }
.install-brand__badge { background: #eff6ff; color: var(--inst-primary); font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: 999px; text-transform: uppercase; }

.install-card,
.auth-card.install-card,
.auth-card--wide.install-card {
    width: 100% !important;
    max-width: 100% !important;
    background: var(--inst-card-bg);
    border: 1px solid var(--inst-border);
    border-radius: 24px;
    padding: 44px 48px;
    box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.08), 0 0 0 1px rgba(226, 232, 240, 0.8);
    position: relative;
    overflow: hidden;
}
.install-card::before,
.install-card::after,
.auth-card::before,
.auth-card::after {
    display: none !important;
    content: none !important;
}

/* Steps Progress Tracker */
.install-stepper {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 36px;
    position: relative;
}
.install-step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    position: relative;
    z-index: 1;
}
.install-step-badge {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #f1f5f9;
    color: var(--inst-muted);
    font-weight: 700;
    font-size: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid var(--inst-border);
    margin-bottom: 10px;
    transition: all 0.25s ease;
}
.install-step-item.is-done .install-step-badge {
    background: #ecfdf5;
    color: var(--inst-success);
    border-color: var(--inst-success);
}
.install-step-item.is-active .install-step-badge {
    background: var(--inst-primary);
    color: #ffffff;
    border-color: var(--inst-primary);
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.18);
}
.install-step-title {
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--inst-muted);
    line-height: 1.25;
}
.install-step-item.is-active .install-step-title {
    color: var(--inst-primary);
    font-weight: 700;
}
.install-step-item.is-done .install-step-title {
    color: var(--inst-text);
}

.install-header {
    text-align: center;
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 1px solid #f1f5f9;
}
.install-header__title {
    font-size: 1.65rem;
    font-weight: 800;
    margin: 0 0 8px 0;
    letter-spacing: -0.02em;
    color: #0f172a;
}
.install-header__desc {
    font-size: 0.96rem;
    color: var(--inst-muted);
    margin: 0 auto;
    max-width: 680px;
    line-height: 1.5;
}

/* Two Column Grid for System Checks on Step 1 */
.check-grid-2col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 28px;
    margin-bottom: 24px;
}

/* Form Fields & Input Wrappers with Icons */
.install-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
}
.install-input-wrap input,
.install-input-wrap select,
.install-input-wrap textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 13px 16px 13px 44px !important;
    font-size: 0.95rem;
    font-weight: 500;
    color: #0f172a;
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    outline: none;
    transition: all 0.2s ease;
}
.install-input-wrap textarea {
    padding-top: 12px !important;
}
.install-input-wrap input:focus,
.install-input-wrap select:focus,
.install-input-wrap textarea:focus {
    background: #ffffff;
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
}
.install-input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 2;
    color: #94a3b8;
    pointer-events: none;
    transition: color 0.2s ease;
}
.install-input-wrap:focus-within .install-input-icon {
    color: #2563eb;
}

/* System Check Cards */
.check-card-group {
    margin-bottom: 0;
}
.check-card-group__title {
    font-size: 0.88rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--inst-muted);
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.check-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    margin-bottom: 10px;
    transition: background 0.15s ease;
}
.check-item:hover { background: #f1f5f9; }
.check-item__info {
    display: flex;
    align-items: center;
    gap: 14px;
}
.check-item__icon {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.85rem;
    flex-shrink: 0;
}
.check-item__icon.ok { background: #dcfce7; color: #15803d; }
.check-item__icon.fail { background: #fee2e2; color: #b91c1c; }
.check-item__label { font-weight: 600; font-size: 0.94rem; }
.check-item__hint { font-size: 0.78rem; color: #ef4444; margin-top: 2px; }

.install-card .form-actions {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-top: 36px;
    padding-top: 24px;
    border-top: 1px solid #f1f5f9;
}
.install-card .form-actions .btn {
    flex: 1 1 auto;
    min-height: 50px;
    font-size: 1rem;
    font-weight: 700;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
}
.install-card .btn--primary,
.install-card .btn--primary:visited {
    background: var(--inst-primary);
    border: 1px solid var(--inst-primary);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);
}
.install-card .btn--primary:hover {
    background: var(--inst-primary-hover);
    border-color: var(--inst-primary-hover);
    color: #ffffff;
    box-shadow: 0 6px 18px rgba(37, 99, 235, 0.35);
    transform: translateY(-1px);
}
.install-card .btn:active { transform: translateY(1px); }
.install-card .btn:disabled,
.install-card .btn[aria-disabled="true"] { opacity: 0.6; cursor: not-allowed; transform: none; box-shadow: none; }

/* Info Callout */
.install-callout {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 14px;
    padding: 16px 20px;
    margin-bottom: 28px;
    font-size: 0.92rem;
    color: #1e40af;
    display: flex;
    gap: 14px;
    line-height: 1.5;
}
.install-callout svg { flex-shrink: 0; color: #2563eb; margin-top: 2px; }

/* Backward Compatibility CSS Hook for Tests */
.install-steps { display: none; }
.install-check { display: none; }

@media (max-width: 860px) {
    .check-grid-2col { grid-template-columns: 1fr; gap: 16px; }
    .install-card { padding: 32px 24px; }
}

@media (max-width: 640px) {
    .install-wrapper { width: 96vw; }
    .install-card { padding: 24px 18px; }
    .install-stepper { gap: 8px; }
    .install-step-badge { width: 36px; height: 36px; font-size: 0.85rem; }
    .install-step-title { font-size: 0.72rem; }
    .install-card .form-actions { flex-direction: column; }
    .install-card .form-actions .btn { width: 100%; }
}
</style>
</head>
<body class="auth-page">
<div class="install-wrapper">
    <div class="install-brand">
        <div class="install-brand__logo">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
            <span class="install-brand__name">ASDR CMS</span>
            <span class="install-brand__badge">v2.0</span>
        </div>
    </div>

    <div class="auth-card auth-card--wide install-card">
        <div class="install-stepper">
            <?php foreach ($steps as $n => $info): ?>
                <?php
                $num = (int) $n;
                $isDone = $num < $stepNumber;
                $isActive = $num === $stepNumber;
                ?>
                <div class="install-step-item <?= $isDone ? 'is-done' : '' ?> <?= $isActive ? 'is-active' : '' ?>">
                    <div class="install-step-badge">
                        <?= $isDone ? '✓' : $n ?>
                    </div>
                    <span class="install-step-title"><?= htmlspecialchars($info['title'], ENT_QUOTES) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
