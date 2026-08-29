<?php

declare(strict_types=1);

/**
 * Типографика админки: одна шкала кегля и три начертания.
 *
 * Было 72 разных размера — от 9px до 26px, вперемешку px и rem, с половинками
 * (11.5, 12.5, 13.5, 14.5) и близнецами вроде 0.82rem = 13.1px рядом с 13px, —
 * и 15 начертаний, включая 450, 550, 650, 720, 750 и 850. Таких начертаний у
 * системных шрифтов нет: браузер подменял их синтетическим жирным, отчего текст
 * и выглядел грубым. Из 340 объявлений веса 285 стояли на 600 и выше.
 *
 * Теперь размер берётся только из `--admin-fs-*`, вес — только из
 * `--admin-fw-*`, семейство — из `--admin-font`. Новое значение мимо шкалы
 * роняет тест: подобрать «ещё один 13.5px» заново нельзя.
 */

/** @return list<string> файлы CSS админки */
function admin_css_files(): array
{
    return glob(APP_ROOT . '/public/assets/css/admin*.css') ?: [];
}

/**
 * Значения одного свойства во всех файлах админки.
 *
 * @return array<string,string> значение => файл, где встретилось
 */
function admin_css_values(string $property): array
{
    $found = [];
    foreach (admin_css_files() as $file) {
        $css = (string) file_get_contents($file);
        if (!preg_match_all('/' . $property . '\s*:\s*([^;}]+)/i', $css, $m)) {
            continue;
        }
        foreach ($m[1] as $value) {
            $value = trim(str_replace('!important', '', $value));
            $found[$value] = basename($file);
        }
    }

    return $found;
}

test('Кегль в админке берётся из шкалы', function () {
    $values = admin_css_values('font-size');
    assert_true(count($values) > 0, 'объявления кегля найдены');

    $strays = [];
    foreach ($values as $value => $file) {
        // Ключ массива с числовым значением PHP приводит к int.
        $value = (string) $value;
        // `--live-*` и `--preview-*` — предпросмотр публичной страницы внутри
        // панели: он обязан повторять сайт, а не шкалу админки. `em` — размер
        // относительно родителя, у него другой смысл.
        if (str_starts_with($value, 'var(--admin-fs-')) { continue; }
        if (str_contains($value, 'var(--live-') || str_contains($value, 'var(--preview-')) { continue; }
        if ($value === '0' || preg_match('/^[0-9.]+em$/', $value)) { continue; }
        $strays[] = $value . ' (' . $file . ')';
    }

    sort($strays);
    assert_true($strays === [], 'кегль мимо шкалы: ' . implode(', ', $strays));
});

test('Начертаний в админке три', function () {
    $values = admin_css_values('font-weight');
    assert_true(count($values) > 0, 'объявления веса найдены');

    $strays = [];
    foreach ($values as $value => $file) {
        $value = (string) $value;
        if (str_starts_with($value, 'var(--admin-fw-')) { continue; }
        if (str_contains($value, 'var(--live-')) { continue; }
        $strays[] = $value . ' (' . $file . ')';
    }

    sort($strays);
    assert_true($strays === [], 'вес мимо набора: ' . implode(', ', $strays));

    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/admin.css');
    foreach (['--admin-fw-normal: 400', '--admin-fw-medium: 500', '--admin-fw-semibold: 600'] as $token) {
        assert_contains($token, $css, 'объявлено начертание ' . $token);
    }
    foreach (['--admin-fs-xs: 11px', '--admin-fs-base: 13px', '--admin-fs-2xl: 24px'] as $token) {
        assert_contains($token, $css, 'объявлена ступень ' . $token);
    }
});

test('Семейство шрифта в админке одно', function () {
    // Шрифты-исключения: три карточки выбора шрифта в «Дизайне» показывают
    // образец начертания, а превью выдачи Google набрано Arial, как в самой
    // выдаче. Остальное — общий токен.
    $allowed = [
        'var(--admin-font)',
        'var(--admin-font-mono)',
        'inherit',
        'monospace',
        'var(--font-mono, monospace)',
        "'Noto Sans', 'Noto Sans Fallback', system-ui, sans-serif",
        'system-ui, Arial, sans-serif',
        "Georgia, 'Times New Roman', serif",
        'arial,sans-serif',
    ];

    $strays = [];
    foreach (admin_css_values('font-family') as $value => $file) {
        $value = (string) $value;
        if (in_array($value, $allowed, true)) { continue; }
        if (str_contains($value, 'var(--live-')) { continue; }
        $strays[] = $value . ' (' . $file . ')';
    }

    sort($strays);
    assert_true($strays === [], 'семейство мимо токена: ' . implode(', ', $strays));
});
