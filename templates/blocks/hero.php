<?php

use App\Core\UrlGuard;
use App\Core\Video;
use App\Core\Media;
use App\Core\AppUrl;
use App\Core\MediaPosition;

/** @var array $data */
$title = $data['title'] ?? '';
$eyebrow = trim((string) ($data['eyebrow'] ?? ''));
$subtitle = $data['subtitle'] ?? '';
$image = trim((string) ($data['image'] ?? ''));
$mediaPositionClasses = MediaPosition::classes($data['image_position'] ?? null, $data['image_position_mobile'] ?? null);

// Тип фона: none | image | video | youtube.
$bgType = (string) ($data['bg_type'] ?? 'none');
$bgType = in_array($bgType, ['none', 'image', 'video', 'youtube'], true) ? $bgType : 'none';
$videoFile = trim((string) ($data['video_url'] ?? ''));
$youtubeId = Video::youtubeId((string) ($data['youtube_url'] ?? ''));

$hasMedia = ($bgType === 'image' && $image !== '')
    || ($bgType === 'video' && $videoFile !== '')
    || ($bgType === 'youtube' && $youtubeId !== null);

// Overlay (полупрозрачная заливка поверх медиа) и подложка под текстом.
$hex2rgb = static function (string $hex): string {
    $hex = ltrim($hex, '#');
    return (int) hexdec(substr($hex, 0, 2)) . ',' . (int) hexdec(substr($hex, 2, 2)) . ',' . (int) hexdec(substr($hex, 4, 2));
};
$ovColor = preg_match('/^#[0-9a-f]{6}$/i', (string) ($data['overlay_color'] ?? '')) ? $data['overlay_color'] : '#0b1a30';
$overlayEnabled = !empty($data['overlay_enabled']);
$ovOpacity = max(0, min(100, (int) ($data['overlay_opacity'] ?? 35))) / 100;
$panelOn = !empty($data['panel_enabled']);
$panelColor = preg_match('/^#[0-9a-f]{6}$/i', (string) ($data['panel_color'] ?? '')) ? $data['panel_color'] : '#0b1a30';
$panelOpacity = max(0, min(100, (int) ($data['panel_opacity'] ?? 40))) / 100;
$textPos = in_array($data['text_position'] ?? 'left', ['left', 'center', 'right'], true) ? $data['text_position'] : 'left';
$heroText = preg_match('/^#[0-9a-f]{6}$/i', (string) ($data['text_color'] ?? '')) ? $data['text_color'] : '';
$heroBtn = preg_match('/^#[0-9a-f]{6}$/i', (string) ($data['button_color'] ?? '')) ? $data['button_color'] : '';
$rawOverlayDirection = (string) ($data['overlay_direction'] ?? 'auto');
$overlayMode = (string) ($data['overlay_mode'] ?? 'gradient');
$overlayMode = in_array($overlayMode, ['solid', 'gradient'], true) ? $overlayMode : 'gradient';
$overlayDirection = $rawOverlayDirection;
$overlayAngles = [
    'to_right' => '90deg',
    'to_left' => '270deg',
    'to_bottom' => '180deg',
    'to_top' => '0deg',
    'to_bottom_right' => '135deg',
    'to_bottom_left' => '225deg',
    'to_top_right' => '45deg',
    'to_top_left' => '315deg',
];
$overlaySolid = $overlayMode === 'solid';
if ($overlayDirection === 'auto' || !isset($overlayAngles[$overlayDirection])) {
    $overlayAngle = $textPos === 'right' ? '270deg' : ($textPos === 'center' ? '0deg' : '90deg');
} else {
    $overlayAngle = $overlayAngles[$overlayDirection];
}

// Инлайн-стиль контейнера текста: подложка + переопределения цветов через CSS-переменные.
$textStyle = ($panelOn ? '--hero-panel-bg:rgba(' . $hex2rgb($panelColor) . ', ' . $panelOpacity . ');' : '')
    . ($heroText !== '' ? '--hero-text:' . $heroText . ';' : '')
    . ($heroBtn !== '' ? '--hero-btn:' . $heroBtn . ';' : '');

