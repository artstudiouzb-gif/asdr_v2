<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AdminEntryConfig;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Logger;

/** Handles the protected admin-entry settings form before the main router. */
final class AdminEntrySettingsController
{
    public function update(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyRequest();

        $action = (string) ($_POST['action'] ?? 'save');
        if (empty($_POST['confirm_access_change'])) {
            Flash::error('Подтвердите, что новый адрес сохранён в безопасном месте.');
            $this->redirect();
        }

        try {
            if ($action === 'disable') {
                AdminEntryConfig::disable();
                Logger::security('Скрытый адрес административной панели отключён', [
                    'user' => (string) (Auth::sessionUser()['username'] ?? ''),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                ]);
                Flash::success('Скрытый адрес отключён. Стандартный /admin/login снова доступен.');
                $this->redirect();
            }

            $state = AdminEntryConfig::state();
            $ttl = (int) ($_POST['admin_entry_ttl'] ?? $state['ttl']);
            $cidrs = (string) ($_POST['admin_entry_allowed_cidrs'] ?? '');
            $path = $action === 'regenerate'
                ? AdminEntryConfig::generatePath()
                : (string) ($_POST['admin_entry_path'] ?? '');

            AdminEntryConfig::save($path, $ttl, preg_split('/[\s,]+/', $cidrs, -1, PREG_SPLIT_NO_EMPTY) ?: []);
            Logger::security(
                $action === 'regenerate'
                    ? 'Скрытый адрес административной панели сгенерирован заново'
                    : 'Настройки скрытого адреса административной панели изменены',
                [
                    'user' => (string) (Auth::sessionUser()['username'] ?? ''),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                    'cidr_restricted' => trim($cidrs) !== '',
                ]
            );
            Flash::success(
                $action === 'regenerate'
                    ? 'Новый адрес создан. Скопируйте его до выхода из панели.'
                    : 'Защищённый адрес входа сохранён.'
            );
        } catch (\Throwable $e) {
            Logger::security('Не удалось изменить скрытый адрес административной панели', [
                'user' => (string) (Auth::sessionUser()['username'] ?? ''),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'error' => $e->getMessage(),
            ]);
            Flash::error($e->getMessage());
        }

        $this->redirect();
    }

    private function redirect(): never
    {
        header('Location: /admin/security#admin-entry');
        exit;
    }
}
