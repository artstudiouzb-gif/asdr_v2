<?php

declare(strict_types=1);

use App\Core\FrontendAssets;

/*
 * Общие слои панели — одной сборкой CSS и одной JS (17 запросов → 4 на
 * страницу). Порядок внутри сборки — порядок каскада и выполнения старой
 * схемы; без сборки панель подключает исходники по-старому. Замерено
 * попиксельно на 12 страницах: вёрстка совпадает.
 */
function admin_bundle_manifest(): array
{
    return json_decode((string) file_get_contents(APP_ROOT . '/public/assets/asset-manifest.json'), true, 512, JSON_THROW_ON_ERROR);
}

test('Сборка панели: слои в порядке каскада, файлы на месте', function (): void {
    $bundles = admin_bundle_manifest()['adminBundles'] ?? [];
    $css = array_column($bundles['css']['sources'] ?? [], 'path');
    $js = array_column($bundles['js']['sources'] ?? [], 'path');

    assert_same('/assets/css/admin.css', $css[0] ?? null, 'основной файл идёт первым');
    assert_same('/assets/js/admin.js', $js[0] ?? null);
    // Слои, которые загрузчик вставлял последними, остаются последними.
    assert_true(array_search('/assets/css/admin-shell-stability.css', $css, true) > array_search('/assets/css/admin-notifications.css', $css, true));
    assert_true(array_search('/assets/css/admin-media-unified.css', $css, true) > array_search('/assets/css/admin-workflow-fixes.css', $css, true));
    foreach ([$bundles['css']['path'] ?? '', $bundles['js']['path'] ?? ''] as $path) {
        assert_true($path !== '' && is_file(APP_ROOT . '/public' . $path), 'нет файла сборки ' . $path);
    }
    foreach (array_merge($css, $js) as $source) {
        assert_true(is_file(APP_ROOT . '/public' . $source), 'нет исходника ' . $source);
    }
});

test('Шапка и подвал панели подключают сборку и гасят самоподгрузку слоёв', function (): void {
    $header = (string) file_get_contents(APP_ROOT . '/app/Views/admin/layout/header.php');
    $footer = (string) file_get_contents(APP_ROOT . '/app/Views/admin/layout/footer.php');
    assert_contains("FrontendAssets::adminBundle('css')", $header);
    assert_contains("FrontendAssets::adminBundle('js')", $footer);
    // Без этих атрибутов слои догружали себя повторно (admin-notifications.js,
    // admin-workflow-fixes.js) — и второй экземпляр перебивал каскад.
    assert_contains('data-admin-notifications-css="1" data-admin-slider-settings-layout', $header);
    assert_contains('ENT_QUOTES) ?>" data-admin-slider-settings-layout></script>', $footer, 'сборка JS — обычный синхронный скрипт');
    assert_contains('AdminBrand::styleTag($adminCssBundle === null)', $header);
});

test('Сборка панели отдаётся только свежей (БД)', function (): void {
    if ((string) (getenv('TEST_DB_DATABASE') ?: '') === '') {
        skip_test('TEST_DB_* не заданы');
    }
    if (!FrontendAssets::enabled()) {
        skip_test('сборка ассетов выключена');
    }
    assert_same(admin_bundle_manifest()['adminBundles']['css']['path'], FrontendAssets::adminBundle('css'));
    assert_same(null, FrontendAssets::adminBundle('nope'));
});
