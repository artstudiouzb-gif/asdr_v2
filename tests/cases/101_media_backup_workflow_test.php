<?php

declare(strict_types=1);

test('Admin media: превью 16:9, выдача по 15 и progressive «Показать ещё»', function (): void {
    $css = file_get_contents(APP_ROOT . '/public/assets/css/admin-workflow-fixes.css');
    $js = file_get_contents(APP_ROOT . '/public/assets/js/admin-workflow-fixes.js');
    $loadMore = file_get_contents(APP_ROOT . '/public/assets/js/admin-media-loadmore.js');
    $dropzone = file_get_contents(APP_ROOT . '/public/assets/js/admin-gallery-dropzone.js');
    assert_true(is_string($css));
    assert_true(is_string($js));
    assert_true(is_string($loadMore));
    assert_true(is_string($dropzone));

    assert_contains('aspect-ratio: 16 / 9', $css);
    assert_contains('PAGE_SIZE = 15', $js);
    assert_contains("image.loading = 'lazy'", $js);
    assert_contains("var STEP = 15", $loadMore);
    assert_contains("button.textContent = 'Показать ещё'", $loadMore);
    assert_contains("'Показано ' + shown + ' из ' + total", $loadMore);
    assert_contains('news-gallery-editor', $js);
    assert_contains('Добавить фотографии', $js);
    assert_contains("empty.addEventListener('drop'", $dropzone);
    assert_contains("pick.click()", $dropzone);
    assert_contains('Нажмите, чтобы выбрать из медиабиблиотеки, или перетащите фото сюда.', $dropzone);
    assert_contains('removeDuplicateHints', $dropzone);
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

test('Admin assets: media workflow-слои входят в cache fingerprint', function (): void {
    $asset = file_get_contents(APP_ROOT . '/app/Core/Asset.php');
    $loader = file_get_contents(APP_ROOT . '/public/assets/js/admin-media-loader.js');
    assert_true(is_string($asset));
    assert_true(is_string($loader));

    assert_contains('/assets/js/admin-workflow-fixes.js', $asset);
    assert_contains('/assets/js/admin-gallery-dropzone.js', $asset);
    assert_contains('/assets/js/admin-media-loadmore.js', $asset);
    assert_contains('/assets/css/admin-workflow-fixes.css', $asset);
    assert_contains("loadStyle('admin-workflow-fixes.css')", $loader);
    assert_contains("load('admin-workflow-fixes.js'", $loader);
    assert_contains("load('admin-gallery-dropzone.js'", $loader);
    assert_contains("load('admin-media-loadmore.js'", $loader);
});
