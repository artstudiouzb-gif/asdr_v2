<?php

use App\Core\AdminUi;
use App\Core\BlockVisibility;
use App\Core\Csrf;
use App\Models\Language;

/** @var array $hero */
/** @var array<string, mixed> $settings */
/** @var array $slide */
/** @var array<string, mixed> $data */
/** @var array<string, array<string, mixed>> $translations */

$heroId = (int) $hero['id'];
$slideId = (int) $slide['id'];
$pageTitle = 'Слайд обложки «' . $hero['name'] . '»';
$activeNav = 'heroes';
require __DIR__ . '/../layout/header.php';

$defaultCode = Language::defaultCode();
$translationLangs = array_values(array_filter(
    Language::active(),
    static fn (array $l): bool => (string) $l['code'] !== $defaultCode
));

$esc = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES);

$select = static function (string $name, string $label, array $options, string $current, string $hint = '') use ($esc): string {
    $html = '<div class="form-field"><label for="slide_' . $name . '">' . $esc($label) . '</label>'
        . '<select id="slide_' . $name . '" name="' . $name . '">';
    foreach ($options as $value => $option) {
        $html .= '<option value="' . $esc($value) . '"' . ((string) $value === $current ? ' selected' : '') . '>'
            . $esc($option) . '</option>';
    }
    $html .= '</select>';
    if ($hint !== '') {
        $html .= '<span class="form-hint">' . $esc($hint) . '</span>';
    }

    return $html . '</div>';
};

$checkbox = static function (string $name, string $label, bool $checked, string $hint = '') use ($esc): string {
    $html = '<div class="form-field form-field--checkbox">'
        . '<input type="checkbox" id="slide_' . $name . '" name="' . $name . '" value="1"' . ($checked ? ' checked' : '') . '>'
        . '<label for="slide_' . $name . '">' . $esc($label) . '</label>';
    if ($hint !== '') {
        $html .= '<span class="form-hint">' . $esc($hint) . '</span>';
    }

    return $html . '</div>';
};

/**
 * Сворачиваемая группа со сводкой текущего состояния в заголовке.
 * Частые настройки раскрыты сразу; редкие открываются автоматически, только
 * когда в них уже есть индивидуальные значения.
 */
$group = static function (
    string $title,
    string $hint,
    string $state,
    string $body,
    bool $open = false,
    string $id = ''
) use ($esc): string {
    return '<details class="form-section"'
        . ($id !== '' ? ' id="' . $esc($id) . '"' : '')
        . ($open ? ' open' : '') . '><summary>' . $esc($title)
        . ($hint !== '' ? ' <span class="form-section__hint">' . $esc($hint) . '</span>' : '')
        . ($state !== '' ? '<span class="form-section__state">' . $esc($state) . '</span>' : '')
        . '</summary><div class="form-section__body form-section__body--grid">' . $body . '</div></details>';
};

$inherit = ['' => 'Использовать общую настройку обложки'];
$posOptions = ['left' => 'Слева', 'center' => 'По центру', 'right' => 'Справа'];
$yOptions = ['top' => 'Сверху', 'center' => 'По центру', 'bottom' => 'Снизу'];
$sizeOptions = ['s' => 'Мелкий', 'm' => 'Средний', 'l' => 'Крупный', 'xl' => 'Очень крупный'];
$subtitleSizes = ['s' => 'Мелкий', 'm' => 'Средний', 'l' => 'Крупный'];
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
$cropOptions = [
    'left-top' => 'Слева сверху',
    'center-top' => 'По центру сверху',
    'right-top' => 'Справа сверху',
    'left-center' => 'Слева',
    'center-center' => 'По центру',
    'right-center' => 'Справа',
    'left-bottom' => 'Слева снизу',
    'center-bottom' => 'По центру снизу',
    'right-bottom' => 'Справа снизу',
];

