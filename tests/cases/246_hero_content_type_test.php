<?php

declare(strict_types=1);

use App\Core\BlockData\HeroBlockNormalizer;
use App\Core\BlockTypeRegistry;
use App\Core\Hero\HeroPresets;
use App\Core\Hero\HeroRenderer;
use App\Core\Hero\HeroSettings;
use App\Core\Hero\HeroSlideData;

/**
 * Обложка как отдельный тип контента.
 *
 * Проверяем то, что нельзя увидеть на скриншоте: контракт настроек, порядок
 * слоёв в разметке, наличие кадра-замены у каждого видео и то, что цветовая
 * схема обложки не красит вложенные компоненты.
 */

/** Слайд для рендера: строка hero_slides с уже разобранным data. */
function hero_test_slide(array $data, int $id = 1): array
{
    return [
        'id' => $id,
        'hero_id' => 1,
        'title' => (string) ($data['title'] ?? ''),
        'sort_order' => 0,
        'is_active' => 1,
        'data' => HeroSlideData::withDefaults($data),
    ];
}

test('Настройки обложки: неизвестные значения заменяются умолчаниями', function () {
    $settings = HeroSettings::normalize([
        'width' => 'wide-please',
        'height' => 'custom',
        'height_value' => '5000',
        'height_unit' => 'px',
        'scheme' => 'rainbow',
        'content_scheme' => 'auto',
        'overlay' => 'gradient',
        'overlay_opacity' => '150',
        'nav_indicator' => 'carousel-dots-3d',
        'transition' => 'explode',
        'autoplay' => '1',
        'autoplay_interval' => '99',
    ]);

    assert_same('full', $settings['width'], 'чужая ширина заменяется умолчанием');
    assert_same('2400px', $settings['height_value'], 'высота ограничена сверху');
    assert_same('navy', $settings['scheme']);
    assert_same(100, $settings['overlay_opacity'], 'плотность затемнения не больше 100');
    assert_same('counter_progress', $settings['nav_indicator']);
    assert_same('fade_slide', $settings['transition']);
    assert_true($settings['autoplay'], 'галочка автопрокрутки читается');
    assert_same(30, $settings['autoplay_interval'], 'интервал ограничен сверху');
});

test('Настройки обложки: чтение старой записи дополняется умолчаниями', function () {
    // Запись, сделанная до появления половины ключей, плюс мусор, которого в
    // контракте нет: шаблон не должен получить ни null, ни лишнего.
    $settings = HeroSettings::withDefaults(['scheme' => 'light', 'evil_key' => '<script>']);

    assert_same('light', $settings['scheme']);
    assert_same(6, $settings['autoplay_interval'], 'недостающий ключ приходит с умолчанием');
    assert_true(!array_key_exists('evil_key', $settings), 'ключей вне контракта в настройках нет');
    assert_same(
        array_keys(HeroSettings::defaults()),
        array_keys($settings),
        'набор ключей совпадает с контрактом'
    );
});

test('Слайд: ссылка на YouTube превращается в идентификатор ролика', function () {
    foreach ([
        'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'https://youtu.be/dQw4w9WgXcQ',
        'https://www.youtube.com/embed/dQw4w9WgXcQ',
    ] as $url) {
        $slide = HeroSlideData::normalize(['youtube_url' => $url]);
        assert_same('dQw4w9WgXcQ', $slide['youtube_id'], $url . ' — идентификатор выделен');
        assert_same('youtube', $slide['media_type'], 'заполненное медиа само выбирает тип фона');
    }

    $broken = HeroSlideData::normalize(['youtube_url' => 'https://example.com/not-a-video']);
    assert_same('', $broken['youtube_id'], 'из чужой ссылки идентификатор не берётся');
    assert_same('none', $broken['media_type']);
});

test('Слайд: небезопасные адреса не сохраняются', function () {
    $slide = HeroSlideData::normalize([
        'image' => 'javascript:alert(1)',
        'video_url' => 'https://evil.example/x.mp4',
        'link_url' => ' javascript:alert(2) ',
        'cta_url' => ' /about ',
    ]);

    assert_same('', $slide['image']);
    assert_same('', $slide['link_url']);
    assert_same('/about', $slide['cta_url'], 'обычная ссылка проходит');
});

