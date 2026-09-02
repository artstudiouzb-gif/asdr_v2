<?php

declare(strict_types=1);

use App\Core\Updater;

/*
 * Обновление из релиза на GitHub (`scripts/update.php`, `App\Core\Updater`).
 *
 * Замена кода — самое опасное действие в CMS: ошибка здесь либо стирает
 * данные сайта, либо оставляет работать старый уязвимый обработчик, либо
 * выполняет на сервере чужой код. Каждое из трёх стережётся отдельно.
 */

/** Собирает дерево файлов во временном каталоге. */
function updater_tree(string $dir, array $files): string
{
    exec('rm -rf ' . escapeshellarg($dir));
    foreach ($files as $path => $content) {
        $full = $dir . '/' . $path;
        if (!is_dir(dirname($full))) {
            mkdir(dirname($full), 0755, true);
        }
        file_put_contents($full, $content);
    }

    return $dir;
}

test('Обновление не трогает данные сайта', function () {
    // Конфиг с секретами, загруженные файлы и журналы обновление обязано
    // оставить как есть: их нет в архиве, и попасть в план они не должны
    // ни на замену, ни на удаление.
    foreach ([
        'config/config.php',
        'storage/logs/error.log',
        'storage/backups/backup.zip',
        'public/uploads/public/photo.jpg',
    ] as $path) {
        assert_true(Updater::isPreserved($path), 'не защищено от замены: ' . $path);
    }

    // А код — не данные, его меняем.
    foreach (['app/Core/Router.php', 'templates/blocks/text.php', 'public/index.php', 'config/config.example.php'] as $path) {
        assert_false(Updater::isPreserved($path), 'ошибочно защищено от обновления: ' . $path);
    }
});

test('План замены сохраняет данные и убирает устаревший код', function () {
    $base = sys_get_temp_dir() . '/updater-' . uniqid();
    $root = updater_tree($base . '/root', [
        'app/Core/Old.php' => 'старый класс',
        'app/Core/bootstrap.php' => 'старый',
        'public/index.php' => 'старый',
        'config/config.php' => 'СЕКРЕТЫ',
        'storage/logs/error.log' => 'журнал',
        'public/uploads/public/photo.jpg' => 'ФОТО',
    ]);
    $new = updater_tree($base . '/new', [
        'app/Core/bootstrap.php' => 'новый',
        'app/Core/Brand.php' => 'новый класс',
        'public/index.php' => 'новый',
    ]);

    try {
        $plan = Updater::plan($new, $root);

        // Устаревший файл в каталоге, которым владеет релиз, обязан уйти:
        // иначе старый обработчик продолжал бы отвечать после обновления.
        assert_true(in_array('app/Core/Old.php', $plan['delete'], true), 'устаревший файл остался бы на сервере');

        // Данные сайта не попадают ни в один список.
        $touched = array_merge($plan['copy'], $plan['delete']);
        foreach (['config/config.php', 'storage/logs/error.log', 'public/uploads/public/photo.jpg'] as $keep) {
            assert_false(in_array($keep, $touched, true), 'обновление затронуло данные сайта: ' . $keep);
        }

        Updater::apply($new, $plan, $root);
        assert_same('СЕКРЕТЫ', file_get_contents($root . '/config/config.php'));
        assert_same('ФОТО', file_get_contents($root . '/public/uploads/public/photo.jpg'));
        assert_same('новый', file_get_contents($root . '/app/Core/bootstrap.php'));
        assert_false(is_file($root . '/app/Core/Old.php'), 'устаревший файл не удалён');
        assert_true(is_file($root . '/app/Core/Brand.php'), 'новый файл не появился');
    } finally {
        exec('rm -rf ' . escapeshellarg($base));
    }
});

test('Ставим только собранный архив релиза, и только с контрольной суммой', function () {
    $archive = ['name' => 'asdr-cms-2.0.0.zip', 'url' => 'https://github.com/x/y/releases/download/v2/asdr-cms-2.0.0.zip', 'size' => 10];
    $sum = ['name' => 'asdr-cms-2.0.0.zip.sha256', 'url' => 'https://github.com/x/y/releases/download/v2/asdr-cms-2.0.0.zip.sha256', 'size' => 1];
    $source = ['name' => 'Source code (zip)', 'url' => 'https://github.com/x/y/zipball/v2', 'size' => 10];

    assert_same('asdr-cms-2.0.0.zip', (Updater::pickAsset([$archive, $sum]) ?? ['archive' => ['name' => '']])['archive']['name']);

    // «Source code» не годится: в нём тесты, .github и composer.json — то,
    // чему на боевом сервере делать нечего. Ставить его нельзя.
    assert_same(null, Updater::pickAsset([$source]), 'принят Source code вместо собранного архива');

    // Архив без .sha256 не ставим: сверять целостность загрузки будет нечем.
    assert_same(null, Updater::pickAsset([$archive]), 'принят архив без контрольной суммы');
});

