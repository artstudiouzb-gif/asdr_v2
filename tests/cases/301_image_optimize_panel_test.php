<?php

declare(strict_types=1);

use App\Core\ImageBatchOptimizer;

/*
 * Достройка миниатюр запускается из админки, а не только из консоли.
 *
 * Пакетная обработка существует давно (`scripts/optimize_images.php`), но
 * владелец сайта на shared-хостинге до SSH не доходит: раздел «Изображения»
 * предлагал команду, которую там некому выполнить, — то есть не предлагал
 * ничего. Отсюда два требования, и оба проверяются здесь.
 *
 * Первое: обработка обязана идти пакетами с курсором. Один запрос на весь
 * каталог шлюз обрывает по таймауту (ровно так падал импорт новостей), и чем
 * больше медиатека, тем вернее отказ — то есть отказ приходит именно туда, где
 * обработка нужна.
 *
 * Второе: курсор обязан сдвигаться даже при исчерпанном бюджете времени, иначе
 * пакет возвращается на том же месте и обработка стоит, показывая движение.
 */

test('Пакет возвращает курсор и продолжается с того же места', function () {
    $dir = sys_get_temp_dir() . '/imgbatch-' . bin2hex(random_bytes(6));
    mkdir($dir, 0755, true);

    foreach (['a', 'b', 'c'] as $name) {
        $image = imagecreatetruecolor(60, 40);
        imagejpeg($image, $dir . '/' . $name . '.jpg', 90);
        unset($image);
    }

    $first = ImageBatchOptimizer::run($dir, true, false, 1);
    assert_same(3, $first['total'], 'Кандидаты посчитаны');
    assert_true($first['cursor'] > 0 && $first['cursor'] < 3, 'Курсор остановился внутри списка');

    $second = ImageBatchOptimizer::run($dir, true, false, 0, $first['cursor']);
    assert_same(3, $second['cursor'], 'Второй пакет дошёл до конца');
    assert_same($first['planned'] + $second['planned'], 3, 'Вместе пакеты покрыли весь список');

    // Порядок обхода каталога задаёт файловая система, поэтому номер, на
    // котором остановился пакет, обязан указывать на тот же файл и в
    // следующем запросе. Держит это сортировка списка кандидатов.
    $candidates = ImageBatchOptimizer::candidates($dir);
    $sorted = $candidates;
    sort($sorted, SORT_STRING);
    assert_same($sorted, $candidates, 'Список кандидатов отсортирован');

    array_map('unlink', glob($dir . '/*') ?: []);
    rmdir($dir);
});

test('Исчерпанный бюджет времени всё равно сдвигает курсор', function () {
    $dir = sys_get_temp_dir() . '/imgbatch-' . bin2hex(random_bytes(6));
    mkdir($dir, 0755, true);
    $image = imagecreatetruecolor(60, 40);
    imagejpeg($image, $dir . '/only.jpg', 90);
    unset($image);

    // Нулевой бюджет исчерпан заведомо: если бы проверка стояла до первого
    // файла, пакет вернул бы курсор 0 и обработка не сдвинулась бы никогда.
    $result = ImageBatchOptimizer::run($dir, true, false, 0, 0, 0.0001);
    assert_same(1, $result['cursor'], 'Хотя бы один файл просмотрен');

    array_map('unlink', glob($dir . '/*') ?: []);
    rmdir($dir);
});

test('Раздел производительности отдаёт кнопку, маршрут и обработчик', function () {
    $view = file_get_contents(APP_ROOT . '/app/Views/admin/performance/index.php') ?: '';
    assert_true(str_contains($view, 'data-image-optimize'), 'В разделе есть блок достройки миниатюр');
    assert_true(str_contains($view, '/admin/performance/optimize-images'), 'Указан адрес обработчика');
    assert_true(str_contains($view, 'admin-image-optimize.js'), 'Подключён скрипт пакетов');

    $routes = file_get_contents(APP_ROOT . '/public/index.php') ?: '';
    assert_true(
        str_contains($routes, "\$router->post('/admin/performance/optimize-images'"),
        'Маршрут объявлен только на POST'
    );

    $controller = file_get_contents(APP_ROOT . '/app/Controllers/Admin/PerformanceController.php') ?: '';
    assert_true(str_contains($controller, 'function optimizeImages'), 'Обработчик на месте');
    assert_true(str_contains($controller, 'Auth::requireSuperAdmin'), 'Доступ только супер-админу');
    assert_true(str_contains($controller, 'Csrf::verifyRequest'), 'Запрос проверяется на CSRF');

    assert_true(
        is_file(APP_ROOT . '/public/assets/js/admin-image-optimize.js'),
        'Файл скрипта существует'
    );
});
