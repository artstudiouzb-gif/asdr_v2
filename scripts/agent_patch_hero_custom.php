<?php

declare(strict_types=1);

function replaceOnce(string $path, string $old, string $new): void
{
    $content = (string) file_get_contents($path);
    if (!str_contains($content, $old)) {
        fwrite(STDERR, "Pattern not found in {$path}: " . substr($old, 0, 100) . "\n");
        exit(2);
    }
    $content = str_replace($old, $new, $content, $count);
    if ($count !== 1) {
        fwrite(STDERR, "Expected one replacement in {$path}, got {$count}\n");
        exit(2);
    }
    file_put_contents($path, $content);
}

function replaceRegex(string $path, string $pattern, string $replacement): void
{
    $content = (string) file_get_contents($path);
    $updated = preg_replace($pattern, $replacement, $content, 1, $count);
    if ($updated === null || $count !== 1) {
        fwrite(STDERR, "Regex replacement failed in {$path}; count={$count}\n");
        exit(2);
    }
    file_put_contents($path, $updated);
}

$root = dirname(__DIR__);

// -------------------------------------------------------------------------
// Hero renderer: a foreground SVG/PNG gets an explicit width. CTA images
// retain 20x20 only in icon mode; fill/custom are first-class image modes.
// -------------------------------------------------------------------------
$renderer = $root . '/app/Core/Hero/HeroRenderer.php';
replaceRegex(
    $renderer,
    '~    /\*\* @param array<string, mixed> \$d \*/\n    private static function art\(array \$d\): string\n    \{.*?\n    \}\n\n    /\*\*\n     \* Фоновая надпись~s',
    <<<'PHP'
    /** @param array<string, mixed> $d */
    private static function art(array $d): string
    {
        $image = (string) $d['art_image'];
        if ($image === '' || !UrlGuard::isSafeMedia($image)) {
            return '';
        }
        $alt = (string) $d['art_alt'];
        $size = (string) $d['art_size'];
        $width = match ($size) {
            'small' => 120,
            'large' => 360,
            'custom' => (int) $d['art_width'],
            default => 220,
        };

        // Width is explicit for both raster and SVG artwork: an auto grid
        // track must not be allowed to collapse a wide logo. Height remains
        // automatic so the original aspect ratio is preserved.
        return '<span class="hero__art hero__art--' . htmlspecialchars($size, ENT_QUOTES)
            . ' hero__art--' . htmlspecialchars((string) $d['art_position'], ENT_QUOTES) . '"'
            . ($alt === '' ? ' aria-hidden="true"' : '') . '>'
            . '<img src="' . htmlspecialchars($image, ENT_QUOTES) . '" alt="' . htmlspecialchars($alt, ENT_QUOTES) . '"'
            . ' width="' . $width . '" loading="lazy" decoding="async"></span>';
    }

    /**
     * Фоновая надпись
PHP
);

replaceRegex(
    $renderer,
    '~    /\*\* @param array<string, mixed> \$d \*/\n    private static function button\(array \$d, string \$key\): string\n    \{.*?\n    \}\n\n    /\*\*\n     \* Переменные конкретного слайда~s',
    <<<'PHP'
    /** @param array<string, mixed> $d */
    private static function button(array $d, string $key): string
    {
        if (empty($d[$key . '_enabled'])) {
            return '';
        }
        $text = trim((string) $d[$key . '_text']);
        $url = (string) $d[$key . '_url'];
        if ($text === '' || $url === '' || !UrlGuard::isSafeLink($url)) {
            return '';
        }

        $image = trim((string) ($d[$key . '_image'] ?? ''));
        $icon = (string) $d[$key . '_icon'];
        $imageMode = (string) ($d[$key . '_image_mode'] ?? 'icon');
        $imageClass = '';

        if ($image !== '' && UrlGuard::isSafeMedia($image)) {
            $imageClass = ' hero__cta--image-' . $imageMode;
            $sizeAttrs = match ($imageMode) {
                'fill' => ' height="44"',
                'custom' => ' width="' . (int) $d[$key . '_image_width'] . '"',
                default => ' width="20" height="20"',
            };
            $iconHtml = '<span class="hero__cta-icon" aria-hidden="true">'
                . '<img src="' . htmlspecialchars($image, ENT_QUOTES) . '" alt=""'
                . $sizeAttrs . ' loading="lazy" decoding="async"></span>';
        } elseif ($icon !== '') {
            $iconHtml = '<span class="hero__cta-icon" aria-hidden="true">' . Icon::render($icon, 20) . '</span>';
        } else {
            $iconHtml = '';
        }

        return '<a class="hero__cta hero__cta--' . htmlspecialchars((string) $d[$key . '_style'], ENT_QUOTES)
            . ($iconHtml !== '' ? ' hero__cta--with-icon' : '') . $imageClass . '"'
            . ' href="' . htmlspecialchars($url, ENT_QUOTES) . '"'
            . (!empty($d[$key . '_new_tab']) ? ' target="_blank" rel="noopener"' : '')
            . '>' . $iconHtml . '<span>' . htmlspecialchars($text, ENT_QUOTES) . '</span></a>';
    }

    /**
     * Переменные конкретного слайда
PHP
);

// Base hero.css no longer forces every custom image to 20x20. The renderer
// owns intrinsic size attributes; mode-specific overrides live in the single
// hero-art-layout.css layer.
$heroCss = $root . '/public/assets/css/blocks/hero.css';
replaceOnce(
    $heroCss,
    ".hero__cta-icon { display: inline-flex; }\n/* Своя картинка кнопки: держим её в тех же 20px, что и иконку набора, и не\n   даём растянуться — SVG без пиксельных размеров иначе занимает всю кнопку\n   (те же грабли, что с логотипом в шапке). */\n.hero__cta-icon img { width: 20px; height: 20px; object-fit: contain; display: block; }",
    ".hero__cta-icon { display: inline-flex; flex: none; }\n/* Размер своей картинки задаёт HeroRenderer: 20x20 только для режима\n   «как иконка». Fill/custom не должны наследовать старое жёсткое ограничение. */\n.hero__cta-icon img { object-fit: contain; display: block; }"
);

