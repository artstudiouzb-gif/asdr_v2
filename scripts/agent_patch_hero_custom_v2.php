<?php

declare(strict_types=1);

function rx(string $path, string $pattern, string $replacement): void
{
    $source = (string) file_get_contents($path);
    $result = preg_replace($pattern, $replacement, $source, 1, $count);
    if ($result === null || $count !== 1) {
        fwrite(STDERR, "Patch failed: {$path}; count={$count}\n");
        exit(2);
    }
    file_put_contents($path, $result);
}

function once(string $path, string $old, string $new): void
{
    $source = (string) file_get_contents($path);
    if (substr_count($source, $old) !== 1) {
        fwrite(STDERR, "Patch target is not unique: {$path}\n");
        exit(2);
    }
    file_put_contents($path, str_replace($old, $new, $source));
}

$root = dirname(__DIR__);
$renderer = $root . '/app/Core/Hero/HeroRenderer.php';
$form = $root . '/app/Views/admin/heroes/slide_form.php';
$heroCss = $root . '/public/assets/css/blocks/hero.css';
$slideModel = $root . '/app/Models/HeroSlide.php';

rx($renderer, '~    /\*\* @param array<string, mixed> \$d \*/\n    private static function art\(array \$d\): string\n    \{.*?\n    \}\n(?=\n    /\*\*\n     \* Фоновая надпись)~s', <<<'PHP'
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

        // Explicit width keeps wide SVG/PNG logos stable in the auto grid
        // column. Height stays automatic, preserving the original ratio.
        return '<span class="hero__art hero__art--' . htmlspecialchars($size, ENT_QUOTES)
            . ' hero__art--' . htmlspecialchars((string) $d['art_position'], ENT_QUOTES) . '"'
            . ($alt === '' ? ' aria-hidden="true"' : '') . '>'
            . '<img src="' . htmlspecialchars($image, ENT_QUOTES) . '" alt="' . htmlspecialchars($alt, ENT_QUOTES) . '"'
            . ' width="' . $width . '" loading="lazy" decoding="async"></span>';
    }
PHP);

rx($renderer, '~    /\*\* @param array<string, mixed> \$d \*/\n    private static function button\(array \$d, string \$key\): string\n    \{.*?\n    \}\n(?=\n    /\*\*\n     \* Переменные конкретного слайда)~s', <<<'PHP'
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
PHP);

once($heroCss, <<<'CSS'
.hero__cta-icon { display: inline-flex; }
/* Своя картинка кнопки: держим её в тех же 20px, что и иконку набора, и не
   даём растянуться — SVG без пиксельных размеров иначе занимает всю кнопку
   (те же грабли, что с логотипом в шапке). */
.hero__cta-icon img { width: 20px; height: 20px; object-fit: contain; display: block; }
CSS, <<<'CSS'
.hero__cta-icon { display: inline-flex; flex: none; }
/* Размер своей картинки задаёт HeroRenderer: 20x20 только для режима
   «как иконка». Fill/custom не наследуют старое жёсткое ограничение. */
.hero__cta-icon img { object-fit: contain; display: block; }
CSS);

once($form, <<<'PHP'
$inherit = ['' => 'Как у обложки'];
PHP, <<<'PHP'
$inherit = ['' => 'Использовать общую настройку обложки'];
PHP);

once($form, <<<'PHP'
$ctaStyles = [
    'primary' => 'Основная (заливка акцентом)',
    'secondary' => 'Вторичная (светлая заливка)',
    'ghost' => 'Контурная',
    'link' => 'Ссылка',
];
PHP, <<<'PHP'
$ctaStyles = [
    'primary' => 'Основная (заливка акцентом)',
    'secondary' => 'Вторичная (светлая заливка)',
    'ghost' => 'Контурная',
    'link' => 'Ссылка',
];
$ctaImageModes = [
    'icon' => 'Как иконка — 20 px',
    'fill' => 'На всю высоту кнопки',
    'custom' => 'Своя ширина',
];
PHP);

