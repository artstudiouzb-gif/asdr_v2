import { chromium } from '@playwright/test';
const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
const page = await browser.newPage({ viewport: { width: 390, height: 900 } });
await page.goto('http://127.0.0.1:8000/o-nas', { waitUntil: 'networkidle' });
console.log(await page.evaluate(() => {
  const cs = getComputedStyle(document.documentElement);
  const el = document.querySelector('.content-pagehead__title');
  const probe = document.createElement('div');
  probe.style.fontSize = 'clamp(var(--step-5), 8vw, var(--step-7))';
  document.body.appendChild(probe);
  const out = {
    step5: cs.getPropertyValue('--step-5').trim(),
    step7: cs.getPropertyValue('--step-7').trim(),
    step8: cs.getPropertyValue('--step-8').trim(),
    baseFont: cs.getPropertyValue('--base-font-size').trim(),
    rootFontSize: cs.fontSize,
    clampProbe: getComputedStyle(probe).fontSize,
    titleClasses: el.className,
    headClasses: el.closest('.content-pagehead')?.className,
    computed: getComputedStyle(el).fontSize,
    innerWidth: window.innerWidth,
  };
  probe.remove();
  return out;
}));
await browser.close();
