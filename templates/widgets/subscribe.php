<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Core\Locale;
use App\Models\Setting;

/** @var array $data */
/** @var string $lang */

$subTitle = !empty($data['title']) ? (string) $data['title'] : t('Agentlik yangiliklariga obuna bo\'ling');
$subText = !empty($data['text']) ? (string) $data['text'] : t('Eng muhim yangiliklar va tahliliy materiallarni pochtangizga oling.');
?>
<div class="newsdetail-subscribe widget-subscribe-card" style="border-radius:14px;padding:22px;background:#18181b;color:#ffffff;box-shadow:0 8px 24px rgba(0,0,0,0.12);">
    <h3 class="newsdetail-subscribe__title" style="color:#ffffff;font-size:1.1rem;font-weight:700;margin-top:0;margin-bottom:8px;line-height:1.35;"><?= htmlspecialchars(t($subTitle), ENT_QUOTES) ?></h3>
    <p class="newsdetail-subscribe__text" style="color:#a1a1aa;font-size:0.88rem;margin-top:0;margin-bottom:16px;line-height:1.45;"><?= htmlspecialchars(t($subText), ENT_QUOTES) ?></p>
    <form class="newsdetail-subscribe__form" method="post" action="<?= htmlspecialchars(Locale::url('subscribe', $lang), ENT_QUOTES) ?>">
        <?= Csrf::field() ?>
        <input type="text" name="website" value="" style="position:absolute;left:-9999px" tabindex="-1" autocomplete="off" aria-hidden="true">
        <div class="newsdetail-subscribe__row" style="display:flex;gap:8px;align-items:center;">
            <label class="visually-hidden" for="w-sub-email"><?= htmlspecialchars(t('Sizning e-pochtangiz'), ENT_QUOTES) ?></label>
            <input id="w-sub-email" type="email" name="email" required placeholder="<?= htmlspecialchars(t('Sizning e-pochtangiz'), ENT_QUOTES) ?>" autocomplete="email" style="flex:1;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);color:#ffffff;padding:10px 14px;border-radius:8px;font-size:0.88rem;">
            <button type="submit" aria-label="<?= htmlspecialchars(t('Obuna bo\'lish'), ENT_QUOTES) ?>" style="background:var(--gov-teal,#0d9488);color:#ffffff;border:none;border-radius:8px;padding:10px 16px;cursor:pointer;font-weight:700;display:inline-flex;align-items:center;justify-content:center;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </button>
        </div>
        <?php if (Setting::get('form_consent_enabled', '0') === '1'): ?>
            <label class="newsdetail-subscribe__consent" style="color:#a1a1aa;font-size:0.78rem;margin-top:10px;display:flex;gap:6px;">
                <input type="checkbox" name="consent" value="1" required>
                <span><?= htmlspecialchars((string) Setting::get('form_consent_text', t('Я даю согласие на обработку персональных данных')), ENT_QUOTES) ?></span>
            </label>
        <?php endif; ?>
    </form>
</div>
