/**
 * Блок «Мониторинг стратегии “Oʻzbekiston — 2030”» (тип u30_report).
 *
 * Подключается только на страницах с этим блоком (AssetCollector::JS_MAP).
 * Диаграммы рисуются в SVG на клиенте; без скрипта остаются заголовки,
 * тексты и таблицы разделов — документ читается и без JS.
 */

/* --- Диаграммы, счётчики и матрица организаций --------------------------- */
(() => {
  'use strict';

  const root = document.querySelector('#uz2030-report[data-report-component]');
  if (!root || root.dataset.u30Ready === 'true') return;
  root.dataset.u30Ready = 'true';

  const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
  // «Остановить анимации» в настройках отображения сайта — тот же смысл,
  // что и системная настройка: атрибут ставится на <html> сервером.
  const reducedMotion = {
    get matches() {
      return motionQuery.matches
        || document.documentElement.getAttribute('data-a11y-motion') === 'off';
    }
  };

  const data = {
    directions: [
      { roman: 'I', name: 'Inson salohiyati', green: 68, yellow: 9, red: 3, pct: 85, icon: 'user' },
      { roman: 'II', name: 'Barqaror iqtisodiy oʻsish', green: 55, yellow: 21, red: 3, pct: 70, icon: 'growth' },
      { roman: 'III', name: 'Suv-ekologiya', green: 15, yellow: 2, red: 2, pct: 79, icon: 'leaf' },
      { roman: 'IV', name: 'Qonun ustuvorligi', green: 14, yellow: 1, red: 0, pct: 93, icon: 'scales' },
      { roman: 'V', name: 'Xavfsiz davlat', green: 10, yellow: 2, red: 1, pct: 77, icon: 'shield' }
    ],
    green: [
      { name: 'Madaniyat', complete: 14, total: 17, icon: 'institution' },
      { name: 'Ekologiya va iqlim', complete: 11, total: 15, icon: 'leaf' },
      { name: 'Adliya', complete: 10, total: 10, all: true, icon: 'scales' },
      { name: 'Oliy taʼlim, fan', complete: 9, total: 10, icon: 'education' },
      { name: 'Raqamli texnologiyalar', complete: 9, total: 11, icon: 'chip' },
      { name: 'Investitsiyalar, sanoat', complete: 8, total: 12, icon: 'building' },
      { name: 'Turizm', complete: 8, total: 8, all: true, icon: 'compass' },
      { name: 'Ijtimoiy himoya', complete: 7, total: 7, all: true, icon: 'shield' },
      { name: 'Sogʻliqni saqlash', complete: 7, total: 11, icon: 'health' },
      { name: 'Yoshlar ishlari', complete: 6, total: 6, all: true, icon: 'people' },
      { name: 'Iqtisodiyot va moliya', complete: 6, total: 7, icon: 'growth' },
      { name: 'Oila va xotin-qizlar', complete: 6, total: 6, all: true, icon: 'user' }
    ],
    yellowSectors: [
      { name: 'Boshqa sohalar', count: 15, pct: 42, color: '#f59e0b', icon: 'building', details: 'Sanoat, ekologiya, shaharsozlik, tashqi aloqalar va boshqalar' },
      { name: 'Transport', count: 7, pct: 20, color: '#0b57d0', icon: 'transport', details: 'Tranzit yoʻllar, aviaqatnovlar, yoʻl qurish, jamoat transporti' },
      { name: 'Sogʻliqni saqlash', count: 4, pct: 11, color: '#10b981', icon: 'health', details: 'Oilaviy shifokorlar qamrovi, bolalar oʻlimi, raqamlashtirish' },
      { name: 'Togʻ-kon sanoati', count: 3, pct: 9, color: '#f97316', icon: 'mining', details: 'Oltin, mis va kumush ishlab chiqarish' },
      { name: 'Energetika', count: 3, pct: 9, color: '#7c3aed', icon: 'bolt', details: 'Elektr energiyasi, tabiiy gaz, tarmoq yoʻqotishlari' },
      { name: 'Madaniyat', count: 3, pct: 9, color: '#0d9488', icon: 'institution', details: 'Sirk va konsert tomoshabinlari, muzeylarni raqamlashtirish' }
    ],
    yellowRanges: [
      { label: '95 foizi va undan yuqori', sub: 'Yarim yillik rejaga juda yaqin', count: 17, color: '#f59e0b', icon: 'target' },
      { label: '90–94 foizi bajarilgan', sub: 'Qisqa muddatli tuzatish zarur', count: 7, color: '#d97706', icon: 'growth' },
      { label: '80–89 foizi bajarilgan', sub: 'Faol boshqaruv chorasi zarur', count: 7, color: '#ea580c', icon: 'check' },
      { label: '80 foizdan past', sub: 'Yuqori darajadagi eʼtibor talab etadi', count: 4, color: '#dc2626', icon: 'decline' }
    ],
    red: [
      { code: '237', name: 'Qayta foydalanishga kiritilgan yer maydonlari', pct: 4, agency: 'Qishloq xoʻjaligi', icon: 'sprout', agencyIcon: 'sprout' },
      { code: '021', name: 'Davlat akkreditatsiyasidan oʻtkazish', pct: 13, agency: 'TSTMA', icon: 'clipboard', agencyIcon: 'shield' },
      { code: '324', name: '“Yashil iqlim” va Global ekologik jamgʻarma loyihalari', pct: 21, agency: 'Ekologiya qoʻmitasi', icon: 'leaf', agencyIcon: 'leaf' },
      { code: '339', name: 'Lokal oqova tozalash inshootlari', pct: 54, agency: 'Ekologiya qoʻmitasi', icon: 'water', agencyIcon: 'leaf' },
      { code: '127', name: 'Razryadli va unvonli sportchilar', pct: 58, agency: 'Sport', icon: 'sport', agencyIcon: 'sport' },
      { code: '291', name: 'Barpo etiladigan xonadonli uy-joylar', pct: 66, agency: 'Urbanizatsiya', icon: 'building', agencyIcon: 'building' },
      { code: '411', name: '“Axori” loyihasi doirasida olib kelingan yoshlar', pct: 67, agency: 'Millatlararo munosabatlar', icon: 'people', agencyIcon: 'people' },
      { code: '215', name: 'Geologiya-qidiruv ishlariga investitsiyalar', pct: 68, agency: 'Energetika', icon: 'rig', agencyIcon: 'bolt' },
      { code: '165', name: 'Kinematografiya mahsulotlari eksporti', pct: 69, agency: 'Madaniyat', icon: 'film', agencyIcon: 'institution' }
    ]
  };

  const icons = {
    user: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>',
    growth: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V9m6 10V5m6 14v-7m4 7V3"/><path d="m4 12 5-5 4 3 7-7"/></svg>',
    leaf: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 4C10 4 4 9 4 16c0 3 2 4 5 4 7 0 11-6 11-16Z"/><path d="M4 20c4-5 8-8 13-11"/></svg>',
    scales: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v18M5 6h14M8 6l-4 7h8L8 6Zm8 0-4 7h8l-4-7ZM7 21h10"/></svg>',
    shield: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3Z"/><path d="m9 12 2 2 4-5"/></svg>',
    institution: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 9 9-5 9 5M5 10v8m4-8v8m6-8v8m4-8v8M3 20h18"/></svg>',
    people: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2"/><path d="M3 20a6 6 0 0 1 12 0m0-5a5 5 0 0 1 6 5"/></svg>',
    education: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 9 9-5 9 5-9 5-9-5Z"/><path d="M7 12v5c3 2 7 2 10 0v-5M21 9v6"/></svg>',
    building: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V5h10v16M14 9h6v12M7 8h2m2 0h1M7 12h2m2 0h1M7 16h2m2 0h1M17 12h1m-1 4h1M2 21h20"/></svg>',
    bolt: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m13 2-9 12h7l-1 8 10-13h-7V2Z"/></svg>',
    clipboard: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5h6M9 3h6v4H9zM6 5H4v16h16V5h-2M8 11h8m-8 4h5"/></svg>',
    transport: '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="3" width="14" height="15" rx="3"/><path d="M5 10h14M8 21l1-3m7 3-1-3M8 6h8m-8 8h.01m8 0h.01"/></svg>',
    health: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 4h6l1 3h4v14H4V7h4l1-3Z"/><path d="M12 10v7m-3.5-3.5h7"/></svg>',
    mining: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 18h16M6 18l2-8h8l2 8M9 10l3-5 3 5"/><path d="M8 14h8"/></svg>',
    target: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="13" r="8"/><circle cx="11" cy="13" r="4"/><path d="m14 10 7-7m-4 0h4v4"/></svg>',
    check: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>',
    decline: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5v14m0 0h16M6 8l5 5 3-3 5 6"/></svg>',
    chip: '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="7" y="7" width="10" height="10" rx="2"/><path d="M9 1v4M15 1v4M9 19v4M15 19v4M1 9h4M1 15h4M19 9h4M19 15h4"/></svg>',
    compass: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-3 7-7 3 3-7 7-3Z"/></svg>',
    sprout: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21v-8"/><path d="M12 13c0-4-2.5-7-7-8 0 5 2 8 7 8Zm0 0c0-3.4 2.2-5.8 7-7-0 4.7-2.1 7-7 7Z"/></svg>',
    water: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3C9 7 6 10.2 6 14a6 6 0 0 0 12 0c0-3.8-3-7-6-11Z"/><path d="M9 16c.7 1.2 1.7 1.8 3 1.8 1.3 0 2.3-.6 3-1.8"/></svg>',
    sport: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="16" cy="5" r="2"/><path d="m6 11 5-2 2 2 2.5 1.5M9 21l2-5-2-3-3 2M14 21l-1.5-5 1-4 4 3"/></svg>',
    rig: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 6 21h12L12 3Zm0 0v18M8.5 12h7M7.5 16h9"/><path d="M4 21h16"/></svg>',
    film: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v11H4zM4 7l4 4M8 7l4 4M12 7l4 4M16 7l4 4"/></svg>'
  };

  const icon = (name) => icons[name] || icons.institution;
  const esc = (value) => String(value).replace(/[&<>'"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[char]));

  function renderDirections() {
    const host = root.querySelector('[data-chart="directions"]');
    if (!host) return;
    const rows = data.directions.map((item, rowIndex) => {
      const total = item.green + item.yellow + item.red;
      const segments = [
        ['green', item.green], ['yellow', item.yellow], ['red', item.red]
      ].filter(([, value]) => value > 0).map(([type, value], segIndex) => {
        const width = total ? (value / total) * 100 : 0;
        return `<span class="u30-stack-segment u30-stack-segment--${type}" style="width:${width.toFixed(2)}%;--delay:${rowIndex * 80 + segIndex * 90}ms" title="${value} ta">${value}</span>`;
      }).join('');
      return `<div class="u30-direction-row">
        <div class="u30-direction-name"><span class="u30-direction-icon">${icon(item.icon)}</span><span><small>${esc(item.roman)} yoʻnalish</small><strong>${esc(item.name)}</strong></span></div>
        <div class="u30-stack-track" role="img" aria-label="${esc(item.name)}: bajarilgan ${item.green} ta, kechikmoqda ${item.yellow} ta, bajarilmagan ${item.red} ta, jami ${total} ta">${segments}</div>
        <div class="u30-direction-pct"><strong>${item.pct}</strong><span>%</span></div>
      </div>`;
    }).join('');
    host.innerHTML = rows + '<div class="u30-axis"><span>0%</span><span>25%</span><span>50%</span><span>75%</span><span>100%</span></div>';
  }

  function renderGreen() {
    const host = root.querySelector('[data-chart="green"]');
    if (!host) return;
    host.innerHTML = data.green.map((item, index) => {
      const remaining = Math.max(0, item.total - item.complete);
      const completeWidth = item.total ? (item.complete / item.total) * 100 : 0;
      const remainingWidth = 100 - completeWidth;
      return `<div class="u30-green-row">
        <div class="u30-green-name"><span class="u30-green-icon">${icon(item.icon || 'institution')}</span><span class="u30-green-name__text">${esc(item.name)}</span></div>
        <div class="u30-green-track" role="img" aria-label="${esc(item.name)}: ${item.complete} ta bajarilgan, ${remaining} ta qolgan, jami ${item.total} ta">
          <span class="u30-green-complete" style="width:${completeWidth.toFixed(2)}%;--delay:${index * 55}ms" title="Bajarilgan: ${item.complete} ta">${item.complete}</span>
          ${remaining ? `<span class="u30-green-remaining" style="width:${remainingWidth.toFixed(2)}%" title="Qolgan: ${remaining} ta">${remaining}</span>` : ''}
        </div>
        <div class="u30-green-meta"><span class="u30-green-total">Jami: <b>${item.total} ta</b></span></div>
      </div>`;
    }).join('');
  }

  function renderYellowSectors() {
    const host = root.querySelector('[data-chart="yellowSectors"]');
    if (!host) return;

    const total = data.yellowSectors.reduce((sum, item) => sum + item.count, 0);
    const cx = 260;
    const cy = 260;
    const outerR = 216;
    const innerR = 116;
    const labelR = 166;

    const polar = (radius, angleDeg) => {
      const angle = (angleDeg - 90) * Math.PI / 180;
      return { x: cx + radius * Math.cos(angle), y: cy + radius * Math.sin(angle) };
    };

    const sectorPath = (startAngle, endAngle) => {
      const outerStart = polar(outerR, endAngle);
      const outerEnd = polar(outerR, startAngle);
      const innerStart = polar(innerR, startAngle);
      const innerEnd = polar(innerR, endAngle);
      const largeArc = endAngle - startAngle > 180 ? 1 : 0;
      return [
        `M ${outerStart.x.toFixed(3)} ${outerStart.y.toFixed(3)}`,
        `A ${outerR} ${outerR} 0 ${largeArc} 0 ${outerEnd.x.toFixed(3)} ${outerEnd.y.toFixed(3)}`,
        `L ${innerStart.x.toFixed(3)} ${innerStart.y.toFixed(3)}`,
        `A ${innerR} ${innerR} 0 ${largeArc} 1 ${innerEnd.x.toFixed(3)} ${innerEnd.y.toFixed(3)}`,
        'Z'
      ].join(' ');
    };

    let angle = 0;
    const slices = data.yellowSectors.map((item, index) => {
      const sweep = item.count / total * 360;
      const startAngle = angle;
      const endAngle = angle + sweep;
      const midAngle = startAngle + sweep / 2;
      const label = polar(labelR, midAngle);
      angle = endAngle;
      return `<g class="u30-donut-slice" data-sector-index="${index}">
        <path class="u30-donut-segment" style="animation-delay:${index * 65}ms" d="${sectorPath(startAngle, endAngle)}" fill="${item.color}" stroke="#fff" stroke-width="4" aria-label="${esc(item.name)}: ${item.count} ta, ${item.pct} foiz"><title>${esc(item.name)} — ${item.count} ta (${item.pct}%)</title></path>
        <text class="u30-donut-label" x="${label.x.toFixed(2)}" y="${(label.y - 7).toFixed(2)}">
          <tspan class="u30-donut-label__count" x="${label.x.toFixed(2)}">${item.count}</tspan>
          <tspan class="u30-donut-label__pct" x="${label.x.toFixed(2)}" dy="23">${item.pct}%</tspan>
        </text>
      </g>`;
    }).join('');

    const rows = data.yellowSectors.map((item, index) => `<article class="u30-sector-row" data-sector-index="${index}" tabindex="0" style="--sector-color:${item.color};--sector-pct:${item.pct}%;--delay:${index * 70}ms">
      <span class="u30-sector-row__icon">${icon(item.icon)}</span>
      <div class="u30-sector-row__body">
        <div class="u30-sector-row__head">
          <h3>${esc(item.name)}</h3>
          <div class="u30-sector-row__metric"><strong>${item.count}</strong><span>ta · ${item.pct}%</span></div>
        </div>
        <p class="u30-sector-row__details">${esc(item.details)}</p>
      </div>
    </article>`).join('');

    host.innerHTML = `<div class="u30-donut-stage">
        <div class="u30-donut-wrap">
          <svg class="u30-donut-svg" viewBox="0 0 520 520" role="img" aria-label="35 ta sariq koʻrsatkichning sohalar boʻyicha taqsimoti">${slices}</svg>
          <div class="u30-donut-center"><strong data-count="35">35</strong><span>ta koʻrsatkich</span></div>
        </div>
      </div>
      <div class="u30-sector-list">${rows}</div>`;

    const setActive = (index, active) => {
      host.querySelector(`.u30-sector-row[data-sector-index="${index}"]`)?.classList.toggle('is-active', active);
      host.querySelector(`.u30-donut-slice[data-sector-index="${index}"] .u30-donut-segment`)?.classList.toggle('is-active', active);
    };

    host.querySelectorAll('[data-sector-index]').forEach((element) => {
      const index = element.dataset.sectorIndex;
      element.addEventListener('mouseenter', () => setActive(index, true));
      element.addEventListener('mouseleave', () => setActive(index, false));
      element.addEventListener('focusin', () => setActive(index, true));
      element.addEventListener('focusout', () => setActive(index, false));
    });
  }

  function renderYellowRanges() {
    const host = root.querySelector('[data-chart="yellowRanges"]');
    if (!host) return;
    const total = data.yellowRanges.reduce((sum, item) => sum + item.count, 0);
    const segments = data.yellowRanges.map((item, index) => {
      const pct = total ? item.count / total * 100 : 0;
      const label = pct >= 15 ? `${item.count} ta · ${Math.round(pct)}%` : `${item.count}`;
      return `<span class="u30-range-segment" style="width:${pct.toFixed(3)}%;background:${item.color};--delay:${index * 90}ms" title="${esc(item.label)}: ${item.count} ta (${Math.round(pct)}%)">${label}</span>`;
    }).join('');
    const legend = data.yellowRanges.map((item) => {
      const pct = total ? item.count / total * 100 : 0;
      return `<div class="u30-range-legend-item" style="--range-color:${item.color}">
        <i class="u30-range-legend-dot"></i>
        <span class="u30-range-legend-copy"><strong>${esc(item.label)}</strong><small>${esc(item.sub)}</small></span>
        <span class="u30-range-legend-value">${item.count} ta · ${Math.round(pct)}%</span>
      </div>`;
    }).join('');
    host.innerHTML = `<div class="u30-range-summary"><strong>35 ta “sariq” ko‘rsatkichning ijro oralig‘i bo‘yicha tarkibi</strong><span>jami 35 ta</span></div>
      <div class="u30-range-stack" role="img" aria-label="35 ta sariq ko‘rsatkich: 17 tasi 95 foiz va undan yuqori, 7 tasi 90–94 foiz, 7 tasi 80–89 foiz, 4 tasi 80 foizdan past">${segments}</div>
      <div class="u30-range-legend">${legend}</div>`;
  }

  function renderRed() {
    const host = root.querySelector('[data-chart="red"]');
    if (!host) return;
    const head = '<div class="u30-red-head"><div>Koʻrsatkich</div><div>Ijro darajasi, foiz <b class="u30-red-threshold">80% · sariq chegara</b></div><div>Masʼul idora</div></div>';
    const rows = data.red.map((item, index) => `<div class="u30-red-row">
      <div class="u30-red-label"><span class="u30-red-item-icon">${icon(item.icon || 'clipboard')}</span><span class="u30-red-code">${esc(item.code)}</span><strong>${esc(item.name)}</strong></div>
      <div class="u30-red-bar-cell"><div class="u30-red-track" role="img" aria-label="${esc(item.name)}: ${item.pct} foiz"><span class="u30-red-fill" style="width:${item.pct}%;--delay:${index * 65}ms">${item.pct}%</span></div></div>
      <div class="u30-red-agency"><span>${esc(item.agency)}</span></div>
    </div>`).join('');
    host.innerHTML = head + rows;
  }

  function animateCount(el) {
    if (el.dataset.u30Counted === 'true') return;
    const target = Number(el.dataset.count);
    if (!Number.isFinite(target)) return;
    el.dataset.u30Counted = 'true';
    if (reducedMotion.matches || window.matchMedia('print').matches) {
      el.textContent = target.toLocaleString('uz-UZ');
      return;
    }
    const duration = 850;
    const start = performance.now();
    const step = (now) => {
      const progress = Math.min(Math.max((now - start) / duration, 0), 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.round(target * eased).toLocaleString('uz-UZ');
      if (progress < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  }

  function reveal(section) {
    section.classList.add('is-visible');
    section.querySelectorAll('[data-count]').forEach(animateCount);
  }

  function setupReveal() {
    const items = [...root.querySelectorAll('.u30-reveal')];
    if (!('IntersectionObserver' in window) || reducedMotion.matches) {
      items.forEach(reveal);
      return;
    }
    try {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            reveal(entry.target);
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
      root.classList.add('u30-js');
      items.forEach((item) => observer.observe(item));
    } catch (error) {
      root.classList.remove('u30-js');
      items.forEach(reveal);
      console.warn('U30 reveal animation disabled:', error);
    }
  }

  function finalizeForPrint() {
    root.querySelectorAll('.u30-reveal').forEach((section) => section.classList.add('is-visible'));
    root.querySelectorAll('[data-count]').forEach((el) => {
      const target = Number(el.dataset.count);
      if (!Number.isFinite(target)) return;
      el.dataset.u30Counted = 'true';
      el.textContent = target.toLocaleString('uz-UZ');
    });
  }

  function setupActions() {
    window.addEventListener('beforeprint', finalizeForPrint);
    root.querySelector('[data-u30-print]')?.addEventListener('click', () => {
      finalizeForPrint();
      window.print();
    });
    root.querySelector('[data-u30-replay]')?.addEventListener('click', () => {
      root.querySelectorAll('.u30-reveal').forEach((section) => {
        section.classList.remove('is-visible');
        section.querySelectorAll('[data-count]').forEach((el) => {
          el.dataset.u30Counted = 'false';
          el.textContent = '0';
        });
      });
      void root.offsetWidth;
      root.querySelectorAll('.u30-reveal').forEach((section, index) => window.setTimeout(() => reveal(section), index * 80));
    });
  }

  renderDirections();
  renderGreen();
  renderYellowSectors();
  renderYellowRanges();
  renderRed();
  setupReveal();
  setupActions();
})();

(() => {
  'use strict';
  const root = document.querySelector('#uz2030-report[data-report-component]');
  if (!root || root.dataset.orgMatrixReady === 'true') return;
  root.dataset.orgMatrixReady = 'true';

  const R = {
    "m": {
      "all": {
        "left": [
          {"o":"Туризм қўмитаси","n":8,"anom":0,"idx":100.0,"bar":{"done":8,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Ижтимоий ҳимоя миллий агентлиги","n":7,"anom":0,"idx":100.0,"bar":{"done":7,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Оила ва хотин-қизлар қўмитаси","n":6,"anom":0,"idx":100.0,"bar":{"done":6,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Фанлар академияси","n":3,"anom":0,"idx":100.0,"bar":{"done":3,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Савдо-саноат палатаси","n":3,"anom":0,"idx":100.0,"bar":{"done":3,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Фавқулодда вазиятлар вазирлиги","n":3,"anom":0,"idx":100.0,"bar":{"done":3,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"вазирлик ва идоралар","n":2,"anom":0,"idx":100.0,"bar":{"done":2,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Республика Маънавият ва маърифат маркази","n":2,"anom":0,"idx":100.0,"bar":{"done":2,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Агросаноат мажмуи устидан назорат қилиш инспекцияси","n":2,"anom":0,"idx":100.0,"bar":{"done":2,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Техник жиҳатдан тартибга солиш агентлиги","n":2,"anom":0,"idx":100.0,"bar":{"done":2,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Сув хўжалиги вазирлиги","n":2,"anom":0,"idx":100.0,"bar":{"done":2,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Миграция агентлиги","n":2,"anom":0,"idx":100.0,"bar":{"done":2,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Ўзбекистон футбол ассоциацияси","n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Марказий банк","n":1,"anom":1,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Солиқ қўмитаси","n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Электр энергияси, нефть маҳсулотлари ва газдан фойдаланишни назорат қилиш инспекцияси","n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"\"Ўзтрансгаз\" АЖ","n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"\"Ҳудудгаз-таъминот\" АЖ","n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Бизнес-омбудсман","n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Қурилиш ва уй-жой коммунал хўжалиги соҳасида назорат қилиш инспекцияси","n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"\"Ўзсувтаъминот\" АЖ","n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Саноат, радиация ва ядро хавфсизлиги қўмитаси","n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Ўзсувтаъминот АЖ","n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"бошқа ҳуқуқни муҳофаза қилувчи органлар","n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Ўзбекистон Ёзувчилар уюшмаси","n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Дин ишлари бўйича қўмита","n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Юксалиш ҳаракати","n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Ёшлар ишлари агентлиги","n":10,"anom":0,"idx":99.9,"bar":{"done":9,"late":1,"fail":0},"qz":0,"nd":0,"ysh":0.9},
          {"o":"Ички ишлар вазирлиги","n":1,"anom":0,"idx":99.0,"bar":{"done":0,"late":1,"fail":0},"qz":0,"nd":0,"ysh":0.0},
          {"o":"Рақамли технологиялар вазирлиги","n":16,"anom":0,"idx":98.9,"bar":{"done":13,"late":3,"fail":0},"qz":0,"nd":0,"ysh":0.8125},
          {"o":"Маданий мерос агентлиги","n":4,"anom":0,"idx":98.8,"bar":{"done":3,"late":1,"fail":0},"qz":0,"nd":0,"ysh":0.75},
          {"o":"Ташқи ишлар вазирлиги","n":2,"anom":0,"idx":97.5,"bar":{"done":0,"late":2,"fail":0},"qz":0,"nd":0,"ysh":0.0},
          {"o":"Маданият вазирлиги","n":17,"anom":0,"idx":97.3,"bar":{"done":14,"late":2,"fail":1},"qz":1,"nd":0,"ysh":0.8235294117647058},
          {"o":"Соғлиқни сақлаш вазирлиги","n":11,"anom":0,"idx":97.1,"bar":{"done":7,"late":4,"fail":0},"qz":0,"nd":0,"ysh":0.6363636363636364},
          {"o":"Камбағалликни қисқартириш ва бандлик вазирлиги","n":7,"anom":0,"idx":95.9,"bar":{"done":6,"late":1,"fail":0},"qz":0,"nd":0,"ysh":0.8571428571428571},
          {"o":"Транспорт вазирлиги","n":12,"anom":0,"idx":95.2,"bar":{"done":5,"late":7,"fail":0},"qz":0,"nd":0,"ysh":0.4166666666666667},
          {"o":"\"Ўзбекнефтгаз\" АЖ","n":1,"anom":0,"idx":95.0,"bar":{"done":0,"late":1,"fail":0},"qz":0,"nd":0,"ysh":0.0},
          {"o":"Тоғ-кон саноати ва геология вазирлиги","n":7,"anom":0,"idx":93.6,"bar":{"done":4,"late":3,"fail":0},"qz":0,"nd":0,"ysh":0.5714285714285714},
          {"o":"Миллатлараро муносабатлар ва хориждаги ватандошлар масалалари бўйича қўмита","n":5,"anom":0,"idx":93.4,"bar":{"done":4,"late":0,"fail":1},"qz":1,"nd":0,"ysh":0.8},
          {"o":"Олий таълим, фан ва инновациялар вазирлиги","n":12,"anom":0,"idx":92.5,"bar":{"done":10,"late":1,"fail":1},"qz":1,"nd":0,"ysh":0.8333333333333334},
          {"o":"Урбанизация ва уй-жой бозорини барқарор ривожлантириш миллий қўмитаси","n":5,"anom":0,"idx":92.0,"bar":{"done":3,"late":1,"fail":1},"qz":1,"nd":0,"ysh":0.6},
          {"o":"Энергетика вазирлиги","n":5,"anom":0,"idx":92.0,"bar":{"done":1,"late":3,"fail":1},"qz":1,"nd":0,"ysh":0.2},
          {"o":"Ватандошлар жамоат фонди","n":4,"anom":0,"idx":91.8,"bar":{"done":3,"late":0,"fail":1},"qz":1,"nd":0,"ysh":0.75},
          {"o":"Адлия вазирлиги","n":12,"anom":0,"idx":91.7,"bar":{"done":11,"late":0,"fail":1},"qz":0,"nd":1,"ysh":0.9166666666666666},
          {"o":"Спорт вазирлиги","n":5,"anom":0,"idx":91.6,"bar":{"done":4,"late":0,"fail":1},"qz":1,"nd":0,"ysh":0.8},
          {"o":"Экология ва иқлим ўзгариши миллий қўмитаси","n":15,"anom":0,"idx":90.1,"bar":{"done":11,"late":2,"fail":2},"qz":2,"nd":0,"ysh":0.7333333333333333},
          {"o":"Қорақалпоғистон Республикаси Вазирлар Кенгаши","n":5,"anom":0,"idx":87.8,"bar":{"done":3,"late":1,"fail":1},"qz":1,"nd":0,"ysh":0.6},
          {"o":"вилоятлар ва Тошкент шаҳар ҳокимликлари","n":5,"anom":0,"idx":87.8,"bar":{"done":3,"late":1,"fail":1},"qz":1,"nd":0,"ysh":0.6},
          {"o":"Инвестициялар, саноат ва савдо вазирлиги","n":15,"anom":0,"idx":84.4,"bar":{"done":9,"late":4,"fail":2},"qz":0,"nd":2,"ysh":0.6},
          {"o":"Иқтисодиёт ва молия вазирлиги","n":11,"anom":0,"idx":80.4,"bar":{"done":8,"late":1,"fail":2},"qz":0,"nd":2,"ysh":0.7272727272727273},
          {"o":"Мактабгача ва мактаб таълими вазирлиги","n":5,"anom":0,"idx":80.0,"bar":{"done":4,"late":0,"fail":1},"qz":0,"nd":1,"ysh":0.8},
          {"o":"Қурилиш ва уй-жой коммунал хўжалиги вазирлиги","n":7,"anom":0,"idx":79.7,"bar":{"done":3,"late":2,"fail":2},"qz":1,"nd":1,"ysh":0.42857142857142855},
          {"o":"Рақобатни ривожлантириш ва истеъмолчилар ҳуқуқларини ҳимоя қилиш қўмитаси","n":3,"anom":0,"idx":66.7,"bar":{"done":2,"late":0,"fail":1},"qz":0,"nd":1,"ysh":0.6666666666666666},
          {"o":"Қишлоқ хўжалиги вазирлиги","n":9,"anom":0,"idx":66.4,"bar":{"done":5,"late":1,"fail":3},"qz":1,"nd":2,"ysh":0.5555555555555556},
          {"o":"Нуроний жамғармаси","n":2,"anom":0,"idx":50.0,"bar":{"done":1,"late":0,"fail":1},"qz":0,"nd":1,"ysh":0.5},
          {"o":"Энергия самарадорлиги миллий агентлиги","n":2,"anom":0,"idx":50.0,"bar":{"done":1,"late":0,"fail":1},"qz":0,"nd":1,"ysh":0.5},
          {"o":"Олий суд","n":2,"anom":0,"idx":50.0,"bar":{"done":1,"late":0,"fail":1},"qz":0,"nd":1,"ysh":0.5},
          {"o":"Таълим сифатини таъминлаш миллий агентлиги","n":1,"anom":0,"idx":13.0,"bar":{"done":0,"late":0,"fail":1},"qz":1,"nd":0,"ysh":0.0},
          {"o":"Енгил саноатни ривожлантириш агентлиги","n":3,"anom":0,"idx":0.0,"bar":{"done":0,"late":0,"fail":3},"qz":0,"nd":3,"ysh":0.0},
          {"o":"Яширин иқтисодиётни қисқартириш бўйича махсус комиссия","n":1,"anom":0,"idx":0.0,"bar":{"done":0,"late":0,"fail":1},"qz":0,"nd":1,"ysh":0.0},
          {"o":"Ўзчармсаноат уюшмаси","n":1,"anom":0,"idx":0.0,"bar":{"done":0,"late":0,"fail":1},"qz":0,"nd":1,"ysh":0.0},
          {"o":"Энергетика бозорини ривожлантириш ва тартибга солиш агентлиги","n":1,"anom":0,"idx":0.0,"bar":{"done":0,"late":0,"fail":1},"qz":0,"nd":1,"ysh":0.0}
        ],
        "right": [
          {"o":"Ташқи ишлар вазирлиги","n":6,"idx":100.0,"bar":{"high":6,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":6},"ysh":1.0},
          {"o":"Таълим сифатини таъминлаш миллий агентлиги","n":3,"idx":100.0,"bar":{"high":3,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":3},"ysh":1.0},
          {"o":"Миллий антидопинг агентлиги","n":2,"idx":100.0,"bar":{"high":2,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":2},"ysh":1.0},
          {"o":"Атом энергияси агентлиги","n":2,"idx":100.0,"bar":{"high":2,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":2},"ysh":1.0},
          {"o":"Жаҳон савдо ташкилоти масалалари бўйича махсус вакил","n":2,"idx":100.0,"bar":{"high":2,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":2},"ysh":1.0},
          {"o":"Давлат сиёсати ва бошқаруви академияси","n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0},
          {"o":"Ёшлар ишлари агентлиги","n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0},
          {"o":"Солиқ қўмитаси","n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0},
          {"o":"Урбанизация ва уй-жой бозорини барқарор ривожлантириш миллий қўмитаси","n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0},
          {"o":"Тошкент шаҳар ҳокимлиги","n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0},
          {"o":"Стратегик ривожланиш ва ислоҳотлар агентлиги","n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0},
          {"o":"Олий Мажлис палаталари, Адлия вазирлиги","n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0},
          {"o":"\"Тараққиёт стратегияси\" маркази","n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0},
          {"o":"Ватандошлар жамоат фонди","n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0},
          {"o":"Миллий статистика қўмитаси","n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0},
          {"o":"Дин ишлари бўйича қўмита","n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0},
          {"o":"Спорт вазирлиги","n":8,"idx":87.5,"bar":{"high":6,"risk":0,"other":2},"tnd":{"мақсадга эришиш эҳтимоли юқори":6,"Ижроси энди бошланади":2},"ysh":0.75},
          {"o":"Ички ишлар вазирлиги","n":8,"idx":87.5,"bar":{"high":6,"risk":0,"other":2},"tnd":{"Маълумот киритилмаган":2,"мақсадга эришиш эҳтимоли юқори":6},"ysh":0.75},
          {"o":"Транспорт вазирлиги","n":4,"idx":87.5,"bar":{"high":3,"risk":0,"other":1},"tnd":{"Ижроси энди бошланади":1,"мақсадга эришиш эҳтимоли юқори":3},"ysh":0.75},
          {"o":"Миллатлараро муносабатлар ва хориждаги ватандошлар масалалари бўйича қўмита","n":4,"idx":87.5,"bar":{"high":3,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1,"мақсадга эришиш эҳтимоли юқори":3},"ysh":0.75},
          {"o":"Давлат активларини бошқариш агентлиги","n":3,"idx":83.3,"bar":{"high":2,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1,"мақсадга эришиш эҳтимоли юқори":2},"ysh":0.6666666666666666},
          {"o":"Мудофаа вазирлиги","n":5,"idx":80.0,"bar":{"high":4,"risk":1,"other":0},"tnd":{"ўсиш йўқ":1,"мақсадга эришиш эҳтимоли юқори":4},"ysh":0.8},
          {"o":"Камбағалликни қисқартириш ва бандлик вазирлиги","n":4,"idx":75.0,"bar":{"high":3,"risk":1,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":3,"ўсиш йўқ":1},"ysh":0.75},
          {"o":"Оила ва хотин-қизлар қўмитаси","n":4,"idx":75.0,"bar":{"high":3,"risk":1,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":3,"тенденция етарли эмас, бажарилмаслик хавфи бор":1},"ysh":0.75},
          {"o":"Қишлоқ хўжалиги вазирлиги","n":8,"idx":75.0,"bar":{"high":4,"risk":0,"other":4},"tnd":{"Ижроси энди бошланади":2,"Маълумот киритилмаган":2,"мақсадга эришиш эҳтимоли юқори":4},"ysh":0.5},
          {"o":"Гендер тенгликни таъминлаш масалалари бўйича комиссия","n":2,"idx":75.0,"bar":{"high":1,"risk":0,"other":1},"tnd":{"мақсадга эришиш эҳтимоли юқори":1,"Маълумот киритилмаган":1},"ysh":0.5},
          {"o":"Марказий банк","n":2,"idx":75.0,"bar":{"high":1,"risk":0,"other":1},"tnd":{"мақсадга эришиш эҳтимоли юқори":1,"Ижроси энди бошланади":1},"ysh":0.5},
          {"o":"Туризм қўмитаси","n":2,"idx":75.0,"bar":{"high":1,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1,"мақсадга эришиш эҳтимоли юқори":1},"ysh":0.5},
          {"o":"Олий Мажлис Сенати","n":2,"idx":75.0,"bar":{"high":1,"risk":0,"other":1},"tnd":{"Ижроси энди бошланади":1,"мақсадга эришиш эҳтимоли юқори":1},"ysh":0.5},
          {"o":"Иқтисодиёт ва молия вазирлиги","n":23,"idx":69.6,"bar":{"high":15,"risk":6,"other":2},"tnd":{"мақсадга эришиш эҳтимоли юқори":15,"ўсиш йўқ":2,"Маълумот киритилмаган":2,"тенденция етарли эмас, бажарилмаслик хавфи бор":4},"ysh":0.6521739130434783},
          {"o":"Адлия вазирлиги","n":14,"idx":67.9,"bar":{"high":9,"risk":4,"other":1},"tnd":{"тенденция етарли эмас, бажарилмаслик хавфи бор":3,"мақсадга эришиш эҳтимоли юқори":9,"ўсиш йўқ":1,"Ижроси энди бошланади":1},"ysh":0.6428571428571429},
          {"o":"Маданият вазирлиги","n":6,"idx":66.7,"bar":{"high":4,"risk":2,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":4,"ўсиш йўқ":2},"ysh":0.6666666666666666},
          {"o":"Сув хўжалиги вазирлиги","n":6,"idx":66.7,"bar":{"high":4,"risk":2,"other":0},"tnd":{"ўсиш йўқ":1,"мақсадга эришиш эҳтимоли юқори":4,"тенденция етарли эмас, бажарилмаслик хавфи бор":1},"ysh":0.6666666666666666},
          {"o":"Инвестициялар, саноат ва савдо вазирлиги","n":3,"idx":66.7,"bar":{"high":2,"risk":1,"other":0},"tnd":{"тенденция етарли эмас, бажарилмаслик хавфи бор":1,"мақсадга эришиш эҳтимоли юқори":2},"ysh":0.6666666666666666},
          {"o":"Бошқарув самарадорлиги агентлиги","n":12,"idx":66.7,"bar":{"high":7,"risk":3,"other":2},"tnd":{"мақсадга эришиш эҳтимоли юқори":7,"тенденция етарли эмас, бажарилмаслик хавфи бор":3,"Маълумот киритилмаган":2},"ysh":0.5833333333333334},
          {"o":"Миграция агентлиги","n":3,"idx":66.7,"bar":{"high":1,"risk":0,"other":2},"tnd":{"мақсадга эришиш эҳтимоли юқори":1,"Маълумот киритилмаган":1,"Ижроси энди бошланади":1},"ysh":0.3333333333333333},
          {"o":"Мактабгача ва мактаб таълими вазирлиги","n":13,"idx":65.4,"bar":{"high":7,"risk":3,"other":3},"tnd":{"Ижроси энди бошланади":1,"мақсадга эришиш эҳтимоли юқори":7,"ўсиш йўқ":3,"Маълумот киритилмаган":2},"ysh":0.5384615384615384},
          {"o":"Олий таълим, фан ва инновациялар вазирлиги","n":23,"idx":65.2,"bar":{"high":12,"risk":5,"other":6},"tnd":{"ўсиш йўқ":5,"мақсадга эришиш эҳтимоли юқори":12,"Ижроси энди бошланади":4,"Маълумот киритилмаган":2},"ysh":0.5217391304347826},
          {"o":"Энергетика вазирлиги","n":5,"idx":60.0,"bar":{"high":3,"risk":2,"other":0},"tnd":{"ўсиш йўқ":2,"мақсадга эришиш эҳтимоли юқори":3},"ysh":0.6},
          {"o":"Соғлиқни сақлаш вазирлиги","n":22,"idx":59.1,"bar":{"high":13,"risk":9,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":13,"ўсиш йўқ":9},"ysh":0.5909090909090909},
          {"o":"Экология ва иқлим ўзгариши миллий қўмитаси","n":12,"idx":58.3,"bar":{"high":6,"risk":4,"other":2},"tnd":{"мақсадга эришиш эҳтимоли юқори":6,"тенденция етарли эмас, бажарилмаслик хавфи бор":2,"Ижроси энди бошланади":2,"ўсиш йўқ":2},"ysh":0.5},
          {"o":"Рақамли технологиялар вазирлиги","n":12,"idx":58.3,"bar":{"high":5,"risk":3,"other":4},"tnd":{"Ижроси энди бошланади":3,"мақсадга эришиш эҳтимоли юқори":5,"ўсиш йўқ":3,"Маълумот киритилмаган":1},"ysh":0.4166666666666667},
          {"o":"Инсон ҳуқуқлари бўйича Миллий марказ","n":2,"idx":50.0,"bar":{"high":1,"risk":1,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1,"тенденция етарли эмас, бажарилмаслик хавфи бор":1},"ysh":0.5},
          {"o":"Эл-юрт умиди жамғармаси","n":3,"idx":50.0,"bar":{"high":1,"risk":1,"other":1},"tnd":{"мақсадга эришиш эҳтимоли юқори":1,"Маълумот киритилмаган":1,"тенденция етарли эмас, бажарилмаслик хавфи бор":1},"ysh":0.3333333333333333},
          {"o":"Бош прокуратура","n":5,"idx":50.0,"bar":{"high":0,"risk":0,"other":5},"tnd":{"Маълумот киритилмаган":2,"Ижроси энди бошланади":3},"ysh":0.0},
          {"o":"Олий суд","n":3,"idx":50.0,"bar":{"high":0,"risk":0,"other":3},"tnd":{"Маълумот киритилмаган":2,"Ижроси энди бошланади":1},"ysh":0.0},
          {"o":"бошқа ҳуқуқни муҳофаза қилувчи органлар","n":2,"idx":50.0,"bar":{"high":0,"risk":0,"other":2},"tnd":{"Маълумот киритилмаган":2},"ysh":0.0},
          {"o":"Коррупцияга қарши курашиш агентлиги","n":2,"idx":50.0,"bar":{"high":0,"risk":0,"other":2},"tnd":{"Ижроси энди бошланади":2},"ysh":0.0},
          {"o":"Оммавий ахборот воситалари учун контент тайёрлаш маркази","n":2,"idx":50.0,"bar":{"high":0,"risk":0,"other":2},"tnd":{"Маълумот киритилмаган":1,"Ижроси энди бошланади":1},"ysh":0.0},
          {"o":"Маданий мерос агентлиги","n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Ижроси энди бошланади":1},"ysh":0.0},
          {"o":"ЮНЕСКО ишлари бўйича Ўзбекистон Республикаси миллий комиссияси","n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Ижроси энди бошланади":1},"ysh":0.0},
          {"o":"Божхона қўмитаси","n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0},
          {"o":"Енгил саноатни ривожлантириш агентлиги","n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0},
          {"o":"Ўзтўқимачилик-саноат уюшмаси","n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0},
          {"o":"Рақобатни ривожлантириш ва истеъмолчилар ҳуқуқларини ҳимоя қилиш қўмитаси","n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0},
          {"o":"Телекоммуни-кацияларни тартибга солиш агентлиги","n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0},
          {"o":"Креатив иқтисодиётни ривожлантириш бўйича республика кенгаши","n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0},
          {"o":"Маданият ва санъатни ривожлантириш жамғармаси","n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0},
          {"o":"Вазирлар Маҳкамаси","n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0},
          {"o":"барча тезкор хизматларни кўрсатувчи ташкилотлар","n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0},
          {"o":"Сиёсий партияларнинг марказий кенгашлари, Оила ва хотин-қизлар қўмитаси","n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0},
          {"o":"\"Эл-юрт умиди\" жамғармаси","n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0},
          {"o":"\"Ижтимоий фикр\" маркази","n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0},
          {"o":"Қорақалпоғистон Республикаси Вазирлар Кенгаши","n":15,"idx":40.0,"bar":{"high":4,"risk":7,"other":4},"tnd":{"мақсадга эришиш эҳтимоли юқори":4,"тенденция етарли эмас, бажарилмаслик хавфи бор":5,"Маълумот киритилмаган":4,"ўсиш йўқ":2},"ysh":0.26666666666666666},
          {"o":"вилоятлар ва Тошкент шаҳар ҳокимликлари","n":15,"idx":40.0,"bar":{"high":4,"risk":7,"other":4},"tnd":{"мақсадга эришиш эҳтимоли юқори":4,"тенденция етарли эмас, бажарилмаслик хавфи бор":5,"Маълумот киритилмаган":4,"ўсиш йўқ":2},"ysh":0.26666666666666666},
          {"o":"вазирлик ва идоралар","n":3,"idx":33.3,"bar":{"high":1,"risk":2,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1,"ўсиш йўқ":1,"тенденция етарли эмас, бажарилмаслик хавфи бор":1},"ysh":0.3333333333333333},
          {"o":"Қурилиш ва уй-жой коммунал хўжалиги вазирлиги","n":6,"idx":0.0,"bar":{"high":0,"risk":6,"other":0},"tnd":{"ўсиш йўқ":2,"тенденция етарли эмас, бажарилмаслик хавфи бор":4},"ysh":0.0},
          {"o":"Энергия самарадорлиги миллий агентлиги","n":2,"idx":0.0,"bar":{"high":0,"risk":2,"other":0},"tnd":{"ўсиш йўқ":2},"ysh":0.0},
          {"o":"Миллий гвардия","n":2,"idx":0.0,"bar":{"high":0,"risk":2,"other":0},"tnd":{"тенденция етарли эмас, бажарилмаслик хавфи бор":2},"ysh":0.0},
          {"o":"Ижтимоий ҳимоя миллий агентлиги","n":1,"idx":0.0,"bar":{"high":0,"risk":1,"other":0},"tnd":{"тенденция етарли эмас, бажарилмаслик хавфи бор":1},"ysh":0.0},
          {"o":"\"Иссиқлик таъминоти\" АЖ","n":1,"idx":0.0,"bar":{"high":0,"risk":1,"other":0},"tnd":{"ўсиш йўқ":1},"ysh":0.0},
          {"o":"Қурилиш ва уй-жой коммунал хўжалиги соҳасида назорат қилиш инспекцияси","n":1,"idx":0.0,"bar":{"high":0,"risk":1,"other":0},"tnd":{"тенденция етарли эмас, бажарилмаслик хавфи бор":1},"ysh":0.0},
          {"o":"\"Ўзсувтаъминот\" АЖ","n":1,"idx":0.0,"bar":{"high":0,"risk":1,"other":0},"tnd":{"тенденция етарли эмас, бажарилмаслик хавфи бор":1},"ysh":0.0},
          {"o":"Саноат, радиация ва ядро хавфсизлиги қўмитаси","n":1,"idx":0.0,"bar":{"high":0,"risk":1,"other":0},"tnd":{"тенденция етарли эмас, бажарилмаслик хавфи бор":1},"ysh":0.0},
          {"o":"Судьялар олий кенгаши","n":1,"idx":0.0,"bar":{"high":0,"risk":1,"other":0},"tnd":{"тенденция етарли эмас, бажарилмаслик хавфи бор":1},"ysh":0.0}
        ]
      },
      "head": {
        "left": [
          {"o":"Адлия вазирлиги","n":10,"anom":0,"idx":100.0,"bar":{"done":10,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Туризм қўмитаси","n":8,"anom":0,"idx":100.0,"bar":{"done":8,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Ижтимоий ҳимоя миллий агентлиги","n":7,"anom":0,"idx":100.0,"bar":{"done":7,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Оила ва хотин-қизлар қўмитаси","n":6,"anom":0,"idx":100.0,"bar":{"done":6,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Ёшлар ишлари агентлиги","n":6,"anom":0,"idx":100.0,"bar":{"done":6,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Камбағалликни қисқартириш ва бандлик вазирлиги","n":5,"anom":0,"idx":100.0,"bar":{"done":5,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Фавқулодда вазиятлар вазирлиги","n":3,"anom":0,"idx":100.0,"bar":{"done":3,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Республика Маънавият ва маърифат маркази","n":2,"anom":0,"idx":100.0,"bar":{"done":2,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Агросаноат мажмуи устидан назорат қилиш инспекцияси","n":2,"anom":0,"idx":100.0,"bar":{"done":2,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Техник жиҳатдан тартибга солиш агентлиги","n":2,"anom":0,"idx":100.0,"bar":{"done":2,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Рақобатни ривожлантириш ва истеъмолчилар ҳуқуқларини ҳимоя қилиш қўмитаси","n":2,"anom":0,"idx":100.0,"bar":{"done":2,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Сув хўжалиги вазирлиги","n":2,"anom":0,"idx":100.0,"bar":{"done":2,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Миграция агентлиги","n":2,"anom":0,"idx":100.0,"bar":{"done":2,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Марказий банк","n":1,"anom":1,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Электр энергияси, нефть маҳсулотлари ва газдан фойдаланишни назорат қилиш инспекцияси","n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Бизнес-омбудсман","n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Қурилиш ва уй-жой коммунал хўжалиги соҳасида назорат қилиш инспекцияси","n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"\"Ўзсувтаъминот\" АЖ","n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Ўзсувтаъминот АЖ","n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Саноат, радиация ва ядро хавфсизлиги қўмитаси","n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Дин ишлари бўйича қўмита","n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},
          {"o":"Олий таълим, фан ва инновациялар вазирлиги","n":10,"anom":0,"idx":99.7,"bar":{"done":9,"late":1,"fail":0},"qz":0,"nd":0,"ysh":0.9},
          {"o":"Ички ишлар вазирлиги","n":1,"anom":0,"idx":99.0,"bar":{"done":0,"late":1,"fail":0},"qz":0,"nd":0,"ysh":0.0},
          {"o":"Маданий мерос агентлиги","n":4,"anom":0,"idx":98.8,"bar":{"done":3,"late":1,"fail":0},"qz":0,"nd":0,"ysh":0.75},
          {"o":"Рақамли технологиялар вазирлиги","n":11,"anom":0,"idx":98.7,"bar":{"done":9,"late":2,"fail":0},"qz":0,"nd":0,"ysh":0.8181818181818182},
          {"o":"Маданият вазирлиги","n":17,"anom":0,"idx":97.3,"bar":{"done":14,"late":2,"fail":1},"qz":1,"nd":0,"ysh":0.8235294117647058},
          {"o":"Инвестициялар, саноат ва савдо вазирлиги","n":12,"anom":0,"idx":97.2,"bar":{"done":8,"late":4,"fail":0},"qz":0,"nd":0,"ysh":0.6666666666666666},
          {"o":"Соғлиқни сақлаш вазирлиги","n":11,"anom":0,"idx":97.1,"bar":{"done":7,"late":4,"fail":0},"qz":0,"nd":0,"ysh":0.6363636363636364},
          {"o":"Ташқи ишлар вазирлиги","n":1,"anom":0,"idx":97.0,"bar":{"done":0,"late":1,"fail":0},"qz":0,"nd":0,"ysh":0.0},
          {"o":"Транспорт вазирлиги","n":12,"anom":0,"idx":95.2,"bar":{"done":5,"late":7,"fail":0},"qz":0,"nd":0,"ysh":0.4166666666666667},
          {"o":"Тоғ-кон саноати ва геология вазирлиги","n":7,"anom":0,"idx":93.6,"bar":{"done":4,"late":3,"fail":0},"qz":0,"nd":0,"ysh":0.5714285714285714},
          {"o":"Миллатлараро муносабатлар ва хориждаги ватандошлар масалалари бўйича қўмита","n":5,"anom":0,"idx":93.4,"bar":{"done":4,"late":0,"fail":1},"qz":1,"nd":0,"ysh":0.8},
          {"o":"Энергетика вазирлиги","n":5,"anom":0,"idx":92.0,"bar":{"done":1,"late":3,"fail":1},"qz":1,"nd":0,"ysh":0.2},
          {"o":"Спорт вазирлиги","n":5,"anom":0,"idx":91.6,"bar":{"done":4,"late":0,"fail":1},"qz":1,"nd":0,"ysh":0.8},
          {"o":"Урбанизация ва уй-жой бозорини барқарор ривожлантириш миллий қўмитаси","n":4,"anom":0,"idx":91.5,"bar":{"done":3,"late":0,"fail":1},"qz":1,"nd":0,"ysh":0.75},
          {"o":"Экология ва иқлим ўзгариши миллий қўмитаси","n":15,"anom":0,"idx":90.1,"bar":{"done":11,"late":2,"fail":2},"qz":2,"nd":0,"ysh":0.7333333333333333},
          {"o":"Иқтисодиёт ва молия вазирлиги","n":9,"anom":0,"idx":76.0,"bar":{"done":6,"late":1,"fail":2},"qz":0,"nd":2,"ysh":0.6666666666666666},
          {"o":"Мактабгача ва мактаб таълими вазирлиги","n":4,"anom":0,"idx":75.0,"bar":{"done":3,"late":0,"fail":1},"qz":0,"nd":1,"ysh":0.75},
          {"o":"Қишлоқ хўжалиги вазирлиги","n":9,"anom":0,"idx":66.4,"bar":{"done":5,"late":1,"fail":3},"qz":1,"nd":2,"ysh":0.5555555555555556},
          {"o":"Қурилиш ва уй-жой коммунал хўжалиги вазирлиги","n":3,"anom":0,"idx":64.0,"bar":{"done":0,"late":2,"fail":1},"qz":0,"nd":1,"ysh":0.0},
          {"o":"Таълим сифатини таъминлаш миллий агентлиги","n":1,"anom":0,"idx":13.0,"bar":{"done":0,"late":0,"fail":1},"qz":1,"nd":0,"ysh":0.0},
          {"o":"Енгил саноатни ривожлантириш агентлиги","n":3,"anom":0,"idx":0.0,"bar":{"done":0,"late":0,"fail":3},"qz":0,"nd":3,"ysh":0.0},
          {"o":"Нуроний жамғармаси","n":1,"anom":0,"idx":0.0,"bar":{"done":0,"late":0,"fail":1},"qz":0,"nd":1,"ysh":0.0},
          {"o":"Яширин иқтисодиётни қисқартириш бўйича махсус комиссия","n":1,"anom":0,"idx":0.0,"bar":{"done":0,"late":0,"fail":1},"qz":0,"nd":1,"ysh":0.0},
          {"o":"Энергетика бозорини ривожлантириш ва тартибга солиш агентлиги","n":1,"anom":0,"idx":0.0,"bar":{"done":0,"late":0,"fail":1},"qz":0,"nd":1,"ysh":0.0},
          {"o":"Олий суд","n":1,"anom":0,"idx":0.0,"bar":{"done":0,"late":0,"fail":1},"qz":0,"nd":1,"ysh":0.0}
        ],
        "right": [
          {"o":"Ички ишлар вазирлиги","n":5,"idx":100.0,"bar":{"high":5,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":5},"ysh":1.0},
          {"o":"Ташқи ишлар вазирлиги","n":4,"idx":100.0,"bar":{"high":4,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":4},"ysh":1.0},
          {"o":"Миллий антидопинг агентлиги","n":2,"idx":100.0,"bar":{"high":2,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":2},"ysh":1.0},
          {"o":"Давлат активларини бошқариш агентлиги","n":2,"idx":100.0,"bar":{"high":2,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":2},"ysh":1.0},
          {"o":"Атом энергияси агентлиги","n":2,"idx":100.0,"bar":{"high":2,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":2},"ysh":1.0},
          {"o":"Жаҳон савдо ташкилоти масалалари бўйича махсус вакил","n":2,"idx":100.0,"bar":{"high":2,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":2},"ysh":1.0},
          {"o":"Таълим сифатини таъминлаш миллий агентлиги","n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0},
          {"o":"Оила ва хотин-қизлар қўмитаси","n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0},
          {"o":"Солиқ қўмитаси","n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0},
          {"o":"Стратегик ривожланиш ва ислоҳотлар агентлиги","n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0},
          {"o":"Олий Мажлис палаталари, Адлия вазирлиги","n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0},
          {"o":"\"Тараққиёт стратегияси\" маркази","n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0},
          {"o":"Миллий статистика қўмитаси","n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0},
          {"o":"Дин ишлари бўйича қўмита","n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0},
          {"o":"Миллатлараро муносабатлар ва хориждаги ватандошлар масалалари бўйича қўмита","n":4,"idx":87.5,"bar":{"high":3,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1,"мақсадга эришиш эҳтимоли юқори":3},"ysh":0.75},
          {"o":"Спорт вазирлиги","n":7,"idx":85.7,"bar":{"high":5,"risk":0,"other":2},"tnd":{"мақсадга эришиш эҳтимоли юқори":5,"Ижроси энди бошланади":2},"ysh":0.7142857142857143},
          {"o":"Транспорт вазирлиги","n":3,"idx":83.3,"bar":{"high":2,"risk":0,"other":1},"tnd":{"Ижроси энди бошланади":1,"мақсадга эришиш эҳтимоли юқори":2},"ysh":0.6666666666666666},
          {"o":"Мудофаа вазирлиги","n":5,"idx":80.0,"bar":{"high":4,"risk":1,"other":0},"tnd":{"ўсиш йўқ":1,"мақсадга эришиш эҳтимоли юқори":4},"ysh":0.8},
          {"o":"Бошқарув самарадорлиги агентлиги","n":4,"idx":75.0,"bar":{"high":3,"risk":1,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":3,"тенденция етарли эмас, бажарилмаслик хавфи бор":1},"ysh":0.75},
          {"o":"Гендер тенгликни таъминлаш масалалари бўйича комиссия","n":2,"idx":75.0,"bar":{"high":1,"risk":0,"other":1},"tnd":{"мақсадга эришиш эҳтимоли юқори":1,"Маълумот киритилмаган":1},"ysh":0.5},
          {"o":"Эл-юрт умиди жамғармаси","n":2,"idx":75.0,"bar":{"high":1,"risk":0,"other":1},"tnd":{"мақсадга эришиш эҳтимоли юқори":1,"Маълумот киритилмаган":1},"ysh":0.5},
          {"o":"Марказий банк","n":2,"idx":75.0,"bar":{"high":1,"risk":0,"other":1},"tnd":{"мақсадга эришиш эҳтимоли юқори":1,"Ижроси энди бошланади":1},"ysh":0.5},
          {"o":"Туризм қўмитаси","n":2,"idx":75.0,"bar":{"high":1,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1,"мақсадга эришиш эҳтимоли юқори":1},"ysh":0.5},
          {"o":"Олий Мажлис Сенати","n":2,"idx":75.0,"bar":{"high":1,"risk":0,"other":1},"tnd":{"Ижроси энди бошланади":1,"мақсадга эришиш эҳтимоли юқори":1},"ysh":0.5},
          {"o":"Адлия вазирлиги","n":9,"idx":72.2,"bar":{"high":6,"risk":2,"other":1},"tnd":{"тенденция етарли эмас, бажарилмаслик хавфи бор":2,"мақсадга эришиш эҳтимоли юқори":6,"Ижроси энди бошланади":1},"ysh":0.6666666666666666},
          {"o":"Олий таълим, фан ва инновациялар вазирлиги","n":21,"idx":69.0,"bar":{"high":12,"risk":4,"other":5},"tnd":{"ўсиш йўқ":4,"мақсадга эришиш эҳтимоли юқори":12,"Ижроси энди бошланади":4,"Маълумот киритилмаган":1},"ysh":0.5714285714285714},
          {"o":"Иқтисодиёт ва молия вазирлиги","n":12,"idx":66.7,"bar":{"high":8,"risk":4,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":8,"ўсиш йўқ":2,"тенденция етарли эмас, бажарилмаслик хавфи бор":2},"ysh":0.6666666666666666},
          {"o":"Маданият вазирлиги","n":6,"idx":66.7,"bar":{"high":4,"risk":2,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":4,"ўсиш йўқ":2},"ysh":0.6666666666666666},
          {"o":"Сув хўжалиги вазирлиги","n":6,"idx":66.7,"bar":{"high":4,"risk":2,"other":0},"tnd":{"ўсиш йўқ":1,"мақсадга эришиш эҳтимоли юқори":4,"тенденция етарли эмас, бажарилмаслик хавфи бор":1},"ysh":0.6666666666666666},
          {"o":"Камбағалликни қисқартириш ва бандлик вазирлиги","n":3,"idx":66.7,"bar":{"high":2,"risk":1,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":2,"ўсиш йўқ":1},"ysh":0.6666666666666666},
          {"o":"Қорақалпоғистон Республикаси Вазирлар Кенгаши","n":9,"idx":66.7,"bar":{"high":4,"risk":1,"other":4},"tnd":{"мақсадга эришиш эҳтимоли юқори":4,"тенденция етарли эмас, бажарилмаслик хавфи бор":1,"Маълумот киритилмаган":4},"ysh":0.4444444444444444},
          {"o":"Миграция агентлиги","n":3,"idx":66.7,"bar":{"high":1,"risk":0,"other":2},"tnd":{"мақсадга эришиш эҳтимоли юқори":1,"Маълумот киритилмаган":1,"Ижроси энди бошланади":1},"ysh":0.3333333333333333},
          {"o":"Энергетика вазирлиги","n":5,"idx":60.0,"bar":{"high":3,"risk":2,"other":0},"tnd":{"ўсиш йўқ":2,"мақсадга эришиш эҳтимоли юқори":3},"ysh":0.6},
          {"o":"Қишлоқ хўжалиги вазирлиги","n":5,"idx":60.0,"bar":{"high":1,"risk":0,"other":4},"tnd":{"Ижроси энди бошланади":2,"Маълумот киритилмаган":2,"мақсадга эришиш эҳтимоли юқори":1},"ysh":0.2},
          {"o":"Соғлиқни сақлаш вазирлиги","n":22,"idx":59.1,"bar":{"high":13,"risk":9,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":13,"ўсиш йўқ":9},"ysh":0.5909090909090909},
          {"o":"Рақамли технологиялар вазирлиги","n":9,"idx":55.6,"bar":{"high":4,"risk":3,"other":2},"tnd":{"Ижроси энди бошланади":2,"мақсадга эришиш эҳтимоли юқори":4,"ўсиш йўқ":3},"ysh":0.4444444444444444},
          {"o":"Экология ва иқлим ўзгариши миллий қўмитаси","n":11,"idx":54.5,"bar":{"high":5,"risk":4,"other":2},"tnd":{"тенденция етарли эмас, бажарилмаслик хавфи бор":2,"Ижроси энди бошланади":2,"мақсадга эришиш эҳтимоли юқори":5,"ўсиш йўқ":2},"ysh":0.45454545454545453},
          {"o":"Инсон ҳуқуқлари бўйича Миллий марказ","n":2,"idx":50.0,"bar":{"high":1,"risk":1,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1,"тенденция етарли эмас, бажарилмаслик хавфи бор":1},"ysh":0.5},
          {"o":"Мактабгача ва мактаб таълими вазирлиги","n":8,"idx":50.0,"bar":{"high":3,"risk":3,"other":2},"tnd":{"Ижроси энди бошланади":1,"ўсиш йўқ":3,"мақсадга эришиш эҳтимоли юқори":3,"Маълумот киритилмаган":1},"ysh":0.375},
          {"o":"Олий суд","n":3,"idx":50.0,"bar":{"high":0,"risk":0,"other":3},"tnd":{"Маълумот киритилмаган":2,"Ижроси энди бошланади":1},"ysh":0.0},
          {"o":"Бош прокуратура","n":3,"idx":50.0,"bar":{"high":0,"risk":0,"other":3},"tnd":{"Ижроси энди бошланади":3},"ysh":0.0},
          {"o":"Коррупцияга қарши курашиш агентлиги","n":2,"idx":50.0,"bar":{"high":0,"risk":0,"other":2},"tnd":{"Ижроси энди бошланади":2},"ysh":0.0},
          {"o":"Оммавий ахборот воситалари учун контент тайёрлаш маркази","n":2,"idx":50.0,"bar":{"high":0,"risk":0,"other":2},"tnd":{"Маълумот киритилмаган":1,"Ижроси энди бошланади":1},"ysh":0.0},
          {"o":"Маданий мерос агентлиги","n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Ижроси энди бошланади":1},"ysh":0.0},
          {"o":"Божхона қўмитаси","n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0},
          {"o":"Енгил саноатни ривожлантириш агентлиги","n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0},
          {"o":"Рақобатни ривожлантириш ва истеъмолчилар ҳуқуқларини ҳимоя қилиш қўмитаси","n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0},
          {"o":"Телекоммуни-кацияларни тартибга солиш агентлиги","n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0},
          {"o":"Креатив иқтисодиётни ривожлантириш бўйича республика кенгаши","n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0},
          {"o":"Вазирлар Маҳкамаси","n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0},
          {"o":"Сиёсий партияларнинг марказий кенгашлари, Оила ва хотин-қизлар қўмитаси","n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0},
          {"o":"\"Эл-юрт умиди\" жамғармаси","n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0},
          {"o":"Қурилиш ва уй-жой коммунал хўжалиги вазирлиги","n":2,"idx":0.0,"bar":{"high":0,"risk":2,"other":0},"tnd":{"тенденция етарли эмас, бажарилмаслик хавфи бор":2},"ysh":0.0},
          {"o":"Миллий гвардия","n":2,"idx":0.0,"bar":{"high":0,"risk":2,"other":0},"tnd":{"тенденция етарли эмас, бажарилмаслик хавфи бор":2},"ysh":0.0},
          {"o":"Ижтимоий ҳимоя миллий агентлиги","n":1,"idx":0.0,"bar":{"high":0,"risk":1,"other":0},"tnd":{"тенденция етарли эмас, бажарилмаслик хавфи бор":1},"ysh":0.0},
          {"o":"\"Иссиқлик таъминоти\" АЖ","n":1,"idx":0.0,"bar":{"high":0,"risk":1,"other":0},"tnd":{"ўсиш йўқ":1},"ysh":0.0},
          {"o":"Энергия самарадорлиги миллий агентлиги","n":1,"idx":0.0,"bar":{"high":0,"risk":1,"other":0},"tnd":{"ўсиш йўқ":1},"ysh":0.0},
          {"o":"Қурилиш ва уй-жой коммунал хўжалиги соҳасида назорат қилиш инспекцияси","n":1,"idx":0.0,"bar":{"high":0,"risk":1,"other":0},"tnd":{"тенденция етарли эмас, бажарилмаслик хавфи бор":1},"ysh":0.0},
          {"o":"\"Ўзсувтаъминот\" АЖ","n":1,"idx":0.0,"bar":{"high":0,"risk":1,"other":0},"tnd":{"тенденция етарли эмас, бажарилмаслик хавфи бор":1},"ysh":0.0},
          {"o":"Судьялар олий кенгаши","n":1,"idx":0.0,"bar":{"high":0,"risk":1,"other":0},"tnd":{"тенденция етарли эмас, бажарилмаслик хавфи бор":1},"ysh":0.0}
        ]
      }
    },
    "meta": {
      "asof": "2026 йил 25 июль ҳолатидаги ярим йиллик свод (uz2030_master_svod_437_25-07_v2)",
      "indDue": 219,
      "indPr": 218,
      "indAll": 437
    }
  };
  const DUE = [
    ['done', 'var(--u30-green)', 'Bajarilgan'],
    ['late', 'var(--u30-yellow)', 'Kechikmoqda'],
    ['fail', 'var(--u30-red)', 'Bajarilmagan']
  ];
  const YEAR = [
    ['high', 'var(--u30-blue)', 'Bajarish ehtimoli yuqori'],
    ['risk', 'var(--u30-orange)', 'Kechikish xavfi bor'],
    ['other', '#94a3b8', 'Boshqalar']
  ];
  const state = {mode: 'all', primary: 'count', primaryDir: 'desc', secondary: 'dueIndex', secondaryDir: 'desc', limit: 30, printLimit: null};
  const app = root.querySelector('[data-org-matrix-app]');
  if (!app) return;

  const esc = (value) => String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
  const fmt = (value) => Number.isFinite(value) ? String(Math.round(value * 10) / 10).replace('.', ',') : '—';
  const indexColor = (value) => value >= 85 ? 'var(--u30-green-dark)' : value >= 60 ? '#b45309' : 'var(--u30-red-dark)';
  const normalize = (value) => String(value || '').toLowerCase().replace(/[«»“”„"']/g, '').replace(/[‐‑–—]/g, '-').replace(/\s+/g, ' ').trim();
  const cleanName = (value) => String(value || '').replace(/[«»“”„"]/g, '').replace(/\s+/g, ' ').trim();

  const CYR = {
    'А':'A','а':'a','Б':'B','б':'b','В':'V','в':'v','Г':'G','г':'g','Д':'D','д':'d',
    'Ё':'Yo','ё':'yo','Ж':'J','ж':'j','З':'Z','з':'z','И':'I','и':'i','Й':'Y','й':'y',
    'К':'K','к':'k','Л':'L','л':'l','М':'M','м':'m','Н':'N','н':'n','О':'O','о':'o',
    'П':'P','п':'p','Р':'R','р':'r','С':'S','с':'s','Т':'T','т':'t','У':'U','у':'u',
    'Ф':'F','ф':'f','Х':'X','х':'x','Ц':'Ts','ц':'ts','Ч':'Ch','ч':'ch','Ш':'Sh','ш':'sh',
    'Щ':'Shch','щ':'shch','Ы':'I','ы':'i','Э':'E','э':'e','Ю':'Yu','ю':'yu','Я':'Ya','я':'ya',
    'Ў':'Oʻ','ў':'oʻ','Қ':'Q','қ':'q','Ғ':'Gʻ','ғ':'gʻ','Ҳ':'H','ҳ':'h','Ъ':'ʼ','ъ':'ʼ','Ь':'','ь':''
  };

  function toLatin(value) {
    const text = String(value || '');
    let out = '';
    for (let i = 0; i < text.length; i += 1) {
      const ch = text[i];
      if (ch === 'Е' || ch === 'е') {
        const prev = i ? text[i - 1] : '';
        const boundary = !i || /[\s\-–—(\[\{«"'ʼъьАЕЁИОУЎЭЮЯаеёиоуўэюя]/.test(prev);
        out += ch === 'Е' ? (boundary ? 'Ye' : 'E') : (boundary ? 'ye' : 'e');
      } else {
        out += Object.prototype.hasOwnProperty.call(CYR, ch) ? CYR[ch] : ch;
      }
    }
    return out.replace(/\s+/g, ' ').trim();
  }

  function stackedBar(status, definition) {
    if (!status) return '<div class="u30-matrix-metric is-empty">—</div>';
    const total = definition.reduce((sum, [key]) => sum + (status.bar[key] || 0), 0) || 1;
    return `<div class="u30-matrix-bar">${definition.map(([key, color, label]) => {
      const value = status.bar[key] || 0;
      if (!value) return '';
      const pct = value / total * 100;
      return `<b class="${key === 'late' ? 'is-yellow' : ''}" style="width:${pct.toFixed(2)}%;background:${color}" title="${esc(label)}: ${value} ta (${Math.round(pct)}%)">${pct >= 8 ? value : ''}</b>`;
    }).join('')}</div>`;
  }

  function detailHtml(status, isDue) {
    if (!status) return '';
    const rows = [];
    if (isDue) {
      if (status.nd) rows.push(['is-fail', 'Maʼlumot kiritilmagan', String(status.nd)]);
      if (status.anom) rows.push(['is-note', 'Rejadan yuqori natija', String(status.anom)]);
    } else {
      const trends = status.tnd || {};
      const risk = [];
      const other = [];
      const known = new Set([
        'мақсадга эришиш эҳтимоли юқори', 'ўсиш йўқ', 'Ижроси энди бошланади',
        'Маълумот киритилмаган', 'тенденция етарли эмас, бажарилмаслик хавфи бор'
      ]);
      if (trends['ўсиш йўқ']) risk.push(`oʻsish yoʻq ${trends['ўсиш йўқ']}`);
      if (trends['тенденция етарли эмас, бажарилмаслик хавфи бор']) risk.push(`tendensiya yetarli emas ${trends['тенденция етарли эмас, бажарилмаслик хавфи бор']}`);
      if (trends['Ижроси энди бошланади']) other.push(`ijro endi boshlanadi ${trends['Ижроси энди бошланади']}`);
      if (trends['Маълумот киритилмаган']) other.push(`maʼlumot kiritilmagan ${trends['Маълумот киритилмаган']}`);
      for (const [key, value] of Object.entries(trends)) {
        if (value && !known.has(key)) other.push(`${toLatin(key)} ${value}`);
      }
      if (risk.length) rows.push(['is-risk', 'Xavf sababi', risk.join(' · ')]);
      if (other.length) rows.push(['is-other', 'Boshqa holatlar', other.join(' · ')]);
    }
    return rows.map(([kind, label, text]) => `<span class="u30-matrix-detail-line ${kind}"><i></i><span class="u30-matrix-detail-label">${esc(label)}:</span><span class="u30-matrix-detail-text">${esc(text)}</span></span>`).join('');
  }

  function mergeSide(list, isDue) {
    const map = new Map();
    for (const status of list) {
      const key = normalize(status.o);
      if (!map.has(key)) {
        map.set(key, {...status, o: cleanName(status.o), bar: {...status.bar}, tnd: {...(status.tnd || {})}, _weight: status.n || 0, _idxSum: (status.idx || 0) * (status.n || 0)});
        continue;
      }
      const current = map.get(key);
      const weight = status.n || 0;
      current.o = current.o.length <= cleanName(status.o).length ? current.o : cleanName(status.o);
      current.n += status.n || 0;
      current._weight += weight;
      current._idxSum += (status.idx || 0) * weight;
      for (const metric of Object.keys(status.bar || {})) current.bar[metric] = (current.bar[metric] || 0) + (status.bar[metric] || 0);
      if (isDue) {
        current.qz = (current.qz || 0) + (status.qz || 0);
        current.nd = (current.nd || 0) + (status.nd || 0);
        current.anom = (current.anom || 0) + (status.anom || 0);
      } else {
        for (const trend of Object.keys(status.tnd || {})) current.tnd[trend] = (current.tnd[trend] || 0) + (status.tnd[trend] || 0);
      }
    }
    for (const item of map.values()) {
      item.idx = item._weight ? item._idxSum / item._weight : 0;
      delete item._weight;
      delete item._idxSum;
    }
    return map;
  }

  function combinedRows(dataset) {
    const dueMap = mergeSide(dataset.left, true);
    const yearMap = mergeSide(dataset.right, false);
    const keys = new Set([...dueMap.keys(), ...yearMap.keys()]);
    return [...keys].map((key) => {
      const due = dueMap.get(key) || null;
      const year = yearMap.get(key) || null;
      const dueCount = due?.n || 0;
      const yearCount = year?.n || 0;
      const total = dueCount + yearCount;
      const rawName = due?.o || year?.o || key;
      return {key, name: toLatin(rawName), due, year, total};
    });
  }

  function compareBy(key, direction, a, b) {
    if (!key || key === 'none') return 0;
    if (key === 'alpha') {
      const result = a.name.localeCompare(b.name, 'uz-Latn', {sensitivity: 'base'});
      return direction === 'asc' ? result : -result;
    }
    if (key === 'dueIndex') {
      const aValue = Number.isFinite(a.due?.idx) ? a.due.idx : null;
      const bValue = Number.isFinite(b.due?.idx) ? b.due.idx : null;
      if (aValue === null && bValue === null) return 0;
      if (aValue === null) return 1;
      if (bValue === null) return -1;
      const result = aValue - bValue;
      return direction === 'asc' ? result : -result;
    }
    const result = a.total - b.total;
    return direction === 'asc' ? result : -result;
  }

  function ordered(rows) {
    return [...rows].sort((a, b) => {
      const primary = compareBy(state.primary, state.primaryDir, a, b);
      if (primary) return primary;
      if (state.secondary !== state.primary) {
        const secondary = compareBy(state.secondary, state.secondaryDir, a, b);
        if (secondary) return secondary;
      }
      return a.name.localeCompare(b.name, 'uz-Latn', {sensitivity: 'base'});
    });
  }

  function metricCell(status, definition, isDue) {
    if (!status) return '<div class="u30-matrix-metric is-empty">—</div>';
    return `<div class="u30-matrix-metric"><div class="u30-matrix-metric-head"><strong>${status.n} ta</strong><span>koʻrsatkich</span></div>${stackedBar(status, definition)}<div class="u30-matrix-detail">${detailHtml(status, isDue)}</div></div>`;
  }

  function indexCell(status) {
    if (!status) return '<div class="u30-matrix-index is-empty">—</div>';
    return `<div class="u30-matrix-index" style="color:${indexColor(status.idx)}" title="Indeks: ${fmt(status.idx)}">${fmt(status.idx)}</div>`;
  }

  function criterionLabel(key) {
    if (key === 'alpha') return 'alifbo';
    if (key === 'dueIndex') return 'muddati kelgan indeks';
    if (key === 'count') return 'koʻrsatkichlar soni';
    return 'yoʻq';
  }

  function directionLabel(key, direction) {
    if (key === 'alpha') return direction === 'asc' ? 'A–Z' : 'Z–A';
    return direction === 'desc' ? '↓' : '↑';
  }

  function sortLabel() {
    const first = `${criterionLabel(state.primary)} ${directionLabel(state.primary, state.primaryDir)}`;
    if (!state.secondary || state.secondary === 'none' || state.secondary === state.primary) return first;
    return `${first} → ${criterionLabel(state.secondary)} ${directionLabel(state.secondary, state.secondaryDir)}`;
  }

  function render() {
    const rows = ordered(combinedRows(R.m[state.mode]));
    const activeLimit = state.printLimit === null ? state.limit : state.printLimit;
    const shown = rows.slice(0, activeLimit);
    app.innerHTML = `
      <div class="u30-matrix-statusbar" aria-label="Ranglar tavsifi">
        <div class="u30-matrix-status-col">
          <div class="u30-matrix-status-subtitle">Muddati kelgan</div>
          <div class="u30-matrix-status-items">
            ${DUE.map(([, color, label]) => `<div class="u30-matrix-status-item"><i style="background:${color}"></i><div class="u30-matrix-status-copy"><span class="u30-matrix-status-label">${label}</span></div></div>`).join('')}
          </div>
        </div>
        <div class="u30-matrix-status-col">
          <div class="u30-matrix-status-subtitle">III-IV-chorak bo‘yicha baholanadigan</div>
          <div class="u30-matrix-status-items is-year">
            ${YEAR.map(([, color, label]) => `<div class="u30-matrix-status-item"><i style="background:${color}"></i><div class="u30-matrix-status-copy"><span class="u30-matrix-status-label">${label}</span></div></div>`).join('')}
          </div>
        </div>
      </div>
      <div class="u30-matrix-wrap">
        <div class="u30-matrix-scroll" tabindex="0" aria-label="Tashkilotlar koʻrsatkichlari jadvali">
          <div class="u30-matrix" role="table" aria-label="Tashkilotlar kesimida yagona koʻrsatkichlar roʻyxati">
            <div class="u30-matrix-group" role="row">
              <div class="u30-matrix-rank-head" role="columnheader">№</div>
              <div class="u30-matrix-org-head" role="columnheader">Tashkilot</div>
              <div class="u30-matrix-group-head" role="columnheader"><div class="u30-matrix-group-title">Muddati kelgan</div><div class="u30-matrix-group-sub">${R.meta.indDue} ta koʻrsatkich</div></div>
              <div class="u30-matrix-index-head" role="columnheader">Indeks</div>
              <div class="u30-matrix-group-head" role="columnheader"><div class="u30-matrix-group-title">III-IV-chorak bo‘yicha baholanadigan ko‘rsatkichlar</div><div class="u30-matrix-group-sub">${R.meta.indPr} ta koʻrsatkich</div></div>
            </div>
            ${shown.map((row, index) => `<div class="u30-matrix-row" role="row">
              <div class="u30-matrix-rank" role="cell">${index + 1}</div>
              <div class="u30-matrix-org" role="cell">
                <div class="u30-matrix-org-main">
                  <div class="u30-matrix-org-copy"><span class="u30-matrix-org-name">${esc(row.name)}</span></div>
                  <div class="u30-matrix-org-count" title="Muddati kelgan: ${row.due?.n || 0} ta · III-IV-chorak: ${row.year?.n || 0} ta"><strong>${row.total}</strong><span>koʻrsatkich</span></div>
                </div>
              </div>
              ${metricCell(row.due, DUE, true)}
              ${indexCell(row.due)}
              ${metricCell(row.year, YEAR, false)}
            </div>`).join('')}
          </div>
          <div class="u30-matrix-foot"><span>${rows.length} ta tashkilot · yagona muvofiqlashtirilgan roʻyxat</span><span>saralash: ${sortLabel()}</span></div>
          ${rows.length > activeLimit && state.printLimit === null ? `<button class="u30-matrix-more" data-matrix-more type="button">Yana koʻrsatish · ${rows.length - activeLimit} ta qoldi</button>` : ''}
        </div>
      </div>`;

    root.querySelectorAll('[data-matrix-mode]').forEach((button) => {
      const active = button.dataset.matrixMode === state.mode;
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-pressed', String(active));
    });
    const primarySelect = root.querySelector('[data-matrix-primary]');
    const secondarySelect = root.querySelector('[data-matrix-secondary]');
    const primaryDir = root.querySelector('[data-matrix-primary-dir]');
    const secondaryDir = root.querySelector('[data-matrix-secondary-dir]');
    if (primarySelect) primarySelect.value = state.primary;
    if (secondarySelect) secondarySelect.value = state.secondary;
    if (primaryDir) primaryDir.textContent = directionLabel(state.primary, state.primaryDir);
    if (secondaryDir) {
      secondaryDir.textContent = state.secondary === 'none' ? '—' : directionLabel(state.secondary, state.secondaryDir);
      secondaryDir.disabled = state.secondary === 'none';
    }
    app.querySelector('[data-matrix-more]')?.addEventListener('click', () => { state.limit += 50; render(); });
  }

  root.querySelectorAll('[data-matrix-mode]').forEach((button) => button.addEventListener('click', () => {
    state.mode = button.dataset.matrixMode;
    state.limit = 30;
    render();
  }));

  root.querySelector('[data-matrix-primary]')?.addEventListener('change', (event) => {
    state.primary = event.target.value;
    state.primaryDir = state.primary === 'alpha' ? 'asc' : 'desc';
    if (state.secondary === state.primary) state.secondary = 'none';
    state.limit = 30;
    render();
  });

  root.querySelector('[data-matrix-secondary]')?.addEventListener('change', (event) => {
    state.secondary = event.target.value === state.primary ? 'none' : event.target.value;
    state.secondaryDir = state.secondary === 'alpha' ? 'asc' : 'desc';
    state.limit = 30;
    render();
  });

  root.querySelector('[data-matrix-primary-dir]')?.addEventListener('click', () => {
    state.primaryDir = state.primaryDir === 'asc' ? 'desc' : 'asc';
    state.limit = 30;
    render();
  });

  root.querySelector('[data-matrix-secondary-dir]')?.addEventListener('click', () => {
    if (state.secondary === 'none') return;
    state.secondaryDir = state.secondaryDir === 'asc' ? 'desc' : 'asc';
    state.limit = 30;
    render();
  });

  window.addEventListener('beforeprint', () => { state.printLimit = Infinity; render(); });
  window.addEventListener('afterprint', () => { state.printLimit = null; render(); });
  render();
})();
/* --- Рейтинг организаций (сортируемая таблица) --------------------------- */

(() => {
  'use strict';
  const report = document.querySelector('#uz2030-report[data-report-component]');
  const app = report?.querySelector('[data-r5-app]');
  if (!report || !app || app.dataset.ready === 'true') return;
  app.dataset.ready = 'true';

  const R5 = {
    "orgs": [
      {"o":"Соғлиқни сақлаш вазирлиги","tot":33,"h2":33,"h":{"n":11,"anom":0,"idx":97.1,"bar":{"done":7,"late":4,"fail":0},"qz":0,"nd":0,"ysh":0.6363636363636364},"y":{"n":22,"idx":59.1,"bar":{"high":13,"risk":9,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":13,"ўсиш йўқ":9},"ysh":0.5909090909090909}},
      {"o":"Олий таълим, фан ва инновациялар вазирлиги","tot":31,"h2":29,"h":{"n":10,"anom":0,"idx":99.7,"bar":{"done":9,"late":1,"fail":0},"qz":0,"nd":0,"ysh":0.9},"y":{"n":21,"idx":69.0,"bar":{"high":12,"risk":4,"other":5},"tnd":{"ўсиш йўқ":4,"мақсадга эришиш эҳтимоли юқори":12,"Ижроси энди бошланади":4,"Маълумот киритилмаган":1},"ysh":0.5714285714285714}},
      {"o":"Экология ва иқлим ўзгариши миллий қўмитаси","tot":26,"h2":25,"h":{"n":15,"anom":0,"idx":90.1,"bar":{"done":11,"late":2,"fail":2},"qz":2,"nd":0,"ysh":0.7333333333333333},"y":{"n":11,"idx":54.5,"bar":{"high":5,"risk":4,"other":2},"tnd":{"тенденция етарли эмас, бажарилмаслик хавфи бор":2,"Ижроси энди бошланади":2,"мақсадга эришиш эҳтимоли юқори":5,"ўсиш йўқ":2},"ysh":0.45454545454545453}},
      {"o":"Маданият вазирлиги","tot":23,"h2":23,"h":{"n":17,"anom":0,"idx":97.3,"bar":{"done":14,"late":2,"fail":1},"qz":1,"nd":0,"ysh":0.8235294117647058},"y":{"n":6,"idx":66.7,"bar":{"high":4,"risk":2,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":4,"ўсиш йўқ":2},"ysh":0.6666666666666666}},
      {"o":"Иқтисодиёт ва молия вазирлиги","tot":22,"h2":21,"h":{"n":9,"anom":0,"idx":76.0,"bar":{"done":6,"late":1,"fail":2},"qz":0,"nd":2,"ysh":0.6666666666666666},"y":{"n":13,"idx":65.4,"bar":{"high":8,"risk":4,"other":1},"tnd":{"мақсадга эришиш эҳтимоли юқори":8,"ўсиш йўқ":2,"Маълумот киритилмаган":1,"тенденция етарли эмас, бажарилмаслик хавфи бор":2},"ysh":0.6153846153846154}},
      {"o":"Рақамли технологиялар вазирлиги","tot":21,"h2":19,"h":{"n":11,"anom":0,"idx":98.7,"bar":{"done":9,"late":2,"fail":0},"qz":0,"nd":0,"ysh":0.8181818181818182},"y":{"n":10,"idx":55.0,"bar":{"high":4,"risk":3,"other":3},"tnd":{"Ижроси энди бошланади":2,"мақсадга эришиш эҳтимоли юқори":4,"ўсиш йўқ":3,"Маълумот киритилмаган":1},"ysh":0.4}},
      {"o":"Адлия вазирлиги","tot":19,"h2":17,"h":{"n":10,"anom":0,"idx":100.0,"bar":{"done":10,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},"y":{"n":9,"idx":72.2,"bar":{"high":6,"risk":2,"other":1},"tnd":{"тенденция етарли эмас, бажарилмаслик хавфи бор":2,"мақсадга эришиш эҳтимоли юқори":6,"Ижроси энди бошланади":1},"ysh":0.6666666666666666}},
      {"o":"Мактабгача ва мактаб таълими вазирлиги","tot":16,"h2":16,"h":{"n":4,"anom":0,"idx":75.0,"bar":{"done":3,"late":0,"fail":1},"qz":0,"nd":1,"ysh":0.75},"y":{"n":12,"idx":62.5,"bar":{"high":6,"risk":3,"other":3},"tnd":{"Ижроси энди бошланади":1,"мақсадга эришиш эҳтимоли юқори":6,"ўсиш йўқ":3,"Маълумот киритилмаган":2},"ysh":0.5}},
      {"o":"Транспорт вазирлиги","tot":15,"h2":14,"h":{"n":12,"anom":0,"idx":95.2,"bar":{"done":5,"late":7,"fail":0},"qz":0,"nd":0,"ysh":0.4166666666666667},"y":{"n":3,"idx":83.3,"bar":{"high":2,"risk":0,"other":1},"tnd":{"Ижроси энди бошланади":1,"мақсадга эришиш эҳтимоли юқори":2},"ysh":0.6666666666666666}},
      {"o":"Қишлоқ хўжалиги вазирлиги","tot":14,"h2":12,"h":{"n":9,"anom":0,"idx":66.4,"bar":{"done":5,"late":1,"fail":3},"qz":1,"nd":2,"ysh":0.5555555555555556},"y":{"n":5,"idx":60.0,"bar":{"high":1,"risk":0,"other":4},"tnd":{"Ижроси энди бошланади":2,"Маълумот киритилмаган":2,"мақсадга эришиш эҳтимоли юқори":1},"ysh":0.2}},
      {"o":"Спорт вазирлиги","tot":12,"h2":9,"h":{"n":5,"anom":0,"idx":91.6,"bar":{"done":4,"late":0,"fail":1},"qz":1,"nd":0,"ysh":0.8},"y":{"n":7,"idx":85.7,"bar":{"high":5,"risk":0,"other":2},"tnd":{"мақсадга эришиш эҳтимоли юқори":5,"Ижроси энди бошланади":2},"ysh":0.7142857142857143}},
      {"o":"Инвестициялар, саноат ва савдо вазирлиги","tot":12,"h2":12,"h":{"n":12,"anom":0,"idx":95.5,"bar":{"done":8,"late":4,"fail":0},"qz":0,"nd":0,"ysh":0.6666666666666666},"y":null},
      {"o":"Туризм қўмитаси","tot":10,"h2":10,"h":{"n":8,"anom":0,"idx":100.0,"bar":{"done":8,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},"y":{"n":2,"idx":75.0,"bar":{"high":1,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1,"мақсадга эришиш эҳтимоли юқори":1},"ysh":0.5}},
      {"o":"Энергетика вазирлиги","tot":10,"h2":10,"h":{"n":5,"anom":0,"idx":90.4,"bar":{"done":1,"late":3,"fail":1},"qz":1,"nd":0,"ysh":0.2},"y":{"n":5,"idx":60.0,"bar":{"high":3,"risk":2,"other":0},"tnd":{"ўсиш йўқ":2,"мақсадга эришиш эҳтимоли юқори":3},"ysh":0.6}},
      {"o":"Оила ва хотин-қизлар қўмитаси","tot":9,"h2":9,"h":{"n":6,"anom":0,"idx":100.0,"bar":{"done":6,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},"y":{"n":3,"idx":50.0,"bar":{"high":1,"risk":1,"other":1},"tnd":{"мақсадга эришиш эҳтимоли юқори":1,"тенденция етарли эмас, бажарилмаслик хавфи бор":1,"Маълумот киритилмаган":1},"ysh":0.3333333333333333}},
      {"o":"Ижтимоий ҳимоя миллий агентлиги","tot":8,"h2":8,"h":{"n":7,"anom":0,"idx":100.0,"bar":{"done":7,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},"y":{"n":1,"idx":0.0,"bar":{"high":0,"risk":1,"other":0},"tnd":{"тенденция етарли эмас, бажарилмаслик хавфи бор":1},"ysh":0.0}},
      {"o":"Камбағалликни қисқартириш ва бандлик вазирлиги","tot":8,"h2":8,"h":{"n":5,"anom":0,"idx":100.0,"bar":{"done":5,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},"y":{"n":3,"idx":66.7,"bar":{"high":2,"risk":1,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":2,"ўсиш йўқ":1},"ysh":0.6666666666666666}},
      {"o":"Сув хўжалиги вазирлиги","tot":8,"h2":8,"h":{"n":2,"anom":0,"idx":100.0,"bar":{"done":2,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},"y":{"n":6,"idx":66.7,"bar":{"high":4,"risk":2,"other":0},"tnd":{"ўсиш йўқ":1,"мақсадга эришиш эҳтимоли юқори":4,"тенденция етарли эмас, бажарилмаслик хавфи бор":1},"ysh":0.6666666666666666}},
      {"o":"Миллатлараро муносабатлар ва хориждаги ватандошлар масалалари бўйича қўмита","tot":8,"h2":8,"h":{"n":5,"anom":0,"idx":93.4,"bar":{"done":4,"late":0,"fail":1},"qz":1,"nd":0,"ysh":0.8},"y":{"n":3,"idx":100.0,"bar":{"high":3,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":3},"ysh":1.0}},
      {"o":"Тоғ-кон саноати ва геология вазирлиги","tot":7,"h2":7,"h":{"n":7,"anom":0,"idx":93.6,"bar":{"done":4,"late":3,"fail":0},"qz":0,"nd":0,"ysh":0.5714285714285714},"y":null},
      {"o":"Ички ишлар вазирлиги","tot":7,"h2":7,"h":{"n":1,"anom":0,"idx":93.0,"bar":{"done":0,"late":1,"fail":0},"qz":0,"nd":0,"ysh":0.0},"y":{"n":6,"idx":100.0,"bar":{"high":6,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":6},"ysh":1.0}},
      {"o":"Ёшлар ишлари агентлиги","tot":6,"h2":6,"h":{"n":6,"anom":0,"idx":100.0,"bar":{"done":6,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},"y":null},
      {"o":"Маданий мерос агентлиги","tot":5,"h2":5,"h":{"n":4,"anom":0,"idx":98.8,"bar":{"done":3,"late":1,"fail":0},"qz":0,"nd":0,"ysh":0.75},"y":{"n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Ижроси энди бошланади":1},"ysh":0.0}},
      {"o":"Қурилиш ва уй-жой коммунал хўжалиги вазирлиги","tot":5,"h2":5,"h":{"n":3,"anom":0,"idx":64.0,"bar":{"done":0,"late":2,"fail":1},"qz":0,"nd":1,"ysh":0.0},"y":{"n":2,"idx":0.0,"bar":{"high":0,"risk":2,"other":0},"tnd":{"тенденция етарли эмас, бажарилмаслик хавфи бор":2},"ysh":0.0}},
      {"o":"Бошқарув самарадорлиги агентлиги","tot":5,"h2":5,"h":null,"y":{"n":5,"idx":70.0,"bar":{"high":3,"risk":1,"other":1},"tnd":{"мақсадга эришиш эҳтимоли юқори":3,"тенденция етарли эмас, бажарилмаслик хавфи бор":1,"Маълумот киритилмаган":1},"ysh":0.6}},
      {"o":"Ташқи ишлар вазирлиги","tot":5,"h2":5,"h":{"n":1,"anom":0,"idx":97.0,"bar":{"done":0,"late":1,"fail":0},"qz":0,"nd":0,"ysh":0.0},"y":{"n":4,"idx":100.0,"bar":{"high":4,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":4},"ysh":1.0}},
      {"o":"Миграция агентлиги","tot":5,"h2":5,"h":{"n":2,"anom":0,"idx":100.0,"bar":{"done":2,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},"y":{"n":3,"idx":66.7,"bar":{"high":1,"risk":0,"other":2},"tnd":{"мақсадга эришиш эҳтимоли юқори":1,"Маълумот киритилмаган":1,"Ижроси энди бошланади":1},"ysh":0.3333333333333333}},
      {"o":"Мудофаа вазирлиги","tot":5,"h2":5,"h":null,"y":{"n":5,"idx":80.0,"bar":{"high":4,"risk":1,"other":0},"tnd":{"ўсиш йўқ":1,"мақсадга эришиш эҳтимоли юқори":4},"ysh":0.8}},
      {"o":"Марказий банк","tot":4,"h2":3,"h":{"n":1,"anom":1,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},"y":{"n":2,"idx":75.0,"bar":{"high":1,"risk":0,"other":1},"tnd":{"мақсадга эришиш эҳтимоли юқори":1,"Ижроси энди бошланади":1},"ysh":0.5}},
      {"o":"Енгил саноатни ривожлантириш агентлиги","tot":4,"h2":4,"h":{"n":3,"anom":0,"idx":0.0,"bar":{"done":0,"late":0,"fail":3},"qz":0,"nd":3,"ysh":0.0},"y":{"n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0}},
      {"o":"Урбанизация ва уй-жой бозорини барқарор ривожлантириш миллий қўмитаси","tot":4,"h2":4,"h":{"n":4,"anom":0,"idx":91.5,"bar":{"done":3,"late":0,"fail":1},"qz":1,"nd":0,"ysh":0.75},"y":null},
      {"o":"Олий суд","tot":4,"h2":4,"h":{"n":1,"anom":0,"idx":0.0,"bar":{"done":0,"late":0,"fail":1},"qz":0,"nd":1,"ysh":0.0},"y":{"n":3,"idx":50.0,"bar":{"high":0,"risk":0,"other":3},"tnd":{"Маълумот киритилмаган":2,"Ижроси энди бошланади":1},"ysh":0.0}},
      {"o":"Эл-юрт умиди жамғармаси","tot":3,"h2":2,"h":null,"y":{"n":3,"idx":66.7,"bar":{"high":1,"risk":0,"other":2},"tnd":{"мақсадга эришиш эҳтимоли юқори":1,"Маълумот киритилмаган":2},"ysh":0.3333333333333333}},
      {"o":"Рақобатни ривожлантириш ва истеъмолчилар ҳуқуқларини ҳимоя қилиш қўмитаси","tot":3,"h2":3,"h":{"n":2,"anom":0,"idx":100.0,"bar":{"done":2,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},"y":{"n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0}},
      {"o":"\"Ўзсувтаъминот\" АЖ","tot":3,"h2":3,"h":{"n":2,"anom":0,"idx":100.0,"bar":{"done":2,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},"y":{"n":1,"idx":0.0,"bar":{"high":0,"risk":1,"other":0},"tnd":{"тенденция етарли эмас, бажарилмаслик хавфи бор":1},"ysh":0.0}},
      {"o":"Бош прокуратура","tot":3,"h2":0,"h":null,"y":{"n":3,"idx":50.0,"bar":{"high":0,"risk":0,"other":3},"tnd":{"Ижроси энди бошланади":3},"ysh":0.0}},
      {"o":"Фавқулодда вазиятлар вазирлиги","tot":3,"h2":3,"h":{"n":3,"anom":0,"idx":100.0,"bar":{"done":3,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},"y":null},
      {"o":"Таълим сифатини таъминлаш миллий агентлиги","tot":2,"h2":2,"h":{"n":1,"anom":0,"idx":13.0,"bar":{"done":0,"late":0,"fail":1},"qz":1,"nd":0,"ysh":0.0},"y":{"n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0}},
      {"o":"Гендер тенгликни таъминлаш масалалари бўйича комиссия","tot":2,"h2":2,"h":null,"y":{"n":2,"idx":75.0,"bar":{"high":1,"risk":0,"other":1},"tnd":{"мақсадга эришиш эҳтимоли юқори":1,"Маълумот киритилмаган":1},"ysh":0.5}},
      {"o":"Миллий антидопинг агентлиги","tot":2,"h2":2,"h":null,"y":{"n":2,"idx":100.0,"bar":{"high":2,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":2},"ysh":1.0}},
      {"o":"Республика Маънавият ва маърифат маркази","tot":2,"h2":2,"h":{"n":2,"anom":0,"idx":100.0,"bar":{"done":2,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},"y":null},
      {"o":"Давлат активларини бошқариш агентлиги","tot":2,"h2":2,"h":null,"y":{"n":2,"idx":100.0,"bar":{"high":2,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":2},"ysh":1.0}},
      {"o":"Агросаноат мажмуи устидан назорат қилиш инспекцияси","tot":2,"h2":2,"h":{"n":2,"anom":0,"idx":100.0,"bar":{"done":2,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},"y":null},
      {"o":"Техник жиҳатдан тартибга солиш агентлиги","tot":2,"h2":2,"h":{"n":2,"anom":0,"idx":100.0,"bar":{"done":2,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},"y":null},
      {"o":"Қурилиш ва уй-жой коммунал хўжалиги соҳасида назорат қилиш инспекцияси","tot":2,"h2":2,"h":{"n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},"y":{"n":1,"idx":0.0,"bar":{"high":0,"risk":1,"other":0},"tnd":{"тенденция етарли эмас, бажарилмаслик хавфи бор":1},"ysh":0.0}},
      {"o":"Атом энергияси агентлиги","tot":2,"h2":2,"h":null,"y":{"n":2,"idx":100.0,"bar":{"high":2,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":2},"ysh":1.0}},
      {"o":"Олий Мажлис Сенати","tot":2,"h2":2,"h":null,"y":{"n":2,"idx":75.0,"bar":{"high":1,"risk":0,"other":1},"tnd":{"Ижроси энди бошланади":1,"мақсадга эришиш эҳтимоли юқори":1},"ysh":0.5}},
      {"o":"Инсон ҳуқуқлари бўйича Миллий марказ","tot":2,"h2":2,"h":null,"y":{"n":2,"idx":50.0,"bar":{"high":1,"risk":1,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1,"тенденция етарли эмас, бажарилмаслик хавфи бор":1},"ysh":0.5}},
      {"o":"Коррупцияга қарши курашиш агентлиги","tot":2,"h2":2,"h":null,"y":{"n":2,"idx":50.0,"bar":{"high":0,"risk":0,"other":2},"tnd":{"Ижроси энди бошланади":2},"ysh":0.0}},
      {"o":"Жаҳон савдо ташкилоти масалалари бўйича махсус вакил","tot":2,"h2":2,"h":null,"y":{"n":2,"idx":100.0,"bar":{"high":2,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":2},"ysh":1.0}},
      {"o":"Оммавий ахборот воситалари учун контент тайёрлаш маркази","tot":2,"h2":1,"h":null,"y":{"n":2,"idx":50.0,"bar":{"high":0,"risk":0,"other":2},"tnd":{"Маълумот киритилмаган":1,"Ижроси энди бошланади":1},"ysh":0.0}},
      {"o":"Миллий гвардия","tot":2,"h2":2,"h":null,"y":{"n":2,"idx":0.0,"bar":{"high":0,"risk":2,"other":0},"tnd":{"тенденция етарли эмас, бажарилмаслик хавфи бор":2},"ysh":0.0}},
      {"o":"Дин ишлари бўйича қўмита","tot":2,"h2":2,"h":{"n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},"y":{"n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0}},
      {"o":"Нуроний жамғармаси","tot":1,"h2":1,"h":{"n":1,"anom":0,"idx":0.0,"bar":{"done":0,"late":0,"fail":1},"qz":0,"nd":1,"ysh":0.0},"y":null},
      {"o":"Солиқ қўмитаси","tot":1,"h2":1,"h":null,"y":{"n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0}},
      {"o":"Божхона қўмитаси","tot":1,"h2":1,"h":null,"y":{"n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0}},
      {"o":"Яширин иқтисодиётни қисқартириш бўйича махсус комиссия","tot":1,"h2":1,"h":{"n":1,"anom":0,"idx":0.0,"bar":{"done":0,"late":0,"fail":1},"qz":0,"nd":1,"ysh":0.0},"y":null},
      {"o":"\"Иссиқлик таъминоти\" АЖ","tot":1,"h2":1,"h":null,"y":{"n":1,"idx":0.0,"bar":{"high":0,"risk":1,"other":0},"tnd":{"ўсиш йўқ":1},"ysh":0.0}},
      {"o":"Энергия самарадорлиги миллий агентлиги","tot":1,"h2":1,"h":null,"y":{"n":1,"idx":0.0,"bar":{"high":0,"risk":1,"other":0},"tnd":{"ўсиш йўқ":1},"ysh":0.0}},
      {"o":"Энергетика бозорини ривожлантириш ва тартибга солиш агентлиги","tot":1,"h2":1,"h":{"n":1,"anom":0,"idx":0.0,"bar":{"done":0,"late":0,"fail":1},"qz":0,"nd":1,"ysh":0.0},"y":null},
      {"o":"Электр энергияси, нефть маҳсулотлари ва газдан фойдаланишни назорат қилиш инспекцияси","tot":1,"h2":1,"h":{"n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},"y":null},
      {"o":"Бизнес-омбудсман","tot":1,"h2":1,"h":{"n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},"y":null},
      {"o":"Телекоммуникацияларни тартибга солиш агентлиги","tot":1,"h2":1,"h":null,"y":{"n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0}},
      {"o":"Креатив иқтисодиётни ривожлантириш бўйича республика кенгаши","tot":1,"h2":1,"h":null,"y":{"n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0}},
      {"o":"Вазирлар Маҳкамаси","tot":1,"h2":1,"h":null,"y":{"n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0}},
      {"o":"Стратегик ривожланиш ва ислоҳотлар агентлиги","tot":1,"h2":1,"h":null,"y":{"n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0}},
      {"o":"Саноат, радиация ва ядро хавфсизлиги қўмитаси","tot":1,"h2":1,"h":{"n":1,"anom":0,"idx":100.0,"bar":{"done":1,"late":0,"fail":0},"qz":0,"nd":0,"ysh":1.0},"y":null},
      {"o":"Олий Мажлис палаталари","tot":1,"h2":1,"h":null,"y":{"n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0}},
      {"o":"\"Тараққиёт стратегияси\" маркази","tot":1,"h2":1,"h":null,"y":{"n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0}},
      {"o":"Фуқаролик жамияти институтларини қўллаб-қувватлаш жамоат фонди маблағларини бошқариш бўйича парламент комиссияси","tot":1,"h2":1,"h":null,"y":{"n":1,"idx":50.0,"bar":{"high":0,"risk":0,"other":1},"tnd":{"Маълумот киритилмаган":1},"ysh":0.0}},
      {"o":"Судьялар олий кенгаши","tot":1,"h2":1,"h":null,"y":{"n":1,"idx":0.0,"bar":{"high":0,"risk":1,"other":0},"tnd":{"тенденция етарли эмас, бажарилмаслик хавфи бор":1},"ysh":0.0}},
      {"o":"Миллий статистика қўмитаси","tot":1,"h2":1,"h":null,"y":{"n":1,"idx":100.0,"bar":{"high":1,"risk":0,"other":0},"tnd":{"мақсадга эришиш эҳтимоли юқори":1},"ysh":1.0}}
    ],
    "meta": {
      "asof": "2026 йил 25 июль ҳолатидаги ярим йиллик свод (uz2030_master_svod_437_25-07_v2)",
      "orgAll": 72,
      "indAll": 437,
      "indH": 206,
      "indY": 231,
      "orgH": 45,
      "orgY": 58,
      "orgH2": 71
    }
  };
  const HALF = [
    ['done', 'var(--u30-green)', 'Bajarilgan'],
    ['late', 'var(--u30-yellow)', 'Kechikmoqda'],
    ['fail', 'var(--u30-red)', 'Bajarilmagan']
  ];
  const YEAR = [
    ['high', 'var(--u30-blue)', 'Bajarish ehtimoli yuqori'],
    ['risk', 'var(--u30-orange)', 'Kechikish xavfi bor'],
    ['other', '#94a3b8', 'Boshqalar']
  ];
  const TRENDS = {
    'мақсадга эришиш эҳтимоли юқори': 'ehtimoli yuqori',
    'ўсиш йўқ': 'o‘sish yo‘q',
    'Ижроси энди бошланади': 'endi boshlanadi',
    'Маълумот киритилмаган': 'maʼlumot kiritilmagan',
    'тенденция етарли эмас, бажарилмаслик хавфи бор': 'tendensiya yetarli emas'
  };
  const state = { key: 'h', dir: 'desc', limit: 40, printLimit: null };

  const esc = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

  const map = {
    'А':'A','а':'a','Б':'B','б':'b','В':'V','в':'v','Г':'G','г':'g','Д':'D','д':'d',
    'Е':'E','е':'e','Ё':'Yo','ё':'yo','Ж':'J','ж':'j','З':'Z','з':'z','И':'I','и':'i',
    'Й':'Y','й':'y','К':'K','к':'k','Л':'L','л':'l','М':'M','м':'m','Н':'N','н':'n',
    'О':'O','о':'o','П':'P','п':'p','Р':'R','р':'r','С':'S','с':'s','Т':'T','т':'t',
    'У':'U','у':'u','Ф':'F','ф':'f','Х':'X','х':'x','Ц':'Ts','ц':'ts','Ч':'Ch','ч':'ch',
    'Ш':'Sh','ш':'sh','Щ':'Shch','щ':'shch','Ъ':'','ъ':'','Ы':'I','ы':'i','Ь':'','ь':'',
    'Э':'E','э':'e','Ю':'Yu','ю':'yu','Я':'Ya','я':'ya','Ў':'Oʻ','ў':'oʻ','Қ':'Q','қ':'q',
    'Ғ':'Gʻ','ғ':'gʻ','Ҳ':'H','ҳ':'h'
  };
  const latin = (value) => String(value ?? '').split('').map(ch => map[ch] ?? ch).join('')
    .replace(/\s+/g, ' ').trim();
  const fmt = (value) => Number.isFinite(value) ? String(Math.round(value * 10) / 10).replace('.', ',') : '—';
  const indexColor = (value) => value >= 85 ? 'var(--u30-green-dark)' : value >= 60 ? '#b45309' : 'var(--u30-red-dark)';

  const bar = (status, definition) => {
    if (!status) return '<div class="u30-r5-bar is-empty" title="Bu kesimda ko‘rsatkich yo‘q"></div>';
    const total = definition.reduce((sum, [key]) => sum + (status.bar?.[key] || 0), 0) || 1;
    return `<div class="u30-r5-bar">${definition.map(([key, color, label]) => {
      const value = status.bar?.[key] || 0;
      if (!value) return '';
      const pct = value / total * 100;
      return `<b class="${key === 'late' ? 'is-late' : ''}" style="width:${pct.toFixed(3)}%;background:${color}" title="${label}: ${value} ta (${Math.round(pct)}%)">${pct >= 9 ? value : ''}</b>`;
    }).join('')}</div>`;
  };

  const subtitle = (row) => {
    const parts = [];
    if (row.h) {
      const notes = [];
      if (row.h.qz) notes.push(`qizil ${row.h.qz}`);
      if (row.h.nd) notes.push(`maʼlumot yo‘q ${row.h.nd}`);
      if (row.h.anom) notes.push(`anomaliya ${row.h.anom} — hisobdan tashqari`);
      parts.push(`I yarim yillik: ${row.h.n + row.h.anom} ta${notes.length ? ` (${notes.join(' · ')})` : ''}`);
    }
    if (row.y) {
      const trends = Object.entries(row.y.tnd || {}).map(([key, value]) => `${TRENDS[key] || latin(key)} ${value}`);
      parts.push(`yil yakuni: ${row.y.n} ta${trends.length ? ` (${trends.join(' · ')})` : ''}`);
    }
    return parts.join(' · ');
  };

  const value = (row) => state.key === 'tot' ? row.tot : row[state.key]?.idx ?? null;
  const tie = (row) => state.key === 'tot' ? 0 : row[state.key]?.ysh ?? 0;

  const render = () => {
    const withValue = R5.orgs.filter(row => value(row) !== null);
    const withoutValue = R5.orgs.filter(row => value(row) === null);
    withValue.sort((a, b) => {
      const primary = state.dir === 'desc' ? value(b) - value(a) : value(a) - value(b);
      if (primary) return primary;
      const secondary = state.dir === 'desc' ? tie(b) - tie(a) : tie(a) - tie(b);
      if (secondary) return secondary;
      return state.dir === 'desc' ? b.tot - a.tot : a.tot - b.tot;
    });
    const rows = withValue.concat(withoutValue);
    const limit = state.printLimit ?? state.limit;
    const shown = rows.slice(0, limit);

    app.innerHTML = `<div class="u30-r5-frame">
      <div class="u30-r5-scroll" tabindex="0" aria-label="Tashkilotlar reytingi jadvali">
        <div class="u30-r5-table" role="table">
          <div class="u30-r5-head" role="row">
            <div class="u30-r5-align-right" role="columnheader">№</div>
            <div role="columnheader">Masʼul tashkilot</div>
            <div class="is-green" role="columnheader">I yarim yillik natija</div>
            <div class="is-green u30-r5-align-right" role="columnheader">Indeks</div>
            <div class="is-blue" role="columnheader">Yil yakuni — umumiy baho</div>
            <div class="is-blue u30-r5-align-right" role="columnheader">Indeks</div>
            <div class="u30-r5-align-right" role="columnheader">Jami</div>
          </div>
          ${shown.map((row, index) => `<div class="u30-r5-row" role="row">
            <div class="u30-r5-rank" role="cell">${index + 1}</div>
            <div class="u30-r5-org" role="cell"><div><div class="u30-r5-org-name">${esc(latin(row.o))}</div><span class="u30-r5-org-sub">${esc(subtitle(row))}</span></div></div>
            <div class="u30-r5-bar-cell" role="cell">${bar(row.h, HALF)}</div>
            <div class="u30-r5-index ${row.h ? '' : 'is-empty'}" style="color:${row.h ? indexColor(row.h.idx) : 'var(--u30-ink-muted)'}" role="cell">${row.h ? fmt(row.h.idx) : '—'}</div>
            <div class="u30-r5-bar-cell" role="cell">${bar(row.y, YEAR)}</div>
            <div class="u30-r5-index ${row.y ? '' : 'is-empty'}" style="color:${row.y ? indexColor(row.y.idx) : 'var(--u30-ink-muted)'}" role="cell">${row.y ? fmt(row.y.idx) : '—'}</div>
            <div class="u30-r5-total" role="cell">${row.tot}</div>
          </div>`).join('')}
        </div>
      </div>
      ${rows.length > limit && state.printLimit === null ? `<button class="u30-r5-more" data-r5-more type="button">Yana ko‘rsatish · ${rows.length - limit} ta qoldi</button>` : ''}
    </div>
    <div class="u30-r5-foot"><span>Jami ${R5.meta.orgAll} ta tashkilot · ${R5.meta.indAll} ta ko‘rsatkich</span><span>I yarim yillik: ${R5.meta.indH} ta · yil yakuni: ${R5.meta.indY} ta</span></div>`;

    report.querySelectorAll('[data-r5-key]').forEach(button => button.classList.toggle('is-active', button.dataset.r5Key === state.key));
    report.querySelectorAll('[data-r5-dir]').forEach(button => button.classList.toggle('is-active', button.dataset.r5Dir === state.dir));
    app.querySelector('[data-r5-more]')?.addEventListener('click', () => { state.limit += 40; render(); });
  };

  report.querySelectorAll('[data-r5-key]').forEach(button => button.addEventListener('click', () => {
    state.key = button.dataset.r5Key;
    state.limit = 40;
    render();
  }));
  report.querySelectorAll('[data-r5-dir]').forEach(button => button.addEventListener('click', () => {
    state.dir = button.dataset.r5Dir;
    state.limit = 40;
    render();
  }));
  window.addEventListener('beforeprint', () => { state.printLimit = R5.orgs.length; render(); });
  window.addEventListener('afterprint', () => { state.printLimit = null; render(); });
  render();
})();
