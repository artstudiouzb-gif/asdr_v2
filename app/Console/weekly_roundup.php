<?php

declare(strict_types=1);

/*
 * Итоги недели одним постом в Telegram-канал.
 *   php app/Console/weekly_roundup.php
 *
 * Cron (понедельник, 09:00):
 *   0 9 * * 1 php /path/to/app/Console/weekly_roundup.php >> /path/to/storage/logs/weekly_roundup.log 2>&1
 *
 * Собирает новости за семь дней на всех языках, где они выходили, и шлёт в
 * канал список со ссылками. Пустую неделю пропускает молча: пост «новостей
 * не было» приучает пролистывать сообщения канала. Повторный запуск в те же
 * дни ничего не отправит — защита от задвоенного cron.
 */

require __DIR__ . '/../Core/Cli.php';
\App\Core\Cli::assertCli();

require __DIR__ . '/../Core/bootstrap.php';

use App\Core\Logger;
use App\Core\ProcessLock;
use App\Core\WeeklyRoundup;

$lock = ProcessLock::acquire('weekly_roundup');
if ($lock === null) {
    fwrite(STDERR, 'weekly_roundup уже выполняется — пропуск запуска.' . PHP_EOL);
    exit(0);
}

try {
    $res = WeeklyRoundup::send();

    if ($res['sent']) {
        Logger::info(sprintf('Итоги недели отправлены в канал: %d новостей.', $res['items']));
        fwrite(STDOUT, sprintf('Отправлено, новостей в сводке: %d.%s', $res['items'], PHP_EOL));
    } else {
        fwrite(STDOUT, 'Не отправлено: ' . $res['reason'] . PHP_EOL);
    }
} finally {
    ProcessLock::release($lock);
}

exit(0);
