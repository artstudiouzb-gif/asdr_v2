<?php

declare(strict_types=1);

require __DIR__ . '/../../app/Core/bootstrap.php';

use App\Controllers\Admin\NewsImportController;

$controller = new NewsImportController();
$action = (string) ($_GET['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    match ($action) {
        'upload' => $controller->uploadChunk(),
        'inspect' => $controller->inspect(),
        'import' => $controller->importBatch(),
        'discard' => $controller->discard(),
        default => $controller->index(),
    };
    exit;
}

$controller->index();
