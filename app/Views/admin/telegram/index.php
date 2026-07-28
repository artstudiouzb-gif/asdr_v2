<?php

use App\Core\AdminUi;
use App\Core\Csrf;

$pageTitle = 'Telegram';
$activeNav = 'telegram';
require __DIR__ . '/../layout/header.php';

/** @var bool $botConfigured */
/** @var string $botUsername */
/** @var bool $botOk */
/** @var bool $linked */
/** @var int $myChatId */
/** @var string|null $linkCode */
/** @var array<string,string> $channel */
/** @var bool $channelOwnTokenConfigured */
/** @var bool $channelEnabled */
/** @var string $notifyChatIds */
/** @var bool $gatewayConfigured */
/** @var bool $setupRestricted */

$channelReady = $channelEnabled && trim((string) ($channel['chat_id'] ?? '')) !== '';
$notifyCount = count(\App\Core\FormNotifier::parseChatIds($notifyChatIds));

// Значок шага через вектоные SVG-иконки
$mark = static function (bool $done, bool $started = true): string {
    if (!$started) {
        return '<span class="badge badge--draft">' . AdminUi::icon('info', 12) . ' не настроено</span>';
    }

    return $done
        ? '<span class="badge badge--published">' . AdminUi::icon('check', 12) . ' готово</span>'
        : '<span class="badge badge--danger">' . AdminUi::icon('warning', 12) . ' требует внимания</span>';
};
?>
<?php // ── Верхняя сводная панель статусов Telegram с SVG-иконками ────────── ?>
<div class="tg-summary-grid">
    <div class="tg-summary-card">
        <div class="tg-summary-card__header">
            <span class="tg-summary-card__title"><?= AdminUi::icon('telegram', 16) ?> Telegram Бот</span>
            <?= $mark($botOk, $botConfigured) ?>
        </div>
        <div class="tg-summary-card__value">
            <?php if ($botOk && $botUsername !== ''): ?>
                <span>@<?= htmlspecialchars($botUsername, ENT_QUOTES) ?></span>
            <?php else: ?>
                <span class="u-inline-594b8be61b">Не подключен</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="tg-summary-card">
        <div class="tg-summary-card__header">
            <span class="tg-summary-card__title"><?= AdminUi::icon('lock', 16) ?> 2FA Вход</span>
            <?= $mark($linked, $botConfigured) ?>
        </div>
        <div class="tg-summary-card__value">
            <?php if ($linked): ?>
                <span>Привязан <small class="u-inline-8001b29eb4">(Chat ID: <?= (int) $myChatId ?>)</small></span>
            <?php else: ?>
                <span class="u-inline-594b8be61b">Не привязан</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="tg-summary-card">
        <div class="tg-summary-card__header">
            <span class="tg-summary-card__title"><?= AdminUi::icon('send', 16) ?> Авто-публикация</span>
            <?= $mark($channelReady, $channelEnabled) ?>
        </div>
        <div class="tg-summary-card__value">
            <?php if ($channelReady): ?>
                <span><?= htmlspecialchars((string) ($channel['chat_id'] ?? ''), ENT_QUOTES) ?></span>
            <?php else: ?>
                <span class="u-inline-594b8be61b">Отключено</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="tg-summary-card">
        <div class="tg-summary-card__header">
            <span class="tg-summary-card__title"><?= AdminUi::icon('email', 16) ?> Заявки с сайта</span>
            <?= $mark($notifyCount > 0, $notifyChatIds !== '') ?>
        </div>
        <div class="tg-summary-card__value">
            <?php if ($notifyCount > 0): ?>
                <span><?= $notifyCount ?> получател<?= $notifyCount === 1 ? 'ь' : ($notifyCount < 5 ? 'я' : 'ей') ?></span>
            <?php else: ?>
                <span class="u-inline-594b8be61b">Выключены</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php // ── Шаг 1. Бот ───────────────────────────────────────────────────── ?>
