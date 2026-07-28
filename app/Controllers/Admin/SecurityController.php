<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

final class SecurityController
{
    public function index(): void
    {
        Auth::requireLogin();

        $auditLogs = [];
        if (Database::isConnected()) {
            try {
                $stmt = Database::pdo()->query("SELECT * FROM audit_log ORDER BY id DESC LIMIT 50");
                $auditLogs = $stmt ? $stmt->fetchAll() : [];
            } catch (\Throwable) {
                $auditLogs = [];
            }
        }

        $logFile = APP_ROOT . "/storage/logs/security.log";
        $securityEvents = [];
        if (is_file($logFile)) {
            $lines = array_reverse(file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []);
            $securityEvents = array_slice($lines, 0, 30);
        }

        View::render("admin/security/index", [
            "auditLogs" => $auditLogs,
            "securityEvents" => $securityEvents,
            "currentUser" => Auth::user(),
        ]);
    }
}