// -------------------------------------------------------------------------
// Admin form: clear inheritance wording, custom sizes, translated URLs.
// -------------------------------------------------------------------------
$form = $root . '/app/Views/admin/heroes/slide_form.php';
replaceOnce($form, "$inherit = ['' => 'Как у обложки'];", "$inherit = ['' => 'Использовать общую настройку обложки'];");
replaceOnce(
    $form,
    "$ctaStyles = [\n    'primary' => 'Основная (заливка акцентом)',\n    'secondary' => 'Вторичная (светлая заливка)',\n    'ghost' => 'Контурная',\n    'link' => 'Ссылка',\n];",
    "$ctaStyles = [\n    'primary' => 'Основная (заливка акцентом)',\n    'secondary' => 'Вторичная (светлая заливка)',\n    'ghost' => 'Контурная',\n    'link' => 'Ссылка',\n];\n$ctaImageModes = [\n    'icon' => 'Как иконка — 20 px',\n    'fill' => 'На всю высоту кнопки',\n    'custom' => 'Своя ширина',\n];"
);
replaceOnce(
    $form,
    "$artSizeLabels = ['small' => 'маленькая', 'medium' => 'средняя', 'large' => 'крупная'];",
    "$artSizeLabels = ['small' => '120 px', 'medium' => '220 px', 'large' => '360 px', 'custom' => 'свой размер'];"
);
replaceOnce($form, "$layoutState = $hasLayoutOverrides ? 'Есть свои настройки' : 'Как у обложки';", "$layoutState = $hasLayoutOverrides ? 'Есть свои настройки' : 'Общее значение';");
replaceOnce($form, "$mobileState = $hasMobileOverrides ? 'Есть свои настройки' : 'Как у обложки';", "$mobileState = $hasMobileOverrides ? 'Есть свои настройки' : 'Общее значение';");
replaceOnce($form, "$colorState = $hasColorOverrides ? 'Есть свои настройки' : 'Как у обложки';", "$colorState = $hasColorOverrides ? 'Есть свои настройки' : 'Общее значение';");