test('Слайд: пустое оформление означает «как у обложки», а не «выключено»', function () {
    $slide = HeroSlideData::normalize(['title' => 'Заголовок']);

    assert_same('', $slide['overlay'], 'затемнение наследуется от обложки');
    assert_same(-1, $slide['overlay_opacity'], 'плотность наследуется, а 0 остаётся значимым');
    assert_same('', $slide['text_position']);
    assert_same('', $slide['scheme']);

    // Явное «нет» отличается от «как у обложки».
    $off = HeroSlideData::normalize(['title' => 'Т', 'overlay' => 'none', 'overlay_opacity' => '0']);
    assert_same('none', $off['overlay']);
    assert_same(0, $off['overlay_opacity']);
});

test('Слайд: кадр-замена берётся по приоритету, пустой слайд не считается слайдом', function () {
    $withPoster = HeroSlideData::withDefaults(['poster' => '/uploads/public/p.jpg', 'image' => '/uploads/public/i.jpg']);
    assert_same('/uploads/public/p.jpg', HeroSlideData::fallbackImage($withPoster), 'постер важнее картинки');

    $imageOnly = HeroSlideData::withDefaults(['image' => '/uploads/public/i.jpg']);
    assert_same('/uploads/public/i.jpg', HeroSlideData::fallbackImage($imageOnly));

    assert_true(HeroSlideData::isEmpty(HeroSlideData::defaults()), 'незаполненный слайд пуст');
    assert_true(
        !HeroSlideData::isEmpty(HeroSlideData::withDefaults(['title' => 'Есть заголовок'])),
        'слайд с одним заголовком уже не пуст'
    );
});

test('Пресеты: задают свои ключи и не трогают остальные настройки', function () {
    $current = HeroSettings::withDefaults([
        'autoplay_interval' => 9,
        'height_mobile' => 'compact',
        'scheme' => 'light',
    ]);
    $applied = HeroPresets::apply('navy', $current);

    assert_same('navy', $applied['scheme'], 'пресет ставит свою схему');
    assert_same(9, $applied['autoplay_interval'], 'ручная настройка вне пресета сохранена');
    assert_same('compact', $applied['height_mobile'], 'мобильная высота не сброшена');

    // Все шесть пресетов из задания на месте и дают валидные настройки.
    foreach (['editorial', 'government', 'navy', 'full_image', 'video', 'minimal'] as $key) {
        assert_true(HeroPresets::has($key), 'пресет ' . $key . ' объявлен');
        $result = HeroPresets::apply($key, HeroSettings::defaults());
        assert_same(
            array_keys(HeroSettings::defaults()),
            array_keys($result),
            'пресет ' . $key . ' не ломает контракт настроек'
        );
    }

    assert_same(
        HeroSettings::defaults(),
        HeroPresets::apply('unknown-preset', HeroSettings::defaults()),
        'неизвестный пресет ничего не меняет'
    );
});

test('Разметка обложки: порядок слоёв — фон, затемнение, контент, навигация', function () {
    $settings = HeroSettings::withDefaults(['overlay' => 'gradient', 'nav_indicator' => 'counter_progress']);
    $rendered = HeroRenderer::render(
        ['id' => 1, 'name' => 'Тест'],
        [
            hero_test_slide(['title' => 'Первый', 'media_type' => 'image', 'image' => '/uploads/public/a.jpg'], 1),
            hero_test_slide(['title' => 'Второй', 'media_type' => 'image', 'image' => '/uploads/public/b.jpg'], 2),
        ],
        $settings,
        7
    );
    $html = $rendered['html'];

    $order = [
        'hero__media' => strpos($html, 'hero__media'),
        'hero__overlay' => strpos($html, 'hero__overlay'),
        'hero__inner' => strpos($html, 'hero__inner'),
        'hero__nav' => strpos($html, 'hero__nav'),
    ];
    foreach ($order as $layer => $position) {
        assert_true($position !== false, 'слой ' . $layer . ' есть в разметке');
    }
    assert_true($order['hero__media'] < $order['hero__overlay'], 'затемнение после фона');
    assert_true($order['hero__overlay'] < $order['hero__inner'], 'контент после затемнения');
    assert_true($order['hero__inner'] < $order['hero__nav'], 'навигация после контента');

    // Инлайн-стилей в блоках быть не должно: оформление уходит в scoped CSS.
    assert_true(strpos($html, ' style="') === false, 'инлайн-стилей в разметке нет');
    assert_true(strpos($rendered['css'], '#block-7 .hero{') !== false, 'переменные обложки заданы scoped-стилями');
});

