<?php

declare(strict_types=1);

use App\Core\BlockRenderer;

/*
 * Цвета CTA, баннера, CTA-ленты и подписи счётчиков задаются в «Настройках
 * блока» и приходят переменными в scoped CSS блока. Правила темы применяли их
 * через `[style*="--cta-bg"]` — то есть искали переменную в атрибуте style,
 * которого в разметке давно нет. Замерено в браузере: `--cta-bg: #aa0000` на
 * блоке есть, фон прозрачный; выбранные цвета не применялись вовсе.
 *
 * Теперь шаблон ставит модификатор `--custom-bg/text/btn`, и тема опирается
 * на него.
 */
test('Цвет блока из настроек включает модификатор на корне блока', function (): void {
    $cases = [
        'default' => 'block-cta',
        'band' => 'block-ctaband',
        'media-dark' => 'block-banner',
    ];
    foreach ($cases as $variant => $base) {
        $html = BlockRenderer::render([
            'id' => 9100,
            'type' => 'cta',
            'data' => json_encode([
                'title' => 'Заголовок',
                'text' => 'Текст',
                'button_text' => 'Кнопка',
                'button_url' => '/',
                'variant' => $variant,
                'bg_color' => '#aa0000',
                'text_color' => '#00aa00',
                'button_color' => '#0000aa',
            ], JSON_THROW_ON_ERROR),
            'custom_css' => '',
        ])['html'];

        foreach (['bg', 'text', 'btn'] as $suffix) {
            assert_contains($base . '--custom-' . $suffix, $html, $variant . ': нет модификатора ' . $suffix);
        }
    }

    $plain = BlockRenderer::render([
        'id' => 9101,
        'type' => 'cta',
        'data' => json_encode(['title' => 'Без цвета', 'variant' => 'default'], JSON_THROW_ON_ERROR),
        'custom_css' => '',
    ])['html'];
    assert_not_contains('--custom-', $plain, 'без выбранного цвета модификатора быть не должно');
});

test('Тема не ищет переменные блоков в атрибуте style', function (): void {
    // Переменные блоков приходят scoped CSS, а не атрибутом: селектор
    // `[style*="--…"]` по ним никогда не совпадёт. Единственное исключение —
    // точка фокуса картинки, которую Media пока выводит атрибутом.
    $css = theme_css() . (string) file_get_contents(APP_ROOT . '/public/assets/css/blocks/counters.css');
    preg_match_all('/\[style\*="(--[a-z-]+)"\]/', $css, $m);
    $vars = array_values(array_diff(array_unique($m[1]), ['--media-object-position']));

    assert_same([], $vars, 'селектор по атрибуту style для переменных: ' . implode(', ', $vars));
});
