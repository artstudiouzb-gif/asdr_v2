<?php

declare(strict_types=1);

/*
 * Обновление курсов валют ЦБ Узбекистана для плашки в шапке.
 *   php app/Console/currency_worker.php
 *
 * Cron (раз в час; нужен только если плашка «Курсы валют» стоит в шапке):
 *   50 * * * * php /path/to/app/Console/currency_worker.php >> /path/to/storage/logs/currency_worker.log 2>&1
 *
 * Воркер существует затем, чтобы этого запроса не делала публичная страница.
 * Прежде курсы тянул рендер шапки: на промахе кэша посетитель ждал ответа
 * cbu.uz, а неудача не запоминалась — пока источник лежит, полный таймаут
 * платил каждый запрос. Замерено: 733 мс против 37 мс на той же странице.
 *
 * Отсюда и поведение при отказе: код возврата ненулевой, чтобы cron написал в
 * лог, но сайт это не трогает — он покажет прежние курсы из кэша, а если кэша
 * нет ни разу, просто не покажет плашку.
 */

require __DIR__ . '/../Core/Cli.php';
\App\Core\Cli::assertCli();

require __DIR__ . '/../Core/bootstrap.php';

use App\Core\CurrencyInformer;
use App\Core\Logger;
use App\Core\ProcessLock;

$lock = ProcessLock::acquire('currency_worker');
if ($lock === null) {
    fwrite(STDERR, 'currency_worker уже выполняется — пропуск запуска.' . PHP_EOL);
    exit(0);
}

try {
    $ok = CurrencyInformer::refresh();
    $age = CurrencyInformer::cacheAge();

    if ($ok) {
        fwrite(STDOUT, 'Курсы валют обновлены.' . PHP_EOL);
        exit(0);
    }

    $reason = $age === null
        ? 'кэша нет — плашка курсов останется пустой'
        : sprintf('показываем прежние курсы, им %d мин', intdiv($age, 60));
    Logger::warning('Курсы валют: источник недоступен, ' . $reason);
    fwrite(STDERR, 'Источник недоступен: ' . $reason . PHP_EOL);
    exit(1);
} finally {
    ProcessLock::release($lock);
}
