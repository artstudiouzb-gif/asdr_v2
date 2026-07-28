<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Webhook;
use App\Models\WebhookDelivery;

/**
 * Диспетчер исходящих вебхуков (задача 136). При наступлении события ставит
 * доставки в очередь для всех активных подписанных вебхуков. Доставка (воркером)
 * подписывается HMAC-SHA256 по секрету вебхука. HTTP-транспорт инжектируется
 * для тестов.
 */
final class WebhookDispatcher
{
    public const SIGNATURE_HEADER = 'X-ASDR-Signature';

    /**
     * Ставит событие в очередь доставки для всех активных вебхуков.
     * @param array<string, mixed> $data
     * @return int число поставленных доставок
     */
    public static function dispatch(string $event, array $data): int
    {
        $payload = [
            'event' => $event,
            'timestamp' => gmdate('c'),
            'data' => $data,
        ];

        $count = 0;
        try {
            foreach (Webhook::activeForEvent($event) as $hook) {
                WebhookDelivery::enqueue((int) $hook['id'], $event, $payload);
                $count++;
            }
        } catch (\Throwable $e) {
            Logger::error('Webhook dispatch failed: ' . $e->getMessage());
        }

        return $count;
    }

    /** Подпись тела запроса: "sha256=<hex hmac>". */
    public static function sign(string $body, string $secret): string
    {
        return 'sha256=' . hash_hmac('sha256', $body, $secret);
    }

    /**
     * Отправляет одну доставку. Возвращает результат для воркера.
     *
     * @param array<string,mixed> $delivery строка webhook_deliveries
     * @param array<string,mixed> $webhook строка webhooks
     * @param callable|null $http fn(url,body,headers):array{status,body,error}
     * @return array{ok:bool, code:int, error:string}
     */
    public static function deliver(array $delivery, array $webhook, ?callable $http = null): array
    {
        $url = (string) ($webhook['url'] ?? '');
        // Защита от SSRF: не шлём на приватные/loopback адреса.
        if (!UrlGuard::isSafeRemote($url)) {
            return ['ok' => false, 'code' => 0, 'error' => 'URL небезопасен или недоступен для внешнего запроса.'];
        }

        $body = (string) $delivery['payload_json'];
        $headers = ['Content-Type: application/json', 'User-Agent: ASDR-CMS-Webhook'];
        if (!empty($webhook['secret'])) {
            $headers[] = self::SIGNATURE_HEADER . ': ' . self::sign($body, (string) $webhook['secret']);
        }

        $send = $http ?? static fn (string $u, string $b, array $h) => Http::request('POST', $u, $b, $h, 15);
        $res = $send($url, $body, $headers);

        $code = (int) ($res['status'] ?? 0);
        if ($code >= 200 && $code < 300) {
            return ['ok' => true, 'code' => $code, 'error' => ''];
        }

        return ['ok' => false, 'code' => $code, 'error' => $res['error'] ?: ('HTTP ' . $code)];
    }
}
