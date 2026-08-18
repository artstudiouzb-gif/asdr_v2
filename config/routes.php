<?php

declare(strict_types=1);

use App\Controllers\Admin\AdminEntrySettingsController;
use App\Controllers\Admin\AlbumController as AdminAlbumController;
use App\Controllers\Admin\AuditController as AdminAuditController;
use App\Controllers\Admin\AuthController as AdminAuthController;
use App\Controllers\Admin\BackupController as AdminBackupController;
use App\Controllers\Admin\BlockController as AdminBlockController;
use App\Controllers\Admin\BulkController as AdminBulkController;
use App\Controllers\Admin\ChunkedUploadController as AdminChunkedUploadController;
use App\Controllers\Admin\ContentEntryController as AdminContentEntryController;
use App\Controllers\Admin\ContentRevisionController as AdminContentRevisionController;
use App\Controllers\Admin\ContentTypeController as AdminContentTypeController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\DesignController as AdminDesignController;
use App\Controllers\Admin\FileController as AdminFileController;
use App\Controllers\Admin\FooterController as AdminFooterController;
use App\Controllers\Admin\FormController as AdminFormController;
use App\Controllers\Admin\HeaderController as AdminHeaderController;
use App\Controllers\Admin\HeroController as AdminHeroController;
use App\Controllers\Admin\LanguageController as AdminLanguageController;
use App\Controllers\Admin\MenuController as AdminMenuController;
use App\Controllers\Admin\NewsCategoryController as AdminNewsCategoryController;
use App\Controllers\Admin\NewsController as AdminNewsController;
use App\Controllers\Admin\NewsImportController as AdminNewsImportController;
use App\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Controllers\Admin\PageController as AdminPageController;
use App\Controllers\Admin\PasswordResetController as AdminPasswordResetController;
use App\Controllers\Admin\PerformanceController as AdminPerformanceController;
use App\Controllers\Admin\ProfileController as AdminProfileController;
use App\Controllers\Admin\ProjectController as AdminProjectController;
use App\Controllers\Admin\RedirectController as AdminRedirectController;
use App\Controllers\Admin\RepositoryController as AdminRepositoryController;
use App\Controllers\Admin\SearchController as AdminSearchController;
use App\Controllers\Admin\SecurityController as AdminSecurityController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\SnippetController as AdminSnippetController;
use App\Controllers\Admin\SocialController as AdminSocialController;
use App\Controllers\Admin\SubscriberController as AdminSubscriberController;
use App\Controllers\Admin\TeamController as AdminTeamController;
use App\Controllers\Admin\TelegramController as AdminTelegramController;
use App\Controllers\Admin\TrashController as AdminTrashController;
use App\Controllers\Admin\UserController as AdminUserController;
use App\Controllers\Admin\VideoController as AdminVideoController;
use App\Controllers\Admin\WebhookController as AdminWebhookController;
use App\Controllers\Admin\WidgetController as AdminWidgetController;
use App\Controllers\InstallController;
use App\Controllers\Repo\AuthController as RepoAuthController;
use App\Controllers\Repo\PortalController as RepoPortalController;
use App\Controllers\Site\AlbumController as SiteAlbumController;
use App\Controllers\Site\CalendarController as SiteCalendarController;
use App\Controllers\Site\CaptchaController as SiteCaptchaController;
use App\Controllers\Site\ContentController as SiteContentController;
use App\Controllers\Site\FormController as SiteFormController;
use App\Controllers\Site\HealthController as SiteHealthController;
use App\Controllers\Site\ManifestController as SiteManifestController;
use App\Controllers\Site\NewsController as SiteNewsController;
use App\Controllers\Site\OpenDataController as SiteOpenDataController;
use App\Controllers\Site\PageController as SitePageController;
use App\Controllers\Site\PollController as SitePollController;
use App\Controllers\Site\ProjectController as SiteProjectController;
use App\Controllers\Site\PushController as SitePushController;
use App\Controllers\Site\ScriptController as SiteScriptController;
use App\Controllers\Site\SearchController as SiteSearchController;
use App\Controllers\Site\SitemapController as SiteSitemapController;
use App\Controllers\Site\SubscribeController as SiteSubscribeController;
use App\Controllers\Site\VitalsController as SiteVitalsController;
use App\Core\Router;

