<?php

use App\Core\Csrf;
use App\Core\Locale;
use App\Models\Setting;

/** @var array $data */
/** @var int $blockId */
?>
<div class="container">
    <div class="subscribe-block">
        <?php if (!empty($data['title'])): ?>
            <h2 class="subscribe-block__title"><?= htmlspecialchars((string) $data['title'], ENT_QUOTES) ?></h2>
        <?php endif; ?>
        <?php if (!empty($data['text'])): ?>
            <p class="subscribe-block__text"><?= htmlspecialchars((string) $data['text'], ENT_QUOTES) ?></p>
        <?php endif; ?>
        <form class="subscribe-block__form" method="post" action="<?= htmlspecialchars(Locale::url('subscribe'), ENT_QUOTES) ?>">
            <?= Csrf::field() ?>
            <?= Csrf::honeypotField() ?>
            <input type="hidden" name="source" value="block">
            <label class="visually-hidden" for="subscribe-email-<?= (int) $blockId ?>">Email</label>
            <input type="email" id="subscribe-email-<?= (int) $blockId ?>" name="email" placeholder="<?= htmlspecialchars(t('Ваш e-mail'), ENT_QUOTES) ?>" autocomplete="email" required>
            <button type="submit" class="btn btn--primary"><?= htmlspecialchars((string) ($data['button_text'] ?: t('Подписаться')), ENT_QUOTES) ?></button>
            <?php if (Setting::get('form_consent_enabled', '0') === '1'): ?>
                <label class="subscribe-block__consent">
                    <input type="checkbox" name="consent" value="1" required>
                    <span><?= htmlspecialchars((string) Setting::get('form_consent_text', t('Я даю согласие на обработку персональных данных')), ENT_QUOTES) ?></span>
                </label>
            <?php endif; ?>
        </form>
    </div>
</div>
