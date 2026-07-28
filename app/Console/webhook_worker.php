<?php

declare(strict_types=1);

/*
 * Воркер очереди вебхуков ArtStudio CMS (задача 136).
 *   php app/Console/webhook_worker.php
 *
 * Запускать по Cron (например, каждую минуту):
 *   [* * * * *] php /path/to/app/Console/webhook_worker.php >> storage/logs/webhook_worker.log 2>&1
 *
 * Забирает pending-доставки, отправляет POST на URL вебхука с HMAC-подписью,
 * фиксирует HTTP-код. При ошибке — ретрай (до 3 попыток), затем failed.
 */

require __DIR__ . '/../Core/Cli.php';
\App\Core\Cli::assertCli();

require __DIR__ . '/../Core/bootstrap.php';

\App\Core\Heartbeat::touch('webhook'); // группа 2.1

$workerLock = \App\Core\ProcessLock::acquire('webhook_worker'); // группа 6
if ($workerLock === null) {
    fwrite(STDERR, 'webhook_worker уже выполняется — пропуск запуска.' . PHP_EOL);
    exit(0);
}

use App\Core\WebhookDispatcher;
use App\Models\Webhook;
use App\Models\WebhookDelivery;

$batch = WebhookDelivery::pendingBatch(30);
if ($batch === []) {
    fwrite(STDOUT, 'Очередь вебхуков пуста.' . PHP_EOL);
    exit(0);
}

$sent = 0;
$failed = 0;

foreach ($batch as $delivery) {
    $id = (int) $delivery['id'];
    $webhook = Webhook::findById((int) $delivery['webhook_id']);
    if ($webhook === null || (int) $webhook['is_active'] !== 1) {
        WebhookDelivery::markFailed($id, 0, 'Вебхук удалён или отключён.');
        $failed++;
        continue;
    }

    $result = WebhookDispatcher::deliver($delivery, $webhook);
    if ($result['ok']) {
        WebhookDelivery::markSent($id, $result['code']);
        $sent++;
        fwrite(STDOUT, sprintf("OK #%d -> %s (%d)\n", $id, (string) $webhook['url'], $result['code']));
    } else {
        WebhookDelivery::markFailed($id, $result['code'], $result['error']);
        $failed++;
    }
}

fwrite(STDOUT, sprintf('Готово: доставлено %d, ошибок %d.%s', $sent, $failed, PHP_EOL));
exit(0);
