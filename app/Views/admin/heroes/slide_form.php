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

$inherit = ['' => 'Как у обложки'];
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
?>
<p>
    <a href="/admin/heroes/<?= $heroId ?>/edit" class="btn btn--small">← К обложке «<?= $esc($hero['name']) ?>»</a>
</p>

<form method="post" action="/admin/heroes/<?= $heroId ?>/slides/<?= $slideId ?>/update">
    <?= Csrf::field() ?>

    <div class="form-card">
        <?= AdminUi::cardHeader('Текст', 'typography') ?>
        <div class="form-grid">
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
        </div>
    </div>

    <div class="form-card">
        <?= AdminUi::cardHeader('Фон слайда', 'photo') ?>
        <p class="form-hint">
            Фон — это подложка, а не содержимое: он не влияет на высоту обложки и не
            даёт горизонтальной прокрутки. Кадр-замена обязателен для видео: он же постер
            до старта, он же то, что увидит посетитель, если видео показать нельзя.
        </p>
        <div class="form-grid">
            <?= $select('media_type', 'Тип фона', [
                'none' => 'Без фона',
                'image' => 'Изображение',
                'video' => 'Загруженное видео (MP4)',
                'youtube' => 'Видео с YouTube',
            ], (string) $data['media_type']) ?>
            <?= AdminUi::imageField('image', (string) $data['image'], [
                'label' => 'Изображение (десктоп)',
                'hint' => 'Оно же кадр-замена, если поле «Кадр-замена» пустое.',
            ]) ?>
            <?= AdminUi::imageField('image_mobile', (string) $data['image_mobile'], [
                'label' => 'Изображение для телефона',
                'hint' => 'Отдельный кадр под узкий экран: широкая фотография там режется до полоски. Пусто — берётся десктопное.',
            ]) ?>
            <?= AdminUi::mediaPositionFields((string) $data['image_position'], (string) $data['image_position_mobile']) ?>
            <?= $select('image_fit', 'Режим отображения', [
                'cover' => 'Заполнить область (обрезать лишнее)',
                'contain' => 'Вписать целиком',
            ], (string) $data['image_fit']) ?>
            <div class="form-field">
                <label for="video_url">Видео MP4</label>
                <input type="text" id="video_url" name="video_url" value="<?= $esc($data['video_url']) ?>" placeholder="/uploads/public/hero.mp4">
                <span class="form-hint">Проигрывается без звука и по кругу; звуковой дорожкой в фоне пользоваться нельзя.</span>
            </div>
            <div class="form-field">
                <label for="video_mobile_url">Отдельное видео для телефона</label>
                <input type="text" id="video_mobile_url" name="video_mobile_url" value="<?= $esc($data['video_mobile_url']) ?>" placeholder="/uploads/public/hero-mobile.mp4">
            </div>
            <div class="form-field">
                <label for="youtube_url">Ссылка на YouTube</label>
                <input type="text" id="youtube_url" name="youtube_url" value="<?= $esc($data['youtube_url']) ?>" placeholder="https://www.youtube.com/watch?v=XXXXXXXXXXX">
                <span class="form-hint">
                    Подойдёт обычная ссылка (watch?v=…, youtu.be/…) — идентификатор ролика система выделит сама.
                    <?php if ($data['youtube_id'] !== ''): ?>
                        Сейчас распознан: <code><?= $esc($data['youtube_id']) ?></code>.
                    <?php endif; ?>
                </span>
            </div>
            <?= AdminUi::imageField('poster', (string) $data['poster'], [
                'label' => 'Кадр-замена (poster / fallback)',
                'hint' => 'Показывается до старта видео и вместо него, если автовоспроизведение запрещено, ролик недоступен, видео выключено на телефоне или посетитель включил «меньше движения».',
            ]) ?>
            <?= $select('mobile_media', 'Видео на телефоне', [
                'image' => 'Заменить изображением (рекомендуется)',
                'desktop' => 'Проигрывать то же видео',
                'mobile_video' => 'Проигрывать отдельное мобильное видео',
            ], (string) $data['mobile_media'],
                'Фоновое видео на мобильном трафике стоит дороже, чем даёт: по умолчанию телефону достаётся кадр-замена.') ?>
        </div>
    </div>

    <div class="form-card">
        <?= AdminUi::cardHeader('Кнопки', 'hand-click') ?>
        <div class="form-grid">
            <?= $checkbox('cta_enabled', 'Показывать основную кнопку', (bool) $data['cta_enabled']) ?>
            <div class="form-field">
                <label for="cta_text">Текст основной кнопки</label>
                <input type="text" id="cta_text" name="cta_text" value="<?= $esc($data['cta_text']) ?>">
            </div>
            <div class="form-field">
                <label for="cta_url">Ссылка основной кнопки</label>
                <input type="text" id="cta_url" name="cta_url" value="<?= $esc($data['cta_url']) ?>" placeholder="/about">
            </div>
            <?= $select('cta_style', 'Тип основной кнопки', $ctaStyles, (string) $data['cta_style']) ?>
            <?= AdminUi::iconField('cta_icon', (string) $data['cta_icon'], ['label' => 'Иконка основной кнопки']) ?>
            <?= $checkbox('cta_new_tab', 'Открывать в новой вкладке', (bool) $data['cta_new_tab']) ?>

            <?= $checkbox('cta2_enabled', 'Показывать дополнительную кнопку', (bool) $data['cta2_enabled']) ?>
            <div class="form-field">
                <label for="cta2_text">Текст дополнительной кнопки</label>
                <input type="text" id="cta2_text" name="cta2_text" value="<?= $esc($data['cta2_text']) ?>">
            </div>
            <div class="form-field">
                <label for="cta2_url">Ссылка дополнительной кнопки</label>
                <input type="text" id="cta2_url" name="cta2_url" value="<?= $esc($data['cta2_url']) ?>">
            </div>
            <?= $select('cta2_style', 'Тип дополнительной кнопки', $ctaStyles, (string) $data['cta2_style']) ?>
            <?= AdminUi::iconField('cta2_icon', (string) $data['cta2_icon'], ['label' => 'Иконка дополнительной кнопки']) ?>
            <?= $checkbox('cta2_new_tab', 'Открывать в новой вкладке', (bool) $data['cta2_new_tab']) ?>
        </div>
    </div>

    <div class="form-card">
        <?= AdminUi::cardHeader('Ссылка со всего слайда', 'link') ?>
        <div class="form-grid">
            <div class="form-field">
                <label for="link_url">Адрес</label>
                <input type="text" id="link_url" name="link_url" value="<?= $esc($data['link_url']) ?>">
                <span class="form-hint">Кликается весь слайд; кнопки при этом остаются самостоятельными ссылками.</span>
            </div>
            <?= $checkbox('link_new_tab', 'Открывать в новой вкладке', (bool) $data['link_new_tab']) ?>
        </div>
    </div>

    <div class="form-card">
        <?= AdminUi::cardHeader('Цвет и затемнение слайда', 'palette') ?>
        <p class="form-hint">Пустое значение означает «как у обложки» — так задаётся исключение для одного светлого кадра, не трогая остальные слайды.</p>
        <div class="form-grid">
            <?= $select('scheme', 'Цветовая схема', $inherit + [
                'light' => 'Light', 'dark' => 'Dark', 'navy' => 'Navy', 'custom' => 'Custom',
            ], (string) $data['scheme']) ?>
            <?= AdminUi::colorField('scheme_bg', (string) $data['scheme_bg'], 'Свой фон (Custom)', '#0b1a30', 'Как у обложки') ?>
            <?= AdminUi::colorField('scheme_text', (string) $data['scheme_text'], 'Свой цвет текста (Custom)', '#ffffff', 'Как у обложки') ?>
            <?= AdminUi::colorField('scheme_accent', (string) $data['scheme_accent'], 'Цвет основной кнопки', '#173a63', 'Как у обложки') ?>
            <?= $select('content_scheme', 'Цвет текста', $inherit + [
                'auto' => 'Auto — по фону и затемнению',
                'light' => 'Light — светлый текст',
                'dark' => 'Dark — тёмный текст',
            ], (string) $data['content_scheme']) ?>
            <?= $select('overlay', 'Затемнение', $inherit + [
                'none' => 'Нет', 'solid' => 'Сплошное', 'gradient' => 'Градиент',
            ], (string) $data['overlay']) ?>
            <?= AdminUi::colorField('overlay_color', (string) $data['overlay_color'], 'Цвет затемнения', '#0b1a30', 'Как у обложки') ?>
            <div class="form-field">
                <label for="overlay_opacity">Плотность затемнения, %</label>
                <input type="number" id="overlay_opacity" name="overlay_opacity" min="0" max="100"
                       value="<?= (int) $data['overlay_opacity'] >= 0 ? (int) $data['overlay_opacity'] : '' ?>" placeholder="как у обложки">
            </div>
            <?= $select('overlay_direction', 'Направление градиента', $inherit + [
                'auto' => 'Автоматически',
                'to_right' => 'Слева направо', 'to_left' => 'Справа налево',
                'to_bottom' => 'Сверху вниз', 'to_top' => 'Снизу вверх',
                'to_bottom_right' => 'В правый нижний угол', 'to_bottom_left' => 'В левый нижний угол',
                'to_top_right' => 'В правый верхний угол', 'to_top_left' => 'В левый верхний угол',
            ], (string) $data['overlay_direction']) ?>
            <?= $select('panel', 'Подложка под текстом', $inherit + ['on' => 'Показывать', 'off' => 'Не показывать'], (string) $data['panel']) ?>
        </div>
    </div>

    <div class="form-card">
        <?= AdminUi::cardHeader('Раскладка слайда', 'layout-align-left') ?>
        <div class="form-grid">
            <?= $select('text_position', 'Текст по горизонтали', $inherit + $posOptions, (string) $data['text_position']) ?>
            <?= $select('text_align_y', 'Текст по вертикали', $inherit + $yOptions, (string) $data['text_align_y']) ?>
            <?= $select('title_size', 'Размер заголовка', $inherit + $sizeOptions, (string) $data['title_size']) ?>
            <?= $select('subtitle_size', 'Размер описания', $inherit + $subtitleSizes, (string) $data['subtitle_size']) ?>
            <?= $select('text_position_mobile', 'Текст по горизонтали (телефон)', $inherit + $posOptions, (string) $data['text_position_mobile']) ?>
            <?= $select('text_align_y_mobile', 'Текст по вертикали (телефон)', $inherit + $yOptions, (string) $data['text_align_y_mobile']) ?>
            <?= $select('title_size_mobile', 'Размер заголовка (телефон)', $inherit + $sizeOptions, (string) $data['title_size_mobile']) ?>
            <?= $select('subtitle_size_mobile', 'Размер описания (телефон)', $inherit + $subtitleSizes, (string) $data['subtitle_size_mobile']) ?>
        </div>
    </div>

    <div class="form-card">
        <?= AdminUi::cardHeader('Картинка поверх фона', 'sparkles') ?>
        <p class="form-hint">Эмблема, логотип программы, иллюстрация. Кладётся рядом с текстом, а не под него.</p>
        <div class="form-grid">
            <?= AdminUi::imageField('art_image', (string) $data['art_image'], ['label' => 'Картинка']) ?>
            <div class="form-field">
                <label for="art_alt">Описание картинки</label>
                <input type="text" id="art_alt" name="art_alt" value="<?= $esc($data['art_alt']) ?>" placeholder="например: Логотип программы «Цифровой Узбекистан»">
                <span class="form-hint">Пусто — картинка считается декоративной и скрывается от диктора. Для логотипа программы описание обязательно.</span>
            </div>
            <?= $select('art_position', 'Где показывать', ['above' => 'Над текстом', 'left' => 'Слева', 'right' => 'Справа'], (string) $data['art_position']) ?>
            <?= $select('art_size', 'Размер', ['small' => 'Маленькая', 'medium' => 'Средняя', 'large' => 'Крупная'], (string) $data['art_size']) ?>
        </div>
    </div>

    <div class="form-card">
        <?= AdminUi::cardHeader('Показ слайда', 'clock') ?>
        <div class="form-grid">
            <div class="form-field">
                <label for="duration">Длительность показа, секунд</label>
                <input type="number" id="duration" name="duration" min="0" max="120" step="1"
                       value="<?= (int) $data['duration'] > 0 ? (int) $data['duration'] : '' ?>"
                       placeholder="как у обложки">
                <span class="form-hint">
                    Пусто или 0 — слайд держится столько же, сколько остальные (интервал задан
                    в настройках обложки). Своё значение нужно там, где времени на чтение
                    требуется больше: длинный заголовок или плотная инфографика.
                </span>
            </div>
        </div>
        <p class="form-hint">Слайд вне окна показа вообще не попадает в разметку страницы. Кэш страницы пересобирается к границе окна автоматически.</p>
        <div class="form-grid">
            <div class="form-field">
                <label for="_visible_from">Показывать с</label>
                <input type="datetime-local" id="_visible_from" name="_visible_from" value="<?= $esc(BlockVisibility::forInput($data['_visible_from'])) ?>">
            </div>
            <div class="form-field">
                <label for="_visible_to">Показывать до</label>
                <input type="datetime-local" id="_visible_to" name="_visible_to" value="<?= $esc(BlockVisibility::forInput($data['_visible_to'])) ?>">
            </div>
        </div>
    </div>

    <?php if ($translationLangs !== []): ?>
        <div class="form-card" id="translations">
            <?= AdminUi::cardHeader('Переводы текста', 'globe') ?>
            <p class="form-hint">
                Переводится только текст: медиа, цвета и раскладка у слайда общие для всех языков.
                Пустое поле — не ошибка: на этом языке покажется текст основного языка
                (<?= $esc(strtoupper($defaultCode)) ?>).
            </p>
            <?php foreach ($translationLangs as $language): ?>
                <?php
                $code = (string) $language['code'];
                $tr = $translations[$code] ?? [];
                $key = 'translations[' . $code . ']';
                $id = 'tr-' . preg_replace('/[^a-z0-9_-]/i', '', $code) . '-';
                ?>
                <fieldset class="form-grid">
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
                        <label for="<?= $id ?>cta2">Текст дополнительной кнопки</label>
                        <input type="text" id="<?= $id ?>cta2" name="<?= $key ?>[cta2_text]" value="<?= $esc($tr['cta2_text'] ?? '') ?>">
                    </div>
                    <div class="form-field">
                        <label for="<?= $id ?>art">Описание картинки поверх фона</label>
                        <input type="text" id="<?= $id ?>art" name="<?= $key ?>[art_alt]" value="<?= $esc($tr['art_alt'] ?? '') ?>">
                    </div>
                </fieldset>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="form-actions">
        <button type="submit" class="btn btn--primary">Сохранить слайд</button>
        <a href="/admin/heroes/<?= $heroId ?>/edit" class="btn">К списку слайдов</a>
    </div>
</form>

<?php require __DIR__ . '/../layout/footer.php'; ?>