test('Разметка обложки: навигация, счётчик и доступность', function () {
    $rendered = HeroRenderer::render(
        ['id' => 1, 'name' => 'Тест'],
        [
            hero_test_slide(['title' => 'Раз'], 1),
            hero_test_slide(['title' => 'Два'], 2),
            hero_test_slide(['title' => 'Три'], 3),
        ],
        HeroSettings::withDefaults(['autoplay' => true, 'autoplay_interval' => 7]),
        3
    );
    $html = $rendered['html'];

    assert_true(strpos($html, 'data-hero-prev') !== false, 'стрелка «назад»');
    assert_true(strpos($html, 'data-hero-next') !== false, 'стрелка «вперёд»');
    assert_true(strpos($html, 'data-hero-progress') !== false, 'полоса прогресса');
    assert_true(strpos($html, '>03<') !== false, 'общее число слайдов показано');
    assert_true(strpos($html, 'data-hero-autoplay="7000"') !== false, 'интервал автопрокрутки в миллисекундах');
    assert_true(strpos($html, 'data-hero-toggle') !== false, 'кнопка остановки автопрокрутки (WCAG 2.2.2)');
    assert_true(strpos($html, 'data-hero-swipe') !== false, 'свайп включён');
    assert_true(strpos($html, 'aria-roledescription="Карусель"') !== false, 'роль карусели объявлена');
    assert_true(substr_count($html, 'aria-label="Предыдущий слайд"') === 1, 'у стрелки есть подпись для диктора');
    assert_true(strpos($html, 'aria-live="polite"') !== false, 'смена слайда объявляется диктору');
    assert_true(substr_count($html, 'aria-hidden="false"') === 1, 'открыт ровно один слайд');
    assert_true(substr_count($html, ' inert') === 2, 'скрытые слайды выключены из порядка табуляции');
});

test('Разметка обложки: один слайд не превращается в карусель', function () {
    $rendered = HeroRenderer::render(
        ['id' => 1, 'name' => 'Тест'],
        [hero_test_slide(['title' => 'Единственный'], 1)],
        HeroSettings::defaults(),
        4
    );

    assert_true(strpos($rendered['html'], 'hero__nav') === false, 'навигации у одиночного слайда нет');
    assert_true(strpos($rendered['html'], 'hero--carousel') === false, 'класс карусели не выставлен');
});

test('Видео и YouTube: кадр-замена всегда лежит под фоном', function () {
    $video = HeroRenderer::render(
        ['id' => 1, 'name' => 'Видео'],
        [hero_test_slide([
            'title' => 'С видео',
            'media_type' => 'video',
            'video_url' => '/uploads/public/hero.mp4',
            'poster' => '/uploads/public/poster.jpg',
        ], 1)],
        HeroSettings::defaults(),
        5
    );
    $html = $video['html'];

    assert_true(strpos($html, 'hero__fallback') !== false, 'кадр-замена в разметке есть всегда');
    assert_true(strpos($html, 'poster="/uploads/public/poster.jpg"') !== false, 'постер задан у самого видео');
    assert_true(strpos($html, 'data-hero-video') !== false, 'видео помечено для скрипта');
    assert_true(strpos($html, '<video') !== false && strpos($html, 'hidden>') !== false,
        'видео скрыто до решения скрипта — пока виден кадр-замена');
    assert_true(strpos($html, ' muted ') !== false, 'фоновое видео без звука');
    assert_true(strpos($html, ' loop ') !== false, 'фоновое видео по кругу');
    assert_true(strpos($html, 'playsinline') !== false, 'на телефоне не открывается во весь экран');
    assert_true(strpos($html, 'autoplay') === false, 'старт даёт скрипт, а не атрибут: иначе грузятся все ролики сразу');

    $youtube = HeroRenderer::render(
        ['id' => 1, 'name' => 'YouTube'],
        [hero_test_slide([
            'title' => 'С ютубом',
            'media_type' => 'youtube',
            'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'poster' => '/uploads/public/poster.jpg',
        ], 1)],
        HeroSettings::defaults(),
        6
    );

    assert_true(strpos($youtube['html'], '<iframe') === false,
        'iframe не отдаётся сервером: до рукопожатия с плеером посетитель видит кадр-замену');
    assert_true(strpos($youtube['html'], 'data-hero-yt-src') !== false, 'адрес плеера передан скрипту');
    assert_true(strpos($youtube['html'], 'youtube-nocookie.com') !== false, 'плеер без куки-трекинга');
    assert_true(strpos($youtube['html'], 'mute=1') !== false, 'YouTube стартует без звука');
    assert_true(strpos($youtube['html'], 'hero__fallback') !== false, 'кадр-замена есть и у YouTube');
});

