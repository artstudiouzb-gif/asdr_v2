<?php

declare(strict_types=1);

namespace App\Core\Hero;

use App\Core\BlockData\BlockDataInput;
use App\Core\MediaPosition;
use App\Core\UrlGuard;
use App\Core\Video;

/**
 * Обложка как обычный блок конструктора: слайды лежат в самом блоке, а не
 * отдельной записью, и настроек ровно столько, сколько редактор действительно
 * выбирает.
 *
 * Рисует её тот же `HeroRenderer`, что и «Обложку» из раздела /admin/heroes, —
 * и это главное решение здесь. Своя разметка означала бы третью реализацию
 * обложки рядом с двумя имеющимися: тот же кадр, то же наложение, та же
 * карусель, только собранные заново. Такие близнецы расходятся при первой же
 * правке — по этой причине в проекте нет отдельных блоков «Аккордеон» и
 * «Цитата». Поэтому блок не рисует ничего сам: он приводит свои настройки к
 * контракту `HeroSettings`/`HeroSlideData` и отдаёт их рендереру. Один
 * `blocks/hero.css`, один `blocks/hero.js`, одна разметка.
 *
 * Разница между этим блоком и прежней обложкой — не в выводе, а в том, что
 * редактор может задать. Из 79 настроек (31 у обложки, 48 у слайда) осталось
 * 22. Остальные не исчезли, а перестали быть настройками: они здесь ниже, в
 * FIXED и в SLIDE_FIXED, с одним значением на все обложки.
 */
final class HeroV2
{
    public const HEIGHTS = ['compact', 'regular', 'tall', 'full'];
    public const BACKGROUNDS = ['navy', 'dark', 'light', 'custom'];
    public const TEXT_SCHEMES = ['auto', 'light', 'dark'];
    public const OVERLAYS = ['none', 'solid', 'gradient'];
    public const TEXT_POSITIONS = ['left', 'center', 'right'];
    public const CTA_STYLES = ['primary', 'secondary', 'ghost', 'link'];

    /**
     * Больше слайдов посетитель не досмотрит: до последнего кадра при
     * шестисекундном показе почти минута, а на первом экране ждать никто не
     * будет. Ограничение нужно и рендереру — точки навигации рисуются в ряд.
     */
    public const MAX_SLIDES = 8;

    /**
     * Настройки обложки, которых у блока нет.
     *
     * Каждая строка — не «поле забыли», а решение: это оформление или
     * поведение, одинаковое для всех обложек сайта, а не выбор редактора.
     * Размеры заголовка и описания задаёт шрифтовая шкала; ширину — колонка
     * сайта; вид перехода и его длительность — оформление; стрелки, свайп и
     * вид индикатора — правильное поведение карусели; подложку под текстом
     * заменяет наложение; направление градиента считается от положения текста
     * (в прежней обложке это и был вариант «авто»).
     */
    private const FIXED = [
        'width' => 'full',
        'text_align_y' => 'center',
        'text_width' => '',
        'title_size' => 'l',
        'subtitle_size' => 'm',
        'text_offset_top' => 0,
        'overlay_direction' => 'auto',
        'panel' => false,
        'nav_arrows' => true,
        'nav_arrows_mobile' => true,
        'nav_indicator' => 'counter_progress',
        'nav_swipe' => true,
        'transition' => 'fade_slide',
        'transition_duration' => 700,
        // Мобильная высота выводится из выбранного пресета
        // (HeroRenderer::HEIGHTS_MOBILE), поэтому второй настройки не нужно.
        'height_mobile' => '',
        'height_mobile_value' => '',
        'height_value' => '',
    ];

    /**
     * Поля слайда, которых у блока нет. Переопределения цвета и наложения
     * убраны намеренно: они существовали только чтобы сказать «как у обложки
     * или своё», то есть удваивали настройку обложки. Фоновая надпись,
     * накладная картинка, свой CSS-класс, своя длительность показа и
     * расписание слайда — оформление отдельного кадра, которого на госсайте
     * не потребовалось ни разу.
     */
    private const SLIDE_FIXED = [
        'content_scheme' => '',
        'overlay' => '',
        'overlay_color' => '',
        'overlay_opacity' => -1,
        'overlay_direction' => '',
        'image_fit' => 'cover',
        'mobile_media' => 'image',
        'link_url' => '',
        'link_new_tab' => false,
        'duration' => 0,
        'css_class' => '',
        'cta2_style' => 'ghost',
    ];