return static function (Router $router): void {
    // --- Установщик после установки: аппаратно заблокирован (403) ---
    $router->get('/install', [InstallController::class, 'step1']);
    $router->post('/install/step2', [InstallController::class, 'step2Submit']);
    $router->post('/install/step3', [InstallController::class, 'step3Submit']);
    $router->post('/install/step4', [InstallController::class, 'step4Submit']);

    // --- Admin: аутентификация (без требования логина) ---
    $router->get('/admin/login', [AdminAuthController::class, 'showLogin']);
    $router->post('/admin/login', [AdminAuthController::class, 'login']);
    $router->get('/admin/login/2fa', [AdminAuthController::class, 'showTwoFactor']);
    $router->post('/admin/login/2fa', [AdminAuthController::class, 'verifyTwoFactor']);
    $router->post('/admin/login/2fa/resend', [AdminAuthController::class, 'resendCode']);
    $router->post('/admin/logout', [AdminAuthController::class, 'logout']);

    // --- Admin: восстановление пароля (без требования логина) ---
    $router->get('/admin/forgot', [AdminPasswordResetController::class, 'showForgot']);
    $router->post('/admin/forgot', [AdminPasswordResetController::class, 'requestReset']);
    $router->get('/admin/reset/{token}', [AdminPasswordResetController::class, 'showReset']);
    $router->post('/admin/reset', [AdminPasswordResetController::class, 'submitReset']);

    // --- Admin: профиль, сессии, backup-коды ---
    $router->get('/admin/profile', [AdminProfileController::class, 'index']);
    $router->post('/admin/profile/password', [AdminProfileController::class, 'changePassword']);
    $router->post('/admin/profile/phone', [AdminProfileController::class, 'updatePhone']);
    $router->post('/admin/profile/admin-lang', [AdminProfileController::class, 'updateAdminLang']);
    $router->post('/admin/profile/admin-theme', [AdminProfileController::class, 'updateAdminTheme']);
    $router->post('/admin/profile/telegram/link', [AdminProfileController::class, 'linkTelegram']);
    $router->post('/admin/profile/telegram/unlink', [AdminProfileController::class, 'unlinkTelegram']);
    $router->post('/admin/profile/totp/enable', [AdminProfileController::class, 'enableTotp']);
    $router->post('/admin/profile/totp/disable', [AdminProfileController::class, 'disableTotp']);
    $router->post('/admin/profile/sessions/revoke-others', [AdminProfileController::class, 'revokeOthers']);
    $router->post('/admin/profile/sessions/{id}/revoke', [AdminProfileController::class, 'revokeSession']);

    // --- Admin: дашборд ---
    $router->get('/admin', [DashboardController::class, 'index']);

    // --- Admin: массовые операции + быстрый поиск ---
    $router->post('/admin/bulk/{type}', [AdminBulkController::class, 'handle']);
    $router->get('/admin/search', [AdminSearchController::class, 'query']);

    // --- Admin: история версий страниц, новостей и проектов ---
    $router->get('/admin/revisions/{type}/{id}', [AdminContentRevisionController::class, 'index']);
    $router->post('/admin/revisions/{type}/{id}/{revisionId}/restore', [AdminContentRevisionController::class, 'restore']);

    // --- Admin: новости ---
    $router->get('/admin/news', [AdminNewsController::class, 'index']);
    $router->get('/admin/news/import', [AdminNewsImportController::class, 'index']);
    $router->post('/admin/news/import/upload', [AdminNewsImportController::class, 'uploadChunk']);
    $router->post('/admin/news/import/inspect', [AdminNewsImportController::class, 'inspect']);
    $router->post('/admin/news/import/run', [AdminNewsImportController::class, 'importBatch']);
    $router->post('/admin/news/import/discard', [AdminNewsImportController::class, 'discard']);
    $router->get('/admin/news/create', [AdminNewsController::class, 'create']);
    $router->post('/admin/news/create', [AdminNewsController::class, 'store']);
    $router->get('/admin/news/{id}/edit', [AdminNewsController::class, 'edit']);
    $router->post('/admin/news/ai-summary', [AdminNewsController::class, 'generateAiSummary']);
    $router->get('/admin/news/{id}/preview', [AdminNewsController::class, 'preview']);
    $router->post('/admin/news/{id}/edit', [AdminNewsController::class, 'update']);
    $router->post('/admin/news/{id}/delete', [AdminNewsController::class, 'destroy']);
    $router->post('/admin/news/{id}/create-translation', [AdminNewsController::class, 'createTranslation']);
    $router->post('/admin/news/{id}/duplicate', [AdminNewsController::class, 'duplicate']);
    $router->post('/admin/news/{id}/social', [AdminNewsController::class, 'pushSocial']);
    $router->get('/admin/news/{id}/social-preview', [AdminNewsController::class, 'socialPreview']);

    // --- Admin: категории новостей ---
    $router->get('/admin/news-categories', [AdminNewsCategoryController::class, 'index']);
    $router->post('/admin/news-categories/create', [AdminNewsCategoryController::class, 'store']);
    $router->get('/admin/news-categories/{id}/edit', [AdminNewsCategoryController::class, 'edit']);
    $router->post('/admin/news-categories/{id}/update', [AdminNewsCategoryController::class, 'update']);
    $router->post('/admin/news-categories/{id}/delete', [AdminNewsCategoryController::class, 'destroy']);

    // --- Admin: обложки (Hero) ---
    $router->get('/admin/heroes', [AdminHeroController::class, 'index']);
    $router->post('/admin/heroes/create', [AdminHeroController::class, 'store']);
    $router->get('/admin/heroes/{id}/edit', [AdminHeroController::class, 'edit']);
    $router->post('/admin/heroes/{id}/update', [AdminHeroController::class, 'update']);
    $router->post('/admin/heroes/{id}/preset', [AdminHeroController::class, 'applyPreset']);
    $router->post('/admin/heroes/{id}/duplicate', [AdminHeroController::class, 'duplicate']);
    $router->post('/admin/heroes/{id}/delete', [AdminHeroController::class, 'destroy']);
    $router->post('/admin/heroes/{id}/slides/create', [AdminHeroController::class, 'slideCreate']);
    $router->post('/admin/heroes/{id}/slides/reorder', [AdminHeroController::class, 'reorder']);
    $router->get('/admin/heroes/{id}/slides/{slide}/edit', [AdminHeroController::class, 'slideEdit']);
    $router->post('/admin/heroes/{id}/slides/{slide}/update', [AdminHeroController::class, 'slideUpdate']);
    $router->post('/admin/heroes/{id}/slides/{slide}/duplicate', [AdminHeroController::class, 'slideDuplicate']);
    $router->post('/admin/heroes/{id}/slides/{slide}/toggle', [AdminHeroController::class, 'slideToggle']);
    $router->post('/admin/heroes/{id}/slides/{slide}/delete', [AdminHeroController::class, 'slideDestroy']);

    // --- Admin: страницы + конструктор блоков ---
    $router->get('/admin/pages', [AdminPageController::class, 'index']);
    $router->get('/admin/pages/create', [AdminPageController::class, 'create']);
    $router->post('/admin/pages/create', [AdminPageController::class, 'store']);
    $router->get('/admin/pages/{id}/edit', [AdminPageController::class, 'edit']);
    $router->get('/admin/pages/{id}/preview', [AdminPageController::class, 'preview']);
    $router->post('/admin/pages/{id}/edit', [AdminPageController::class, 'update']);
    $router->post('/admin/pages/{id}/delete', [AdminPageController::class, 'destroy']);
    $router->post('/admin/pages/{id}/make-home', [AdminPageController::class, 'makeHome']);
    $router->post('/admin/pages/{id}/copy-language-blocks', [AdminPageController::class, 'copyLanguageBlocks']);
    $router->post('/admin/pages/{id}/create-translation', [AdminPageController::class, 'createTranslation']);
    $router->post('/admin/pages/{id}/duplicate', [AdminPageController::class, 'duplicate']);
    $router->post('/admin/pages/{id}/blocks/add', [AdminBlockController::class, 'store']);

    $router->get('/admin/blocks/{id}/edit', [AdminBlockController::class, 'edit']);
    $router->post('/admin/blocks/{id}/edit', [AdminBlockController::class, 'update']);
    $router->post('/admin/blocks/{id}/delete', [AdminBlockController::class, 'destroy']);
    $router->post('/admin/blocks/{id}/move', [AdminBlockController::class, 'move']);
    $router->post('/admin/blocks/{id}/toggle', [AdminBlockController::class, 'toggle']);
    $router->post('/admin/blocks/reorder', [AdminBlockController::class, 'reorder']);
    $router->get('/admin/blocks/{id}/revisions', [AdminBlockController::class, 'revisions']);
    $router->post('/admin/blocks/{id}/revisions/restore', [AdminBlockController::class, 'restoreRevision']);

    // --- Admin: шаблоны блоков (сниппеты) ---
    $router->post('/admin/pages/{id}/snippets/save', [AdminSnippetController::class, 'save']);
    $router->post('/admin/pages/{id}/snippets/insert', [AdminSnippetController::class, 'insert']);
    $router->post('/admin/pages/{id}/presets/apply', [AdminSnippetController::class, 'applyPreset']);
    $router->post('/admin/snippets/{id}/delete', [AdminSnippetController::class, 'destroy']);

    // --- Admin: проекты ---
    $router->get('/admin/projects', [AdminProjectController::class, 'index']);
    $router->get('/admin/projects/create', [AdminProjectController::class, 'create']);
    $router->post('/admin/projects/create', [AdminProjectController::class, 'store']);
    $router->get('/admin/projects/{id}/edit', [AdminProjectController::class, 'edit']);
    $router->post('/admin/projects/{id}/edit', [AdminProjectController::class, 'update']);
    $router->post('/admin/projects/{id}/delete', [AdminProjectController::class, 'destroy']);
    $router->post('/admin/projects/{id}/create-translation', [AdminProjectController::class, 'createTranslation']);
    $router->post('/admin/projects/{id}/duplicate', [AdminProjectController::class, 'duplicate']);

    // --- Admin: команда ---
    $router->get('/admin/team', [AdminTeamController::class, 'index']);
    $router->get('/admin/team/create', [AdminTeamController::class, 'create']);
    $router->post('/admin/team/create', [AdminTeamController::class, 'store']);
    $router->get('/admin/team/{id}/edit', [AdminTeamController::class, 'edit']);
    $router->post('/admin/team/{id}/edit', [AdminTeamController::class, 'update']);
    $router->post('/admin/team/{id}/delete', [AdminTeamController::class, 'destroy']);

    // --- Admin: формы и заявки ---
    $router->get('/admin/forms', [AdminFormController::class, 'index']);
    $router->get('/admin/forms/submissions', [AdminFormController::class, 'allSubmissions']);
    $router->get('/admin/forms/submissions/{id}', [AdminFormController::class, 'submission']);
    $router->get('/admin/forms/create', [AdminFormController::class, 'create']);
    $router->post('/admin/forms/create', [AdminFormController::class, 'store']);
    $router->get('/admin/forms/{id}/edit', [AdminFormController::class, 'edit']);
    $router->post('/admin/forms/{id}/edit', [AdminFormController::class, 'update']);
    $router->post('/admin/forms/{id}/delete', [AdminFormController::class, 'destroy']);
    $router->get('/admin/forms/{id}/submissions', [AdminFormController::class, 'submissions']);
    $router->post('/admin/forms/submissions/{id}/delete', [AdminFormController::class, 'deleteSubmission']);

    // --- Admin: языки ---
    $router->get('/admin/languages', [AdminLanguageController::class, 'index']);
    $router->post('/admin/languages/create', [AdminLanguageController::class, 'store']);
    $router->post('/admin/languages/{id}/edit', [AdminLanguageController::class, 'update']);
    $router->post('/admin/languages/{id}/delete', [AdminLanguageController::class, 'destroy']);

    // --- Admin: конструктор меню ---
    $router->get('/admin/menu', [AdminMenuController::class, 'index']);
    $router->post('/admin/menu/create', [AdminMenuController::class, 'store']);
    $router->post('/admin/menu/reorder', [AdminMenuController::class, 'reorder']);
    $router->post('/admin/menu/synchronize', [AdminMenuController::class, 'synchronize']);
    $router->post('/admin/menu/{id}/edit', [AdminMenuController::class, 'update']);
    $router->post('/admin/menu/{id}/delete', [AdminMenuController::class, 'destroy']);
    $router->post('/admin/menu/{id}/move', [AdminMenuController::class, 'move']);

    // --- Admin: конструктор шапки ---
    $router->get('/admin/header', [AdminHeaderController::class, 'index']);
    $router->post('/admin/header', [AdminHeaderController::class, 'update']);

    // --- Admin: конструктор подвала ---
    $router->get('/admin/footer', [AdminFooterController::class, 'index']);
    $router->post('/admin/footer', [AdminFooterController::class, 'update']);

    // --- Admin: производительность ---
    $router->get('/admin/performance', [AdminPerformanceController::class, 'index']);
    $router->post('/admin/performance', [AdminPerformanceController::class, 'update']);
    $router->post('/admin/performance/clear-cache', [AdminPerformanceController::class, 'clearCache']);
    $router->post('/admin/performance/reset-opcache', [AdminPerformanceController::class, 'resetOpcache']);
    $router->post('/admin/cloudflare/verify', [AdminPerformanceController::class, 'cloudflareVerify']);
    $router->post('/admin/cloudflare/purge', [AdminPerformanceController::class, 'cloudflarePurge']);

    // --- Admin: боковые виджеты ---
    $router->get('/admin/widgets', [AdminWidgetController::class, 'index']);
    $router->get('/admin/widgets/create', [AdminWidgetController::class, 'create']);
    $router->post('/admin/widgets/create', [AdminWidgetController::class, 'store']);
    $router->get('/admin/widgets/{id}/edit', [AdminWidgetController::class, 'edit']);
    $router->post('/admin/widgets/{id}/edit', [AdminWidgetController::class, 'update']);
    $router->post('/admin/widgets/{id}/delete', [AdminWidgetController::class, 'destroy']);
    $router->post('/admin/widgets/{id}/move', [AdminWidgetController::class, 'move']);

    // --- Admin: корзина (soft deletes) ---
    $router->get('/admin/trash', [AdminTrashController::class, 'index']);
    $router->post('/admin/trash/empty', [AdminTrashController::class, 'emptyAll']);
    $router->post('/admin/trash/{type}/{id}/restore', [AdminTrashController::class, 'restore']);
    $router->post('/admin/trash/{type}/{id}/force-delete', [AdminTrashController::class, 'forceDelete']);

    // --- Admin: аудит и журнал ---
    $router->get('/admin/audit', [AdminAuditController::class, 'index']);
    $router->get('/admin/audit/errors', [AdminAuditController::class, 'errors']);
    $router->post('/admin/audit/errors/clear', [AdminAuditController::class, 'errorsClear']);

    // --- Admin: подписчики ---
    $router->get('/admin/subscribers', [AdminSubscriberController::class, 'index']);
    $router->post('/admin/subscribers/send-digest', [AdminSubscriberController::class, 'sendDigest']);
    $router->post('/admin/subscribers/{id}/delete', [AdminSubscriberController::class, 'destroy']);

    // --- Admin: фотоальбомы и видео ---
    $router->get('/admin/albums', [AdminAlbumController::class, 'index']);
    $router->post('/admin/albums/create', [AdminAlbumController::class, 'store']);
    $router->get('/admin/albums/{id}/edit', [AdminAlbumController::class, 'edit']);
    $router->post('/admin/albums/{id}/update', [AdminAlbumController::class, 'update']);
    $router->post('/admin/albums/{id}/delete', [AdminAlbumController::class, 'destroy']);
    $router->post('/admin/albums/{id}/images/add', [AdminAlbumController::class, 'addImage']);
    $router->post('/admin/albums/{id}/images/{imageId}/delete', [AdminAlbumController::class, 'deleteImage']);
    $router->get('/admin/videos', [AdminVideoController::class, 'index']);
    $router->post('/admin/videos/create', [AdminVideoController::class, 'store']);
    $router->get('/admin/videos/{id}/edit', [AdminVideoController::class, 'edit']);
    $router->post('/admin/videos/{id}/update', [AdminVideoController::class, 'update']);
    $router->post('/admin/videos/{id}/delete', [AdminVideoController::class, 'destroy']);

    // --- Admin: редиректы ---
    $router->get('/admin/redirects', [AdminRedirectController::class, 'index']);
    $router->post('/admin/redirects/create', [AdminRedirectController::class, 'store']);
    $router->post('/admin/redirects/import', [AdminRedirectController::class, 'import']);
    $router->post('/admin/redirects/{id}/toggle', [AdminRedirectController::class, 'toggle']);
    $router->post('/admin/redirects/{id}/delete', [AdminRedirectController::class, 'destroy']);
    $router->post('/admin/redirects/404/{id}/delete', [AdminRedirectController::class, 'dismissNotFound']);

    // --- Admin: пользователи ---
    $router->get('/admin/users', [AdminUserController::class, 'index']);
    $router->post('/admin/users/create', [AdminUserController::class, 'store']);
    $router->post('/admin/users/{id}/delete', [AdminUserController::class, 'destroy']);

    // --- Admin: конструктор типов контента ---
    $router->get('/admin/content-types', [AdminContentTypeController::class, 'index']);
    $router->post('/admin/content-types/create', [AdminContentTypeController::class, 'store']);
    $router->get('/admin/content-types/{id}/fields', [AdminContentTypeController::class, 'fields']);
    $router->post('/admin/content-types/{id}/fields', [AdminContentTypeController::class, 'saveFields']);
    $router->post('/admin/content-types/{id}/delete', [AdminContentTypeController::class, 'destroy']);

    // --- Admin: авто-CRUD записей типов контента ---
    $router->get('/admin/content/{type}', [AdminContentEntryController::class, 'index']);
    $router->get('/admin/content/{type}/create', [AdminContentEntryController::class, 'create']);
    $router->post('/admin/content/{type}/create', [AdminContentEntryController::class, 'store']);
    $router->get('/admin/content/{type}/{id}/edit', [AdminContentEntryController::class, 'edit']);
    $router->post('/admin/content/{type}/{id}/edit', [AdminContentEntryController::class, 'update']);
    $router->post('/admin/content/{type}/{id}/delete', [AdminContentEntryController::class, 'destroy']);

    // --- Admin: тема-билдер (дизайн сайта) ---
    $router->get('/admin/design', [AdminDesignController::class, 'index']);
    $router->post('/admin/design', [AdminDesignController::class, 'update']);
    $router->post('/admin/design/preset', [AdminDesignController::class, 'applyPreset']);
    $router->post('/admin/design/preset/save', [AdminDesignController::class, 'savePreset']);
    $router->post('/admin/design/preset/delete', [AdminDesignController::class, 'deletePreset']);
    $router->get('/admin/design/preview', [AdminDesignController::class, 'preview']);

    // --- Admin: настройки ---
    $router->get('/admin/settings', [SettingsController::class, 'index']);
    $router->post('/admin/settings', [SettingsController::class, 'update']);
    $router->post('/admin/settings/test-email', [SettingsController::class, 'testMail']);
    $router->post('/admin/settings/demo-content', [SettingsController::class, 'seedDemo']);
    $router->post('/admin/settings/demo-reset', [SettingsController::class, 'resetDemo']);

    // --- Admin: соцсети ---
    $router->get('/admin/social', [AdminSocialController::class, 'index']);
    $router->post('/admin/social', [AdminSocialController::class, 'update']);
    $router->post('/admin/social/run', [AdminSocialController::class, 'runNow']);
    $router->post('/admin/social/retry', [AdminSocialController::class, 'retry']);

    // --- Admin: Telegram ---
    $router->get('/admin/telegram', [AdminTelegramController::class, 'index']);
    $router->post('/admin/telegram/bot', [AdminTelegramController::class, 'saveBot']);
    $router->post('/admin/telegram/bot/check', [AdminTelegramController::class, 'checkBot']);
    $router->post('/admin/telegram/link', [AdminTelegramController::class, 'link']);
    $router->post('/admin/telegram/channel', [AdminTelegramController::class, 'saveChannel']);
    $router->post('/admin/telegram/channel/check', [AdminTelegramController::class, 'checkChannel']);
    $router->post('/admin/telegram/channel/detect', [AdminTelegramController::class, 'detectChannel']);
    $router->post('/admin/telegram/extras', [AdminTelegramController::class, 'saveExtras']);
    $router->post('/admin/telegram/roundup/send', [AdminTelegramController::class, 'sendRoundup']);
    $router->post('/admin/telegram/extras/check', [AdminTelegramController::class, 'checkExtras']);

    // --- Admin: вебхуки ---
    $router->get('/admin/webhooks', [AdminWebhookController::class, 'index']);
    $router->post('/admin/webhooks/create', [AdminWebhookController::class, 'store']);
    $router->post('/admin/webhooks/{id}/edit', [AdminWebhookController::class, 'update']);
    $router->post('/admin/webhooks/{id}/delete', [AdminWebhookController::class, 'destroy']);
    $router->post('/admin/webhooks/{id}/test', [AdminWebhookController::class, 'test']);
    $router->post('/admin/webhooks/deliveries/{id}/retry', [AdminWebhookController::class, 'retry']);
    $router->post('/admin/webhooks/run', [AdminWebhookController::class, 'runNow']);

    // --- Admin: центр безопасности ---
    $router->get('/admin/security', [AdminSecurityController::class, 'index']);

    // --- Admin: бэкапы ---
    $router->post('/admin/backup', [AdminBackupController::class, 'create']);

    // --- Admin: файловый менеджер ---
    $router->get('/admin/files', [AdminFileController::class, 'index']);
    $router->get('/admin/media/list', [AdminFileController::class, 'library']);
    $router->post('/admin/files/upload', [AdminFileController::class, 'upload']);
    $router->post('/admin/files/chunk', [AdminChunkedUploadController::class, 'chunk']);
    $router->post('/admin/files/{id}/delete', [AdminFileController::class, 'destroy']);
    $router->post('/admin/files/{id}/regenerate-token', [AdminFileController::class, 'regenerateToken']);
    $router->post('/admin/files/bulk-delete', [AdminFileController::class, 'bulkDelete']);

    // --- Admin: репозиторий файлов ---
    $router->get('/admin/repository', [AdminRepositoryController::class, 'files']);
    $router->post('/admin/repository/upload', [AdminRepositoryController::class, 'upload']);
    $router->post('/admin/repository/settings', [AdminRepositoryController::class, 'saveSettings']);
    $router->get('/admin/repository/categories', [AdminRepositoryController::class, 'categories']);
    $router->post('/admin/repository/categories/create', [AdminRepositoryController::class, 'storeCategory']);
    $router->post('/admin/repository/categories/{id}/rename', [AdminRepositoryController::class, 'renameCategory']);
    $router->post('/admin/repository/categories/{id}/delete', [AdminRepositoryController::class, 'destroyCategory']);
    $router->post('/admin/repository/{id}/update', [AdminRepositoryController::class, 'updateFile']);
    $router->post('/admin/repository/{id}/approve', [AdminRepositoryController::class, 'approveFile']);
    $router->post('/admin/repository/{id}/delete', [AdminRepositoryController::class, 'destroyFile']);
    $router->get('/admin/repository/users', [AdminRepositoryController::class, 'users']);
    $router->post('/admin/repository/users/create', [AdminRepositoryController::class, 'storeUser']);
    $router->post('/admin/repository/users/{id}/toggle', [AdminRepositoryController::class, 'toggleUser']);
    $router->post('/admin/repository/users/{id}/reset-password', [AdminRepositoryController::class, 'resetUserPassword']);
    $router->post('/admin/repository/users/{id}/delete', [AdminRepositoryController::class, 'destroyUser']);

    // --- Портал файлового хранилища (/repo) ---
    $router->get('/repo/login', [RepoAuthController::class, 'showLogin']);
    $router->post('/repo/login', [RepoAuthController::class, 'login']);
    $router->get('/repo/login/2fa', [RepoAuthController::class, 'showTwoFactor']);
    $router->post('/repo/login/2fa', [RepoAuthController::class, 'verifyTwoFactor']);
    $router->post('/repo/login/2fa/resend', [RepoAuthController::class, 'resendTelegramCode']);
    $router->post('/repo/logout', [RepoAuthController::class, 'logout']);
    $router->get('/repo', [RepoPortalController::class, 'index']);
    $router->post('/repo/upload', [RepoPortalController::class, 'upload']);
    $router->get('/repo/download/{id}', [RepoPortalController::class, 'download']);
    $router->get('/repo/preview/{id}', [RepoPortalController::class, 'preview']);
    $router->post('/repo/download-zip', [RepoPortalController::class, 'downloadZip']);
    $router->get('/repo/security', [RepoPortalController::class, 'security']);
    $router->post('/repo/security/2fa/enable', [RepoPortalController::class, 'enableTotp']);
    $router->post('/repo/security/2fa/disable', [RepoPortalController::class, 'disableTotp']);
    $router->post('/repo/security/telegram/verify', [RepoPortalController::class, 'telegramVerify']);
    $router->post('/repo/security/telegram/disable', [RepoPortalController::class, 'telegramDisable']);

    // --- Health-check & Vitals ---
    $router->get('/health', [SiteHealthController::class, 'index']);
    $router->post('/_vitals', [SiteVitalsController::class, 'store']);

    // --- PWA-манифест ---
    $router->get('/manifest.webmanifest', [SiteManifestController::class, 'webmanifest']);

    // --- SEO & RSS ---
    $router->get('/sitemap.xml', [SiteSitemapController::class, 'sitemap']);
    $router->get('/rss.xml', [SiteSitemapController::class, 'rss']);
    $router->get('/rss/{lang}', [SiteSitemapController::class, 'rss']);
    $router->get('/robots.txt', [SiteSitemapController::class, 'robots']);

    // --- Письменность узбекского текста ---
    $router->get('/script/{code}', [SiteScriptController::class, 'switch']);

    // --- Публичный сайт ---
    $router->get('/', [SitePageController::class, 'home']);
    $router->get('/news', [SiteNewsController::class, 'index']);
    $router->get('/projects', [SiteProjectController::class, 'index']);
    $router->get('/projects/{slug}', [SiteProjectController::class, 'show']);
    $router->get('/news/rss.xml', [SiteNewsController::class, 'feed']);
    $router->get('/news/{slug}/photos.zip', [SiteNewsController::class, 'photosZip']);
    $router->get('/news/{slug}', [SiteNewsController::class, 'show']);
    $router->get('/search', [SiteSearchController::class, 'index']);
    $router->get('/search/suggest', [SiteSearchController::class, 'suggest']);
    $router->get('/calendar', [SiteCalendarController::class, 'index']);
    $router->get('/albums', [SiteAlbumController::class, 'index']);
    $router->get('/albums/{slug}', [SiteAlbumController::class, 'show']);
    $router->post('/subscribe', [SiteSubscribeController::class, 'subscribe']);
    $router->post('/api/polls/{id}/vote', [SitePollController::class, 'vote']);
    $router->get('/captcha.png', [SiteCaptchaController::class, 'image']);
    $router->get('/push/key', [SitePushController::class, 'key']);
    $router->post('/push/subscribe', [SitePushController::class, 'subscribe']);
    $router->post('/push/unsubscribe', [SitePushController::class, 'unsubscribe']);
    $router->get('/unsubscribe', [SiteSubscribeController::class, 'unsubscribe']);
    $router->get('/opendata', [SiteOpenDataController::class, 'index']);
    $router->get('/opendata/{dataset}', [SiteOpenDataController::class, 'dataset']);
    $router->get('/catalog/{type}', [SiteContentController::class, 'index']);
    $router->get('/catalog/{type}/{slug}', [SiteContentController::class, 'show']);
    $router->post('/forms/{slug}/submit', [SiteFormController::class, 'submit']);
    $router->get('/{slug}', [SitePageController::class, 'show']);
};
