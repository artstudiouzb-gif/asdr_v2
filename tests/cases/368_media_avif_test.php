<?php

declare(strict_types=1);

use App\Core\Media;

/*
 * AVIF — первым источником <picture> там, где загрузчик его сделал; WebP —
 * следом для браузеров без AVIF. Preload и CSS-фон берут тот же формат, что
 * выберет <picture>, иначе браузер скачал бы картинку дважды.
 */
function media_avif_fixture(bool $withAvif): string
{
    $name = 'media-avif-' . bin2hex(random_bytes(4));
    $dir = APP_ROOT . '/public/uploads/public';
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
    file_put_contents($dir . '/' . $name . '.jpg', $png);
    foreach (['-800', '-1600', ''] as $suffix) {
        file_put_contents($dir . '/' . $name . $suffix . '.webp', 'w');
        if ($withAvif) {
            file_put_contents($dir . '/' . $name . $suffix . '.avif', 'a');
        }
    }

    return $name;
}

function media_avif_cleanup(string $name): void
{
    $dir = APP_ROOT . '/public/uploads/public';
    @unlink($dir . '/' . $name . '.jpg');
    foreach (Media::variantSuffixes() as $suffix) {
        @unlink($dir . '/' . $name . $suffix);
    }
}

test('AVIF идёт первым источником, WebP — запасным', function (): void {
    $name = media_avif_fixture(true);
    try {
        $html = Media::picture('/uploads/public/' . $name . '.jpg', 'Тест');
        $avif = strpos($html, 'type="image/avif"');
        $webp = strpos($html, 'type="image/webp"');
        assert_true($avif !== false && $webp !== false && $avif < $webp, 'порядок источников: AVIF, затем WebP');
        assert_contains($name . '-800.avif 800w', $html);

        $preload = Media::preloadLink('/uploads/public/' . $name . '.jpg');
        assert_contains('type="image/avif"', $preload, 'preload совпадает с форматом, который выберет <picture>');
        assert_not_contains('.webp', $preload);

        $css = Media::cssImageSet('/uploads/public/' . $name . '.jpg');
        assert_contains($name . '-1600.avif") type("image/avif")', $css, 'фону хватает варианта 1600px');
        assert_contains($name . '-1600.webp") type("image/webp")', $css);
        assert_true(strpos($css, 'image/avif') < strpos($css, 'image/webp'));
    } finally {
        media_avif_cleanup($name);
    }
});

test('Без AVIF-вариантов вывод прежний: только WebP', function (): void {
    $name = media_avif_fixture(false);
    try {
        $html = Media::picture('/uploads/public/' . $name . '.jpg', 'Тест');
        assert_not_contains('image/avif', $html);
        assert_contains('type="image/webp"', $html);
        assert_contains('type="image/webp"', Media::preloadLink('/uploads/public/' . $name . '.jpg'));
    } finally {
        media_avif_cleanup($name);
    }
});

test('Удаление файла забирает и AVIF-варианты', function (): void {
    $suffixes = Media::variantSuffixes();
    foreach (['.avif', '-400.avif', '-800.avif', '-1600.avif', '.webp', '-1600.webp'] as $suffix) {
        assert_true(in_array($suffix, $suffixes, true), 'нет суффикса ' . $suffix);
    }
});
