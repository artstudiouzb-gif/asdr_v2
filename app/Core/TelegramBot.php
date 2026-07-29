<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Setting;

/**
 * Бесплатная доставка кодов входа и уведомлений через собственного Telegram-бота (Bot API).
 * Токен выдаёт @BotFather бесплатно; сообщения приходят от вашего бота.
 * Сообщения размечаются через HTML с интерактивными моноширинными блоками <code>code</code>
 * для мгновенного копирования кодов, номеров телефонов и email по клику/тапу.
 */
final class TelegramBot
{
    private const API_BASE = 'https://api.telegram.org';
    private const TIMEOUT_SECONDS = 10;

    public static function isConfigured(): bool
    {
        return trim(Setting::get('telegram_bot_token', '')) !== '';
    }

    /** Информация о боте (username для ссылки t.me/...); null при ошибке. */
    public static function getMe(): ?array
    {
        $res = self::request('getMe', []);

        return is_array($res) ? $res : null;
    }

    /** Отправляет одноразовый код входа в админку привязанному аккаунту с авто-копированием по клику. */
    public static function sendLoginCode(int $chatId, string $code): bool
    {
        $safeCode = htmlspecialchars($code, ENT_QUOTES);
        $text = "<b>Код входа в панель управления</b>\n\n"
            . "Код: <code>{$safeCode}</code>\n\n"
            . "⏱ <i>Действует 5 минут. Никому не сообщайте этот код.</i>";

        return self::sendMessage($chatId, $text, 'HTML');
    }

    /**
     * Произвольное HTML/текстовое сообщение (уведомления о заявках, 2FA и т.п.).
     * По умолчанию размечает HTML с автоматическим откатом на чистый текст при ошибке синтаксиса тегов.
     */
    public static function sendMessage(int $chatId, string $text, string $parseMode = 'HTML'): bool
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $text,
        ];
        if ($parseMode !== '') {
            $params['parse_mode'] = $parseMode;
        }

        $res = self::request('sendMessage', $params);
        if ($res === null && $parseMode !== '') {
            // Если HTML-разметка выбила ошибку (например, незакрытый тег в пользовательском вводе),
            // делаем безопасный откат на чистый текст без parse_mode.
            return self::request('sendMessage', [
                'chat_id' => $chatId,
                'text' => strip_tags($text),
            ]) !== null;
        }

        return $res !== null;
    }

    /**
     * Ищет в свежих сообщениях бота одноразовый код привязки и возвращает
     * chat_id отправителя. Используется страницей профиля («Проверить
     * привязку»): админ отправляет боту код, CMS забирает его getUpdates.
     */
    public static function findChatIdByCode(string $code): ?int
    {
        $updates = self::request('getUpdates', ['limit' => 100, 'allowed_updates' => ['message']]);
        if (!is_array($updates)) {
            return null;
        }

        return self::matchUpdates($updates, $code);
    }

    /**
     * Чистая логика сопоставления getUpdates с кодом привязки (тестируемо).
     * Принимает и «CODE», и «/start CODE».
     *
     * @param array<int,array<string,mixed>> $updates
     */
    public static function matchUpdates(array $updates, string $code): ?int
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        // Идём с конца — берём самое свежее совпадение.
        foreach (array_reverse($updates) as $update) {
            $msg = $update['message'] ?? null;
            if (!is_array($msg)) {
                continue;
            }
            $text = trim((string) ($msg['text'] ?? ''));
            if ($text === $code || $text === '/start ' . $code) {
                $chatId = $msg['chat']['id'] ?? null;
                if (is_int($chatId) || (is_string($chatId) && ctype_digit($chatId))) {
                    return (int) $chatId;
                }
            }
        }

        return null;
    }

    /**
     * Вызов метода Bot API. Возвращает поле result либо null при любой ошибке.
     *
     * @param array<string,mixed> $params
     */
    private static function request(string $method, array $params): mixed
    {
        $token = trim(Setting::get('telegram_bot_token', ''));
        if ($token === '') {
            return null;
        }

        // База переопределяется переменной окружения только для тестов.
        $base = getenv('TELEGRAM_BOT_URL') ?: self::API_BASE;
        $url = rtrim($base, '/') . '/bot' . $token . '/' . $method;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($params, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $body = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);

        if ($body === false) {
            Logger::warning('Telegram Bot API недоступен: ' . $curlError, ['method' => $method]);
            return null;
        }

        $json = json_decode((string) $body, true);
        if ($httpCode !== 200 || !is_array($json) || ($json['ok'] ?? false) !== true) {
            Logger::warning('Telegram Bot API вернул ошибку', [
                'method' => $method,
                'http' => $httpCode,
                'error' => is_array($json) ? (string) ($json['description'] ?? '') : 'bad response',
            ]);
            return null;
        }

        return $json['result'] ?? null;
    }
}