test('Цветовая схема: обложка не красит вложенные компоненты', function () {
    $rendered = HeroRenderer::render(
        ['id' => 1, 'name' => 'Navy'],
        [hero_test_slide(['title' => 'Тёмная'], 1)],
        HeroSettings::withDefaults(['scheme' => 'navy', 'content_scheme' => 'light']),
        8
    );

    assert_true(strpos($rendered['html'], 'hero--content-light') !== false, 'светлый текст на тёмной обложке');

    // Цвет объявлен переменной на .hero__text, а компонент со своей
    // поверхностью переопределяет её у себя — иначе белая карточка внутри
    // navy-обложки получила бы белый текст на белом.
    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/blocks/hero.css');
    assert_true(strpos($css, '.hero--content-light .hero__text { --hero-fg: #fff; }') !== false,
        'светлая схема задаётся переменной на текстовом слое');
    assert_true(preg_match('/\.hero__surface\s*\{[^}]*--hero-fg:\s*#101a2b/', $css) === 1,
        'вложенная светлая поверхность возвращает себе тёмный текст');

    $light = HeroRenderer::render(
        ['id' => 1, 'name' => 'Light'],
        [hero_test_slide(['title' => 'Светлая'], 1)],
        HeroSettings::withDefaults(['scheme' => 'light', 'content_scheme' => 'auto', 'overlay' => 'none']),
        9
    );
    assert_true(strpos($light['html'], 'hero--content-dark') !== false,
        'на светлой обложке без фото авторежим даёт тёмный текст');
});

test('Расписание слайда: слайд вне окна не попадает в разметку', function () {
    $future = date('Y-m-d H:i', time() + 86400);
    $rendered = HeroRenderer::render(
        ['id' => 1, 'name' => 'Расписание'],
        [
            hero_test_slide(['title' => 'Виден'], 1),
            hero_test_slide(['title' => 'Ещё не начался', '_visible_from' => $future], 2),
        ],
        HeroSettings::defaults(),
        10
    );

    // HeroRenderer получает уже отфильтрованные слайды (это делает
    // HeroSlide::forDisplay), поэтому здесь проверяем сам фильтр.
    $visible = array_values(array_filter(
        [
            HeroSlideData::withDefaults(['title' => 'Виден']),
            HeroSlideData::withDefaults(['title' => 'Ещё не начался', '_visible_from' => $future]),
        ],
        static fn (array $d): bool => \App\Core\BlockVisibility::isVisible($d)
    ));
    assert_same(1, count($visible), 'слайд с будущим стартом отфильтрован');
    assert_true(strpos($rendered['html'], 'Виден') !== false);
});

test('Блок «Обложка»: ссылка на обложку хранится в данных блока', function () {
    assert_true(
        array_key_exists('hero_id', BlockTypeRegistry::defaultsFor('hero')),
        'у блока есть поле выбора обложки'
    );
    assert_same(0, BlockTypeRegistry::defaultsFor('hero')['hero_id'], 'по умолчанию обложка не выбрана');

    $data = HeroBlockNormalizer::normalize(['hero_id' => '42', 'title_field' => 'Свой заголовок']);
    assert_same(42, $data['hero_id'], 'выбранная обложка сохраняется числом');
    assert_same('Свой заголовок', $data['title'], 'собственные поля блока не теряются при выборе обложки');

    assert_same(0, HeroBlockNormalizer::normalize(['hero_id' => '-5'])['hero_id'], 'отрицательный id не проходит');
});

