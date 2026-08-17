<?php

declare(strict_types=1);

function once(string $path, string $old, string $new): void
{
    $source = (string) file_get_contents($path);
    if (substr_count($source, $old) !== 1) {
        fwrite(STDERR, "Patch target not unique in {$path}\n");
        exit(2);
    }
    file_put_contents($path, str_replace($old, $new, $source));
}

$root = dirname(__DIR__);
$heroCss = $root . '/public/assets/css/blocks/hero.css';
$layoutCss = $root . '/public/assets/css/blocks/hero-art-layout.css';
$form = $root . '/app/Views/admin/heroes/slide_form.php';
$test = $root . '/tests/cases/104_hero_media_size_translation_links_test.php';

// Remove the old max-height preset system. Width now has exactly one source:
// the validated width attribute emitted by HeroRenderer; CSS only keeps it
// responsive and preserves aspect ratio.
once($heroCss, <<<'CSS'
.hero__art { display: block; margin-top: var(--hero-gap-art); }
.hero__art img { max-width: 100%; height: auto; }
.hero__art--small img { max-height: 64px; }
.hero__art--medium img { max-height: 120px; }
.hero__art--large img { max-height: 200px; }
CSS, <<<'CSS'
.hero__art { display: block; margin-top: var(--hero-gap-art); }
.hero__art img { max-width: 100%; height: auto; object-fit: contain; }
CSS);

// Side-layout CSS must not duplicate artwork sizing. It owns only side layout
// and the two non-default CTA image modes.
once($layoutCss, <<<'CSS'
/*
 * Foreground image/logo layout for Hero slides.
 *
 * This is the single sizing/layout layer for foreground Hero artwork. The
 * legacy max-height caps in hero.css remain harmless compatibility limits for
 * the three presets; explicit widths below prevent SVG/PNG from collapsing in
 * an auto grid track. Custom mode deliberately removes that height cap.
 */
CSS, <<<'CSS'
/*
 * Side layout for the foreground Hero image/logo plus non-default CTA image
 * modes. Artwork width itself has one source: HeroRenderer's validated width
 * attribute; this file never duplicates those preset/custom numbers.
 */
CSS);

once($layoutCss, <<<'CSS'
/* Presets have an explicit width: max-height alone let wide SVG logos shrink
   unexpectedly when the side column was sized as `auto`. */
.hero__art--small img {
    width: 120px;
    max-width: 100%;
}

.hero__art--medium img {
    width: 220px;
    max-width: 100%;
}

.hero__art--large img {
    width: 360px;
    max-width: 100%;
}

.hero__art--custom img {
    max-width: 100%;
    max-height: none;
    height: auto;
}

CSS, '');

once($layoutCss, <<<'CSS'
/* A custom CTA image has three explicit modes. The default icon mode keeps
   hero.css's 20×20 contract. Fill mode uses the button's whole inner height;
   custom mode uses the safe width emitted by HeroRenderer. */
CSS, <<<'CSS'
/* Default icon mode is sized by HeroRenderer at 20×20. Fill/custom are the
   only CSS overrides, so there is no second generic sizing rule to conflict. */
CSS);

once($layoutCss, <<<'CSS'
.hero__cta--image-fill .hero__cta-icon {
    align-self: stretch;
    align-items: stretch;
}

.hero__cta--image-fill .hero__cta-icon img {
    width: auto;
    height: 100%;
    max-width: 180px;
    object-fit: contain;
}

.hero__cta--image-custom .hero__cta-icon img {
    height: auto;
    max-height: 48px;
    object-fit: contain;
}
CSS, <<<'CSS'
.hero__cta--image-fill .hero__cta-icon {
    align-self: center;
    align-items: center;
}

.hero__cta--image-fill .hero__cta-icon img {
    width: auto;
    height: 44px;
    max-width: 180px;
    object-fit: contain;
}

.hero__cta--image-custom .hero__cta-icon img {
    height: auto;
    max-height: none;
    object-fit: contain;
}
CSS);

// Clear the two remaining stale UI phrases after translated links were added.
once($form, <<<'PHP'
$durationState = $customDuration > 0
    ? $customDuration . ' с · своё значение'
    : $globalDuration . ' с · из обложки';
PHP, <<<'PHP'
$durationState = $customDuration > 0
    ? $customDuration . ' с · своё значение'
    : $globalDuration . ' с · общее значение';
PHP);

once($form, <<<'PHP'
                'Переводы',
                'только текстовые поля',
PHP, <<<'PHP'
                'Переводы',
                'текст и ссылки',
PHP);

// Regression contract reflects the cleaned architecture: renderer owns width;
// legacy max-height preset rules are gone; CTA mode CSS only changes fill/custom.
once($test, <<<'PHP'
    assert_not_contains('width: 20px; height: 20px;', $baseCss, 'base CSS no longer forces every CTA image to 20px');
    assert_contains('.hero__art--large img', $layoutCss, 'single artwork sizing layer');
    assert_contains('width: 360px;', $layoutCss, 'large artwork has stable width');
PHP, <<<'PHP'
    assert_not_contains('.hero__art--large img { max-height:', $baseCss, 'legacy max-height artwork sizing is removed');
    assert_not_contains('.hero__art--large img', $layoutCss, 'side-layout CSS does not duplicate artwork width presets');
    assert_contains("'large' => 360", $renderer, 'large artwork width has one renderer source');
    assert_contains('.hero__cta--image-fill .hero__cta-icon img', $layoutCss, 'fill mode has a dedicated override');
    assert_contains('height: 44px;', $layoutCss, 'fill mode occupies the button inner height');
PHP);

@unlink(__FILE__);
@unlink($root . '/.github/workflows/agent-cleanup-hero-media.yml');
