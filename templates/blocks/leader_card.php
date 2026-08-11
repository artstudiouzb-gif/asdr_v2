<?php

use App\Core\Icon;

/**
 * Карточка руководителя: слева портрет и контакты, справа вкладки.
 *
 * Вкладки — прогрессивное улучшение. Сервер отдаёт обычные разделы с
 * заголовками и якорные ссылки на них: без JS посетитель видит всё содержимое
 * сразу, а ссылки просто прокручивают к разделу. Роли вкладок расставляет JS
 * (blocks/leader_card.js) — объявлять их в разметке нельзя, иначе без скрипта
 * скринридер обещает переключение, которого не произойдёт.
 *
 * @var array $data
 * @var int $blockId
 */
$photo = trim((string) ($data['photo'] ?? ''));
$name = trim((string) ($data['name'] ?? ''));
$position = trim((string) ($data['position'] ?? ''));
$phone = trim((string) ($data['phone'] ?? ''));
$email = trim((string) ($data['email'] ?? ''));
$hours = trim((string) ($data['hours'] ?? ''));

$socials = [];
foreach ([
    'facebook' => ['brand-facebook', 'Facebook'],
    'x' => ['brand-x', 'X'],
    'linkedin' => ['brand-linkedin', 'LinkedIn'],
    'instagram' => ['brand-instagram', 'Instagram'],
    'telegram' => ['brand-telegram', 'Telegram'],
] as $key => [$icon, $label]) {
    $url = trim((string) ($data[$key] ?? ''));
    if ($url !== '') {
        $socials[] = ['url' => $url, 'icon' => $icon, 'label' => $label];
    }
}

// Вкладка показывается, только если в ней что-то есть: пустая вкладка —
// обещание содержимого, которого нет.
$facts = array_values(array_filter(
    (array) ($data['items'] ?? []),
    static fn (array $row): bool => trim((string) ($row['label'] ?? '')) !== '' || trim((string) ($row['value'] ?? '')) !== ''
));
$bio = trim((string) ($data['bio'] ?? ''));
$duties = trim((string) ($data['duties'] ?? ''));

$tabs = [];
if ($facts !== []) {
    $tabs[] = ['key' => 'facts', 'title' => trim((string) ($data['facts_title'] ?? '')) ?: t('Основная информация')];
}
if ($bio !== '') {
    $tabs[] = ['key' => 'bio', 'title' => trim((string) ($data['bio_title'] ?? '')) ?: t('Биография')];
}
if ($duties !== '') {
    $tabs[] = ['key' => 'duties', 'title' => trim((string) ($data['duties_title'] ?? '')) ?: t('Функции')];
}

$panelId = static fn (string $key): string => 'leader-' . (int) $blockId . '-' . $key;
$esc = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES);
?>
<div class="leader-card"<?= $tabs !== [] ? ' data-leader-card' : '' ?>>
    <div class="leader-card__side">
        <?php if ($photo !== ''): ?>
            <div class="leader-card__photo">
                <?= \App\Core\Media::picture($photo, $name, null, null, 'leader-card__img', false, '(max-width: 720px) 40vw, 260px') ?>
            </div>
        <?php endif; ?>

        <div class="leader-card__info">
        <div class="leader-card__ident">
            <?php if ($name !== ''): ?><p class="leader-card__name"><?= $esc($name) ?></p><?php endif; ?>
            <?php if ($position !== ''): ?><p class="leader-card__position"><?= nl2br($esc($position)) ?></p><?php endif; ?>
        </div>

        <?php if ($phone !== '' || $email !== ''): ?>
            <ul class="leader-card__contacts">
                <?php if ($phone !== ''): ?>
                    <li class="leader-card__contact">
                        <span class="leader-card__contact-icon" aria-hidden="true"><?= Icon::render('phone', 18) ?></span>
                        <a href="tel:<?= $esc(preg_replace('/[^0-9+]/', '', $phone) ?? '') ?>"><?= $esc($phone) ?></a>
                    </li>
                <?php endif; ?>
                <?php if ($email !== ''): ?>
                    <li class="leader-card__contact">
                        <span class="leader-card__contact-icon" aria-hidden="true"><?= Icon::render('mail', 18) ?></span>
                        <a href="mailto:<?= $esc($email) ?>"><?= $esc($email) ?></a>
                    </li>
                <?php endif; ?>
            </ul>
        <?php endif; ?>

        <?php if ($socials !== []): ?>
            <ul class="leader-card__socials">
                <?php foreach ($socials as $social): ?>
                    <li>
                        <a class="leader-card__social" href="<?= $esc($social['url']) ?>" target="_blank" rel="noopener noreferrer">
                            <span aria-hidden="true"><?= Icon::render($social['icon'], 18) ?></span>
                            <span class="visually-hidden"><?= $esc($social['label']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($hours !== ''): ?>
            <p class="leader-card__hours">
                <span class="leader-card__contact-icon" aria-hidden="true"><?= Icon::render('clock', 18) ?></span>
                <?= $esc(t('Приёмные часы')) ?>: <?= $esc($hours) ?>
            </p>
        <?php endif; ?>
        </div>
    </div>

    <?php if ($tabs !== []): ?>
        <div class="leader-card__main">
            <nav class="leader-card__tabs" data-leader-tablist aria-label="<?= $esc(t('Разделы карточки')) ?>">
                <?php foreach ($tabs as $index => $tab): ?>
                    <a class="leader-card__tab<?= $index === 0 ? ' is-active' : '' ?>"
                       href="#<?= $esc($panelId($tab['key'])) ?>"
                       data-leader-tab="<?= $esc($tab['key']) ?>"><?= $esc($tab['title']) ?></a>
                <?php endforeach; ?>
            </nav>

            <?php foreach ($tabs as $tab): ?>
                <section class="leader-card__panel" id="<?= $esc($panelId($tab['key'])) ?>" data-leader-panel="<?= $esc($tab['key']) ?>">
                    <h3 class="leader-card__panel-title"><?= $esc($tab['title']) ?></h3>

                    <?php if ($tab['key'] === 'facts'): ?>
                        <dl class="leader-card__facts">
                            <?php foreach ($facts as $row): ?>
                                <div class="leader-card__fact">
                                    <dt class="leader-card__fact-label">
                                        <?php if (trim((string) ($row['icon_svg'] ?? '')) !== ''): ?>
                                            <span class="leader-card__fact-icon" aria-hidden="true"><?= Icon::render((string) $row['icon_svg'], 18) ?></span>
                                        <?php endif; ?>
                                        <?= $esc((string) ($row['label'] ?? '')) ?>
                                    </dt>
                                    <dd class="leader-card__fact-value"><?= nl2br($esc((string) ($row['value'] ?? ''))) ?></dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                    <?php elseif ($tab['key'] === 'bio'): ?>
                        <div class="leader-card__rich"><?= $bio ?></div>
                    <?php else: ?>
                        <div class="leader-card__rich"><?= $duties ?></div>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
