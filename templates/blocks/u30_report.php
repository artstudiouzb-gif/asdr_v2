<?php

/**
 * Блок «Мониторинг стратегии “Oʻzbekiston — 2030”».
 *
 * Документ целиком: тексты, таблицы и разделы — в разметке, диаграммы
 * дорисовывает /assets/js/blocks/u30_report.js по данным внутри скрипта.
 * Полей у блока нет — это готовый отчёт, он правится вместе с шаблоном.
 *
 * Разметка идёт от <div id="uz2030-report">: секцию-обёртку с id="block-N"
 * добавляет BlockRenderer. Второго такого блока на странице быть не должно —
 * и стили, и скрипт находят документ по этому идентификатору.
 *
 * @var array $data
 * @var int $blockId
 */

// Первый претендент на h1 получает h1, следующий — h2 (BlockRenderer::H1_BLOCKS).
$headingTag = (($data['_heading_tag'] ?? 'h1') === 'h1') ? 'h1' : 'h2';
$logoSrc = \App\Core\Asset::url('/assets/img/u30-strategy-logo.svg');
?>
<div aria-label="Oʻzbekiston — 2030 strategiyasi monitoring hisoboti" class="u30-report" data-report-component="" id="uz2030-report">
<div class="u30-shell">
<div class="u30-content" id="u30-top">

<header class="u30-hero u30-reveal" id="u30-overview">
<div class="u30-hero__meta">
<span class="u30-kicker">2026-yil · I yarim yillik · 1-iyul holatiga</span>
<div class="u30-hero__tools">
<span class="u30-status-dot"><i aria-hidden="true"></i>Raqamli monitoring platformasi</span>
<div aria-label="Hisobot amallari" class="u30-hero__actions">
<button aria-label="Animatsiyalarni qayta ishga tushirish" class="u30-icon-button" data-u30-replay="" title="Animatsiyalarni qayta ishga tushirish" type="button">
<?= \App\Core\Icon::render('refresh', 18) ?>
</button>
<button aria-label="Hisobotni chop etish" class="u30-icon-button" data-u30-print="" title="Chop etish / PDF" type="button">
<?= \App\Core\Icon::render('printer', 18) ?>
</button>
</div>
</div>
</div>
<div class="u30-hero__grid">
<div>
<<?= $headingTag ?>>“Oʻzbekiston — 2030” strategiyasining I-yarim yillik monitoringi natijalari</<?= $headingTag ?>>
<p class="u30-lead">Strategik rejalashtirish va rivojlanishning raqamli platformasi orqali 72 ta tashkilot kesimida 437 ta samaradorlik koʻrsatkichi monitoring qilindi.</p>
</div>
<div class="u30-hero__seal u30-hero__seal--brand">
<img class="u30-brand-logo" src="<?= htmlspecialchars($logoSrc, ENT_QUOTES) ?>" alt="“Oʻzbekiston — 2030” strategiyasi logotipi" width="160" height="302" loading="lazy" decoding="async">
</div>
</div>
<div aria-label="Monitoringning asosiy ko‘rsatkichlari" class="u30-stat-grid">
<article class="u30-stat-card u30-stat-card--blue">
<span class="u30-stat-card__label">Jami strategiya koʻrsatkichlari</span>
<strong data-count="437">437</strong>
<small>raqamli platformada</small>
</article>
<article class="u30-stat-card">
<span class="u30-stat-card__label">I-yarim yillik yakuni bo‘yicha baholangan</span>
<strong data-count="206">206</strong>
<small>toifalangan koʻrsatkich</small>
</article>
</div>
<div class="u30-intro-grid">
<div class="u30-prose">
<p>Hisobot davrida Strategik rejalashtirish va rivojlanishning raqamli platformasi toʻliq ishga tushirildi. Tizim orqali samaradorlik koʻrsatkichlariga masʼul 72 ta tashkilot tomonidan maʼlumotlarni kiritib borish hamda monitoring qilish tartibi yoʻlga qoʻyildi.</p>
<p>Farmonda 437 ta koʻrsatkichdan 13 tasi uchun 2026-yilga maqsadli qiymat belgilanmagan — ular joriy yil bahosidan tashqarida. Qolgan 424 ta koʻrsatkichdan 206 tasining ijro holati I-yarim yillik yakuni boʻyicha Farmonda belgilangan tartibda baholanib, uch toifaga ajratildi; 218 tasi yil yakunida baholanadi.</p>
</div>
<div aria-label="Ijro toifalari" class="u30-classification">
<div class="u30-classification__item u30-classification__item--green">
<span class="u30-classification__swatch"></span>
<div><strong><span data-count="162">162</span> ta</strong><small>Oʻz vaqtida va toʻliq erishilgan (“yashil”) koʻrsatkichlar</small></div>
<b>79%</b>
</div>
<div class="u30-classification__item u30-classification__item--yellow">
<span class="u30-classification__swatch"></span>
<div><strong><span data-count="35">35</span> ta</strong><small>Bartaraf etish imkoni bor, lekin kechikishlar kuzatilayotgan (“sariq”) koʻrsatkichlar</small></div>
<b>17%</b>
</div>
<div class="u30-classification__item u30-classification__item--red">
<span class="u30-classification__swatch"></span>
<div><strong><span data-count="9">9</span> ta</strong><small>Jiddiy kechikishdagi (“qizil”) koʻrsatkichlar</small></div>
<b>4%</b>
</div>
</div>
</div>
</header>

