<?php

declare(strict_types=1);

test('Composer подключён только для инструментов разработки', function () {
    $path = APP_ROOT . '/composer.json';
    $data = json_decode((string) file_get_contents($path), true);
    assert_true(is_array($data), 'composer.json содержит валидный JSON');
    assert_same([], $data['require'] ?? null, 'production-зависимости отсутствуют');
    assert_same('2.2.5', $data['require-dev']['phpstan/phpstan'] ?? null, 'версия PHPStan закреплена');
    assert_false(isset($data['autoload']), 'production-автозагрузка Composer не включена');

    $gitignore = (string) file_get_contents(APP_ROOT . '/.gitignore');
    assert_contains('/vendor/', $gitignore);
});

test('CI устанавливает dev-инструменты через Composer', function () {
    $workflow = (string) file_get_contents(APP_ROOT . '/.github/workflows/ci.yml');
    assert_contains('composer install --no-interaction --prefer-dist --no-progress', $workflow);
    assert_contains('run: composer audit --no-interaction', $workflow);
    assert_contains('run: composer analyse', $workflow);
    assert_not_contains('composer analyse || true', $workflow);
    assert_not_contains('tools: phpstan', $workflow);
});
