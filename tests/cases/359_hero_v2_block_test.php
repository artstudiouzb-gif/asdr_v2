<?php

declare(strict_types=1);

use App\Core\AssetCollector;
use App\Core\BlockData\BlockFieldSchema;
use App\Core\BlockTypeRegistry;
use App\Core\Hero\HeroV2;

/*
 * Блок «Обложка страницы» (hero_v2).
 *
 * Обложек в проекте уже две: старая внутри блока и «Обложка как тип
 * контента». Третья реализация одного и того же — тот же кадр, то же
 * наложение, та же карусель, собранные заново — разошлась бы с ними при
 * первой правке; ровно поэтому в проекте нет отдельных блоков «Аккордеон» и
 * «Цитата». Поэтому hero_v2 не рисует ничего сам: он приводит свои настройки
 * к контракту HeroSettings/HeroSlideData и отдаёт их тому же HeroRenderer,
 * тому же blocks/hero.css и тому же blocks/hero.js.
 *
 * Отличается блок не выводом, а тем, что редактор может задать: 22 настройки
 * против 79. Тесты ниже стерегут обе половины этого решения — что вывод общий
 * и что каждая оставленная настройка действительно работает.
 */

/**
 * Готовый вывод блока: разметка и scoped CSS одной строкой.
 *
 * @param array<string, mixed> $settings
 * @param list<array<string, mixed>> $slides
 */
function hero_v2_output(array $settings = [], array $slides = []): string
{
    if ($slides === []) {
        $slides = [[
            'title' => 'Заголовок', 'eyebrow' => 'Над', 'subtitle' => 'Описание',
            'image' => '/uploads/public/a.jpg',
            'cta_text' => 'Подробнее', 'cta_url' => '/about',
        ], [
            'title' => 'Второй', 'image' => '/uploads/public/b.jpg',
        ]];
    }
    $data = array_merge(
        BlockTypeRegistry::defaults()['hero_v2'],
        $settings,
        ['slides' => $slides, '_heading_tag' => 'h1']
    );

    $blockId = 12;
    $templateCss = '';
    ob_start();
    include APP_ROOT . '/templates/blocks/hero_v2.php';
    $html = (string) ob_get_clean();

    return $html . "\n===CSS===\n" . $templateCss;
}

test('hero_v2 не заводит собственных стилей и скрипта', function () {
    // Ключ совпадает с типом блока, поэтому подключение происходит само
    // (BlockRenderer::renderPage прогоняет типы страницы через requireJs).
    $collector = new ReflectionClass(AssetCollector::class);
    $js = $collector->getConstant('JS_MAP');
    $themeParts = $collector->getConstant('THEME_PART_MAP');

    assert_same(
        '/assets/js/blocks/hero.js',
        $js['hero_v2'] ?? '',
        'у обложки-блока обязан быть тот же скрипт, что у обложки-типа контента'
    );
    assert_same(
        '/assets/css/blocks/hero.css',
        $themeParts['hero_v2'] ?? '',
        'и те же стили: второй почти такой же набор правил разойдётся с первым'
    );

    assert_false(
        is_file(APP_ROOT . '/public/assets/css/blocks/hero-v2.css'),
        'своего CSS у hero_v2 быть не должно — это была бы третья обложка'
    );
    assert_false(
        is_file(APP_ROOT . '/public/assets/js/blocks/hero-v2.js'),
        'своего скрипта у hero_v2 быть не должно — поведение карусели уже написано'
    );
});

test('hero_v2 объявлен схемой полей и виден редактору', function () {
    assert_true(BlockTypeRegistry::has('hero_v2'), 'тип обязан быть в реестре');
    assert_same(
        [],
        BlockTypeRegistry::BASE_DEFAULTS['hero_v2'],
        'у типа на схеме в реестре пустой массив — ключ остаётся ради порядка в редакторе'
    );

    $schema = BlockFieldSchema::all()['hero_v2'] ?? [];
    assert_true($schema !== [], 'настройки блока объявляются один раз — в схеме полей');

    $markup = block_editor_markup();
    foreach (array_keys($schema) as $key) {
        assert_contains(
            'name="' . $key . '"',
            $markup,
            'настройка «' . $key . '» недоступна редактору: объявлена и не нарисована'
        );
    }
});

test('Форма слайда и нормализатор знают одни и те же поля', function () {
    // Слайды идут мимо схемы (репитер), поэтому единственного объявления у их
    // полей нет — есть два списка: форма и HeroV2::normalizeSlides. Разъедутся
    // они молча: форма отдаст значение, которое нормализатор выбросит, или
    // сохранённое поле перестанет показываться редактору.
    $form = (string) file_get_contents(APP_ROOT . '/app/Views/admin/pages/block_form.php');
    preg_match_all("/\\\$name\\('([a-z0-9_]+)'\\)/", $form, $drawn);

    $stored = HeroV2::normalizeSlides(['slides' => [['title' => 'Т']]])[0];

    $drawnKeys = array_values(array_unique($drawn[1]));
    $storedKeys = array_keys($stored);
    sort($drawnKeys);
    sort($storedKeys);

    assert_same($storedKeys, $drawnKeys, 'форма слайда обложки разошлась с нормализатором');
});