<section aria-labelledby="u30-directions-title" class="u30-section u30-reveal" id="u30-directions">
<div class="u30-section-head">
<div>
<span class="u30-eyebrow">1-diagramma</span>
<h2 id="u30-directions-title">Strategiya ustuvor yoʻnalishlari boʻyicha ijro holati</h2>
</div>
<div class="u30-info-panel u30-info-panel--blue" aria-label="Diagrammani o‘qish tartibi">
<ul class="u30-info-points">
<li><strong class="u30-info-value">100%</strong><span>Har bir yoʻnalish alohida shkala sifatida ko‘rsatiladi.</span></li>
<li><strong class="u30-info-value">3 rang</strong><span>Yashil, sariq va qizil segmentlar shu yoʻnalishdagi jami ko‘rsatkichlar tarkibini bildiradi.</span></li>
</ul>
</div>
</div>
<div class="u30-chart-card u30-chart-card--wide">
<div class="u30-chart-toolbar">
<div aria-label="Diagramma belgilarining izohi" class="u30-legend">
<span class="u30-legend-rich"><i class="u30-dot u30-dot--green"></i><span class="u30-legend-rich__text"><strong>“Yashil”</strong><small>Oʻz vaqtida va toʻliq erishilgan koʻrsatkichlar</small></span></span>
<span class="u30-legend-rich"><i class="u30-dot u30-dot--yellow"></i><span class="u30-legend-rich__text"><strong>“Sariq”</strong><small>Bartaraf etish imkoni bor, lekin kechikishlar kuzatilayotgan koʻrsatkichlar</small></span></span>
<span class="u30-legend-rich"><i class="u30-dot u30-dot--red"></i><span class="u30-legend-rich__text"><strong>“Qizil”</strong><small>Jiddiy kechikishdagi koʻrsatkichlar</small></span></span>
</div>
<span class="u30-chart-note">Har bir qator — o‘z jami ko‘rsatkichlariga nisbatan 100%</span>
</div>
<div class="u30-scroll-hint" aria-hidden="true">Diagrammani ko‘rish uchun o‘ngga suring</div>
<div aria-label="1-diagramma, gorizontal ko‘rish mumkin" class="u30-scroll-frame" tabindex="0">
<div class="u30-direction-chart" data-chart="directions"></div>
</div>
</div>
<div class="u30-insight-grid">
<article class="u30-insight u30-insight--positive">
<span class="u30-insight__value">93%</span>
<div><strong>Eng yuqori natija</strong><p>“Qonun ustuvorligi” yoʻnalishida 14 ta koʻrsatkich toʻliq bajarilgan.</p></div>
</article>
<article class="u30-insight u30-insight--attention">
<span class="u30-insight__value">70%</span>
<div><strong>Eng past natija</strong><p>“Barqaror iqtisodiy oʻsish” yoʻnalishida 21 ta “sariq” koʻrsatkich jamlangan.</p></div>
</article>
</div>
</section>