replaceOnce(
    $form,
    "            В остальных разделах значение «Как у обложки» означает наследование общей настройки.\n            Если здесь задано своё значение, оно действует только для этого слайда и имеет приоритет.",
    "            Пункт «Использовать общую настройку обложки» берёт значение из общих настроек Hero-блока.\n            Если здесь задано своё значение, оно действует только для этого слайда и имеет приоритет."
);

replaceOnce(
    $form,
    "            <?= $select('art_size', 'Размер', [\n                'small' => 'Маленькая', 'medium' => 'Средняя', 'large' => 'Крупная',\n            ], (string) $data['art_size']) ?>",
    "            <?= $select('art_size', 'Размер', [\n                'small' => 'Маленькая — 120 px', 'medium' => 'Средняя — 220 px',\n                'large' => 'Крупная — 360 px', 'custom' => 'Свой размер',\n            ], (string) $data['art_size']) ?>\n            <div class=\"form-field\">\n                <label for=\"art_width\">Своя ширина логотипа, px</label>\n                <input type=\"number\" id=\"art_width\" name=\"art_width\" min=\"40\" max=\"1200\" step=\"1\" value=\"<?= (int) $data['art_width'] ?>\">\n                <span class=\"form-hint\">Используется только при размере «Свой размер». Высота рассчитывается автоматически.</span>\n            </div>"
);

replaceOnce(
    $form,
    "            <?= AdminUi::imageField('cta_image', (string) $data['cta_image'], [\n                'label' => 'Своя картинка вместо иконки',\n                'hint' => 'SVG или PNG. Если задана, используется вместо иконки Tabler.',\n            ]) ?>\n            <?= $checkbox('cta_new_tab', 'Основную кнопку открывать в новой вкладке', (bool) $data['cta_new_tab']) ?>",
    "            <?= AdminUi::imageField('cta_image', (string) $data['cta_image'], [\n                'label' => 'Своя картинка вместо иконки',\n                'hint' => 'SVG или PNG. Если задана, используется вместо иконки Tabler.',\n            ]) ?>\n            <?= $select('cta_image_mode', 'Размер своей картинки', $ctaImageModes, (string) $data['cta_image_mode']) ?>\n            <div class=\"form-field\">\n                <label for=\"cta_image_width\">Своя ширина картинки, px</label>\n                <input type=\"number\" id=\"cta_image_width\" name=\"cta_image_width\" min=\"20\" max=\"400\" step=\"1\" value=\"<?= (int) $data['cta_image_width'] ?>\">\n                <span class=\"form-hint\">Работает в режиме «Своя ширина». Режим «На всю высоту» использует высоту самой кнопки.</span>\n            </div>\n            <?= $checkbox('cta_new_tab', 'Основную кнопку открывать в новой вкладке', (bool) $data['cta_new_tab']) ?>"
);

replaceOnce(
    $form,
    "            <?= AdminUi::imageField('cta2_image', (string) $data['cta2_image'], [\n                'label' => 'Своя картинка вместо иконки',\n                'hint' => 'SVG или PNG. Если задана, используется вместо иконки Tabler.',\n            ]) ?>\n            <?= $checkbox('cta2_new_tab', 'Дополнительную кнопку открывать в новой вкладке', (bool) $data['cta2_new_tab']) ?>",
    "            <?= AdminUi::imageField('cta2_image', (string) $data['cta2_image'], [\n                'label' => 'Своя картинка вместо иконки',\n                'hint' => 'SVG или PNG. Если задана, используется вместо иконки Tabler.',\n            ]) ?>\n            <?= $select('cta2_image_mode', 'Размер своей картинки', $ctaImageModes, (string) $data['cta2_image_mode']) ?>\n            <div class=\"form-field\">\n                <label for=\"cta2_image_width\">Своя ширина картинки, px</label>\n                <input type=\"number\" id=\"cta2_image_width\" name=\"cta2_image_width\" min=\"20\" max=\"400\" step=\"1\" value=\"<?= (int) $data['cta2_image_width'] ?>\">\n                <span class=\"form-hint\">Работает в режиме «Своя ширина». Режим «На всю высоту» использует высоту самой кнопки.</span>\n            </div>\n            <?= $checkbox('cta2_new_tab', 'Дополнительную кнопку открывать в новой вкладке', (bool) $data['cta2_new_tab']) ?>"
);