once($form, <<<'PHP'
$artSizeLabels = ['small' => 'маленькая', 'medium' => 'средняя', 'large' => 'крупная'];
PHP, <<<'PHP'
$artSizeLabels = ['small' => '120 px', 'medium' => '220 px', 'large' => '360 px', 'custom' => 'свой размер'];
PHP);

once($form, <<<'PHP'
$layoutState = $hasLayoutOverrides ? 'Есть свои настройки' : 'Как у обложки';
$mobileState = $hasMobileOverrides ? 'Есть свои настройки' : 'Как у обложки';
$colorState = $hasColorOverrides ? 'Есть свои настройки' : 'Как у обложки';
PHP, <<<'PHP'
$layoutState = $hasLayoutOverrides ? 'Есть свои настройки' : 'Общее значение';
$mobileState = $hasMobileOverrides ? 'Есть свои настройки' : 'Общее значение';
$colorState = $hasColorOverrides ? 'Есть свои настройки' : 'Общее значение';
PHP);

once($form, <<<'HTML'
            В остальных разделах значение «Как у обложки» означает наследование общей настройки.
            Если здесь задано своё значение, оно действует только для этого слайда и имеет приоритет.
HTML, <<<'HTML'
            Пункт «Использовать общую настройку обложки» берёт значение из общих настроек Hero-блока.
            Если здесь задано своё значение, оно действует только для этого слайда и имеет приоритет.
HTML);

once($form, <<<'PHP'
            <?= $select('art_size', 'Размер', [
                'small' => 'Маленькая', 'medium' => 'Средняя', 'large' => 'Крупная',
            ], (string) $data['art_size']) ?>
PHP, <<<'PHP'
            <?= $select('art_size', 'Размер', [
                'small' => 'Маленькая — 120 px', 'medium' => 'Средняя — 220 px',
                'large' => 'Крупная — 360 px', 'custom' => 'Свой размер',
            ], (string) $data['art_size']) ?>
            <div class="form-field">
                <label for="art_width">Своя ширина логотипа, px</label>
                <input type="number" id="art_width" name="art_width" min="40" max="1200" step="1" value="<?= (int) $data['art_width'] ?>">
                <span class="form-hint">Используется только при размере «Свой размер». Высота рассчитывается автоматически.</span>
            </div>
PHP);

once($form, <<<'PHP'
            <?= AdminUi::imageField('cta_image', (string) $data['cta_image'], [
                'label' => 'Своя картинка вместо иконки',
                'hint' => 'SVG или PNG. Если задана, используется вместо иконки Tabler.',
            ]) ?>
            <?= $checkbox('cta_new_tab', 'Основную кнопку открывать в новой вкладке', (bool) $data['cta_new_tab']) ?>
PHP, <<<'PHP'
            <?= AdminUi::imageField('cta_image', (string) $data['cta_image'], [
                'label' => 'Своя картинка вместо иконки',
                'hint' => 'SVG или PNG. Если задана, используется вместо иконки Tabler.',
            ]) ?>
            <?= $select('cta_image_mode', 'Размер своей картинки', $ctaImageModes, (string) $data['cta_image_mode']) ?>
            <div class="form-field">
                <label for="cta_image_width">Своя ширина картинки, px</label>
                <input type="number" id="cta_image_width" name="cta_image_width" min="20" max="400" step="1" value="<?= (int) $data['cta_image_width'] ?>">
                <span class="form-hint">Работает в режиме «Своя ширина». «На всю высоту кнопки» использует высоту самой кнопки.</span>
            </div>
            <?= $checkbox('cta_new_tab', 'Основную кнопку открывать в новой вкладке', (bool) $data['cta_new_tab']) ?>
PHP);