<div class="form-card">
    <h2 class="u-inline-8981e56111">
        <?= AdminUi::icon('telegram', 20) ?> 1. Настройка Telegram-бота <?= $mark($botOk, $botConfigured) ?>
    </h2>
    <p class="form-hint">
        Создайте бота у <strong>@BotFather</strong> (команда <code>/newbot</code>) и вставьте токен.
        Токен всегда можно посмотреть в Telegram: <code>/mybots</code> → ваш бот → <em>API Token</em>.
    </p>
    <form method="post" action="/admin/telegram/bot" class="form-grid">
        <?= Csrf::field() ?>
        <div class="form-field">
            <label for="telegram_bot_token">Токен бота (Bot API Token)</label>
            <input type="password" id="telegram_bot_token" name="telegram_bot_token"
                   value=""
                   placeholder="<?= $botConfigured ? 'Сохранён — оставьте пустым без изменений' : '1234567890:AAH…' ?>"
                   autocomplete="new-password" spellcheck="false">
            <span class="form-hint">
                Формат: цифры, двоеточие, ключ. Без слова «bot» в начале. Этот токен используется для кодов входа и публикаций.
            </span>
            <?php if ($botConfigured && !$setupRestricted): ?>
                <label class="form-hint"><input type="checkbox" name="clear_telegram_bot_token" value="1"> Удалить сохранённый токен</label>
            <?php endif; ?>
        </div>
        <div class="form-actions u-inline-df20dd0984">
            <button type="submit" class="btn btn--primary"><?= AdminUi::icon('save') ?>Сохранить токен</button>
            <?php if ($botConfigured): ?>
                <button type="submit" formaction="/admin/telegram/bot/check" class="btn btn--outline">
                    <?= AdminUi::icon('check') ?>Проверить бота
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php // ── Шаг 2. Привязка администратора ───────────────────────────────── ?>
<div class="form-card u-inline-9eb125f52f">
    <h2 class="u-inline-8981e56111">
        <?= AdminUi::icon('lock', 20) ?> 2. Коды входа и двухфакторка (2FA) <?= $mark($linked, $botConfigured) ?>
    </h2>
    <?php if (!$botConfigured): ?>
        <p class="form-hint">Сначала сохраните токен бота в шаге 1.</p>
    <?php elseif ($linked): ?>
        <p class="form-hint">
            Ваш аккаунт привязан, коды входа приходят в Telegram от бота.
            Ваш <code>chat_id</code>: <strong><?= (int) $myChatId ?></strong> — он пригодится для получения уведомлений о заявках с сайта.
        </p>
    <?php else: ?>
        <p class="form-hint">Инструкция по привязке аккаунта:</p>
        <ol class="form-hint u-inline-f60258483c">
            <li>Откройте бота
                <?php if ($botUsername !== ''): ?>
                    <a href="https://t.me/<?= htmlspecialchars($botUsername, ENT_QUOTES) ?>" target="_blank" rel="noopener"><strong>@<?= htmlspecialchars($botUsername, ENT_QUOTES) ?></strong></a>
                <?php else: ?>
                    в Telegram
                <?php endif; ?>
                и нажмите «Start».
            </li>
            <li>Отправьте боту код привязки ниже:</li>
        </ol>

        <div class="tg-code-box">
            <div>
                <div class="u-inline-afaed7b4de">Код привязки</div>
                <div class="tg-code-val" id="tg_link_code_val"><?= htmlspecialchars((string) $linkCode, ENT_QUOTES) ?></div>
            </div>
            <button type="button" class="btn btn--small btn--outline" onclick="copyTgCode(this)">
                <?= AdminUi::icon('copy') ?>Скопировать код
            </button>
        </div>

        <form method="post" action="/admin/telegram/link">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn--primary"><?= AdminUi::icon('check') ?>Проверить привязку аккаунта</button>
        </form>
    <?php endif; ?>
</div>

