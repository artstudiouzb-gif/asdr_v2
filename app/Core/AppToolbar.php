<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Плавающая панель администратора/редактора на публичном сайте (Admin TopBar).
 */
final class AppToolbar
{
    public static function isVisible(): bool
    {
        return Auth::sessionUser() !== null;
    }

    /**
     * Рендерит плавающую панель администратора на публичных страницах.
     */
    public static function renderHtml(array $context = []): string
    {
        $user = Auth::sessionUser();
        if ($user === null) {
            return '';
        }
        $username = htmlspecialchars((string) ($user['username'] ?? 'Admin'), ENT_QUOTES);
        $role = htmlspecialchars((string) ($user['role'] ?? 'admin'), ENT_QUOTES);

        // Определяем прямую ссылку на редактирование текущей сущности
        $editUrl = '/admin';
        $editLabel = 'Панель';

        if (!empty($context['page']['id'])) {
            $id = (int) $context['page']['id'];
            $editUrl = "/admin/pages/{$id}/edit";
            $editLabel = 'Редактировать страницу';
        } elseif (!empty($context['news']['id'])) {
            $id = (int) $context['news']['id'];
            $editUrl = "/admin/news/{$id}/edit";
            $editLabel = 'Редактировать новость';
        } elseif (!empty($context['project']['id'])) {
            $id = (int) $context['project']['id'];
            $editUrl = "/admin/projects/{$id}/edit";
            $editLabel = 'Редактировать проект';
        }

        $csrfToken = Csrf::token();

        $html = <<<HTML
<aside class="app-admin-bar" id="app-admin-bar" aria-label="Панель администратора" lang="ru">
    <nav class="app-admin-bar__left" aria-label="Управление содержимым">
        <a href="/admin" class="app-admin-bar__brand">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            ASDR CMS
        </a>
        <a href="{$editUrl}" class="app-admin-bar__btn app-admin-bar__btn--edit">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            {$editLabel}
        </a>
        
        <div class="app-admin-bar__drop">
            <button type="button" class="app-admin-bar__btn">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Создать ▾
            </button>
            <div class="app-admin-bar__drop-menu">
                <a href="/admin/pages/create" class="app-admin-bar__drop-item">Страницу</a>
                <a href="/admin/news/create" class="app-admin-bar__drop-item">Новость</a>
                <a href="/admin/projects/create" class="app-admin-bar__drop-item">Проект</a>
            </div>
        </div>

        <form class="u-inline-1654353117" method="post" action="/admin/performance/clear-cache">
            <input type="hidden" name="csrf_token" value="{$csrfToken}">
            <button type="submit" class="app-admin-bar__btn" title="Сбросить кеш сайта">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                Сбросить кеш
            </button>
        </form>
    </nav>

    <div class="app-admin-bar__right">
        <span class="u-inline-a223f6dc36">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            {$username} ({$role})
        </span>
        <a href="/admin/profile" class="app-admin-bar__btn">Профиль</a>
        <a href="/admin/logout" class="app-admin-bar__btn">Выйти</a>
    </div>
</aside>
HTML;

        return $html;
    }
}
