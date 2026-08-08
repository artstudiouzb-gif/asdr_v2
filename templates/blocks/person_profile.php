<?php
/** @var array $data */
$photo = trim((string) ($data['photo'] ?? ''));
$phone = trim((string) ($data['phone'] ?? ''));
$email = trim((string) ($data['email'] ?? ''));
$btnText = trim((string) ($data['button_text'] ?? ''));
$btnUrl = trim((string) ($data['button_url'] ?? ''));
?>
<div class="block-profile">
    <div class="profile__media">
        <?php if ($photo !== ''): ?>
            <?= \App\Core\Media::picture($photo, (string) ($data['name'] ?? ''), null, null, 'profile__img', false, '(max-width: 700px) 100vw, 35vw', false, 'profile__photo') ?>
        <?php else: ?>
            <?php // Портрета нет — вместо серого поля фирменная эмблема из CSS. ?>
            <span class="profile__photo profile__photo--empty" aria-hidden="true"></span>
        <?php endif; ?>
    </div>
    <div class="profile__info">
        <?php if (!empty($data['name'])): ?><?php $hTag = $data['_heading_tag'] ?? 'h1'; ?><<?= $hTag ?> class="profile__name"><?= htmlspecialchars((string) $data['name'], ENT_QUOTES) ?></<?= $hTag ?>><?php endif; ?>
        <?php if (!empty($data['position'])): ?><div class="profile__position"><?= htmlspecialchars((string) $data['position'], ENT_QUOTES) ?></div><?php endif; ?>
        <?php if (!empty($data['text'])): ?>
            <div class="profile__text rich-content">
                <?= \App\Core\HtmlSanitizer::sanitize((string) $data['text']) ?>
            </div>
        <?php endif; ?>
        <div class="profile__contacts">
            <?php if ($phone !== ''): ?>
                <span class="profile__contact">
                    <?= \App\Core\Icon::render('phone', 17, 'profile__contact-icon', 1.6) ?>
                    <span class="profile__contact-label"><?= htmlspecialchars((string) ($data['phone_label'] ?? ''), ENT_QUOTES) ?></span>
                    <a href="tel:<?= htmlspecialchars(preg_replace('/[^+\d]/', '', $phone) ?? '', ENT_QUOTES) ?>"><?= htmlspecialchars($phone, ENT_QUOTES) ?></a>
                </span>
            <?php endif; ?>
            <?php if ($email !== ''): ?>
                <span class="profile__contact">
                    <?= \App\Core\Icon::render('mail', 17, 'profile__contact-icon', 1.6) ?>
                    <span class="profile__contact-label"><?= htmlspecialchars((string) ($data['email_label'] ?? ''), ENT_QUOTES) ?></span>
                    <a href="mailto:<?= htmlspecialchars($email, ENT_QUOTES) ?>"><?= htmlspecialchars($email, ENT_QUOTES) ?></a>
                </span>
            <?php endif; ?>
        </div>
        <?php if ($btnText !== '' && $btnUrl !== ''): ?>
            <a class="profile__button" href="<?= htmlspecialchars($btnUrl, ENT_QUOTES) ?>"><?= htmlspecialchars($btnText, ENT_QUOTES) ?> →</a>
        <?php endif; ?>
    </div>
</div>
