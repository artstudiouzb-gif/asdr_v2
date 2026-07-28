<?php

declare(strict_types=1);

use App\Core\MediaOptimizer;
use App\Core\WafGuard;
use App\Core\AiAssistantService;
use App\Core\RbacGuard;
use App\Core\S3BackupAdapter;
use App\Controllers\Site\SitemapController;

test('Усиление системы: Sitemap & RSS генерация', function (): void {
    ensure_test_db();
    ob_start();
    $controller = new SitemapController();
    $controller->sitemap();
    $sitemapXml = ob_get_clean();

    assert_true(str_contains($sitemapXml, '<urlset'), 'Sitemap содержит urlset');
    assert_true(str_contains($sitemapXml, 'xmlns:xhtml'), 'Sitemap содержит xhtml теги для языков');

    ob_start();
    $controller->rss();
    $rssXml = ob_get_clean();

    assert_true(str_contains($rssXml, '<rss version="2.0"'), 'RSS содержит валидный элемент rss');
});

test('Усиление системы: MediaOptimizer srcset generation', function (): void {
    $html = MediaOptimizer::renderImg('/assets/images/hero.jpg', 'Hero Image', 'hero-class');
    assert_true(str_contains($html, '<img src="/assets/images/hero.jpg"'), 'Рендерит базовый src');
    assert_true(str_contains($html, 'loading="lazy"'), 'Добавляет атрибут lazy loading');
});

test('Усиление системы: AI Assistant metadata extraction', function (): void {
    $metaTitle = AiAssistantService::generateMetaTitle('Заголовок тестовой статьи для проверки SEO');
    assert_true(mb_strlen($metaTitle) <= 60, 'Meta title ограничен 60 символами');

    $desc = AiAssistantService::generateMetaDescription('Это тестовое описание новости для генерации метаданных Яндекс и Google.');
    assert_true(mb_strlen($desc) <= 160, 'Meta description ограничен 160 символами');
});

test('Усиление системы: RBAC права доступа', function (): void {
    $_SERVER['HTTP_USER_AGENT'] = 'asdr-test';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SESSION = [
        'user_id' => 1,
        'username' => 'admin',
        'role' => 'superadmin',
        'fingerprint' => hash('sha256', 'asdr-test|127.0'),
    ];

    assert_true(RbacGuard::can('manage_users'), 'Супер-админ может управлять пользователями');
    assert_true(RbacGuard::can('publish_content'), 'Супер-админ может публиковать контент');
});

test('Усиление системы: S3 Backup Adapter проверка инициализации', function (): void {
    $result = S3BackupAdapter::upload('/non/existent/file.zip');
    assert_false($result, 'Ненесуществующий файл отклоняется');
});