<section aria-labelledby="u30-green-title" class="u30-section u30-section--green u30-reveal" id="u30-green">
<div class="u30-section-label"><span>01</span>Oʻz vaqtida va toʻliq erishilgan</div>
<div class="u30-section-head">
<div>
<span class="u30-eyebrow">“Yashil” toifa</span>
<h2 id="u30-green-title">37 ta vazirlik va idora tomonidan 162 ta koʻrsatkich bajarilgan</h2>
</div>

</div>
<div class="u30-chart-caption">
<span>2-diagramma</span>
<strong>Vazirlik va idoralar kesimida bajarilgan koʻrsatkichlar soni</strong>
</div>
<div class="u30-chart-card">
<p class="u30-green-scale-note">Har bir qatorning to‘liq uzunligi — shu tashkilotdagi jami ko‘rsatkichlar soni. Yashil qism bajarilgan, kulrang qism qolgan ko‘rsatkichlarni bildiradi.</p>
<div class="u30-green-chart" data-chart="green"></div>
</div>
<div class="u30-topfive-feature" aria-label="Belgilangan maqsadlarni toʻliq bajargan TOP-5 tashkilot">
  <div class="u30-topfive-feature__head">
    <span class="u30-topfive-feature__kicker">Eng yuqori natija</span>
    <h3>Belgilangan maqsadlarni toʻliq bajargan TOP-5</h3>
    <p>Quyidagi tashkilotlarda muddati kelgan koʻrsatkichlarning barchasi toʻliq bajarilgan.</p>
  </div>
  <ol class="u30-info-topfive u30-info-topfive--feature">
    <li>Adliya vazirligi</li>
    <li>Turizm qoʻmitasi</li>
    <li>Ijtimoiy himoya milliy agentligi</li>
    <li>Yoshlar ishlari agentligi</li>
    <li>Oila va xotin-qizlar qoʻmitasi</li>
  </ol>
</div>
<p class="u30-editorial-note">Oʻtkazilayotgan tahlil va oʻrganishlar islohotlarning amaliy samarasi aholining turmush sharoiti hamda hayot sifati yaxshilanishida oʻz ifodasini topayotganini koʻrsatmoqda.</p>
</section>

<section aria-labelledby="u30-yellow-title" class="u30-section u30-section--yellow u30-reveal" id="u30-yellow">
<div class="u30-section-label"><span>02</span>Bartaraf etish imkoni bor, lekin kechikishlar kuzatilmoqda</div>
<div class="u30-section-head">
<div>
<span class="u30-eyebrow">“Sariq” toifa</span>
<h2 id="u30-yellow-title">35 ta koʻrsatkichda kechikish mavjud</h2>
</div>
<div class="u30-info-panel u30-info-panel--yellow" aria-label="Sariq toifadagi asosiy holat">
<ul class="u30-info-points">
<li><strong class="u30-info-value">21 ta</strong><span>“II. Barqaror iqtisodiy oʻsish” yoʻnalishiga toʻgʻri keladi.</span></li>
<li><strong class="u30-info-value">60%</strong><span>Jami “sariq” toifadagi koʻrsatkichlarning ulushi.</span></li>
</ul>
</div>
</div>
<div class="u30-chart-caption">
<span>3-diagramma</span>
<strong>“Sariq” toifadagi koʻrsatkichlarning sohalar kesimidagi taqsimoti</strong>
</div>
<div class="u30-chart-card u30-chart-card--donut">
<p class="u30-distribution-note">Doira sektorlari ijro darajasini emas, 35 ta “sariq” koʻrsatkichning sohalar boʻyicha soni va ulushini bildiradi.</p>
<div class="u30-donut-layout" data-chart="yellowSectors"></div>
</div>
<div class="u30-chart-caption u30-chart-caption--spaced">
<span>4-diagramma</span>
<strong>“Sariq” toifadagi koʻrsatkichlarning yarim yillik ijro darajasi</strong>
</div>
<div class="u30-chart-card">
<div class="u30-range-chart" data-chart="yellowRanges"></div>
</div>