// Свой цвет фона под текстом (для hero без фото/видео): полупрозрачный
// градиент выбранного цвета, не зависящий от светлой/тёмной темы. Отдаётся
// на корень hero — под медиа он не виден (там работает overlay), а на hero
// без медиа заменяет фон темы, который иначе менялся при переключении режима.
$heroBg = preg_match('/^#[0-9a-f]{6}$/i', (string) ($data['bg_color'] ?? '')) ? $data['bg_color'] : '';
$heroRootStyle = '';
if ($heroBg !== '') {
    $rgb = $hex2rgb($heroBg);
    // Направление градиента — от стороны с текстом к прозрачному краю.
    $dir = $textPos === 'right' ? '270deg' : ($textPos === 'center' ? '180deg' : '90deg');
    $heroRootStyle = '--hero-background:linear-gradient(' . $dir . ', rgba(' . $rgb . ',.96) 0%, rgba(' . $rgb . ',.92) 42%, rgba(' . $rgb . ',.55) 72%, rgba(' . $rgb . ',.12) 100%)'
        // Инлайновый background перебивает navy темы, и сквозь полупрозрачные
        // участки градиента просвечивал белый фон страницы — hero выглядел
        // светлее задуманного. Под медиа подкладываем сплошной navy-слой;
        // hero без медиа не трогаем (там градиент поверх фона темы задуман).
        . ($hasMedia ? ', linear-gradient(var(--gov-navy, #173a63), var(--gov-navy, #173a63))' : '')
        . ';';
}

$btnText = trim((string) ($data['button_text'] ?? ''));
$btnUrl = trim((string) ($data['button_url'] ?? ''));
$btn2Text = trim((string) ($data['button2_text'] ?? ''));
$btn2Url = trim((string) ($data['button2_url'] ?? ''));
/**
 * Иконка кнопки: своя картинка (SVG из медиабиблиотеки) важнее ключа Tabler.
 * Без иконки возвращается пустая строка — разметка кнопки не меняется.
 */
$heroButtonIcon = static function (string $iconName, string $iconImage): string {
    $iconImage = trim($iconImage);
    if ($iconImage !== '' && UrlGuard::isSafeMedia($iconImage)) {
        return '<img class="block-hero__button-icon" src="' . htmlspecialchars($iconImage, ENT_QUOTES)
            . '" alt="" aria-hidden="true" width="20" height="20">';
    }
    $iconName = \App\Core\Icon::cleanName($iconName);

    return $iconName !== ''
        ? '<span class="block-hero__button-icon" aria-hidden="true">' . \App\Core\Icon::render($iconName, 20, '', 2) . '</span>'
        : '';
};
$btnIcon = $heroButtonIcon((string) ($data['button_icon'] ?? ''), (string) ($data['button_icon_image'] ?? ''));
$btn2Icon = $heroButtonIcon((string) ($data['button2_icon'] ?? ''), (string) ($data['button2_icon_image'] ?? ''));

$vBtnText = trim((string) ($data['video_button_text'] ?? ''));
$vBtnUrl = trim((string) ($data['video_button_url'] ?? ''));

// Своя ширина текстовой колонки: px (200–2000) или %/vw (10–100).
// Пусто/невалидно — ширина темы (560/620px). Отдаётся CSS-переменной.
$textWidth = (string) ($data['text_width'] ?? '');
if ($textWidth !== '' && preg_match('/^(\d+(?:\.\d+)?)(px|%|vw)$/', $textWidth, $twParts)) {
    $twValue = (float) $twParts[1];
    $twLimits = $twParts[2] === 'px' ? [200.0, 2000.0] : [10.0, 100.0];
    $twValue = max($twLimits[0], min($twLimits[1], $twValue));
    $twNumber = rtrim(rtrim(number_format($twValue, 1, '.', ''), '0'), '.');
    $heroRootStyle .= '--hero-text-width:' . $twNumber . $twParts[2] . ';';
}