$mediaLabels = [
    'none' => 'Без фона',
    'image' => 'Фото',
    'video' => 'Видео MP4',
    'youtube' => 'YouTube',
];
$artPositionLabels = ['above' => 'Над текстом', 'left' => 'Слева', 'right' => 'Справа'];
$artSizeLabels = ['small' => '120 px', 'medium' => '220 px', 'large' => '360 px', 'custom' => 'свой размер'];

$hasArt = trim((string) $data['art_image']) !== '';
$hasButtons = !empty($data['cta_enabled']) || !empty($data['cta2_enabled']) || trim((string) $data['link_url']) !== '';
$hasLayoutOverrides = false;
foreach (['text_position', 'text_align_y', 'title_size', 'subtitle_size', 'gap_art', 'gap_title', 'gap_subtitle', 'gap_actions'] as $key) {
    if ($data[$key] !== '') {
        $hasLayoutOverrides = true;
        break;
    }
}
$hasMobileOverrides = trim((string) $data['image_mobile']) !== ''
    || trim((string) $data['video_mobile_url']) !== ''
    || (string) $data['mobile_media'] !== 'image';
foreach (['text_position_mobile', 'text_align_y_mobile', 'title_size_mobile', 'subtitle_size_mobile'] as $key) {
    if ($data[$key] !== '') {
        $hasMobileOverrides = true;
        break;
    }
}
$hasColorOverrides = (string) $data['scheme'] !== ''
    || (string) $data['scheme_bg'] !== ''
    || (string) $data['scheme_text'] !== ''
    || (string) $data['scheme_accent'] !== ''
    || (string) $data['content_scheme'] !== ''
    || (string) $data['overlay'] !== ''
    || (string) $data['overlay_color'] !== ''
    || (int) $data['overlay_opacity'] >= 0
    || (string) $data['overlay_direction'] !== ''
    || (string) $data['panel'] !== '';
$hasWatermark = trim((string) $data['watermark']) !== '';
$hasSchedule = (int) $data['duration'] > 0
    || trim((string) $data['_visible_from']) !== ''
    || trim((string) $data['_visible_to']) !== '';
$hasCss = trim((string) $data['css_class']) !== '';
$hasTranslations = false;
foreach ($translations as $translation) {
    foreach ($translation as $value) {
        if (is_scalar($value) && trim((string) $value) !== '') {
            $hasTranslations = true;
            break 2;
        }
    }
}

$globalDuration = max(1, (int) ($settings['autoplay_interval'] ?? 6));
$customDuration = (int) $data['duration'];
$effectiveDuration = $customDuration > 0 ? $customDuration : $globalDuration;
$durationState = $customDuration > 0
    ? $customDuration . ' с · своё значение'
    : $globalDuration . ' с · из обложки';

$artState = $hasArt
    ? ($artPositionLabels[(string) $data['art_position']] ?? 'Задана') . ' · ' . ($artSizeLabels[(string) $data['art_size']] ?? '')
    : 'Не используется';
$backgroundState = $mediaLabels[(string) $data['media_type']] ?? 'Без фона';
if (trim((string) $data['image_mobile']) !== '') {
    $backgroundState .= ' · свой кадр для телефона';
}
$buttonCount = (!empty($data['cta_enabled']) ? 1 : 0) + (!empty($data['cta2_enabled']) ? 1 : 0);
$buttonsState = $buttonCount > 0 ? $buttonCount . ' кноп.' : 'Без кнопок';
if (trim((string) $data['link_url']) !== '') {
    $buttonsState .= ' · весь слайд кликабелен';
}
$layoutState = $hasLayoutOverrides ? 'Есть свои настройки' : 'Общее значение';
$mobileState = $hasMobileOverrides ? 'Есть свои настройки' : 'Общее значение';
$colorState = $hasColorOverrides ? 'Есть свои настройки' : 'Общее значение';
$watermarkState = $hasWatermark ? (string) $data['watermark'] : 'Не используется';
$translationState = $hasTranslations ? 'Есть переводы' : 'Используется основной язык';
?>
<p>
    <a href="/admin/heroes/<?= $heroId ?>/edit" class="btn btn--small">← К обложке «<?= $esc($hero['name']) ?>»</a>