<?php // ── Шаг 3. Канал ─────────────────────────────────────────────────── ?>
<div class="form-card u-inline-9eb125f52f">
    <?php
    $chatIdVal = trim((string) ($channel['chat_id'] ?? ''));
    $channelBadge = '';
    if ($chatIdVal === '') {
        $channelBadge = '<span class="badge badge--draft">' . AdminUi::icon('info', 12) . ' не настроено</span>';
    } elseif ($channelEnabled) {
        $channelBadge = '<span class="badge badge--published">' . AdminUi::icon('check', 12) . ' включено (' . htmlspecialchars($chatIdVal, ENT_QUOTES) . ')</span>';
    } else {
        $channelBadge = '<span class="badge badge--warning">' . AdminUi::icon('pause', 12) . ' сохранен (' . htmlspecialchars($chatIdVal, ENT_QUOTES) . '), выключен</span>';
    }
    ?>
    <h2 class="u-inline-8981e56111">
        <?= AdminUi::icon('send', 20) ?> 3. Публикация новостей в Telegram-канал <?= $channelBadge ?>
    </h2>
    <p class="form-hint">
        Бот должен быть <strong>администратором канала</strong> с правом «Публикация сообщений».
        Посты в канале отображаются от имени канала.
    </p>
    <form method="post" action="/admin/telegram/channel" class="form-grid">
        <?= Csrf::field() ?>
        <div class="form-field form-field--checkbox">
            <input type="checkbox" id="tg_enabled" name="enabled" value="1" <?= $channelEnabled ? 'checked' : '' ?>>
            <label for="tg_enabled">Включить авто-публикацию новостей в канал</label>
        </div>
        <div class="form-field">
            <label for="tg_chat_id">Канал</label>
            <input type="text" id="tg_chat_id" name="chat_id"
                   value="<?= htmlspecialchars((string) ($channel['chat_id'] ?? ''), ENT_QUOTES) ?>"
                   placeholder="@имя_канала или -1001234567890" autocomplete="off" spellcheck="false">
            <span class="form-hint">
                Публичный канал — <code>@имя_канала</code>, приватный — <code>-100…</code>.
            </span>
        </div>

        <div class="form-field">
            <label for="tg_signature">Подпись под постом (необязательно)</label>
            
            <?php // Интерактивная панель вставки HTML-тегов для Telegram ?>
            <div class="tg-toolbar">
                <span class="tg-toolbar__title">Вставить HTML-тег:</span>
                <button type="button" class="tg-tag-btn" onclick="insertTgTag('<b>', '</b>')"><b>B</b> Жирный</button>
                <button type="button" class="tg-tag-btn" onclick="insertTgTag('<i>', '</i>')"><i>I</i> Курсив</button>
                <button type="button" class="tg-tag-btn" onclick="insertTgTag('<code>', '</code>')"><?= AdminUi::icon('code', 13) ?> <code>Код (копирование)</code></button>
                <button type="button" class="tg-tag-btn" onclick="insertTgTag('<blockquote>', '</blockquote>')">💬 Цитата</button>
                <button type="button" class="tg-tag-btn" onclick="insertTgTag('<tg-spoiler>', '</tg-spoiler>')">👁 Спойлер</button>
                <button type="button" class="tg-tag-btn" onclick="insertTgTag('<a href=&quot;https://example.com&quot;>', '</a>')"><?= AdminUi::icon('external', 13) ?> Ссылка</button>
            </div>

            <textarea class="u-inline-8ff9961267" id="tg_signature" name="signature" rows="3"><?= htmlspecialchars((string) ($channel['signature'] ?? ''), ENT_QUOTES) ?></textarea>
            <span class="form-hint">
                Поддерживаются HTML-теги Telegram: <code>&lt;b&gt;</code>, <code>&lt;i&gt;</code>, <code>&lt;code&gt;</code> (автокопирование по клику), <code>&lt;blockquote&gt;</code>, <code>&lt;tg-spoiler&gt;</code>, <code>&lt;a href="..."&gt;</code>.
            </span>
        </div>

        <details class="form-section">
            <summary>Отдельный бот для публикаций <span class="form-section__hint">(опционально)</span></summary>
            <div class="form-section__body">
                <div class="form-field">
                    <label for="tg_own_token">Токен бота-публикатора</label>
                    <input type="password" id="tg_own_token" name="own_token"
                           value=""
                           placeholder="<?= $channelOwnTokenConfigured ? 'Сохранён — оставьте пустым без изменений' : 'Пусто — использовать основного бота' ?>"
                           autocomplete="new-password" spellcheck="false">
                    <span class="form-hint">
                        Если не заполнено — публикации отправляются основным ботом.
                    </span>
                    <?php if ($channelOwnTokenConfigured): ?>
                        <label class="form-hint"><input type="checkbox" name="clear_own_token" value="1"> Удалить отдельный токен</label>
                    <?php endif; ?>
                </div>
            </div>
        </details>

        <div class="form-actions u-inline-df20dd0984">
            <button type="submit" class="btn btn--primary"><?= AdminUi::icon('save') ?>Сохранить канал</button>
            <button type="submit" formaction="/admin/telegram/channel/check" class="btn btn--outline">
                <?= AdminUi::icon('check') ?>Проверить канал и права бота
            </button>
        </div>
    </form>