</section>

<section aria-labelledby="u30-red-title" class="u30-section u30-section--red u30-reveal" id="u30-red">
<div class="u30-section-label"><span>03</span>Jiddiy kechikishdagi koʻrsatkichlar</div>
<div class="u30-section-head">
<div>
<span class="u30-eyebrow">“Qizil” toifa</span>
<h2 id="u30-red-title">8 ta vazirlik va idora tomonidan 9 ta koʻrsatkich bajarilmagan</h2>
</div>
<div class="u30-info-panel u30-info-panel--red" aria-label="Qizil toifadagi ijro chegaralari">
<ul class="u30-info-points">
<li><strong class="u30-info-value">4–69%</strong><span>Koʻrsatkichlarning yarim yillik ijro darajasi.</span></li>
<li><strong class="u30-info-value">80%</strong><span>“Sariq” toifaga oʻtish chegarasi.</span></li>
</ul>
</div>
</div>
<div class="u30-chart-caption">
<span>5-diagramma</span>
<strong>Bajarilmagan (“qizil”) 9 ta koʻrsatkichning ijro darajasi</strong>
</div>
<div class="u30-chart-card u30-chart-card--red">
<div class="u30-scroll-hint" aria-hidden="true">Diagrammani ko‘rish uchun o‘ngga suring</div>
<div aria-label="5-diagramma, gorizontal ko‘rish mumkin" class="u30-scroll-frame" tabindex="0">
<div class="u30-red-chart" data-chart="red"></div>
</div>
</div>
<div class="u30-callout u30-callout--red">
<?= \App\Core\Icon::render('alert-triangle', 22) ?>
<p>Tahlilga koʻra, bajarilmagan 9 ta koʻrsatkich 4 ta strategik yoʻnalishga toʻgʻri keladi.</p>
</div>
</section>