once($form, <<<'PHP'
            <?= AdminUi::imageField('cta2_image', (string) $data['cta2_image'], [
                'label' => 'Своя картинка вместо иконки',
                'hint' => 'SVG или PNG. Если задана, используется вместо иконки Tabler.',
            ]) ?>
            <?= $checkbox('cta2_new_tab', 'Дополнительную кнопку открывать в новой вкладке', (bool) $data['cta2_new_tab']) ?>
PHP, <<<'PHP'
            <?= AdminUi::imageField('cta2_image', (string) $data['cta2_image'], [
                'label' => 'Своя картинка вместо иконки',
                'hint' => 'SVG или PNG. Если задана, используется вместо иконки Tabler.',
            ]) ?>
            <?= $select('cta2_image_mode', 'Размер своей картинки', $ctaImageModes, (string) $data['cta2_image_mode']) ?>
            <div class="form-field">
                <label for="cta2_image_width">Своя ширина картинки, px</label>
                <input type="number" id="cta2_image_width" name="cta2_image_width" min="20" max="400" step="1" value="<?= (int) $data['cta2_image_width'] ?>">
                <span class="form-hint">Работает в режиме «Своя ширина». «На всю высоту кнопки» использует высоту самой кнопки.</span>
            </div>
            <?= $checkbox('cta2_new_tab', 'Дополнительную кнопку открывать в новой вкладке', (bool) $data['cta2_new_tab']) ?>
PHP);

$source = (string) file_get_contents($form);
$source = str_replace('«Как у обложки»', '«Использовать общую настройку обложки»', $source);
$source = str_replace("'Как у обложки'", "'Общее значение'", $source);
$source = str_replace('placeholder="как у обложки"', 'placeholder="общая настройка"', $source);
$source = str_replace('placeholder="<?= $globalDuration ?> — из обложки"', 'placeholder="<?= $globalDuration ?> — общее значение"', $source);
file_put_contents($form, $source);

once($form, <<<'HTML'
                        Переводится только текст. Фон, расположение и оформление общие для всех языков.
                        Пустое поле использует текст основного языка <?= $esc(strtoupper($defaultCode)) ?>.
HTML, <<<'HTML'
                        Текст и ссылки можно задавать отдельно для каждого языка. Фон, расположение и оформление остаются общими.
                        Пустое поле использует значение основного языка <?= $esc(strtoupper($defaultCode)) ?>.
HTML);

once($form, <<<'PHP'
                        <div class="form-field">
                            <label for="<?= $id ?>cta">Текст основной кнопки</label>
                            <input type="text" id="<?= $id ?>cta" name="<?= $key ?>[cta_text]" value="<?= $esc($tr['cta_text'] ?? '') ?>">
                        </div>
PHP, <<<'PHP'
                        <div class="form-field">
                            <label for="<?= $id ?>cta">Текст основной кнопки</label>
                            <input type="text" id="<?= $id ?>cta" name="<?= $key ?>[cta_text]" value="<?= $esc($tr['cta_text'] ?? '') ?>">
                        </div>
                        <div class="form-field">
                            <label for="<?= $id ?>cta-url">Ссылка основной кнопки</label>
                            <input type="text" id="<?= $id ?>cta-url" name="<?= $key ?>[cta_url]" value="<?= $esc($tr['cta_url'] ?? '') ?>" placeholder="пусто — ссылка основного языка">
                        </div>
PHP);

once($form, <<<'PHP'
                        <div class="form-field">
                            <label for="<?= $id ?>cta2">Текст дополнительной кнопки</label>
                            <input type="text" id="<?= $id ?>cta2" name="<?= $key ?>[cta2_text]" value="<?= $esc($tr['cta2_text'] ?? '') ?>">
                        </div>
PHP, <<<'PHP'
                        <div class="form-field">
                            <label for="<?= $id ?>cta2">Текст дополнительной кнопки</label>
                            <input type="text" id="<?= $id ?>cta2" name="<?= $key ?>[cta2_text]" value="<?= $esc($tr['cta2_text'] ?? '') ?>">
                        </div>
                        <div class="form-field">
                            <label for="<?= $id ?>cta2-url">Ссылка дополнительной кнопки</label>
                            <input type="text" id="<?= $id ?>cta2-url" name="<?= $key ?>[cta2_url]" value="<?= $esc($tr['cta2_url'] ?? '') ?>" placeholder="пусто — ссылка основного языка">
                        </div>
                        <div class="form-field">
                            <label for="<?= $id ?>slide-url">Ссылка со всего слайда</label>
                            <input type="text" id="<?= $id ?>slide-url" name="<?= $key ?>[link_url]" value="<?= $esc($tr['link_url'] ?? '') ?>" placeholder="пусто — ссылка основного языка">
                        </div>
PHP);