</div>

<?php // ── Шаг 4. Дополнительно (Заявки и Gateway) ───────────────────────── ?>
<div class="form-card u-inline-d8a0156797">
    <h2 class="u-inline-8981e56111">
        <?= AdminUi::icon('settings', 20) ?> 4. Уведомления о заявках с сайта и Telegram Gateway
    </h2>
    <form method="post" action="/admin/telegram/extras" class="form-grid">
        <?= Csrf::field() ?>
        <div class="form-field">
            <label for="telegram_notify_chat_ids">Уведомления о заявках с форм: chat_id получателей</label>
            <div class="u-inline-78cead6503">
                <input class="u-inline-7623f05545" type="text" id="telegram_notify_chat_ids" name="telegram_notify_chat_ids"
                       value="<?= htmlspecialchars($notifyChatIds, ENT_QUOTES) ?>"
                       placeholder="123456789, -1001234567890" autocomplete="off" spellcheck="false">
                <?php if ($linked && $myChatId > 0): ?>
                    <button type="button" class="btn btn--small btn--outline" onclick="addMyChatId(<?= (int) $myChatId ?>)" title="Добавить мой Chat ID">
                        + Добавить мой ID (<?= (int) $myChatId ?>)
                    </button>
                <?php endif; ?>
            </div>
            <span class="form-hint">
                Сообщения о новых заявках с сайта приходят на указанные chat_id через запятую. Укажите отрицательный ID для группы.
            </span>
        </div>

        <div class="form-field">
            <label for="telegram_gateway_token">Токен Telegram Gateway API (платный резервный SMS-сервис)</label>
            <input type="password" id="telegram_gateway_token" name="telegram_gateway_token"
                   value=""
                   placeholder="<?= $gatewayConfigured ? 'Сохранён — оставьте пустым без изменений' : 'Введите токен Gateway' ?>"
                   autocomplete="new-password" spellcheck="false">
            <?php if ($gatewayConfigured): ?>
                <label class="form-hint"><input type="checkbox" name="clear_telegram_gateway_token" value="1"> Удалить токен Gateway</label>
            <?php endif; ?>
            <span class="form-hint">
                Служба <code>gateway.telegram.org</code> (для отправки кодов входа на телефоны администраторов без привязки бота).
            </span>
        </div>

        <div class="form-actions form-actions--sticky">
            <button type="submit" class="btn btn--primary"><?= AdminUi::icon('save') ?>Сохранить настройки Telegram</button>
        </div>
    </form>
</div>

<script>
// Вставка HTML-тегов Telegram в поле подписи
function insertTgTag(startTag, endTag) {
    const textarea = document.getElementById('tg_signature');
    if (!textarea) return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;

    const selectedText = text.substring(start, end);
    const replacement = startTag + selectedText + endTag;

    textarea.value = text.substring(0, start) + replacement + text.substring(end);
    textarea.focus();
    textarea.selectionStart = start + startTag.length;
    textarea.selectionEnd = start + startTag.length + selectedText.length;
}

// Скопировать код привязки (работает по HTTPS и HTTP с фаллбэком)
function copyTgCode(btnEl) {
    const codeEl = document.getElementById('tg_link_code_val');
    if (!codeEl) return;
    const text = codeEl.innerText.trim();
    if (window.copyToClipboard) {
        window.copyToClipboard(text, btnEl);
    }
}

// Быстрое добавление Chat ID администратора
function addMyChatId(chatId) {
    const input = document.getElementById('telegram_notify_chat_ids');
    if (!input) return;
    let val = input.value.trim();
    if (val === '') {
        input.value = chatId;
    } else if (!val.includes(String(chatId))) {
        input.value = val + ', ' + chatId;
    }
}
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
