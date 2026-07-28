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
            <?= \App\Core\Media::picture($photo, (string) ($data['name'] ?? ''), null, null, 'profile__photo', false, '(max-width: 700px) 100vw, 35vw') ?>
        <?php else: ?>
            <span class="profile__photo profile__photo--empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" width="64" height="64"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-6 8-6s8 2 8 6"/></svg>
            </span>
        <?php endif; ?>
    </div>
    <div class="profile__info">
        <?php if (!empty($data['name'])): ?><?php $hTag = $data['_heading_tag'] ?? 'h1'; ?><<?= $hTag ?> class="profile__name"><?= htmlspecialchars((string) $data['name'], ENT_QUOTES) ?></<?= $hTag ?>><?php endif; ?>
        <?php if (!empty($data['position'])): ?><div class="profile__position"><?= htmlspecialchars((string) $data['position'], ENT_QUOTES) ?></div><?php endif; ?>
        <?php if (!empty($data['text'])): ?><p class="profile__text"><?= nl2br(htmlspecialchars((string) $data['text'], ENT_QUOTES)) ?></p><?php endif; ?>
        <div class="profile__contacts">
            <?php if ($phone !== ''): ?>
                <span class="profile__contact">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" width="17" height="17"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>
                    <span class="profile__contact-label"><?= htmlspecialchars((string) ($data['phone_label'] ?? ''), ENT_QUOTES) ?></span>
                    <a href="tel:<?= htmlspecialchars(preg_replace('/[^+\d]/', '', $phone) ?? '', ENT_QUOTES) ?>"><?= htmlspecialchars($phone, ENT_QUOTES) ?></a>
                </span>
            <?php endif; ?>
            <?php if ($email !== ''): ?>
                <span class="profile__contact">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" width="17" height="17"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg>
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