test('Обложка: рендер подключается блоком, а не только шаблоном', function () {
    $collector = (string) file_get_contents(APP_ROOT . '/app/Core/AssetCollector.php');
    assert_true(strpos($collector, "'hero_slides' => '/assets/js/blocks/hero.js'") !== false,
        'скрипт обложки объявлен в карте ассетов');
    assert_true(strpos($collector, "'hero_slides' => '/assets/css/blocks/hero.css'") !== false,
        'часть темы обложки объявлена в карте ассетов');

    // Ключ намеренно не совпадает с типом блока: старая обложка, собранная
    // прямо в блоке, этих файлов не использует и грузить их не должна.
    $renderer = (string) file_get_contents(APP_ROOT . '/app/Core/BlockRenderer.php');
    assert_true(strpos($renderer, "data-hero-transition") !== false,
        'ассеты подключаются по признаку новой разметки, а не по типу блока');

    $template = (string) file_get_contents(APP_ROOT . '/templates/blocks/hero.php');
    assert_true(strpos($template, 'HeroRenderer::render') !== false, 'шаблон блока умеет выводить выбранную обложку');
});

test('Стили обложки: фон вне потока, навигация не перекрывает контент', function () {
    $css = (string) file_get_contents(APP_ROOT . '/public/assets/css/blocks/hero.css');

    assert_true(preg_match('/\.hero\s*\{[^}]*overflow:\s*hidden/', $css) === 1,
        'фон обрезан корнем — горизонтальной прокрутки от него не бывает');
    assert_true(preg_match('/\.hero__media\s*\{[^}]*position:\s*absolute/', $css) === 1,
        'слой фона вынут из потока и не влияет на высоту');
    assert_true(strpos($css, 'object-fit: var(--hero-fit)') !== false,
        'кадрирование фона задаётся object-fit');
    assert_true(strpos($css, 'padding-block-end: calc(var(--space-xl) + var(--hero-nav-space))') !== false,
        'под навигацию зарезервировано место, а не надежда на короткий текст');
    assert_true(strpos($css, '@media (scripting: none)') !== false,
        'без JavaScript слайды показываются подряд, а не исчезают');
    assert_true(strpos($css, '@media (prefers-reduced-motion: reduce)') !== false,
        'системная настройка «меньше движения» учтена');
    assert_true(strpos($css, '[data-a11y-motion="off"] .hero') !== false,
        'тумблер остановки анимаций из панели настроек учтён');
});

test('Скрипт обложки: клавиатура, свайп и уважение к «меньше движения»', function () {
    $js = (string) file_get_contents(APP_ROOT . '/public/assets/js/blocks/hero.js');

    assert_true(strpos($js, 'asdrReduceMotion') !== false, 'скрипт спрашивает общий признак «меньше движения»');
    assert_true(strpos($js, "'ArrowLeft'") !== false && strpos($js, "'ArrowRight'") !== false,
        'стрелки клавиатуры переключают слайды');
    assert_true(strpos($js, 'touchstart') !== false && strpos($js, 'touchend') !== false, 'свайп обрабатывается');
    assert_true(strpos($js, 'visibilitychange') !== false, 'в фоновой вкладке автопрокрутка останавливается');
    assert_true(strpos($js, 'startAuto()') !== false && strpos($js, 'stopAuto()') !== false,
        'таймер автопрокрутки пересчитывается, а не продолжается с середины');
    assert_true(strpos($js, 'listening') !== false, 'готовность плеера YouTube проверяется рукопожатием');
});

