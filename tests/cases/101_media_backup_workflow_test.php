<?php

declare(strict_types=1);

test('Admin media: превью унифицированы до 16:9 и пагинация включена', function (): void {
    $css = file_get_contents(APP_ROOT . '/public/assets/css/admin-workflow-fixes.css');
    $js = file_get_contents(APP_ROOT . '/public/assets/js/admin-workflow-fixes.js');
    assert_true(is_string($css));
    assert_true(is_string($js));

    assert_contains('aspect-ratio: 16 / 9', $css);
    assert_contains('PAGE_SIZE = 15', $js);
    assert_contains("image.loading = 'lazy'", $js);
    assert_contains('media-client-pager', $js);
    assert_contains('news-gallery-editor', $js);
    assert_contains('Добавить фотографии', $js);
});

test('Admin backup: восстановление защищено подтверждением и страховочным бэкапом', function (): void {
    $controller = file_get_contents(APP_ROOT . '/app/Controllers/Admin/BackupController.php');
    $restore = file_get_contents(APP_ROOT . '/app/Core/BackupRestore.php');
    $workflow = file_get_contents(APP_ROOT . '/public/assets/js/admin-workflow-fixes.js');
    assert_true(is_string($controller));
    assert_true(is_string($restore));
    assert_true(is_string($workflow));

    assert_contains("backup_action'] ?? '') === 'restore'", $controller);
    assert_contains('BackupRestore::restoreUploaded', $controller);
    assert_contains("private const CONFIRM_CODE = 'RESTORE'", $restore);
    assert_contains('$safetyPath = Backup::create(false);', $restore);
    assert_contains('self::assertCompatibleArchive($incoming);', $restore);
    assert_contains('self::rollbackSwap($swap);', $restore);
    assert_contains("action.value = 'restore'", $workflow);
    assert_contains("codeOk = String(code.value || '').trim().toUpperCase() === 'RESTORE'", $workflow);
});

test('Admin assets: workflow-слой входит в cache fingerprint', function (): void {
    $asset = file_get_contents(APP_ROOT . '/app/Core/Asset.php');
    $loader = file_get_contents(APP_ROOT . '/public/assets/js/admin-media-loader.js');
    assert_true(is_string($asset));
    assert_true(is_string($loader));

    assert_contains('/assets/js/admin-workflow-fixes.js', $asset);
    assert_contains('/assets/css/admin-workflow-fixes.css', $asset);
    assert_contains("loadStyle('admin-workflow-fixes.css')", $loader);
    assert_contains("load('admin-workflow-fixes.js')", $loader);
});
