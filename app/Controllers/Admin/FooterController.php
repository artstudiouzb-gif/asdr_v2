<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\FooterConfig;
use App\Core\Flash;
use App\Core\View;

final class FooterController
{
    public function index(): void
    {
        Auth::requireSuperAdmin();
        View::render('admin/footer/index', ['config' => FooterConfig::get()]);
    }

    public function update(): void
    {
        Auth::requireSuperAdmin();
        Csrf::verifyRequest();

        $columns = [];
        foreach ((array) ($_POST['columns'] ?? []) as $col) {
            $columns[] = [
                'heading' => (string) ($col['heading'] ?? ''),
                'widget' => (string) ($col['widget'] ?? ''),
                'text' => (string) ($col['text'] ?? ''),
            ];
        }

        FooterConfig::save([
            'style' => $_POST['style'] ?? 'columns',
            'columns' => $columns,
            'bottom' => $_POST['bottom'] ?? '',
        ]);

        Flash::success('Подвал сохранён.');
        header('Location: /admin/footer');
        exit;
    }
}
