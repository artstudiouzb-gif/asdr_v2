<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\BlockRenderer;
use App\Core\ContentLanguageNotice;
use App\Core\Locale;
use App\Core\View;
use App\Models\Block;
use App\Models\Page;

final class PageController
{
    public function home(): void
    {
        $lang = Locale::current();
        $page = Page::findHome($lang);

        if (!$page) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        if (ContentLanguageNotice::renderIfMissing(Page::availableLangs((int) $page['id']), '/')) {
            return;
        }

        $this->renderPage($page, $lang);
    }

    public function show(array $params): void
    {
        $lang = Locale::current();
        $slug = $params['slug'] ?? '';
        $page = Page::findBySlug($slug, $lang);

        if (!$page) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $canonicalSlug = (string) ($page['slug'] ?? '');
        if (($page['lang'] ?? '') === $lang && $canonicalSlug !== '' && $slug !== $canonicalSlug) {
            header('Location: ' . Locale::url($canonicalSlug, $lang), true, 301);
            exit;
        }

        // Главная доступна по «/», а не «/{slug}» — со slug'ом это дубль
        // контента. Постоянный редирект на канонический корневой URL.
        if (!empty($page['is_home'])) {
            header('Location: ' . Locale::url('/'), true, 301);
            exit;
        }

        if (ContentLanguageNotice::renderIfMissing(Page::availableLangs((int) $page['id']), '/' . $slug)) {
            return;
        }

        $this->renderPage($page, $lang);
    }

    private function renderPage(array $page, string $lang): void
    {
        // Переключатель языков и hreflang показывают только языки, на которых
        // страница реально наполнена (перевод или собственный стек блоков).
        Locale::setContentLangs(Page::availableLangs((int) $page['id']));
        Locale::setAlternatePaths(\App\Core\TranslationGroupHelper::publishedPaths('pages', (int) $page['id']));

        // Сборка блоков (кэш, свежий CSRF, nonce, ассеты) общая со страницей
        // проекта — она живёт в App\Core\PageBlocks.
        $rendered = \App\Core\PageBlocks::compile((int) $page['id'], $lang);

        $layoutType = $page['layout_type'] ?? 'no_sidebar';
        // Виджеты собираются вне кэша блоков: правка виджета видна сразу.
        $sidebar = \App\Core\WidgetRenderer::sidebarFor($layoutType, $lang);

        View::render('site/page', [
            'page' => $page,
            'content' => $rendered['html'],
            'blockCss' => $rendered['css'],
            'preloadImages' => $rendered['preload_images'] ?? [],
            'layoutType' => $layoutType,
            'sidebar' => $sidebar,
        ]);
    }
}