<section aria-labelledby="u30-summary-title" class="u30-section u30-summary u30-reveal" id="u30-summary">
<div class="u30-section-label"><span>04</span>Yil yakuni boʻyicha baholash</div>
<div class="u30-section-head">
<div>
<span class="u30-eyebrow">4-boʻlim</span>
<h2 id="u30-summary-title">I yarim yillikda baholanmagan koʻrsatkichlarning yil yakuni boʻyicha istiqboli</h2>
</div>
<div class="u30-info-panel u30-info-panel--blue" aria-label="Tahlil qamrovi">
<ul class="u30-info-points">
<li><strong class="u30-info-value">424 ta</strong><span>koʻrsatkich 2026-yilda baholanadi (437 tadan 13 tasiga joriy yil maqsadi belgilanmagan).</span></li>
<li><strong class="u30-info-value">218 ta</strong><span>koʻrsatkich ushbu boʻlimda tahlil qilinadi — I yarim yillikda baholangan 206 tadan tashqari qolgan qismi.</span></li>
<li><strong class="u30-info-value">6 oy</strong><span>I yarim yillikdagi oyma-oy dinamika va oʻzgarish tendensiyasi tahlil qilindi.</span></li>
</ul>
</div>
</div>
<div class="u30-summary-grid u30-summary-grid--year-end">
<article class="u30-summary-card--positive"><span class="u30-summary-number">118 ta</span><h3>Belgilangan maqsadga erishish ehtimoli yuqori</h3><p>Muammolar kuzatilmagan.</p></article>
<article class="u30-summary-card--attention"><span class="u30-summary-number">85 ta</span><h3>Ijobiy tendensiya yetarli emas</h3><p>Ijroni toʻliq taʼminlash uchun alohida eʼtibor qaratish talab etiladi.</p></article>
<article class="u30-summary-card--planned"><span class="u30-summary-number">15 ta</span><h3>Ishlarni boshlash keyingi davrlarga rejalashtirilgan</h3><p>Ishlar III–IV choraklarda boshlanishi rejalashtirilgan.</p></article>
</div>
<p class="u30-summary-note">
<strong>Maʼlumot uchun.</strong> Strategiyaning 437 ta koʻrsatkichidan <strong>13 tasi uchun Farmonda 2026-yilga maqsadli qiymat belgilanmagan</strong> — ular boʻyicha natija keyingi yillarda (2027–2030) baholanadi, shu bois joriy yil bahosiga kiritilmadi: 17, 29, 45, 130, 200, 224, 270, 335, 345, 390, 391, 392, 418. Shu sababli 2026-yilda baholanadigan koʻrsatkichlar soni — <strong>424 ta</strong> (206 ta I yarim yillik yakuni boʻyicha, 218 ta yil yakunida).
</p>
</section>

<section aria-labelledby="u30-ranking-title" class="u30-section u30-org-ranking u30-reveal" id="u30-ranking">
  <div class="u30-section-label"><span>05</span>Tashkilotlar reytingi</div>
  <div class="u30-section-head u30-section-head--single">
    <div>
      <span class="u30-eyebrow">Yagona ijrochi bo‘yicha hisoblangan reyting</span>
      <h2 id="u30-ranking-title">Tashkilotlar reytingi</h2>
      <p class="u30-r5-intro">Har bir qatorda bitta masʼul tashkilot ko‘rsatiladi: chap tomonda I yarim yillik natijalari, o‘ng tomonda yil yakuni bo‘yicha umumiy baho. Rangli segmentlar ko‘rsatkichlar sonini, yonidagi raqamlar esa 0–100 oralig‘idagi indeksni bildiradi.</p>
    </div>
  </div>

  <div class="u30-r5-controls" aria-label="Reytingni saralash">
    <span class="u30-r5-control-label">Saralash</span>
    <button class="u30-r5-btn is-active" data-r5-key="h" type="button">I yarim yillik indeksi</button>
    <button class="u30-r5-btn" data-r5-key="y" type="button">Yil yakuni indeksi</button>
    <button class="u30-r5-btn" data-r5-key="tot" type="button">Ko‘rsatkichlar soni</button>
    <span class="u30-r5-sep" aria-hidden="true"></span>
    <button class="u30-r5-btn is-active" data-r5-dir="desc" type="button">Yuqoridan pastga</button>
    <button class="u30-r5-btn" data-r5-dir="asc" type="button">Pastdan yuqoriga</button>
  </div>

  <div class="u30-r5-legend" aria-label="Ranglar tavsifi">
    <div class="u30-r5-legend-group">
      <strong>I yarim yillik</strong>
      <span><i class="is-green"></i>Bajarilgan</span>
      <span><i class="is-yellow"></i>Kechikmoqda</span>
      <span><i class="is-red"></i>Bajarilmagan</span>
    </div>
    <div class="u30-r5-legend-group">
      <strong>Yil yakuni</strong>
      <span><i class="is-blue"></i>Bajarish ehtimoli yuqori</span>
      <span><i class="is-orange"></i>Kechikish xavfi bor</span>
      <span><i class="is-other"></i>Boshqalar</span>
    </div>
  </div>

  <div class="u30-r5-scroll-hint" aria-hidden="true">← Jadvalni yon tomonga surish mumkin →</div>
  <div class="u30-r5-app" data-r5-app aria-live="polite"></div>