test('Каждая настройка hero_v2 что-то меняет на выводе', function () {
    $base = hero_v2_output();

    $probe = [
        'height' => 'full',
        'text_position' => 'right',
        'bg' => 'light',
        'text_scheme' => 'dark',
        'accent' => '#ffe066',
        'overlay' => 'solid',
        'overlay_opacity' => 90,
        'autoplay' => 9,
    ];
    foreach ($probe as $key => $value) {
        assert_true(
            hero_v2_output([$key => $value]) !== $base,
            'настройка «' . $key . '» ничего не меняет на выводе — значит её нет'
        );
    }

    // Зависимые проверяются там, где действуют: цвет фона — только у схемы
    // «свой», цвет наложения — только при включённом наложении.
    assert_true(
        hero_v2_output(['bg' => 'custom', 'bg_color' => '#123456']) !== hero_v2_output(['bg' => 'custom']),
        'свой цвет фона обязан доезжать до вывода'
    );
    assert_true(
        hero_v2_output(['overlay' => 'solid', 'overlay_color' => '#00ff00'])
            !== hero_v2_output(['overlay' => 'solid']),
        'цвет наложения обязан доезжать до вывода'
    );
});

test('hero_v2 отдаёт разметку, которую ждут скрипт и прозрачная шапка', function () {
    $out = hero_v2_output();

    assert_contains('data-hero', $out, 'корень карусели ищет blocks/hero.js по этому атрибуту');
    assert_contains('data-hero-slide', $out, 'слайды скрипт находит по этому атрибуту');
    // Прозрачная шапка переключает светлый набор по признаку слайда: без него
    // на светлом кадре белое лого окажется на белом.
    assert_contains('data-hero-scheme=', $out, 'слайд обязан сообщать шапке светлоту кадра');
    // Заголовок первого слайда — h1 страницы: hero_v2 обязан быть в H1_BLOCKS,
    // иначе _heading_tag ему не придёт и h1 на странице не будет вовсе.
    assert_contains('<h1 class="hero__title"', $out, 'заголовок первого слайда — h1 страницы');
    assert_contains('<h2 class="hero__title"', $out, 'у остальных слайдов заголовок ниже уровнем');

    $renderer = new ReflectionClass(\App\Core\BlockRenderer::class);
    $h1 = $renderer->getConstant('H1_BLOCKS');
    assert_true(in_array('hero_v2', (array) $h1, true), 'hero_v2 обязан быть в списке блоков-претендентов на h1');
});

test('Разметка заголовка обложки работает, HTML в него не проходит', function () {
    $out = hero_v2_output([], [['title' => 'Первый *кадр*|строка', 'image' => '/uploads/public/a.jpg']]);

    assert_contains('<span class="tx-mark">кадр</span>', $out, 'выделение *слово* обязано работать');
    assert_contains('<br>', $out, 'черта обязана переносить строку');

    $evil = hero_v2_output([], [['title' => '<script>alert(1)</script>', 'image' => '/uploads/public/a.jpg']]);
    assert_not_contains('<script>alert(1)</script>', $evil, 'HTML в заголовке обязан экранироваться');
});

test('Слайд: источник видео опознаётся по ссылке, пустой слайд не сохраняется', function () {
    $slides = HeroV2::normalizeSlides(['slides' => [
        ['title' => 'YouTube', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ'],
        ['title' => 'Файл', 'video_url' => '/uploads/public/hero.mp4'],
        ['title' => 'Чужая схема', 'video_url' => 'javascript:alert(1)'],
        ['title' => '', 'subtitle' => '', 'image' => ''],
    ]]);

    assert_same(3, count($slides), 'пустой слайд занял бы кадр карусели и показал пустой экран');
    assert_same('https://youtu.be/dQw4w9WgXcQ', $slides[0]['video_url'], 'ссылку на YouTube принимаем как есть');
    assert_same('/uploads/public/hero.mp4', $slides[1]['video_url'], 'файл в медиатеке принимаем как есть');
    assert_same('', $slides[2]['video_url'], 'ссылка с чужой схемой до атрибута доходить не должна');

    $out = hero_v2_output([], [['title' => 'Ролик', 'video_url' => 'https://youtu.be/dQw4w9WgXcQ', 'image' => '/uploads/public/a.jpg']]);
    assert_contains('data-hero-youtube', $out, 'ролик YouTube обязан опознаваться по самой ссылке');
    // Кадр-замена лежит под видео всегда: «не загрузилось», «запрещено
    // автовоспроизведение» и «выключено на телефоне» — один сценарий.
    assert_contains('/uploads/public/a.jpg', $out, 'постером служит кадр слайда');
});

test('Слайдов не больше предела, небезопасная ссылка кнопки отбрасывается', function () {
    $many = [];
    for ($i = 0; $i < HeroV2::MAX_SLIDES + 4; $i++) {
        $many[] = ['title' => 'Слайд ' . $i];
    }
    assert_same(
        HeroV2::MAX_SLIDES,
        count(HeroV2::normalizeSlides(['slides' => $many])),
        'до последнего кадра длинной карусели посетитель не досматривает'
    );

    $slides = HeroV2::normalizeSlides(['slides' => [
        ['title' => 'Кнопка', 'cta_text' => 'Жать', 'cta_url' => 'javascript:alert(1)'],
    ]]);
    assert_same('', $slides[0]['cta_url'], 'адрес кнопки с чужой схемой сохраняться не должен');

    // Кнопку включает заполненность, а не отдельная галочка.
    $out = hero_v2_output([], [['title' => 'Т', 'image' => '/uploads/public/a.jpg', 'cta_text' => 'Жать', 'cta_url' => '']]);
    assert_not_contains('hero__cta', $out, 'кнопка без адреса не выводится');
});

test('Настроек у hero_v2 меньше, чем у обложки-типа контента', function () {
    $blockSettings = count(BlockFieldSchema::all()['hero_v2'] ?? []);
    $heroSettings = count(\App\Core\Hero\HeroSettings::defaults());

    assert_true(
        $blockSettings < $heroSettings,
        'блок затевался ради меньшего набора настроек: ' . $blockSettings . ' против ' . $heroSettings
    );
});