// Remove the old ambiguous wording from this editor entirely.
$content = (string) file_get_contents($form);
$content = str_replace('«Как у обложки»', '«Использовать общую настройку обложки»', $content);
$content = str_replace("'Как у обложки'", "'Общее значение'", $content);
$content = str_replace('placeholder="как у обложки"', 'placeholder="общая настройка"', $content);
$content = str_replace('placeholder="<?= $globalDuration ?> — из обложки"', 'placeholder="<?= $globalDuration ?> — общее значение"', $content);
file_put_contents($form, $content);

replaceOnce(
    $form,
    "                        Переводится только текст. Фон, расположение и оформление общие для всех языков.\n                        Пустое поле использует текст основного языка <?= $esc(strtoupper($defaultCode)) ?>.",
    "                        Текст и ссылки можно задавать отдельно для каждого языка. Фон, расположение и оформление остаются общими.\n                        Пустое поле использует значение основного языка <?= $esc(strtoupper($defaultCode)) ?>."
);

replaceOnce(
    $form,
    "                        <div class=\"form-field\">\n                            <label for=\"<?= $id ?>cta\">Текст основной кнопки</label>\n                            <input type=\"text\" id=\"<?= $id ?>cta\" name=\"<?= $key ?>[cta_text]\" value=\"<?= $esc($tr['cta_text'] ?? '') ?>\">\n                        </div>",
    "                        <div class=\"form-field\">\n                            <label for=\"<?= $id ?>cta\">Текст основной кнопки</label>\n                            <input type=\"text\" id=\"<?= $id ?>cta\" name=\"<?= $key ?>[cta_text]\" value=\"<?= $esc($tr['cta_text'] ?? '') ?>\">\n                        </div>\n                        <div class=\"form-field\">\n                            <label for=\"<?= $id ?>cta-url\">Ссылка основной кнопки</label>\n                            <input type=\"text\" id=\"<?= $id ?>cta-url\" name=\"<?= $key ?>[cta_url]\" value=\"<?= $esc($tr['cta_url'] ?? '') ?>\" placeholder=\"пусто — ссылка основного языка\">\n                        </div>"
);
replaceOnce(
    $form,
    "                        <div class=\"form-field\">\n                            <label for=\"<?= $id ?>cta2\">Текст дополнительной кнопки</label>\n                            <input type=\"text\" id=\"<?= $id ?>cta2\" name=\"<?= $key ?>[cta2_text]\" value=\"<?= $esc($tr['cta2_text'] ?? '') ?>\">\n                        </div>",
    "                        <div class=\"form-field\">\n                            <label for=\"<?= $id ?>cta2\">Текст дополнительной кнопки</label>\n                            <input type=\"text\" id=\"<?= $id ?>cta2\" name=\"<?= $key ?>[cta2_text]\" value=\"<?= $esc($tr['cta2_text'] ?? '') ?>\">\n                        </div>\n                        <div class=\"form-field\">\n                            <label for=\"<?= $id ?>cta2-url\">Ссылка дополнительной кнопки</label>\n                            <input type=\"text\" id=\"<?= $id ?>cta2-url\" name=\"<?= $key ?>[cta2_url]\" value=\"<?= $esc($tr['cta2_url'] ?? '') ?>\" placeholder=\"пусто — ссылка основного языка\">\n                        </div>\n                        <div class=\"form-field\">\n                            <label for=\"<?= $id ?>slide-url\">Ссылка со всего слайда</label>\n                            <input type=\"text\" id=\"<?= $id ?>slide-url\" name=\"<?= $key ?>[link_url]\" value=\"<?= $esc($tr['link_url'] ?? '') ?>\" placeholder=\"пусто — ссылка основного языка\">\n                        </div>"
);