</section>

<section aria-labelledby="u30-measures-title" class="u30-section u30-reveal" id="u30-measures">
  <div class="u30-section-label"><span>06</span>Amaliy choralar</div>
  <div class="u30-section-head">
    <div>
      <span class="u30-eyebrow">Ijro samaradorligini oshirish</span>
      <h2 id="u30-measures-title">“O‘zbekiston — 2030” strategiyasining samaradorlik ko‘rsatkichlariga erishish darajasini oshirish yuzasidan ko‘riladigan choralar</h2>
    </div>
    <div class="u30-info-panel u30-info-panel--blue" aria-label="Amaliy choralar maqsadi">
      <ul class="u30-info-points">
        <li><strong class="u30-info-value">Maqsad</strong><span>Muammoli ko‘rsatkichlar ijrosini yil yakuniga qadar to‘liq taʼminlash.</span></li>
        <li><strong class="u30-info-value">Yondashuv</strong><span>Masʼul vazirlik va idoralar tomonidan tashkiliy va amaliy choralarni amalga oshirish.</span></li>
      </ul>
    </div>
  </div>
  <div class="u30-measures-grid">
    <article class="u30-measure-card u30-measure-card--red">
      <div class="u30-measure-head">
        <span class="u30-measure-count">9</span>
        <div>
          <h3>Bajarilmagan (“qizil” toifadagi) 9 ta ko‘rsatkich bo‘yicha:</h3>
          <p>Tezkor tahlil, manzilli yordam va shaxsiy masʼuliyatni kuchaytirish.</p>
        </div>
      </div>
      <ol class="u30-measure-list">
        <li>ko‘rsatkichlarning bajarilmaslik sabablarini chuqur tahlil qilgan holda, talab etiladigan ekspert yordami, qo‘shimcha resurslar va boshqa amaliy ko‘mak to‘g‘risida aniq takliflarni kiritish;</li>
        <li>ko‘rsatkich ijrosiga biriktirilgan har bir kompleks rahbari va masʼul xodimlari tomonidan ko‘rsatkichlar ijrosini to‘laqonli taʼminlashga qaratilgan chora-tadbirlar rejasini ishlab chiqish, tasdiqlash va ijrosini taʼminlash;</li>
        <li>masʼul vazirlik va idoralar tomonidan joriy yil yakuniga qadar belgilangan chora-tadbirlar ijrosini samarali tashkil etib, ko‘rsatkichlar ijrosini to‘liq taʼminlash.</li>
      </ol>
    </article>
    <article class="u30-measure-card u30-measure-card--yellow">
      <div class="u30-measure-head">
        <span class="u30-measure-count">35</span>
        <div>
          <h3>Kechikish mavjud (“sariq” toifadagi) 35 ta ko‘rsatkich bo‘yicha</h3>
          <p>Aniq reja-grafiklarni qayta tasdiqlash.</p>
        </div>
      </div>
      <div class="u30-measure-single">
        <p>Kechikish mavjud (“sariq” toifadagi) 35 ta ko‘rsatkichga masʼul idoralar tomonidan yil yakunigacha ijroni to‘liq taʼminlaydigan reja-grafiklarni qaytadan tasdiqlab, ijroga qaratish.</p>
      </div>
    </article>
  </div>
</section>

<div class="u30-footer-note">
<span>Hisobot bazasi</span>
<p>Asosiy monitoring — 2026-yil I yarim yillik, 1-iyul holatiga · tashkilotlar kesimidagi umumiy holat — 2026-yil 25-iyul holatidagi yigʻma maʼlumot · yakuniy boʻlim — ijro samaradorligini oshirish choralari</p>
</div>

</div>
</div>
</div>
