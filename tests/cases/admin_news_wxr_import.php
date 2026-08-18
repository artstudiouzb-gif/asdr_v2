<?php

declare(strict_types=1);

test('admin WXR importer is super-admin and CSRF protected', function (): void {
    $root = dirname(__DIR__, 2);
    $controller = (string) file_get_contents($root . '/app/Controllers/Admin/NewsImportController.php');
    $entry = (string) file_get_contents($root . '/public/admin/import-news.php');
    $htaccess = (string) file_get_contents($root . '/public/.htaccess');
    $rootHtaccess = (string) file_get_contents($root . '/.htaccess');
    $newsIndex = (string) file_get_contents($root . '/app/Views/admin/news/index.php');

    assert_contains('Auth::requireSuperAdmin()', $controller);
    assert_contains('Csrf::verifyRequest()', $controller);
    assert_contains("'upload' => \$controller->uploadChunk()", $entry);
    assert_contains("'inspect' => \$controller->inspect()", $entry);
    assert_contains("'import' => \$controller->importBatch()", $entry);
    assert_contains('Backup::isWriteGuardActive()', $entry);
    assert_contains('!^/admin/import-news\\.php$', $htaccess);
    assert_contains('<Files "import-news.php">', $htaccess);
    assert_contains('admin/import-news\\.php$', $rootHtaccess);
    assert_contains('/admin/import-news.php', $newsIndex);
    assert_not_contains('(index|download|import-news)\\.php$', $htaccess);
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
    assert_contains("jsonPost('import'", $script);
    assert_contains('1024 * 1024', $script);
});