// -------------------------------------------------------------------------
// Apply translated URLs using the same map as translated text. There is no
// parallel link resolver: HeroSlide::applyTranslation remains the one path.
// -------------------------------------------------------------------------
$slideModel = $root . '/app/Models/HeroSlide.php';
replaceOnce(
    $slideModel,
    "            'cta_text' => 'cta_text',\n            'cta2_text' => 'cta2_text',\n            'art_alt' => 'art_alt',",
    "            'cta_text' => 'cta_text',\n            'cta_url' => 'cta_url',\n            'cta2_text' => 'cta2_text',\n            'cta2_url' => 'cta2_url',\n            'link_url' => 'link_url',\n            'art_alt' => 'art_alt',"
);

// Regression contract: one sizing system, translated links, no old wording.
$test = <<<'PHP'
<?php

declare(strict_types=1);

use App\Core\Hero\HeroSlideData;

test('Hero: custom artwork and CTA image sizes are normalized', function (): void {
    $data = HeroSlideData::normalize([
        'art_size' => 'custom',
        'art_width' => '777',
        'cta_image_mode' => 'fill',
        'cta_image_width' => '333',
        'cta2_image_mode' => 'custom',
        'cta2_image_width' => '222',
    ]);

    assert_same('custom', $data['art_size']);
    assert_same(777, $data['art_width']);
    assert_same('fill', $data['cta_image_mode']);
    assert_same(333, $data['cta_image_width']);
    assert_same('custom', $data['cta2_image_mode']);
    assert_same(222, $data['cta2_image_width']);
});

test('Hero editor: sizing and translated links use one UI contract', function (): void {
    $form = (string) file_get_contents(APP_ROOT . '/app/Views/admin/heroes/slide_form.php');
    $renderer = (string) file_get_contents(APP_ROOT . '/app/Core/Hero/HeroRenderer.php');
    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/blocks/hero-art-layout.css');
    $baseCss = (string) file_get_contents(APP_ROOT . '/public/assets/css/blocks/hero.css');
    $translations = (string) file_get_contents(APP_ROOT . '/app/Models/HeroSlideTranslation.php');
    $slide = (string) file_get_contents(APP_ROOT . '/app/Models/HeroSlide.php');

    assert_contains("'custom' => 'Свой размер'", $form, 'логотип имеет custom size');
    assert_contains('name="art_width"', $form, 'есть ширина логотипа');
    assert_contains("'fill' => 'На всю высоту кнопки'", $form, 'картинка CTA может занять высоту кнопки');
    assert_contains('name="cta_image_width"', $form, 'основная кнопка имеет custom width');
    assert_contains('name="cta2_image_width"', $form, 'дополнительная кнопка имеет custom width');
    assert_not_contains('Как у обложки', $form, 'старое неоднозначное название удалено из UI');
    assert_contains('Использовать общую настройку обложки', $form, 'наследование объяснено явно');

    assert_contains('hero__cta--image-', $renderer, 'renderer выдаёт один режим картинки CTA');
    assert_contains("'custom' => (int) $d['art_width']", $renderer, 'custom width логотипа попадает в HTML');
    assert_not_contains('width="20" height="20" loading="lazy" decoding="async"></span>', $baseCss, 'base CSS не фиксирует все картинки CTA в 20px');
    assert_contains('.hero__art--large img', $css, 'единый sizing layer содержит large preset');
    assert_contains('width: 360px;', $css, 'large preset имеет явную ширину');

    foreach (['cta_url', 'cta2_url', 'link_url'] as $field) {
        assert_contains("[{$field}]", $form, "поле перевода {$field} присутствует");
        assert_contains("'{$field}'", $translations, "translation model хранит {$field}");
        assert_contains("'{$field}' => '{$field}'", $slide, "HeroSlide применяет {$field}");
    }
});
PHP;
file_put_contents($root . '/tests/cases/104_hero_media_size_translation_links_test.php', $test);

// This helper and its one-shot workflow are implementation scaffolding only.
@unlink(__FILE__);
@unlink($root . '/.github/workflows/agent-patch-hero-custom.yml');