    /**
     * Настройки блока → контракт `HeroSettings`.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function settings(array $data): array
    {
        $seconds = max(0, (int) ($data['autoplay'] ?? 0));

        return HeroSettings::withDefaults(array_merge(self::FIXED, [
            'height' => self::pick($data, 'height', self::HEIGHTS, 'regular'),
            'text_position' => self::pick($data, 'text_position', self::TEXT_POSITIONS, 'left'),
            'scheme' => self::pick($data, 'bg', self::BACKGROUNDS, 'navy'),
            'scheme_bg' => self::hex($data, 'bg_color', '#0b1a30'),
            // Своего цвета текста у блока нет: на фотографии цвет решает
            // читаемость, а не палитра. Схема `custom` без него считает цвет
            // по светлоте фона (HeroRenderer::schemeVars) — это и нужно.
            'scheme_text' => '',
            'scheme_accent' => self::hex($data, 'accent', ''),
            'content_scheme' => self::pick($data, 'text_scheme', self::TEXT_SCHEMES, 'auto'),
            'overlay' => self::pick($data, 'overlay', self::OVERLAYS, 'gradient'),
            'overlay_color' => self::hex($data, 'overlay_color', '#0b1a30'),
            'overlay_opacity' => max(0, min(100, (int) ($data['overlay_opacity'] ?? 45))),
            // Одно поле вместо пары «включить» и «интервал»: ноль и есть
            // «выключена». Так же устроена автопрокрутка блока «Слайдер».
            'autoplay' => $seconds > 0,
            'autoplay_interval' => $seconds > 0 ? $seconds : 6,
        ]));
    }

    /**
     * Слайды блока → строки, которых ждёт `HeroRenderer`.
     *
     * @param array<string, mixed> $data
     * @return list<array<string, mixed>>
     */
    public static function slides(array $data): array
    {
        $rows = [];
        foreach (array_values((array) ($data['slides'] ?? [])) as $index => $slide) {
            if (!is_array($slide)) {
                continue;
            }
            $rows[] = [
                'id' => $index + 1,
                'sort_order' => $index,
                'is_active' => 1,
                'data' => HeroSlideData::withDefaults(array_merge(self::SLIDE_FIXED, self::slideData($slide))),
            ];
        }

        return $rows;
    }

    /**
     * Присланный формой слайд. Хранится ровно то, что показывает форма:
     * лишний ключ всё равно потерялся бы при первом сохранении.
     *
     * @param array<string, mixed> $input
     * @return list<array<string, mixed>>
     */
    public static function normalizeSlides(array $input): array
    {
        $slides = [];
        foreach ((array) ($input['slides'] ?? []) as $slide) {
            if (!is_array($slide)) {
                continue;
            }
            $row = [
                'eyebrow' => self::line($slide, 'eyebrow'),
                'title' => self::line($slide, 'title'),
                'subtitle' => trim((string) ($slide['subtitle'] ?? '')),
                'image' => BlockDataInput::safeMedia($slide['image'] ?? ''),
                'image_mobile' => BlockDataInput::safeMedia($slide['image_mobile'] ?? ''),
                'image_position' => self::position($slide),
                'video_url' => self::video($slide),
                'cta_text' => self::line($slide, 'cta_text'),
                'cta_url' => self::link($slide, 'cta_url'),
                'cta_style' => self::pick($slide, 'cta_style', self::CTA_STYLES, 'primary'),
                'cta2_text' => self::line($slide, 'cta2_text'),
                'cta2_url' => self::link($slide, 'cta2_url'),
            ];
            // Пустой слайд — не слайд: он занял бы кадр карусели и показал
            // посетителю пустой экран.
            if (self::isBlank($row)) {
                continue;
            }
            $slides[] = $row;
            if (count($slides) >= self::MAX_SLIDES) {
                break;
            }
        }

        return $slides;
    }

