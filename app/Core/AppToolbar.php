<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Плавающая панель администратора/редактора на публичном сайте (Admin TopBar)
 * + Движок мгновенной предзагрузки страниц (Speculative Prefetching < 5ms).
 */
final class AppToolbar
{
    /**
     * Рендерит плавающую панель администратора на публичных страницах.
     */
    public static function renderHtml(array $context = []): string
    {
        $user = Auth::sessionUser();
        if ($user === null) {
            return self::renderPrefetchScript();
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
<style nonce="%NONCE%">
.app-admin-bar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 38px;
    background: #0f172a;
    color: #f8fafc;
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 16px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    font-size: 13px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
body.has-admin-bar {
    --hdr-panel-height: 38px !important;
    --hdr-top: 38px !important;
    margin-top: 38px !important;
}
body.has-admin-bar .site-header--sticky,
body.has-admin-bar .site-header.is-scrolled,
body.has-admin-bar .site-header[data-header-scroll],
body.has-admin-bar .site-header--transparent,
body.has-admin-bar .a11y-panel,
body.has-admin-bar .preview-bar {
    top: 38px !important;
}
body.has-admin-bar:has(.site-header--transparent) .site-topbar {
    top: 38px !important;
}
.app-admin-bar__left, .app-admin-bar__right {
    display: flex;
    align-items: center;
    gap: 12px;
}
.app-admin-bar__brand {
    font-weight: 700;
    color: #38bdf8;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 6px;
}
.app-admin-bar__btn {
    color: #e2e8f0;
    text-decoration: none;
    padding: 4px 10px;
    border-radius: 4px;
    background: rgba(255,255,255,0.08);
    transition: background 0.15s ease, color 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    border: none;
    cursor: pointer;
    font-size: 12px;
}
.app-admin-bar__btn:hover {
    background: #0284c7;
    color: #ffffff;
}
.app-admin-bar__btn--edit {
    background: #0284c7;
    color: #ffffff;
    font-weight: 600;
}
.app-admin-bar__btn--edit:hover {
    background: #0369a1;
}
.app-admin-bar__drop {
    position: relative;
    display: inline-block;
}
.app-admin-bar__drop-menu {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 6px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.4);
    min-width: 160px;
    padding: 6px 0;
    z-index: 100001;
}
.app-admin-bar__drop:hover .app-admin-bar__drop-menu {
    display: block;
}
.app-admin-bar__drop-item {
    display: block;
    padding: 6px 14px;
    color: #cbd5e1;
    text-decoration: none;
    font-size: 12px;
}
.app-admin-bar__drop-item:hover {
    background: #0284c7;
    color: #ffffff;
}
</style>

<div class="app-admin-bar" id="app-admin-bar">
    <div class="app-admin-bar__left">
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

        <form method="post" action="/admin/performance/clear-cache" style="display:inline;margin:0;">
            <input type="hidden" name="csrf_token" value="{$csrfToken}">
            <button type="submit" class="app-admin-bar__btn" title="Сбросить кеш сайта">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                Сбросить кеш
            </button>
        </form>
    </div>

    <div class="app-admin-bar__right">
        <span style="display:inline-flex;align-items:center;gap:5px;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            {$username} ({$role})
        </span>
        <a href="/admin/profile" class="app-admin-bar__btn">Профиль</a>
        <a href="/admin/logout" class="app-admin-bar__btn">Выйти</a>
    </div>
</div>

<script nonce="%NONCE%">
document.body.classList.add('has-admin-bar');
</script>
HTML;

        $nonce = SecurityHeaders::nonce();
        $html = str_replace('%NONCE%', $nonce, $html);

        return $html . self::renderPrefetchScript();
    }

    /**
     * Скрипт мнимой предзагрузки страниц (Speculative Prefetch < 5ms).
     */
    public static function renderPrefetchScript(): string
    {
        $nonce = SecurityHeaders::nonce();
        return <<<HTML
<script nonce="{$nonce}">
(function(){
    var prefetched = new Set();
    function prefetch(url) {
        if (!url || prefetched.has(url)) return;
        prefetched.add(url);
        var link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = url;
        document.head.appendChild(link);
    }
    document.addEventListener('mouseover', function(e) {
        var a = e.target.closest('a');
        if (a && a.href && a.origin === location.origin && !a.href.includes('#') && !a.href.includes('/admin')) {
            prefetch(a.href);
        }
    }, {passive: true});
})();
</script>
HTML;
    }
}