once($slideModel, <<<'PHP'
            'cta_text' => 'cta_text',
            'cta2_text' => 'cta2_text',
            'art_alt' => 'art_alt',
PHP, <<<'PHP'
            'cta_text' => 'cta_text',
            'cta_url' => 'cta_url',
            'cta2_text' => 'cta2_text',
            'cta2_url' => 'cta2_url',
            'link_url' => 'link_url',
            'art_alt' => 'art_alt',
PHP);

file_put_contents($root . '/tests/cases/104_hero_media_size_translation_links_test.php', <<<'PHP'
<?php

declare(strict_types=1);

use App\Core\Hero\HeroSlideData;

test('Hero: custom artwork and CTA image sizes are normalized', function (): void {
    $data = HeroSlideData::normalize([
        'art_size' => 'custom', 'art_width' => '777',
        'cta_image_mode' => 'fill', 'cta_image_width' => '333',
        'cta2_image_mode' => 'custom', 'cta2_image_width' => '222',
    ]);
    assert_same('custom', $data['art_size']);
    assert_same(777, $data['art_width']);
    assert_same('fill', $data['cta_image_mode']);
    assert_same(333, $data['cta_image_width']);
    assert_same('custom', $data['cta2_image_mode']);
    assert_same(222, $data['cta2_image_width']);
});

test('Hero editor: one sizing system and translated action URLs', function (): void {
    $form = (string) file_get_contents(APP_ROOT . '/app/Views/admin/heroes/slide_form.php');
    $renderer = (string) file_get_contents(APP_ROOT . '/app/Core/Hero/HeroRenderer.php');
    $layoutCss = (string) file_get_contents(APP_ROOT . '/public/assets/css/blocks/hero-art-layout.css');
    $baseCss = (string) file_get_contents(APP_ROOT . '/public/assets/css/blocks/hero.css');
    $translations = (string) file_get_contents(APP_ROOT . '/app/Models/HeroSlideTranslation.php');
    $slide = (string) file_get_contents(APP_ROOT . '/app/Models/HeroSlide.php');

    assert_contains("'custom' => 'Свой размер'", $form, 'logo custom size');
    assert_contains('name="art_width"', $form, 'logo width field');
    assert_contains("'fill' => 'На всю высоту кнопки'", $form, 'CTA fill mode');
    assert_contains('name="cta_image_width"', $form, 'CTA custom width');
    assert_contains('name="cta2_image_width"', $form, 'CTA2 custom width');
    assert_not_contains('Как у обложки', $form, 'ambiguous legacy wording removed');
    assert_contains('Использовать общую настройку обложки', $form, 'inheritance wording is explicit');

    assert_contains('hero__cta--image-', $renderer, 'renderer emits CTA image mode');
    assert_contains("'custom' => (int) $d['art_width']", $renderer, 'renderer uses custom logo width');
    assert_not_contains('width: 20px; height: 20px;', $baseCss, 'base CSS no longer forces every CTA image to 20px');
    assert_contains('.hero__art--large img', $layoutCss, 'single artwork sizing layer');
    assert_contains('width: 360px;', $layoutCss, 'large artwork has stable width');

    foreach (['cta_url', 'cta2_url', 'link_url'] as $field) {
        assert_contains("[{$field}]", $form, "translation UI includes {$field}");
        assert_contains("'{$field}'", $translations, "translation model stores {$field}");
        assert_contains("'{$field}' => '{$field}'", $slide, "display applies {$field}");
    }
});
PHP);

@unlink($root . '/scripts/agent_patch_hero_custom.php');
@unlink(__FILE__);
@unlink($root . '/.github/workflows/agent-patch-hero-custom.yml');
