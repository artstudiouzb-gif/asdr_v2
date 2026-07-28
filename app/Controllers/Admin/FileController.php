<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Uploader;
use App\Core\View;
use App\Models\FileEntry;

final class FileController
{
    public function index(): void
    {
        Auth::requireLogin();
        View::render('admin/files/index', [
            'items' => FileEntry::filtered($_GET),
            'availableDates' => FileEntry::availableDates(),
        ]);
    }

    /**
     * JSON-список публичных изображений для модальной «Медиабиблиотеки»
     * (задача 90). Позволяет переиспользовать уже загруженные файлы.
     */
    public function library(): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json; charset=UTF-8');

        // Фильтр по типу: image (по умолчанию), svg, video, audio, document, all_files, all.
        $type = (string) ($_GET['type'] ?? 'image');
        $type = in_array($type, ['image', 'svg', 'video', 'audio', 'document', 'all_files', 'all'], true) ? $type : 'image';
        $matches = static function (string $mime) use ($type): bool {
            return match ($type) {
                'svg' => $mime === 'image/svg+xml',
                'video' => str_starts_with($mime, 'video/'),
                'audio' => str_starts_with($mime, 'audio/'),
                'document' => !str_starts_with($mime, 'image/') && !str_starts_with($mime, 'video/') && !str_starts_with($mime, 'audio/'),
                'all_files' => true,
                'all' => str_starts_with($mime, 'image/') || str_starts_with($mime, 'video/') || str_starts_with($mime, 'audio/'),
                default => str_starts_with($mime, 'image/'),
            };
        };

        $items = [];
        foreach (FileEntry::all() as $f) {
            if (($f['access_type'] ?? '') !== 'public') {
                continue;
            }
            if (!$matches((string) $f['mime_type'])) {
                continue;
            }
            $items[] = [
                'url' => FileEntry::publicUrl($f),
                'name' => (string) $f['original_name'],
            ];
        }

        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
    }

    public function upload(): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $accessType = ($_POST['access_type'] ?? 'public') === 'protected' ? 'protected' : 'public';

        if (empty($_FILES['file']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            Flash::error('Выберите файл для загрузки.');
            header('Location: /admin/files');
            exit;
        }

        try {
            Uploader::store($_FILES['file'], $accessType, Auth::id());
            Flash::success('Файл загружен.');
        } catch (\RuntimeException $e) {
            Flash::error($e->getMessage());
        }

        header('Location: /admin/files');
        exit;
    }

    public function destroy(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $file = FileEntry::findById((int) $params['id']);
        if ($file) {
            // Переиспользование файлов (задача 90): не удаляем файл, который ещё
            // где-то используется — иначе сломались бы связанные сущности.
            $publicUrl = FileEntry::publicUrl($file);
            $refs = \App\Core\MediaCleaner::referenceCount($publicUrl);
            if ($refs > 0) {
                Flash::error("Файл используется в {$refs} местах и не может быть удалён. Сначала уберите его из этих записей.");
                header('Location: /admin/files');
                exit;
            }

            $basePath = $file['access_type'] === 'protected'
                ? Config::get('paths.protected_uploads')
                : Config::get('paths.public_uploads');
            $path = rtrim((string) $basePath, '/') . '/' . $file['stored_name'];
            if (is_file($path)) {
                unlink($path);
            }

            // Удаляем сопутствующие WebP-варианты (name.webp, name-1600.webp, name-800.webp) для растровых картинок
            $base = preg_replace('/\.[^.]+$/', '', $path) ?? $path;
            foreach (['.webp', '-1600.webp', '-800.webp'] as $suffix) {
                $variant = $base . $suffix;
                if (is_file($variant)) {
                    @unlink($variant);
                }
            }

            FileEntry::delete((int) $file['id']);
            Flash::success('Файл удалён.');
        }

        header('Location: /admin/files');
        exit;
    }

    public function regenerateToken(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        FileEntry::regenerateToken((int) $params['id']);
        Flash::success('Токен доступа обновлён.');
        header('Location: /admin/files');
        exit;
    }

    public function bulkDelete(): void
    {
        Auth::requireLogin();
        Csrf::verifyRequest();

        $ids = $_POST['ids'] ?? [];
        if (is_string($ids)) {
            $ids = json_decode($ids, true) ?: [];
        }
        if (!is_array($ids) || empty($ids)) {
            Flash::error('Не выбраны файлы для удаления.');
            header('Location: /admin/files');
            exit;
        }

        $deletedCount = 0;
        $skippedCount = 0;

        foreach ($ids as $id) {
            $file = FileEntry::findById((int) $id);
            if (!$file) {
                continue;
            }
            $publicUrl = FileEntry::publicUrl($file);
            $refs = \App\Core\MediaCleaner::referenceCount($publicUrl);
            if ($refs > 0) {
                $skippedCount++;
                continue;
            }
            $basePath = $file['access_type'] === 'protected'
                ? Config::get('paths.protected_uploads')
                : Config::get('paths.public_uploads');
            $path = rtrim((string) $basePath, '/') . '/' . $file['stored_name'];
            if (is_file($path)) {
                unlink($path);
            }
            $base = preg_replace('/\.[^.]+$/', '', $path) ?? $path;
            foreach (['.webp', '-1600.webp', '-800.webp'] as $suffix) {
                $variant = $base . $suffix;
                if (is_file($variant)) {
                    @unlink($variant);
                }
            }
            FileEntry::delete((int) $file['id']);
            $deletedCount++;
        }

        if ($deletedCount > 0) {
            Flash::success("Удалено файлов: {$deletedCount}." . ($skippedCount > 0 ? " Пропущено (используются в записях): {$skippedCount}." : ''));
        } elseif ($skippedCount > 0) {
            Flash::error("Выбранные файлы ({$skippedCount}) используются в контенте и не могут быть удалены.");
        }

        header('Location: /admin/files');
        exit;
    }
}
