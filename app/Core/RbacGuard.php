<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Role-Based Access Control (RBAC).
 * Контролирует доступ к модулям в зависимости от роли пользователя.
 */
final class RbacGuard
{
    public const ROLE_SUPERADMIN = 'superadmin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_EDITOR = 'editor';
    public const ROLE_AUTHOR = 'author';

    private const PERMISSIONS = [
        'manage_users' => [self::ROLE_SUPERADMIN],
        'manage_settings' => [self::ROLE_SUPERADMIN, self::ROLE_ADMIN],
        'manage_design' => [self::ROLE_SUPERADMIN, self::ROLE_ADMIN],
        'manage_trash' => [self::ROLE_SUPERADMIN, self::ROLE_ADMIN],
        'publish_content' => [self::ROLE_SUPERADMIN, self::ROLE_ADMIN, self::ROLE_EDITOR],
        'edit_content' => [self::ROLE_SUPERADMIN, self::ROLE_ADMIN, self::ROLE_EDITOR, self::ROLE_AUTHOR],
    ];

    public static function can(string $permission): bool
    {
        $user = Auth::sessionUser();
        if (!$user) {
            return false;
        }

        $role = (string) ($user['role'] ?? self::ROLE_SUPERADMIN);
        $allowed = self::PERMISSIONS[$permission] ?? [self::ROLE_SUPERADMIN];

        return in_array($role, $allowed, true);
    }

    public static function requirePermission(string $permission): void
    {
        if (!self::can($permission)) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }
}
