<?php

use App\Core\Csrf;
use App\Models\SessionRegistry;

/** @var array $sessions */
/** @var string $currentHash */
/** @var array|null $profileUser */
$sessions = $sessions ?? [];
$currentHash = $currentHash ?? '';
$profileUser = $profileUser ?? null;
$pageTitle = 'Профиль и безопасность';
$activeNav = 'profile';
require __DIR__ . '/../layout/header.php';

function ua_short(?string $ua): string
{
    $ua = (string) $ua;
    $browser = 'Браузер';
    if (stripos($ua, 'Firefox') !== false) { $browser = 'Firefox'; }
    elseif (stripos($ua, 'Edg') !== false) { $browser = 'Edge'; }
    elseif (stripos($ua, 'Chrome') !== false) { $browser = 'Chrome'; }
    elseif (stripos($ua, 'Safari') !== false) { $browser = 'Safari'; }
    $os = '';
    if (stripos($ua, 'Windows') !== false) { $os = 'Windows'; }
    elseif (stripos($ua, 'Android') !== false) { $os = 'Android'; }
    elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) { $os = 'iOS'; }
    elseif (stripos($ua, 'Mac') !== false) { $os = 'macOS'; }
    elseif (stripos($ua, 'Linux') !== false) { $os = 'Linux'; }
    return trim($browser . ($os !== '' ? ' · ' . $os : ''));
}
?>