test('Обложка: слайды и настройки хранятся отдельной записью (БД)', function () {
    ensure_test_db();
    $pdo = \App\Core\Database::pdo();
    $pdo->exec('DELETE FROM heroes');

    $heroId = \App\Models\Hero::create('Тестовая обложка');
    assert_true($heroId !== null && $heroId > 0, 'обложка создана');

    $first = \App\Models\HeroSlide::create($heroId, HeroSlideData::normalize([
        'title' => 'Первый слайд',
        'image' => '/uploads/public/a.jpg',
    ]));
    $second = \App\Models\HeroSlide::create($heroId, HeroSlideData::normalize([
        'title' => 'Второй слайд',
        'image' => '/uploads/public/b.jpg',
    ]));
    assert_true($first !== null && $second !== null, 'слайды созданы');

    // Черновик на сайт не выходит.
    assert_true(!\App\Models\Hero::isVisible((array) \App\Models\Hero::find($heroId)), 'черновик скрыт');

    \App\Models\Hero::update($heroId, 'Тестовая обложка', 'published', null, null, 5, HeroSettings::defaults(), '');
    assert_true(\App\Models\Hero::isVisible((array) \App\Models\Hero::find($heroId)), 'опубликованная обложка видна');

    // Порядок из админки — это порядок на сайте.
    \App\Models\HeroSlide::reorder($heroId, [$second, $first]);
    $ordered = \App\Models\HeroSlide::forHero($heroId);
    assert_same($second, (int) $ordered[0]['id'], 'перетаскивание меняет порядок показа');

    // Выключенный слайд остаётся в админке, но не уходит на сайт.
    \App\Models\HeroSlide::toggle($first);
    assert_same(2, count(\App\Models\HeroSlide::forHero($heroId)), 'в админке слайд остался');
    assert_same(1, count(\App\Models\HeroSlide::forDisplay($heroId)), 'на сайт ушёл только включённый');

    // Дубль встаёт сразу за оригиналом, а не в конец списка.
    \App\Models\HeroSlide::duplicate($second);
    $afterCopy = \App\Models\HeroSlide::forHero($heroId);
    assert_same(3, count($afterCopy));
    assert_same('Второй слайд', (string) $afterCopy[1]['title'], 'копия стоит следом за оригиналом');

    $pdo->exec('DELETE FROM heroes');
});

test('Обложка: расписание публикации и граница пересборки кэша (БД)', function () {
    ensure_test_db();
    $pdo = \App\Core\Database::pdo();
    $pdo->exec('DELETE FROM heroes');

    $heroId = (int) \App\Models\Hero::create('Праздничная обложка');
    $from = date('Y-m-d H:i:s', time() + 3600);
    $to = date('Y-m-d H:i:s', time() + 7200);
    \App\Models\Hero::update($heroId, 'Праздничная обложка', 'scheduled', $from, $to, 0, HeroSettings::defaults(), '');

    $hero = (array) \App\Models\Hero::find($heroId);
    assert_true(!\App\Models\Hero::isVisible($hero), 'до начала окна обложка скрыта');
    assert_true(\App\Models\Hero::isVisible($hero, time() + 4000), 'внутри окна обложка видна');
    assert_true(!\App\Models\Hero::isVisible($hero, time() + 8000), 'после окна обложка снова скрыта');

    // Без границы кэш страницы завис бы с прошлогодним баннером.
    assert_same(strtotime($from), \App\Models\Hero::boundary($hero), 'ближайшая граница расписания сообщена');

    // «По расписанию» без дат — это не «всегда»: редактор просто не заполнил окно.
    \App\Models\Hero::update($heroId, 'Праздничная обложка', 'scheduled', null, null, 0, HeroSettings::defaults(), '');
    assert_true(
        !\App\Models\Hero::isVisible((array) \App\Models\Hero::find($heroId)),
        'расписание без дат не показывает обложку'
    );

    $pdo->exec('DELETE FROM heroes');
});

test('Обложка: перевод накладывается на текст, медиа остаётся общим (БД)', function () {
    ensure_test_db();
    $pdo = \App\Core\Database::pdo();
    $pdo->exec('DELETE FROM heroes');

    $heroId = (int) \App\Models\Hero::create('Двуязычная обложка');
    \App\Models\Hero::update($heroId, 'Двуязычная обложка', 'published', null, null, 0, HeroSettings::defaults(), '');
    $slideId = (int) \App\Models\HeroSlide::create($heroId, HeroSlideData::normalize([
        'title' => 'Русский заголовок',
        'subtitle' => 'Русское описание',
        'image' => '/uploads/public/a.jpg',
    ]));

    \App\Models\HeroSlideTranslation::upsert($slideId, 'uz', [
        'title' => 'Oʻzbekcha sarlavha',
        // Описание намеренно не переведено: должен остаться русский текст.
        'subtitle' => '',
    ]);

    $uz = \App\Models\HeroSlide::forDisplay($heroId, 'uz');
    assert_same(1, count($uz));
    assert_same('Oʻzbekcha sarlavha', (string) $uz[0]['data']['title'], 'переведённый заголовок подставлен');
    assert_same('Русское описание', (string) $uz[0]['data']['subtitle'], 'непереведённое поле берётся с основного языка');
    assert_same('/uploads/public/a.jpg', (string) $uz[0]['data']['image'], 'медиа у слайда общее для всех языков');

    $pdo->exec('DELETE FROM heroes');
});