    /**
     * Один слайд блока → поля `HeroSlideData`.
     *
     * @param array<string, mixed> $slide
     * @return array<string, mixed>
     */
    private static function slideData(array $slide): array
    {
        $image = BlockDataInput::safeMedia($slide['image'] ?? '');
        $video = trim((string) ($slide['video_url'] ?? ''));
        $youtube = Video::youtubeId($video);

        // Источник видео опознаётся по самой ссылке, а не выбирается полем:
        // дубликат того, что видно в адресе, редактор рано или поздно
        // поставит не тот. Тот же приём, что в блоке «Внешняя врезка».
        if ($youtube !== null && $youtube !== '') {
            $mediaType = 'youtube';
        } elseif ($video !== '' && UrlGuard::isSafeMedia($video)) {
            $mediaType = 'video';
        } else {
            $mediaType = $image !== '' ? 'image' : 'none';
            $video = '';
        }

        $ctaText = trim((string) ($slide['cta_text'] ?? ''));
        $ctaUrl = self::link($slide, 'cta_url');
        $cta2Text = trim((string) ($slide['cta2_text'] ?? ''));
        $cta2Url = self::link($slide, 'cta2_url');

        return [
            'eyebrow' => trim((string) ($slide['eyebrow'] ?? '')),
            'title' => trim((string) ($slide['title'] ?? '')),
            'subtitle' => trim((string) ($slide['subtitle'] ?? '')),
            'media_type' => $mediaType,
            'image' => $image,
            'image_mobile' => BlockDataInput::safeMedia($slide['image_mobile'] ?? ''),
            'image_position' => self::position($slide),
            // Кадрирование на телефоне отдельной настройкой не является:
            // точка фокуса у снимка одна, а второе поле рядом с первым
            // редактор заполняет наугад.
            'image_position_mobile' => self::position($slide),
            'video_url' => $mediaType === 'video' ? $video : '',
            'youtube_url' => $mediaType === 'youtube' ? $video : '',
            // Кадр-замена лежит под видео всегда: «не загрузилось», «запрещено
            // автовоспроизведение» и «выключено на телефоне» — один сценарий.
            'poster' => $image,
            // Кнопки включает заполненность, а не отдельная галочка: пустая
            // строка и есть «кнопки нет».
            'cta_enabled' => $ctaText !== '' && $ctaUrl !== '',
            'cta_text' => $ctaText,
            'cta_url' => $ctaUrl,
            'cta_style' => self::pick($slide, 'cta_style', self::CTA_STYLES, 'primary'),
            'cta2_enabled' => $cta2Text !== '' && $cta2Url !== '',
            'cta2_text' => $cta2Text,
            'cta2_url' => $cta2Url,
        ];
    }

    /** @param array<string, mixed> $row */
    private static function isBlank(array $row): bool
    {
        foreach (['eyebrow', 'title', 'subtitle', 'image', 'image_mobile', 'video_url'] as $key) {
            if (trim((string) $row[$key]) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $input
     * @param list<string> $allowed
     */
    private static function pick(array $input, string $key, array $allowed, string $default): string
    {
        $value = isset($input[$key]) && is_scalar($input[$key]) ? (string) $input[$key] : '';

        return in_array($value, $allowed, true) ? $value : $default;
    }

    /** @param array<string, mixed> $input */
    private static function hex(array $input, string $key, string $default): string
    {
        $value = isset($input[$key]) && is_scalar($input[$key]) ? trim((string) $input[$key]) : '';

        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtolower($value) : $default;
    }

    /** @param array<string, mixed> $input */
    private static function line(array $input, string $key): string
    {
        return trim((string) ($input[$key] ?? ''));
    }

    /** @param array<string, mixed> $input */
    private static function link(array $input, string $key): string
    {
        $url = trim((string) ($input[$key] ?? ''));

        return ($url !== '' && UrlGuard::isSafeLink($url)) ? $url : '';
    }

    /** @param array<string, mixed> $slide */
    private static function position(array $slide): string
    {
        $value = trim((string) ($slide['image_position'] ?? ''));

        return MediaPosition::normalize($value);
    }

    /** @param array<string, mixed> $slide */
    private static function video(array $slide): string
    {
        $url = trim((string) ($slide['video_url'] ?? ''));
        if ($url === '') {
            return '';
        }
        $youtube = Video::youtubeId($url);
        if ($youtube !== null && $youtube !== '') {
            return UrlGuard::isSafeLink($url) ? $url : '';
        }

        return UrlGuard::isSafeMedia($url) ? $url : '';
    }
}
