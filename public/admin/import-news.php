<?php

declare(strict_types=1);

require __DIR__ . '/../../app/Core/bootstrap.php';

use App\Controllers\Admin\NewsImportController;
use App\Core\Backup;

if (!APP_INSTALLED) {
    header('Location: /install');
    exit;
}

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (!in_array($method, ['GET', 'POST'], true)) {
    http_response_code(405);
    header('Allow: GET, POST');
    exit;
}

if ($method === 'POST' && Backup::isWriteGuardActive()) {
    http_response_code(503);
    header('Content-Type: application/json; charset=UTF-8');
    header('Retry-After: 60');
    echo json_encode(['ok' => false, 'error' => 'Выполняется резервное копирование. Повторите действие через минуту.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$controller = new NewsImportController();
$action = (string) ($_GET['action'] ?? '');

if ($method === 'POST') {
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
