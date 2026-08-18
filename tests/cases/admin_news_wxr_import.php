<?php

declare(strict_types=1);

test('admin WXR importer is routed through the front controller and protected', function (): void {
    $root = dirname(__DIR__, 2);
    $controller = (string) file_get_contents($root . '/app/Controllers/Admin/NewsImportController.php');
    $routes = (string) file_get_contents($root . '/public/index.php');
    $htaccess = (string) file_get_contents($root . '/public/.htaccess');
    $rootHtaccess = (string) file_get_contents($root . '/.htaccess');
    $newsIndex = (string) file_get_contents($root . '/app/Views/admin/news/index.php');
    $view = (string) file_get_contents($root . '/app/Views/admin/news/import.php');
    $script = (string) file_get_contents($root . '/public/assets/js/admin-news-import.js');

    assert_contains('Auth::requireSuperAdmin()', $controller);
    assert_contains('Csrf::verifyRequest()', $controller);
    assert_contains("\$router->get('/admin/news/import', [\\App\\Controllers\\Admin\\NewsImportController::class, 'index'])", $routes);
    assert_contains("\$router->post('/admin/news/import/upload', [\\App\\Controllers\\Admin\\NewsImportController::class, 'uploadChunk'])", $routes);
    assert_contains("\$router->post('/admin/news/import/inspect', [\\App\\Controllers\\Admin\\NewsImportController::class, 'inspect'])", $routes);
    assert_contains("\$router->post('/admin/news/import/run', [\\App\\Controllers\\Admin\\NewsImportController::class, 'importBatch'])", $routes);
    assert_contains("\$router->post('/admin/news/import/discard', [\\App\\Controllers\\Admin\\NewsImportController::class, 'discard'])", $routes);
    assert_contains('/admin/news/import', $newsIndex);
    assert_contains('data-endpoint="/admin/news/import"', $view);
    assert_contains("endpoint + '/' + encodeURIComponent(action)", $script);
    assert_not_contains('/admin/import-news.php', $newsIndex);
    assert_not_contains('/admin/import-news.php', $view);
    assert_not_contains('/admin/import-news.php', $script);
    assert_not_contains('import-news.php', $htaccess);
    assert_not_contains('admin/import-news\\.php', $rootHtaccess);
    assert_not_contains('shell_exec(', $controller);
    assert_not_contains('exec(', $controller);
});

test('admin WXR importer stages XML outside public and limits upload size', function (): void {
    $root = dirname(__DIR__, 2);
    $controller = (string) file_get_contents($root . '/app/Controllers/Admin/NewsImportController.php');

    assert_contains('/storage/imports/wxr', $controller);
    assert_contains('268435456', $controller);
    assert_contains("pathinfo(\$originalName, PATHINFO_EXTENSION)", $controller);
    assert_contains("hash_file('sha256'", $controller);
});

test('admin WXR importer keeps verification separate from writes', function (): void {
    $root = dirname(__DIR__, 2);
    $controller = (string) file_get_contents($root . '/app/Controllers/Admin/NewsImportController.php');
    $view = (string) file_get_contents($root . '/app/Views/admin/news/import.php');
    $script = (string) file_get_contents($root . '/public/assets/js/admin-news-import.js');

    assert_contains("'dryRun' => true", $controller);
    assert_contains("'unresolved' => count(\$plan['unresolved'])", $controller);
    assert_contains('Сначала загрузите и проверьте XML.', $controller);
    assert_contains('Черновики — рекомендуется', $view);
    assert_contains('Создать резервную копию перед импортом', $view);
    assert_contains("jsonPost('inspect'", $script);
    assert_contains("jsonPost('run'", $script);
    assert_contains('1024 * 1024', $script);
});