$heroWidth = ($data['width'] ?? 'full') === 'standard' ? 'standard' : 'full';
$heroHeight = in_array($data['height'] ?? 'regular', ['regular', 'full', 'custom'], true) ? $data['height'] : 'regular';
$customHeight = (string) ($data['custom_height'] ?? '720px');
if ($heroHeight === 'custom' && preg_match('/^(\d+(?:\.\d+)?)(px|vh|dvh|rem)$/', $customHeight, $heightParts)) {
    $heightValue = (float) $heightParts[1];
    $heightUnit = $heightParts[2];
    $heightLimits = $heightUnit === 'px' ? [160.0, 2000.0]
        : ($heightUnit === 'rem' ? [10.0, 120.0] : [20.0, 150.0]);
    $heightValue = max($heightLimits[0], min($heightLimits[1], $heightValue));
    $heightNumber = rtrim(rtrim(number_format($heightValue, 1, '.', ''), '0'), '.');
    $heroRootStyle .= '--hero-custom-height:' . $heightNumber . $heightUnit . ';';
}
$scrimStyle = '--hero-scrim-rgb:' . $hex2rgb($ovColor)
    . ';--hero-scrim-a:' . $ovOpacity
    . ';--hero-scrim-direction:' . $overlayAngle . ';';
$templateCss = ($heroRootStyle !== '' ? '#block-' . $blockId . ' .block-hero{' . $heroRootStyle . '}' : '')
    . ($hasMedia && $overlayEnabled ? "\n#block-" . $blockId . ' .block-hero__scrim{' . $scrimStyle . '}' : '')
    . ($textStyle !== '' ? "\n#block-" . $blockId . ' .block-hero__text{' . $textStyle . '}' : '');

$youtubeEmbedUrl = '';
if ($bgType === 'youtube' && $youtubeId !== null) {
    $youtubeParams = [
        'autoplay' => '1',
        'mute' => '1',
        'loop' => '1',
        'playlist' => $youtubeId,
        'controls' => '0',
        'playsinline' => '1',
        'disablekb' => '1',
        'fs' => '0',
        'enablejsapi' => '1',
    ];
    $youtubeOrigin = AppUrl::base();
    if ($youtubeOrigin !== '') {
        $youtubeParams['origin'] = $youtubeOrigin;
    }
    $youtubeEmbedUrl = 'https://www.youtube-nocookie.com/embed/' . $youtubeId
        . '?' . http_build_query($youtubeParams, '', '&', PHP_QUERY_RFC3986);
}
?>
<?php // Без медиа и без своего фона hero — это просто шапка страницы:
      // карточка с рамкой и подложкой в этой роли читается как чужой блок. ?>
