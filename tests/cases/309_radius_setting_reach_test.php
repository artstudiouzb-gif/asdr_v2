<?php

declare(strict_types=1);

use App\Core\DesignSettings;

/*
 * Настройки «Дизайна» обязаны доходить до страницы.
 *
 * Настройка печатает `--radius`, `--radius-sm` (0.6 от него) и `--btn-radius`,
 * но правило, где радиус написан числом, переменную не слушает. Замерено
 * в браузере до правки: на странице «Руководство» настройку не слушали
 * карточка сотрудника, фотография руководителя и блок образования, в ленте —
 * карточка новости, в каталоге — панель фильтров. Редактор двигал ползунок,
 * а половина страницы не менялась — это и читается как «настройки не
 * работают».
 *
 * Поэтому здесь два стража: у ключевых компонентов радиус берётся из
 * переменной, а общее число жёстких значений в публичном CSS может только
 * уменьшаться — тем же приёмом, что бюджеты `!important` и классов без правил.
 */

/** Правила публичного CSS: селектор => список объявлений border-radius. */
$publicRadiusRules = static function (): array {
    $root = dirname(__DIR__, 2) . '/public/assets/css';
    $files = [];
    /** @var SplFileInfo $file */
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)) as $file) {
        $path = $file->getPathname();
        if (!str_ends_with($path, '.css')
            || str_contains($path, '.min.')
            || str_contains($path, 'admin')
            || str_contains($path, 'vendor')) {
            continue;
        }
        $files[] = $path;
    }
    sort($files);

    $rules = [];
    foreach ($files as $path) {
        $css = (string) file_get_contents($path);
        preg_match_all('/([^{}]+)\{([^}]*)\}/s', $css, $matches, PREG_SET_ORDER);
        foreach ($matches as $rule) {
            $selector = trim((string) preg_replace('/\s+/', ' ', $rule[1]));
            if ($selector === '' || str_starts_with($selector, '@')) {
                continue;
            }
            preg_match_all('/border(?:-[a-z-]+)?-radius\s*:\s*([^;!}]+)/', $rule[2], $found);
            foreach ($found[1] as $value) {
                $rules[] = ['file' => basename($path), 'selector' => $selector, 'value' => trim($value)];
            }
        }
    }

    return $rules;
};

test('Настройка «Скругление углов» печатает переменные', function () {
    $css = DesignSettings::cssVariables(['radius' => 'large', 'button' => 'rounded']);

    assert_contains('--radius:22px', $css);
    assert_contains('--radius-sm:calc(22px * .6)', $css);
    assert_contains('--btn-radius:', $css);

    $none = DesignSettings::cssVariables(['radius' => 'none']);
    assert_contains('--radius:0px', $none, 'вариант «Прямые» обязан давать ноль');
});

test('Ключевые карточки берут радиус из переменной', function () use ($publicRadiusRules) {
    $rules = $publicRadiusRules();

    // Компонент => селектор, ровно с которого начинается его правило.
    $required = [
        'карточка новости' => '.news-card',
        'карточка сотрудника' => '.person-card',
        'карточка записи' => '.content-card',
        'карточка документа' => '.doc-card',
        'слайдер' => '.block-slider',
        'фотография руководителя' => '.editorial-page__content .profile__img',
        'блок «Преимущества»' => '.block-advantages__item',
    ];

    foreach ($required as $title => $selector) {
        $own = array_values(array_filter(
            $rules,
            static fn (array $rule): bool => $rule['selector'] === $selector
        ));
        assert_true($own !== [], $title . ': правило ' . $selector . ' не найдено — селектор переименовали?');
        foreach ($own as $rule) {
            assert_true(
                str_contains($rule['value'], 'var(--radius')
                || str_contains($rule['value'], 'var(--btn-radius'),
                $title . ' (' . $rule['file'] . ') не слушает настройку: ' . $rule['value']
            );
        }
    }
});

test('Бюджет жёстких скруглений в публичном CSS только уменьшается', function () use ($publicRadiusRules) {
    // Круги, пилюли и нули настройкой не управляются: это форма элемента,
    // а не оформление карточки.
    $hard = array_values(array_filter($publicRadiusRules(), static function (array $rule): bool {
        $value = $rule['value'];

        return !str_contains($value, 'var(')
            && preg_match('/^(0|0px|50%|100%|999px|9999px|inherit)$/', $value) !== 1;
    }));

    $budget = 90;
    assert_true(
        count($hard) <= $budget,
        'жёстких значений border-radius стало больше бюджета: ' . count($hard) . ' > ' . $budget
        . '; новое правило должно брать var(--radius), var(--radius-sm) или var(--btn-radius)'
    );
});

test('«Плотность секций» меняет вертикальный ритм', function () {
    // До правки настройка не действовала вовсе: блок выводится с классом
    // `cms-block--space-<пресет>`, а тот берёт отступ из --space-*, минуя
    // --section-pad, куда плотность и печаталась. Значение подменяем в памяти
    // запроса — в БД тест не пишет и соседние сценарии не задевает.
    \App\Models\Setting::overrideInMemory('design_density', 'compact');
    $compact = DesignSettings::semanticSpacings();
    \App\Models\Setting::overrideInMemory('design_density', 'spacious');
    $spacious = DesignSettings::semanticSpacings();
    \App\Models\Setting::overrideInMemory('design_density', 'standard');
    $standard = DesignSettings::semanticSpacings();

    assert_true($compact !== $spacious, 'плотность не меняет отступы секций');
    assert_true($compact !== $standard, '«Компактно» совпало со «Стандартом»');
    // «Стандарт» обязан совпадать с прежними значениями: сайт, где настройку
    // не трогали, от этой правки меняться не должен.
    assert_same('clamp(28px, 4vw, 56px)', $standard['space_premium']);
});

test('Заголовки берут межстрочный интервал из настройки', function () {
    // Тема грузится после базы, и жёсткое line-height в её общем правиле
    // заголовков перекрывало переменную: замерено, что настройку слушал один
    // заголовок из двадцати семи.
    $theme = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/gov-theme.css');
    $rule = '';
    if (preg_match('/h1, h2, h3, h4, h5, h6,.*?\{(.*?)\}/s', $theme, $m) === 1) {
        $rule = $m[1];
    }
    assert_true($rule !== '', 'общее правило заголовков темы не найдено');
    assert_contains('var(--heading-line-height', $rule);
    assert_contains('var(--heading-font-weight', $rule);
    assert_contains('var(--heading-letter-spacing', $rule);
});