<div class="form-card">
    <h2 style="margin-top:0;">Язык интерфейса по умолчанию</h2>
    <p class="form-hint">Выберите ваш персональный язык для работы в панели управления. Он будет использоваться при каждом входе.</p>
    <form method="post" action="/admin/profile/admin-lang" class="form-grid" style="max-width:480px;">
        <?= Csrf::field() ?>
        <div class="form-field">
            <label for="profile_admin_lang">Язык админ-панели</label>
            <select id="profile_admin_lang" name="admin_lang">
                <option value="">— Системный по умолчанию —</option>
                <?php foreach (\App\Models\Language::active() as $l): ?>
                    <?php
                    $code = (string) $l['code'];
                    $name = (string) $l['name'];
                    $userLang = (string) ($profileUser['admin_lang'] ?? '');
                    ?>
                    <option value="<?= htmlspecialchars($code, ENT_QUOTES) ?>"<?= $code === $userLang ? ' selected' : '' ?>>
                        <?= htmlspecialchars($name, ENT_QUOTES) ?> (<?= strtoupper(htmlspecialchars($code, ENT_QUOTES)) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Сохранить язык</button>
        </div>
    </form>
</div>

<?php
$currentUserId = (int) ($profileUser['id'] ?? 0);
$currentTheme = $_SESSION['admin_theme'] ?? (\App\Models\Setting::get('admin_theme_user_' . $currentUserId, 'default'));

$themeList = [
    'default' => [
        'name' => 'Лазурный сапфир (По умолчанию)',
        'desc' => 'Современный сине-лазурный акцент (#0284c7), строгий тёмно-синий топбар (#0f172a) и свежий светлый сайдбар.',
        'accent' => '#0284c7',
        'topbar' => '#0f172a',
        'sidebar' => '#f8fafc',
    ],
    'wp_classic' => [
        'name' => 'Классический тёмный (CMS Style)',
        'desc' => 'Классический стиль с тёмно-серым сайдбаром (#2c3338), строгим топбаром (#1d2327) и синим акцентом (#2271b1).',
        'accent' => '#2271b1',
        'topbar' => '#1d2327',
        'sidebar' => '#2c3338',
    ],
    'navy_teal' => [
        'name' => 'Глубокий океан (Navy & Teal)',
        'desc' => 'Премиальный тёмно-синий сайдбар (#1e293b) с насыщенным бирюзовым акцентом (#0d9488).',
        'accent' => '#0d9488',
        'topbar' => '#0f172a',
        'sidebar' => '#1e293b',
    ],
    'royal_purple' => [
        'name' => 'Премиум Пурпур (Royal Purple)',
        'desc' => 'Королевский тёмно-фиолетовый сайдбар (#1e1b4b) со свежим пурпурным акцентом (#7c3aed).',
        'accent' => '#7c3aed',
        'topbar' => '#18181b',
        'sidebar' => '#1e1b4b',
    ],
    'dark_emerald' => [
        'name' => 'Ночной изумруд (Full Dark Emerald)',
        'desc' => 'Полноценная тёмная тема для ночной работы с контрастными изумрудными акцентами (#10b981).',
        'accent' => '#10b981',
        'topbar' => '#020617',
        'sidebar' => '#0f172a',
    ],
];
?>

<div class="form-card" style="margin-top:24px;">
    <h2 style="margin-top:0;display:flex;align-items:center;gap:10px;">
        🎨 Цветовая схема админ-панели
    </h2>
    <p class="form-hint" style="margin-bottom:18px;">
        Выберите персональное цветовое оформление интерфейса. Нажимая на варианты ниже, вы можете <strong>в реальном времени мгновенно примерять темы</strong> перед сохранением!
    </p>

    <form method="post" action="/admin/profile/admin-theme">
        <?= Csrf::field() ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;margin-bottom:20px;">
            <?php foreach ($themeList as $tKey => $tInfo): ?>
                <?php $isAct = $tKey === $currentTheme; ?>
                <label style="border:2px solid <?= $isAct ? 'var(--admin-primary,#0284c7)' : 'var(--admin-border,#cbd5e1)' ?>;border-radius:12px;padding:16px;background:var(--admin-card-bg,#fff);cursor:pointer;display:flex;flex-direction:column;gap:10px;transition:all .15s ease;box-shadow:<?= $isAct ? '0 4px 14px rgba(2,132,199,0.15)' : 'none' ?>;"
                       onclick="document.documentElement.setAttribute('data-admin-theme', '<?= $tKey ?>');">
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <span style="font-weight:700;font-size:0.95rem;display:flex;align-items:center;gap:8px;">
                            <input type="radio" name="admin_theme" value="<?= $tKey ?>" <?= $isAct ? 'checked' : '' ?> style="margin:0;">
                            <?= htmlspecialchars($tInfo['name'], ENT_QUOTES) ?>
                        </span>
                        <?php if ($isAct): ?>
                            <span class="badge badge--success" style="font-size:0.75rem;">Активна</span>
                        <?php endif; ?>
                    </div>
                    
                    <div style="display:flex;align-items:center;gap:8px;background:var(--admin-surface-soft,#f8fafc);padding:8px 12px;border-radius:8px;border:1px solid var(--admin-border,#e2e8f0);">
                        <span style="font-size:0.78rem;color:var(--admin-text-muted,#64748b);font-weight:600;">Палитра:</span>
                        <div style="display:flex;gap:6px;margin-left:auto;">
                            <span style="width:20px;height:20px;border-radius:50%;background:<?= $tInfo['topbar'] ?>;border:1px solid rgba(0,0,0,0.15);" title="Топбар: <?= $tInfo['topbar'] ?>"></span>
                            <span style="width:20px;height:20px;border-radius:50%;background:<?= $tInfo['sidebar'] ?>;border:1px solid rgba(0,0,0,0.15);" title="Сайдбар: <?= $tInfo['sidebar'] ?>"></span>
                            <span style="width:20px;height:20px;border-radius:50%;background:<?= $tInfo['accent'] ?>;border:1px solid rgba(0,0,0,0.15);" title="Акцент: <?= $tInfo['accent'] ?>"></span>
                        </div>
                    </div>

                    <p style="margin:0;font-size:0.82rem;color:var(--admin-text-muted,#64748b);line-height:1.4;">
                        <?= htmlspecialchars($tInfo['desc'], ENT_QUOTES) ?>
                    </p>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Сохранить цветовую схему</button>
        </div>
    </form>
</div>

<div class="form-card" style="margin-top:24px;">
    <h2 style="margin-top:0;">Смена пароля</h2>
    <form method="post" action="/admin/profile/password" class="form-grid" style="max-width:480px;">
        <?= Csrf::field() ?>
        <div class="form-field">
            <label for="current_password">Текущий пароль</label>
            <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
        </div>
        <div class="form-field">
            <label for="new_password">Новый пароль</label>
            <input type="password" id="new_password" name="new_password" autocomplete="new-password" required>
            <span class="form-hint">Минимум 10 символов, минимум две группы символов, не из списка популярных паролей.</span>
        </div>
        <div class="form-field">
            <label for="new_password_confirm">Повторите новый пароль</label>
            <input type="password" id="new_password_confirm" name="new_password_confirm" autocomplete="new-password" required>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn--primary">Изменить пароль</button>
        </div>
    </form>
</div>

<div class="form-card" style="margin-top:24px;">
    <h2 style="margin-top:0;">Код входа через Telegram-бота (бесплатно)</h2>
    <?php if (!($botConfigured ?? false)): ?>
        <p class="form-hint">
            Бот не настроен. Создайте бота у <strong>@BotFather</strong> (бесплатно) и укажите токен
            в разделе <a href="/admin/telegram">Telegram</a> — там же проверка подключения и привязка.
        </p>
    <?php elseif ($botLinked ?? false): ?>
        <p><span class="badge badge--success">Привязан</span> Коды входа приходят вам в Telegram от бота.</p>
        <p class="form-hint">Ваш chat_id: <code><?= (int) ($profileUser['telegram_chat_id'] ?? 0) ?></code> — укажите его в разделе <a href="/admin/telegram">Telegram</a>, чтобы получать уведомления о заявках с форм.</p>
        <form method="post" action="/admin/profile/telegram/unlink" class="form-grid" style="max-width:480px;">
            <?= Csrf::field() ?>
            <div class="form-field">
                <label for="tg_password">Подтвердите паролем, чтобы отвязать</label>
                <input type="password" id="tg_password" name="password" autocomplete="current-password" required>
            </div>
            <div class="form-actions"><button type="submit" class="btn btn--danger">Отвязать Telegram</button></div>
        </form>
    <?php else: ?>
        <p class="form-hint">Привяжите свой Telegram — и коды входа будут приходить от бота бесплатно (без QR-кодов и секретов на экране):</p>
        <ol style="margin:0 0 14px 18px;line-height:1.8;">
            <li>Откройте бота<?php if (!empty($botUsername)): ?> <a href="https://t.me/<?= htmlspecialchars($botUsername, ENT_QUOTES) ?>" target="_blank" rel="noopener">@<?= htmlspecialchars($botUsername, ENT_QUOTES) ?></a><?php endif; ?> и нажмите <strong>Start</strong>.</li>
            <li>Отправьте боту код: <code style="font-size:15px;padding:3px 8px;background:var(--admin-accent-soft,#eef0fe);border-radius:6px;"><?= htmlspecialchars((string) ($linkCode ?? ''), ENT_QUOTES) ?></code></li>
            <li>Нажмите кнопку ниже.</li>
        </ol>
        <form method="post" action="/admin/profile/telegram/link">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn--primary">Проверить привязку</button>
        </form>
    <?php endif; ?>
</div>

<div class="form-card" style="margin-top:24px;">
    <h2 style="margin-top:0;">Резервный канал: телефон (платный шлюз)</h2>
    <p class="form-hint">Код входа приходит в Telegram от официального канала
       <strong>Verification&nbsp;Codes</strong> (t.me/VerificationCodes) на номер, привязанный к вашему
       Telegram-аккаунту. Без телефона вход выполняется только по паролю.</p>
    <form method="post" action="/admin/profile/phone" class="form-grid" style="max-width:480px;">
        <?= Csrf::field() ?>
        <div class="form-field">
            <label for="phone">Телефон (международный формат)</label>
            <input type="tel" id="phone" name="phone" placeholder="+998901234567" value="<?= htmlspecialchars((string) ($profileUser['phone'] ?? ''), ENT_QUOTES) ?>" autocomplete="tel">
            <span class="form-hint">Оставьте пустым, чтобы отключить код подтверждения для своего аккаунта.</span>
        </div>
        <div class="form-field">
            <label for="ph_password">Подтвердите паролем</label>
            <input type="password" id="ph_password" name="password" autocomplete="current-password" required>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn">Сохранить телефон</button>
        </div>
    </form>
</div>

<div class="form-card" style="margin-top:24px;">
    <h2 style="margin-top:0;">Активные сессии</h2>
    <p class="form-hint">Список устройств, где выполнен вход. Отзыв сессии мгновенно завершает её на сервере.</p>
    <table class="data-table">
        <thead>
            <tr><th>Устройство</th><th>IP</th><th>Вход</th><th>Активность</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($sessions as $s): ?>
            <?php $isCurrent = hash_equals((string) $currentHash, (string) $s['sid_hash']); ?>
            <tr>
                <td><?= htmlspecialchars(ua_short($s['user_agent'] ?? ''), ENT_QUOTES) ?>
                    <?php if ($isCurrent): ?><span class="badge">текущая</span><?php endif; ?>
                </td>
                <td><?= htmlspecialchars((string) ($s['ip_address'] ?? '—'), ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars((string) $s['created_at'], ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars((string) $s['last_seen_at'], ENT_QUOTES) ?></td>
                <td>
                    <?php if (!$isCurrent): ?>
                    <form method="post" action="/admin/profile/sessions/<?= (int) $s['id'] ?>/revoke">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn--small">Отозвать</button>
                    </form>
                    <?php else: ?>—<?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <form method="post" action="/admin/profile/sessions/revoke-others" style="margin-top:16px;">
        <?= Csrf::field() ?>
        <button type="submit" class="btn">Выйти на всех других устройствах</button>
    </form>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