<div class="block-hero<?= $hasMedia ? ' block-hero--media' : '' ?><?= (!$hasMedia && $heroBg === '') ? ' block-hero--plain' : '' ?><?= $heroBg !== '' ? ' block-hero--bgcolor' : '' ?><?= ($bgType === 'video' || $bgType === 'youtube') ? ' block-hero--video' : '' ?> block-hero--w-<?= $heroWidth ?> block-hero--h-<?= $heroHeight ?> block-hero--pos-<?= $textPos ?>">
    <?php if ($bgType === 'video' && $videoFile !== ''): ?>
        <video class="block-hero__video <?= $mediaPositionClasses ?>" data-hero-background-video autoplay muted loop playsinline webkit-playsinline preload="metadata"
               disablepictureinpicture disableremoteplayback controlslist="nodownload nofullscreen noremoteplayback noplaybackrate"
               tabindex="-1" <?= $image !== '' ? 'poster="' . htmlspecialchars($image, ENT_QUOTES) . '"' : '' ?> aria-hidden="true">
            <source src="<?= htmlspecialchars($videoFile, ENT_QUOTES) ?>" type="video/mp4">
        </video>
    <?php elseif ($bgType === 'youtube' && $youtubeId !== null): ?>
        <?php if ($image !== '' && UrlGuard::isSafeMedia($image)): ?>
            <img
                class="block-hero__youtube-poster <?= $mediaPositionClasses ?>"
                src="<?= htmlspecialchars($image, ENT_QUOTES) ?>"
                alt=""
                loading="eager"
                decoding="async"
                fetchpriority="high"
                aria-hidden="true"
            >
        <?php endif; ?>
        <div class="block-hero__yt" data-hero-youtube-container aria-hidden="true">
            <iframe
                data-hero-youtube-background
                data-src="<?= htmlspecialchars($youtubeEmbedUrl, ENT_QUOTES) ?>"
                tabindex="-1"
                loading="lazy"
                referrerpolicy="strict-origin-when-cross-origin"
                allow="autoplay; encrypted-media"
                aria-hidden="true"
            ></iframe>
        </div>
    <?php elseif ($bgType === 'image' && $image !== ''): ?>
        <?= Media::picture($image, '', null, null, 'block-hero__image ' . $mediaPositionClasses, false, '100vw', true, 'block-hero__media ' . $mediaPositionClasses) ?>
    <?php endif; ?>
    <?php if ($hasMedia && $overlayEnabled): ?><div class="block-hero__scrim<?= $overlaySolid ? ' block-hero__scrim--solid' : '' ?>" aria-hidden="true"></div><?php endif; ?>
    <div class="block-hero__inner">
        <div class="block-hero__text<?= $panelOn ? ' block-hero__text--panel' : '' ?>">
            <?php if ($eyebrow !== ''): ?><span class="block-hero__eyebrow"><?= htmlspecialchars($eyebrow, ENT_QUOTES) ?></span><?php endif; ?>
            <?php if ($title !== ''): ?><?php $hTag = $data['_heading_tag'] ?? 'h1'; ?><<?= $hTag ?> class="block-hero__title"><?= htmlspecialchars($title, ENT_QUOTES) ?></<?= $hTag ?>><?php endif; ?>
            <?php if ($subtitle !== ''): ?><p class="block-hero__subtitle"><?= htmlspecialchars($subtitle, ENT_QUOTES) ?></p><?php endif; ?>
            <?php if (($btnText !== '' && $btnUrl !== '') || ($btn2Text !== '' && $btn2Url !== '') || ($vBtnText !== '')): ?>
            <div class="block-hero__actions">
                <?php if ($btnText !== '' && $btnUrl !== '' && UrlGuard::isSafeLink($btnUrl)): ?>
                    <a class="block-hero__button" href="<?= htmlspecialchars($btnUrl, ENT_QUOTES) ?>"><?= $btnIcon ?><?= htmlspecialchars($btnText, ENT_QUOTES) ?> →</a>
                <?php endif; ?>
                <?php if ($btn2Text !== '' && $btn2Url !== '' && UrlGuard::isSafeLink($btn2Url)): ?>
                    <a class="block-hero__button block-hero__button--ghost" href="<?= htmlspecialchars($btn2Url, ENT_QUOTES) ?>"><?= $btn2Icon ?><?= htmlspecialchars($btn2Text, ENT_QUOTES) ?> →</a>
                <?php endif; ?>
                <?php if ($vBtnText !== ''): ?>
                    <?php $vSafe = $vBtnUrl !== '' && UrlGuard::isSafeLink($vBtnUrl); ?>
                    <<?= $vSafe ? 'a' : 'span' ?> class="block-hero__play"<?= $vSafe ? ' href="' . htmlspecialchars($vBtnUrl, ENT_QUOTES) . '"' : '' ?>>
                        <span class="block-hero__play-icon" aria-hidden="true"><?= \App\Core\Icon::render('player-play', 24) ?></span>
                        <span class="block-hero__play-label"><?= htmlspecialchars($vBtnText, ENT_QUOTES) ?></span>
                    </<?= $vSafe ? 'a' : 'span' ?>>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