test('Контрольная сумма сверяется, а не имитируется', function () {
    $file = tempnam(sys_get_temp_dir(), 'upd');
    file_put_contents($file, 'содержимое архива');
    try {
        $real = hash_file('sha256', $file);
        assert_true(Updater::verifyChecksum($file, $real . '  asdr-cms.zip'));
        assert_false(Updater::verifyChecksum($file, str_repeat('0', 64) . '  asdr-cms.zip'), 'принята чужая сумма');
        assert_false(Updater::verifyChecksum($file, 'мусор без суммы'), 'принята строка без суммы');
    } finally {
        @unlink($file);
    }
});

test('Обновление ходит только на GitHub и только по https', function () {
    // Адрес приходит из ответа GitHub, но подставить туда чужой хост — значит
    // выполнить свой код на сервере. Список доменов закрытый.
    foreach ([
        'https://api.github.com/repos/a/b/releases/latest',
        'https://objects.githubusercontent.com/x',
        'https://release-assets.githubusercontent.com/x',
    ] as $good) {
        assert_true(Updater::isAllowedUrl($good), 'отклонён законный адрес: ' . $good);
    }
    foreach ([
        'http://api.github.com/x',
        'https://evil.example.com/x',
        'https://api.github.com.evil.example/x',
        'https://127.0.0.1/x',
        'file:///etc/passwd',
    ] as $bad) {
        assert_false(Updater::isAllowedUrl($bad), 'принят чужой адрес: ' . $bad);
    }
});

test('Архив не той формы к установке не допускается', function () {
    $base = sys_get_temp_dir() . '/updater-shape-' . uniqid();
    try {
        // Пустой каталог — не наш архив.
        assert_true(Updater::validateTree(updater_tree($base . '/empty', ['readme.md' => 'x'])) !== []);

        // Есть всё нужное — годен.
        $good = [
            'app/Core/bootstrap.php' => 'x',
            'public/index.php' => 'x',
            'database/schema.sql' => 'x',
            'config/config.example.php' => 'x',
        ];
        assert_same([], Updater::validateTree(updater_tree($base . '/good', $good)));

        // Боевой конфиг внутри архива — признак, что подсунули не то.
        $problems = Updater::validateTree(updater_tree($base . '/bad', $good + ['config/config.php' => 'x']));
        assert_contains('config/config.php', implode(' ', $problems));
    } finally {
        exec('rm -rf ' . escapeshellarg($base));
    }
});

test('Репозиторий обновления берётся из окружения, а не из настроек', function () {
    // Настройка в панели дала бы редактору увести обновление на чужой
    // репозиторий — то есть выполнить свой код на сервере.
    $updater = (string) file_get_contents(APP_ROOT . '/app/Core/Updater.php');
    assert_contains("getenv('UPDATE_REPO')", $updater);
    assert_false(str_contains($updater, 'Setting::get'), 'адрес репозитория читается из БД');

    putenv('UPDATE_REPO=someone/evil repo');
    assert_same('artstudiouzb-gif/asdr_v2', Updater::repo(), 'принято мусорное имя репозитория');
    putenv('UPDATE_REPO=other-org/other-cms');
    assert_same('other-org/other-cms', Updater::repo());
    putenv('UPDATE_REPO');
});

test('Обновление не вынесено кнопкой в админку', function () {
    // Веб-запрос обрывается по таймауту, и обрыв посреди замены оставит сайт
    // с половиной старых и половиной новых файлов. Панель может только
    // сообщать о новой версии.
    foreach (glob(APP_ROOT . '/app/Controllers/Admin/*.php') ?: [] as $controller) {
        $source = (string) file_get_contents($controller);
        assert_false(
            str_contains($source, 'Updater::apply') || str_contains($source, 'Updater::download'),
            'замена файлов доступна из админки: ' . basename($controller)
        );
    }
});
