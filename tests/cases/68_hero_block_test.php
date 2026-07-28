<?php

declare(strict_types=1);

use App\Core\BlockRenderer;
use App\Core\BlockData\HeroBlockNormalizer;

// Hero: фон видео/YouTube/фото, overlay с цветом и прозрачностью, позиция
// текста и подложка под текстом.

function render_hero(array $data): string
{
    return BlockRenderer::render(['id' => 1, 'type' => 'hero', 'data' => json_encode($data), 'custom_css' => null])['html'];
}

test('Hero: YouTube-фон рендерит iframe с nocookie-доменом и id', function () {
    $html = render_hero([
        'title' => 'Заголовок',
        'bg_type' => 'youtube',
        'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    ]);
    assert_true(str_contains($html, 'youtube-nocookie.com/embed/dQw4w9WgXcQ'), 'iframe YouTube с id');
    assert_true(str_contains($html, 'block-hero--video'), 'класс видео-героя');
    assert_true(str_contains($html, 'autoplay=1&mute=1&loop=1'), 'автозапуск без звука, цикл');
    assert_contains('playlist=dQw4w9WgXcQ', $html, 'playlist нужен YouTube для бесшовного loop');
    assert_contains('controls=0', $html, 'стандартные элементы управления отключены');
    assert_contains('enablejsapi=1', $html, 'фон можно возобновить после системной паузы');
    assert_contains('data-hero-youtube-background', $html);
    assert_true(str_contains($html, 'loading="eager"'), 'фон первого экрана загружается сразу');
    assert_contains('referrerpolicy="strict-origin-when-cross-origin"', $html, 'YouTube получает origin сайта для проверки embed');
    assert_true(!str_contains($html, 'loading="lazy"'), 'YouTube hero не откладывается lazy-loading');
});

test('Hero: сохранённая ссылка YouTube включает фон даже при старом bg_type none', function () {
    $html = render_hero([
        'title' => 'Заголовок',
        'bg_type' => 'none',
        'youtube_url' => 'https://www.youtube.com/watch?v=s_lKTkRGKc8',
    ]);

    assert_contains('youtube-nocookie.com/embed/s_lKTkRGKc8', $html);
    assert_contains('block-hero--video', $html);
    assert_not_contains('block-hero--plain', $html);
});

test('Hero: сохранённый MP4 включает фон даже при старом bg_type none', function () {
    $html = render_hero([
        'title' => 'Заголовок',
        'bg_type' => 'none',
        'video_url' => '/uploads/public/hero.mp4',
    ]);

    assert_contains('<video class="block-hero__video" data-hero-background-video autoplay muted loop playsinline webkit-playsinline preload="metadata"', $html);
    assert_contains('<source src="/uploads/public/hero.mp4" type="video/mp4">', $html);
    assert_not_contains(' controls ', $html);
    assert_contains('block-hero--video', $html);
    assert_not_contains('block-hero--plain', $html);

    $js = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/js/frontend.js');
    assert_contains("video.controls = false", $js);
    assert_contains("video.muted = true", $js);
    assert_contains("video.loop = true", $js);
    assert_contains("document.addEventListener('visibilitychange'", $js);
    assert_contains("command('playVideo')", $js);
    assert_contains("command('mute')", $js);
    assert_contains("command('setLoop', [true])", $js);
});

test('Hero: overlay использует начальный и конечный цвета, направление и прозрачность', function () {
    $html = render_hero([
        'title' => 'X', 'bg_type' => 'image', 'image' => '/uploads/public/x.jpg',
        'overlay_color' => '#123456', 'overlay_end_color' => '#abcdef',
        'overlay_direction' => 'to_bottom_right', 'overlay_opacity' => 80,
    ]);
    // #123456 = rgb(18,52,86), #abcdef = rgb(171,205,239), 80% => 0.8
    assert_true(str_contains($html, '--hero-scrim-rgb: 18,52,86'), 'overlay RGB из цвета');
    assert_true(str_contains($html, '--hero-scrim-end-rgb: 171,205,239'), 'конечный RGB overlay');
    assert_true(str_contains($html, '--hero-scrim-a: 0.8'), 'overlay alpha из прозрачности');
    assert_true(str_contains($html, '--hero-scrim-direction: 135deg'), 'направление градиента');
});

test('Hero: overlay поддерживает сплошную заливку без градиента', function () {
    $html = render_hero([
        'title' => 'X', 'bg_type' => 'image', 'image' => '/uploads/public/x.jpg',
        'overlay_direction' => 'solid', 'overlay_color' => '#123456',
    ]);

    assert_contains('block-hero__scrim--solid', $html);

    $css = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/gov-theme.css');
    assert_contains('.block-hero__scrim--solid { background: rgba(var(--hero-scrim-rgb), var(--hero-scrim-a)); }', $css);
});

test('Hero: автоматическое направление overlay следует за положением текста', function () {
    $right = render_hero([
        'title' => 'X', 'bg_type' => 'image', 'image' => '/uploads/public/x.jpg',
        'overlay_direction' => 'auto', 'text_position' => 'right',
    ]);
    assert_contains('--hero-scrim-direction: 270deg', $right);

    $invalid = render_hero([
        'title' => 'X', 'bg_type' => 'image', 'image' => '/uploads/public/x.jpg',
        'overlay_direction' => '90deg;background:red', 'text_position' => 'center',
    ]);
    assert_contains('--hero-scrim-direction: 0deg', $invalid);
    assert_not_contains('background:red', $invalid);
});

test('Hero: форма и сохранение содержат настройки градиента overlay', function () {
    $root = dirname(__DIR__, 2);
    $form = (string) file_get_contents($root . '/app/Views/admin/pages/block_form.php');
    assert_contains('name="overlay_direction"', $form);
    assert_contains('value="solid"', $form);
    assert_contains('name="overlay_end_color"', $form);

    $normalizer = (string) file_get_contents($root . '/app/Core/BlockData/HeroBlockNormalizer.php');
    assert_contains("'overlay_direction' => \$overlayDirection", $normalizer);
    assert_contains("'overlay_end_color' => self::hexOrDefault", $normalizer);

    assert_same('auto', BlockRenderer::DEFAULTS['hero']['overlay_direction']);
    assert_same('#0b1a30', BlockRenderer::DEFAULTS['hero']['overlay_end_color']);
});

test('Hero: контроллер передаёт данные формы отдельному нормализатору', function () {
    $controller = (string) file_get_contents(dirname(__DIR__, 2) . '/app/Controllers/Admin/BlockController.php');

    assert_contains('HeroBlockNormalizer::normalize($_POST, $locale)', $controller);
    assert_not_contains("case 'hero':\n                \$safe =", $controller);
});

test('Hero: позиция текста и подложка отражаются в разметке', function () {
    $html = render_hero([
        'title' => 'X', 'bg_type' => 'image', 'image' => '/uploads/public/x.jpg',
        'text_position' => 'center',
        'panel_enabled' => true, 'panel_color' => '#000000', 'panel_opacity' => 50,
    ]);
    assert_true(str_contains($html, 'block-hero--pos-center'), 'класс позиции текста');
    assert_true(str_contains($html, 'block-hero__text--panel'), 'класс подложки');
    assert_true(str_contains($html, 'rgba(0,0,0, 0.5)'), 'подложка rgba из цвета и прозрачности');
});

test('Hero: цвет текста и кнопок отдаются CSS-переменными', function () {
    $html = render_hero([
        'title' => 'X', 'bg_type' => 'image', 'image' => '/x.jpg',
        'text_color' => '#112233', 'button_color' => '#aabbcc',
        'button_text' => 'Кнопка', 'button_url' => '/o-nas',
    ]);
    assert_true(str_contains($html, '--hero-text:#112233'), 'переменная цвета текста');
    assert_true(str_contains($html, '--hero-btn:#aabbcc'), 'переменная цвета кнопок');
});

test('Hero: свой цвет фона под текстом — не зависящий от темы градиент', function () {
    $html = render_hero(['title' => 'X', 'bg_type' => 'none', 'bg_color' => '#123a6b', 'text_position' => 'left']);
    assert_true(str_contains($html, 'block-hero--bgcolor'), 'класс цветного фона');
    assert_true(str_contains($html, 'linear-gradient(90deg, rgba(18,58,107'), 'градиент выбранного цвета');
});

test('Hero: без bg_type определяет тип по заполненным полям (обратная совместимость)', function () {
    $html = render_hero(['title' => 'X', 'image' => '/uploads/public/x.jpg']);
    assert_true(str_contains($html, 'block-hero--media'), 'старый блок с картинкой = медиа-герой');
    assert_true(!str_contains($html, 'block-hero--video'), 'без видео нет video-класса');
});

test('Hero: небезопасная произвольная высота не попадает в style', function () {
    $html = render_hero(['title' => 'X', 'height' => 'custom', 'custom_height' => '100vh;background:red']);
    assert_true(str_contains($html, 'block-hero--h-custom'), 'режим сохраняется');
    assert_true(!str_contains($html, 'background:red'), 'CSS-инъекция отброшена');
});

test('Hero: своя ширина текста отдаётся переменной, мусор отбрасывается', function () {
    $html = render_hero(['title' => 'X', 'image' => '/uploads/public/x.jpg', 'text_width' => '50vw']);
    assert_true(str_contains($html, '--hero-text-width:50vw'), 'переменная ширины текста');

    $html = render_hero(['title' => 'X', 'text_width' => '5000px']);
    assert_true(str_contains($html, '--hero-text-width:2000px'), 'px ограничивается лимитом');

    $html = render_hero(['title' => 'X', 'text_width' => '50vw;background:red']);
    assert_true(!str_contains($html, 'background:red'), 'CSS-инъекция отброшена');
    assert_true(!str_contains($html, '--hero-text-width'), 'невалидное значение не выводится');
});
