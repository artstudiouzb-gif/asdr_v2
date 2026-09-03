/**
 * Достройка WebP-вариантов для ранее загруженных фотографий.
 *
 * Работа идёт пакетами: сервер обрабатывает столько файлов, сколько успевает
 * за отведённое время, и возвращает курсор. Один длинный запрос на весь
 * каталог хостинг обрывает по таймауту шлюза, и обработка выглядела бы
 * сломанной ровно там, где она нужнее всего — на большой медиатеке.
 */
(function () {
    'use strict';

    var root = document.querySelector('[data-image-optimize]');
    if (!root) return;

    var endpoint = root.getAttribute('data-endpoint') || '';
    var buttons = {
        dry: root.querySelector('[data-optimize="dry"]'),
        run: root.querySelector('[data-optimize="run"]'),
        stop: root.querySelector('[data-optimize="stop"]')
    };
    var progress = document.querySelector('[data-optimize-progress]');
    var bar = document.querySelector('[data-optimize-bar]');
    var status = document.querySelector('[data-optimize-status]');

    var running = false;
    var cancelled = false;

    function token() {
        var input = document.querySelector('input[name="csrf_token"]');
        return input ? input.value : '';
    }

    function say(text) {
        if (!status) return;
        status.hidden = false;
        status.textContent = text;
    }

    function draw(cursor, total) {
        if (!progress || !bar) return;
        progress.hidden = false;
        var percent = total > 0 ? Math.round((cursor / total) * 100) : 0;
        bar.style.width = Math.max(0, Math.min(100, percent)) + '%';
    }

    function lock(on) {
        running = on;
        if (buttons.dry) buttons.dry.disabled = on;
        if (buttons.run) buttons.run.disabled = on;
        if (buttons.stop) buttons.stop.hidden = !on;
    }

    async function batch(offset, dry) {
        var data = new FormData();
        data.append('csrf_token', token());
        data.append('offset', String(offset));
        data.append('dry', dry ? '1' : '0');

        var response = await fetch(endpoint, {
            method: 'POST',
            body: data,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        var text = await response.text();
        var payload;
        try {
            payload = JSON.parse(text);
        } catch (error) {
            throw new Error('Сервер ответил не JSON (HTTP ' + response.status + ').');
        }
        if (!payload || payload.ok !== true) {
            throw new Error((payload && payload.error) || 'Не удалось обработать пакет.');
        }
        return payload;
    }

    async function loop(dry) {
        if (running) return;
        cancelled = false;
        lock(true);

        var offset = 0;
        var totals = { optimized: 0, planned: 0, skipped: 0, failed: 0, total: 0 };
        say(dry ? 'Считаю, сколько фотографий без миниатюр…' : 'Достраиваю миниатюры…');

        try {
            for (;;) {
                var result = await batch(offset, dry);
                totals.optimized += result.optimized;
                totals.planned += result.planned;
                totals.skipped += result.skipped;
                totals.failed += result.failed;
                totals.total = result.total;
                offset = result.cursor;
                draw(offset, result.total);

                var seen = 'Просмотрено ' + offset + ' из ' + result.total;
                if (dry) {
                    say(seen + ' · без миниатюр: ' + totals.planned + ' · уже готовы: ' + totals.skipped);
                } else {
                    say(seen + ' · достроено: ' + totals.optimized + ' · уже были: ' + totals.skipped
                        + (totals.failed ? ' · не удалось: ' + totals.failed : ''));
                }

                if (result.done) break;
                if (cancelled) {
                    say('Остановлено. ' + seen + '. Повторный запуск продолжит с этого места.');
                    lock(false);
                    return;
                }
            }
        } catch (error) {
            say('Ошибка: ' + error.message + ' Обработанное сохранено — можно запустить снова.');
            lock(false);
            return;
        }

        draw(1, 1);
        if (dry) {
            say(totals.planned > 0
                ? 'Без миниатюр: ' + totals.planned + ' из ' + totals.total
                    + '. Нажмите «Достроить миниатюры», чтобы их создать.'
                : 'Миниатюры есть у всех ' + totals.total + ' фотографий — делать нечего.');
        } else {
            say('Готово. Достроено: ' + totals.optimized + ' · уже были: ' + totals.skipped
                + (totals.failed ? ' · не удалось: ' + totals.failed : '') + '.');
        }
        lock(false);
    }

    if (buttons.dry) buttons.dry.addEventListener('click', function () { loop(true); });
    if (buttons.run) buttons.run.addEventListener('click', function () { loop(false); });
    if (buttons.stop) buttons.stop.addEventListener('click', function () { cancelled = true; });
})();