</p>

<form method="post" action="/admin/heroes/<?= $heroId ?>/slides/<?= $slideId ?>/update">
    <?= Csrf::field() ?>

    <div class="form-card">
        <?= AdminUi::cardHeader('Настройки слайда', 'slideshow') ?>
        <p class="form-hint">
            Обычно достаточно разделов «Основное», «Фон» и «Логотип / картинка».
            Пункт «Использовать общую настройку обложки» берёт значение из общих настроек Hero-блока.
            Если здесь задано своё значение, оно действует только для этого слайда и имеет приоритет.
        </p>

        <?php
        ob_start(); ?>
            <div class="form-field">
                <label for="eyebrow">Надзаголовок</label>
                <input type="text" id="eyebrow" name="eyebrow" value="<?= $esc($data['eyebrow']) ?>" placeholder="Например: Национальная программа">
            </div>
            <div class="form-field">
                <label for="title">Заголовок</label>
                <input type="text" id="title" name="title" value="<?= $esc($data['title']) ?>">
            </div>
            <div class="form-field">
                <label for="subtitle">Описание</label>
                <textarea id="subtitle" name="subtitle" rows="3"><?= $esc($data['subtitle']) ?></textarea>
            </div>
        <?php echo $group(
            'Основное',
            'заголовок и описание',
            trim((string) $data['title']) !== '' ? (string) $data['title'] : 'Без заголовка',
            (string) ob_get_clean(),
            true
        ); ?>

        <?php
        ob_start(); ?>
            <div class="form-field form-field--wide">
                <span class="form-hint">Эмблема, логотип программы или иллюстрация поверх фоновой фотографии. Это отдельный элемент содержимого, а не фон.</span>
            </div>
            <?= AdminUi::imageField('art_image', (string) $data['art_image'], ['label' => 'Картинка / логотип']) ?>
            <div class="form-field">
                <label for="art_alt">Описание картинки</label>
                <input type="text" id="art_alt" name="art_alt" value="<?= $esc($data['art_alt']) ?>" placeholder="Например: Логотип программы «Цифровой Узбекистан»">
                <span class="form-hint">Пусто — декоративная картинка. Для смыслового логотипа укажите короткое описание.</span>
            </div>
            <?= $select('art_position', 'Расположение', [
                'above' => 'Над текстом', 'left' => 'Слева от текста', 'right' => 'Справа от текста',
            ], (string) $data['art_position'], 'При варианте «Справа» картинка прижимается к правому краю контентной области.') ?>
            <?= $select('art_size', 'Размер', [
                'small' => 'Маленькая — 120 px', 'medium' => 'Средняя — 220 px',
                'large' => 'Крупная — 360 px', 'custom' => 'Свой размер',
            ], (string) $data['art_size']) ?>
            <div class="form-field">
                <label for="art_width">Своя ширина логотипа, px</label>
                <input type="number" id="art_width" name="art_width" min="40" max="1200" step="1" value="<?= (int) $data['art_width'] ?>">
                <span class="form-hint">Используется только при размере «Свой размер». Высота рассчитывается автоматически.</span>
            </div>
        <?php echo $group(
            'Логотип / картинка',
            'отдельный PNG или SVG рядом с текстом',
            $artState,
            (string) ob_get_clean(),
            $hasArt
        ); ?>

        <?php
        ob_start(); ?>
            <div class="form-field form-field--wide">
                <span class="form-hint">Фон заполняет весь слайд. Для видео обязательно оставьте изображение или poster: он показывается до запуска и при недоступном видео.</span>
            </div>
            <?= $select('media_type', 'Тип фона', [
                'none' => 'Без фона',
                'image' => 'Изображение',
                'video' => 'Загруженное видео (MP4)',
                'youtube' => 'Видео с YouTube',
            ], (string) $data['media_type']) ?>
            <?= AdminUi::imageField('image', (string) $data['image'], [
                'label' => 'Изображение для компьютера',
                'hint' => 'Оно же используется как запасной кадр, если отдельный poster не задан.',
            ]) ?>
            <?= $select('image_position', 'Кадрирование на компьютере', $cropOptions, (string) $data['image_position']) ?>
            <?= $select('image_fit', 'Как вписать изображение', [
                'cover' => 'Заполнить область (обрезать лишнее)',
                'contain' => 'Показать целиком',
            ], (string) $data['image_fit']) ?>
            <div class="form-field">
                <label for="video_url">Видео MP4</label>
                <input type="text" id="video_url" name="video_url" value="<?= $esc($data['video_url']) ?>" placeholder="/uploads/public/hero.mp4">
                <span class="form-hint">Фоновое видео проигрывается без звука и по кругу.</span>
            </div>
            <div class="form-field">
                <label for="youtube_url">Ссылка на YouTube</label>
                <input type="text" id="youtube_url" name="youtube_url" value="<?= $esc($data['youtube_url']) ?>" placeholder="https://www.youtube.com/watch?v=XXXXXXXXXXX">
                <span class="form-hint">
                    Подойдёт обычная ссылка watch?v=… или youtu.be/… .
                    <?php if ($data['youtube_id'] !== ''): ?>Распознан ID: <code><?= $esc($data['youtube_id']) ?></code>.<?php endif; ?>
                </span>
            </div>
            <?= AdminUi::imageField('poster', (string) $data['poster'], [
                'label' => 'Кадр-замена (poster)',
                'hint' => 'Показывается до старта видео и вместо него при ошибке, запрете автовоспроизведения или режиме «меньше движения».',
            ]) ?>
        <?php echo $group(
            'Фон слайда',
            'фото или видео на всю площадь',
            $backgroundState,
            (string) ob_get_clean(),
            (string) $data['media_type'] !== 'none'
        ); ?>

        <?php
        ob_start(); ?>
            <div class="form-field form-field--wide">
                <span class="form-hint">Кнопки — явные действия. Ссылка со всего слайда нужна только когда вся обложка ведёт в одно место.</span>
            </div>
            <?= $checkbox('cta_enabled', 'Показывать основную кнопку', (bool) $data['cta_enabled']) ?>
            <div class="form-field">
                <label for="cta_text">Текст основной кнопки</label>
                <input type="text" id="cta_text" name="cta_text" value="<?= $esc($data['cta_text']) ?>">
            </div>
            <div class="form-field">
                <label for="cta_url">Ссылка основной кнопки</label>
                <input type="text" id="cta_url" name="cta_url" value="<?= $esc($data['cta_url']) ?>" placeholder="/about">
            </div>
            <?= $select('cta_style', 'Стиль основной кнопки', $ctaStyles, (string) $data['cta_style']) ?>
            <?= AdminUi::iconField('cta_icon', (string) $data['cta_icon'], ['label' => 'Иконка основной кнопки']) ?>
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

            <?= $checkbox('cta2_enabled', 'Показывать дополнительную кнопку', (bool) $data['cta2_enabled']) ?>
            <div class="form-field">
                <label for="cta2_text">Текст дополнительной кнопки</label>
                <input type="text" id="cta2_text" name="cta2_text" value="<?= $esc($data['cta2_text']) ?>">
            </div>
            <div class="form-field">
                <label for="cta2_url">Ссылка дополнительной кнопки</label>
                <input type="text" id="cta2_url" name="cta2_url" value="<?= $esc($data['cta2_url']) ?>">
            </div>
            <?= $select('cta2_style', 'Стиль дополнительной кнопки', $ctaStyles, (string) $data['cta2_style']) ?>
            <?= AdminUi::iconField('cta2_icon', (string) $data['cta2_icon'], ['label' => 'Иконка дополнительной кнопки']) ?>
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

            <div class="form-field form-field--wide">
                <label for="link_url">Ссылка со всего слайда</label>
                <input type="text" id="link_url" name="link_url" value="<?= $esc($data['link_url']) ?>">
                <span class="form-hint">Пусто — кликабельны только кнопки. Если адрес задан, вся свободная площадь слайда ведёт по этой ссылке.</span>
            </div>
            <?= $checkbox('link_new_tab', 'Ссылку со всего слайда открывать в новой вкладке', (bool) $data['link_new_tab']) ?>
        <?php echo $group(
            'Кнопки и ссылка',
            'действия пользователя',
            $buttonsState,
            (string) ob_get_clean(),
            $hasButtons
        ); ?>

        <?php
        ob_start(); ?>
            <div class="form-field form-field--wide">
                <span class="form-hint">Оставьте «Использовать общую настройку обложки», если этот слайд не должен отличаться от остальных.</span>
            </div>
            <?= $select('text_position', 'Текст по горизонтали', $inherit + $posOptions, (string) $data['text_position']) ?>
            <?= $select('text_align_y', 'Текст по вертикали', $inherit + $yOptions, (string) $data['text_align_y']) ?>
            <?= $select('title_size', 'Размер заголовка', $inherit + $sizeOptions, (string) $data['title_size']) ?>
            <?= $select('subtitle_size', 'Размер описания', $inherit + $subtitleSizes, (string) $data['subtitle_size']) ?>
            <?php foreach ([
                'gap_art' => 'Отступ у картинки / логотипа, px',
                'gap_title' => 'Отступ перед заголовком, px',
                'gap_subtitle' => 'Отступ перед описанием, px',
                'gap_actions' => 'Отступ перед кнопками, px',
            ] as $gapKey => $gapLabel): ?>
                <div class="form-field">
                    <label for="<?= $gapKey ?>"><?= $esc($gapLabel) ?></label>
                    <input type="number" id="<?= $gapKey ?>" name="<?= $gapKey ?>" min="0" max="200" step="1"
                           value="<?= $data[$gapKey] === '' ? '' : (int) $data[$gapKey] ?>" placeholder="общая настройка">
                    <?php if ($gapKey === 'gap_art'): ?><span class="form-hint">При боковом логотипе это расстояние между картинкой и текстом.</span><?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php echo $group(
            'Расположение на компьютере',
            'текст, размеры и отступы',
            $layoutState,
            (string) ob_get_clean(),
            $hasLayoutOverrides
        ); ?>

        <?php
        ob_start(); ?>
            <div class="form-field form-field--wide">
                <span class="form-hint">Все поля здесь необязательны. Если оставить их пустыми, телефон использует десктопную настройку или общую настройку обложки.</span>
            </div>
            <?= $select('text_position_mobile', 'Текст по горизонтали', $inherit + $posOptions, (string) $data['text_position_mobile']) ?>
            <?= $select('text_align_y_mobile', 'Текст по вертикали', $inherit + $yOptions, (string) $data['text_align_y_mobile']) ?>
            <?= $select('title_size_mobile', 'Размер заголовка', $inherit + $sizeOptions, (string) $data['title_size_mobile']) ?>
            <?= $select('subtitle_size_mobile', 'Размер описания', $inherit + $subtitleSizes, (string) $data['subtitle_size_mobile']) ?>
            <?= AdminUi::imageField('image_mobile', (string) $data['image_mobile'], [
                'label' => 'Отдельное изображение для телефона',
                'hint' => 'Пусто — используется изображение для компьютера.',
            ]) ?>
            <?= $select('image_position_mobile', 'Кадрирование на телефоне', $cropOptions, (string) $data['image_position_mobile']) ?>
            <div class="form-field">
                <label for="video_mobile_url">Отдельное видео MP4 для телефона</label>
                <input type="text" id="video_mobile_url" name="video_mobile_url" value="<?= $esc($data['video_mobile_url']) ?>" placeholder="/uploads/public/hero-mobile.mp4">
            </div>
            <?= $select('mobile_media', 'Что делать с видео на телефоне', [
                'image' => 'Заменить изображением (рекомендуется)',
                'desktop' => 'Проигрывать то же видео',
                'mobile_video' => 'Проигрывать отдельное мобильное видео',
            ], (string) $data['mobile_media']) ?>
        <?php echo $group(
            'Мобильная версия',
            'только отличия для телефона',
            $mobileState,
            (string) ob_get_clean(),
            $hasMobileOverrides
        ); ?>

        <?php
        ob_start(); ?>
            <div class="form-field form-field--wide">
                <span class="form-hint">Эти поля нужны только для исключений: например, один светлый кадр среди тёмных. В обычном случае оставьте «Использовать общую настройку обложки».</span>
            </div>
            <?= $select('scheme', 'Цветовая схема', $inherit + [
                'light' => 'Light', 'dark' => 'Dark', 'navy' => 'Navy', 'custom' => 'Custom',
            ], (string) $data['scheme']) ?>
            <?= AdminUi::colorField('scheme_bg', (string) $data['scheme_bg'], 'Свой фон (Custom)', '#0b1a30', 'Общее значение') ?>
            <?= AdminUi::colorField('scheme_text', (string) $data['scheme_text'], 'Свой цвет текста (Custom)', '#ffffff', 'Общее значение') ?>
            <?= AdminUi::colorField('scheme_accent', (string) $data['scheme_accent'], 'Цвет основной кнопки', '#173a63', 'Общее значение') ?>
            <?= $select('content_scheme', 'Цвет текста', $inherit + [
                'auto' => 'Auto — по фону и затемнению',
                'light' => 'Light — светлый текст',
                'dark' => 'Dark — тёмный текст',
            ], (string) $data['content_scheme']) ?>
            <?= $select('overlay', 'Затемнение', $inherit + [
                'none' => 'Нет', 'solid' => 'Сплошное', 'gradient' => 'Градиент',
            ], (string) $data['overlay']) ?>
            <?= AdminUi::colorField('overlay_color', (string) $data['overlay_color'], 'Цвет затемнения', '#0b1a30', 'Общее значение') ?>
            <div class="form-field">
                <label for="overlay_opacity">Плотность затемнения, %</label>
                <input type="number" id="overlay_opacity" name="overlay_opacity" min="0" max="100"
                       value="<?= (int) $data['overlay_opacity'] >= 0 ? (int) $data['overlay_opacity'] : '' ?>" placeholder="общая настройка">
            </div>
            <?= $select('overlay_direction', 'Направление градиента', $inherit + [
                'auto' => 'Автоматически',
                'to_right' => 'Слева направо', 'to_left' => 'Справа налево',
                'to_bottom' => 'Сверху вниз', 'to_top' => 'Снизу вверх',
                'to_bottom_right' => 'В правый нижний угол', 'to_bottom_left' => 'В левый нижний угол',
                'to_top_right' => 'В правый верхний угол', 'to_top_left' => 'В левый верхний угол',
            ], (string) $data['overlay_direction']) ?>
            <?= $select('panel', 'Подложка под текстом', $inherit + ['on' => 'Показывать', 'off' => 'Не показывать'], (string) $data['panel']) ?>
        <?php echo $group(
            'Цвет и читаемость',
            'схема, затемнение и подложка',
            $colorState,
            (string) ob_get_clean(),
            $hasColorOverrides
        ); ?>

        <?php
        ob_start(); ?>
            <div class="form-field form-field--wide">
                <span class="form-hint">
                    Общая длительность в обложке: <strong><?= $globalDuration ?> с</strong>.
                    Значение, указанное ниже, имеет приоритет только для этого слайда.
                </span>
            </div>
            <div class="form-field">
                <label for="duration">Время показа этого слайда, секунд</label>
                <input type="number" id="duration" name="duration" min="0" max="120" step="1"
                       value="<?= $customDuration > 0 ? $customDuration : '' ?>"
                       placeholder="<?= $globalDuration ?> — общее значение">
                <span class="form-hint">
                    Сейчас действует: <strong><?= $effectiveDuration ?> с</strong> —
                    <?= $customDuration > 0 ? 'индивидуальное значение этого слайда.' : 'общая настройка обложки.' ?>
                    Оставьте пустым или поставьте 0, чтобы снова наследовать обложку.
                </span>
            </div>
            <div class="form-field form-field--wide">
                <span class="form-hint">Расписание относится только к этому слайду. Вне указанного окна он вообще не попадает в страницу.</span>
            </div>
            <div class="form-field">
                <label for="_visible_from">Показывать с</label>
                <input type="datetime-local" id="_visible_from" name="_visible_from" value="<?= $esc(BlockVisibility::forInput($data['_visible_from'])) ?>">
            </div>
            <div class="form-field">
                <label for="_visible_to">Показывать до</label>
                <input type="datetime-local" id="_visible_to" name="_visible_to" value="<?= $esc(BlockVisibility::forInput($data['_visible_to'])) ?>">
            </div>
        <?php echo $group(
            'Время показа и расписание',
            'длительность конкретного слайда',
            $durationState,
            (string) ob_get_clean(),
            $hasSchedule
        ); ?>

        <?php
        ob_start(); ?>
            <div class="form-field form-field--wide">
                <span class="form-hint">Крупное декоративное слово за содержимым. Если оно не нужно, оставьте поле текста пустым — остальные параметры можно не трогать.</span>
            </div>
            <div class="form-field">
                <label for="watermark">Текст надписи</label>
                <input type="text" id="watermark" name="watermark" value="<?= $esc($data['watermark']) ?>" maxlength="120" placeholder="Например: aerion">
            </div>
            <?= $select('watermark_x', 'По горизонтали', [
                'left' => 'К левому краю', 'center' => 'По центру', 'right' => 'К правому краю',
            ], (string) $data['watermark_x']) ?>
            <?= $select('watermark_y', 'По вертикали', [
                'top' => 'К верху', 'middle' => 'По центру', 'bottom' => 'К низу',
            ], (string) $data['watermark_y']) ?>
            <div class="form-field">
                <label for="watermark_size">Размер, % ширины экрана</label>
                <input type="number" id="watermark_size" name="watermark_size" min="2" max="60" step="1" value="<?= (int) $data['watermark_size'] ?>">
            </div>
            <div class="form-field">
                <label for="watermark_opacity">Заметность, %</label>
                <input type="number" id="watermark_opacity" name="watermark_opacity" min="0" max="100" step="1" value="<?= (int) $data['watermark_opacity'] ?>">
                <span class="form-hint">Около 12 % — спокойный фон. Выше 30 % надпись становится заметным элементом.</span>
            </div>
            <?= $select('watermark_style', 'Начертание', ['fill' => 'Заливка', 'outline' => 'Только контур'], (string) $data['watermark_style']) ?>
            <?= $select('watermark_font', 'Шрифт', ['heading' => 'Заголовочный', 'body' => 'Основной'], (string) $data['watermark_font']) ?>
            <div class="form-field">
                <label for="watermark_stroke">Толщина контура, px</label>
                <input type="number" id="watermark_stroke" name="watermark_stroke" min="1" max="12" step="1" value="<?= (int) $data['watermark_stroke'] ?>">
            </div>
            <?= AdminUi::colorField('watermark_color', (string) $data['watermark_color'], 'Цвет надписи') ?>
            <div class="form-field">
                <label for="watermark_dx">Сдвиг вправо, %</label>
                <input type="number" id="watermark_dx" name="watermark_dx" min="-100" max="100" step="1" value="<?= (int) $data['watermark_dx'] ?>">
            </div>
            <div class="form-field">
                <label for="watermark_dy">Сдвиг вниз, %</label>
                <input type="number" id="watermark_dy" name="watermark_dy" min="-100" max="100" step="1" value="<?= (int) $data['watermark_dy'] ?>">
            </div>
        <?php echo $group(
            'Фоновая надпись',
            'редкий декоративный элемент',
            $watermarkState,
            (string) ob_get_clean(),
            $hasWatermark
        ); ?>

        <?php if ($translationLangs !== []): ?>
            <?php ob_start(); ?>
                <div class="form-field form-field--wide">
                    <span class="form-hint">
                        Текст и ссылки можно задавать отдельно для каждого языка. Фон, расположение и оформление остаются общими.
                        Пустое поле использует значение основного языка <?= $esc(strtoupper($defaultCode)) ?>.
                    </span>
                </div>
                <?php foreach ($translationLangs as $language): ?>
                    <?php
                    $code = (string) $language['code'];
                    $tr = $translations[$code] ?? [];
                    $key = 'translations[' . $code . ']';
                    $id = 'tr-' . preg_replace('/[^a-z0-9_-]/i', '', $code) . '-';
                    ?>
                    <fieldset class="form-grid form-field--wide">
                        <legend><?= $esc($language['name'] ?? strtoupper($code)) ?></legend>
                        <div class="form-field">
                            <label for="<?= $id ?>eyebrow">Надзаголовок</label>
                            <input type="text" id="<?= $id ?>eyebrow" name="<?= $key ?>[eyebrow]" value="<?= $esc($tr['eyebrow'] ?? '') ?>">
                        </div>
                        <div class="form-field">
                            <label for="<?= $id ?>title">Заголовок</label>
                            <input type="text" id="<?= $id ?>title" name="<?= $key ?>[title]" value="<?= $esc($tr['title'] ?? '') ?>">
                        </div>
                        <div class="form-field">
                            <label for="<?= $id ?>subtitle">Описание</label>
                            <textarea id="<?= $id ?>subtitle" name="<?= $key ?>[subtitle]" rows="3"><?= $esc($tr['subtitle'] ?? '') ?></textarea>
                        </div>
                        <div class="form-field">
                            <label for="<?= $id ?>cta">Текст основной кнопки</label>
                            <input type="text" id="<?= $id ?>cta" name="<?= $key ?>[cta_text]" value="<?= $esc($tr['cta_text'] ?? '') ?>">
                        </div>
                        <div class="form-field">
                            <label for="<?= $id ?>cta-url">Ссылка основной кнопки</label>
                            <input type="text" id="<?= $id ?>cta-url" name="<?= $key ?>[cta_url]" value="<?= $esc($tr['cta_url'] ?? '') ?>" placeholder="пусто — ссылка основного языка">
                        </div>
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
                        <div class="form-field">
                            <label for="<?= $id ?>art">Описание логотипа / картинки</label>
                            <input type="text" id="<?= $id ?>art" name="<?= $key ?>[art_alt]" value="<?= $esc($tr['art_alt'] ?? '') ?>">
                        </div>
                        <div class="form-field">
                            <label for="<?= $id ?>watermark">Фоновая надпись</label>
                            <input type="text" id="<?= $id ?>watermark" name="<?= $key ?>[watermark]" value="<?= $esc($tr['watermark'] ?? '') ?>" maxlength="120">
                        </div>
                    </fieldset>
                <?php endforeach; ?>
            <?php echo $group(
                'Переводы',
                'только текстовые поля',
                $translationState,
                (string) ob_get_clean(),
                $hasTranslations,
                'translations'
            ); ?>
        <?php endif; ?>

        <?php
        ob_start(); ?>
            <div class="form-field form-field--wide">
                <label for="css_class">CSS-класс слайда</label>
                <input type="text" id="css_class" name="css_class" value="<?= $esc($data['css_class']) ?>" placeholder="hero-programma-2030">
                <span class="form-hint">
                    Только для оформления, которого нет в настройках выше. Стили пишутся в поле «Свой CSS» у страницы.
                    Допустимы латиница, цифры, дефис и подчёркивание; класс должен начинаться с буквы.
                </span>
            </div>
        <?php echo $group(
            'Для разработчика',
            'свой CSS-класс',
            $hasCss ? (string) $data['css_class'] : 'Не используется',
            (string) ob_get_clean(),
            $hasCss
        ); ?>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary">Сохранить слайд</button>
        <a href="/admin/heroes/<?= $heroId ?>/edit" class="btn">К списку слайдов</a>
    </div>
</form>

<?php require __DIR__ . '/../layout/footer.php'; ?>
