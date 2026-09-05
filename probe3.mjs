import { chromium } from '@playwright/test';
const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
const page = await browser.newPage({ viewport: { width: 390, height: 900 } });
await page.goto('http://127.0.0.1:8000/o-nas', { waitUntil: 'networkidle' });
console.log(await page.evaluate(() => {
  const el = document.querySelector('.content-pagehead__title');
  const out = { onRoot: getComputedStyle(document.documentElement).getPropertyValue('--font-size-h1').trim() };
  let n = el, chain = [];
  while (n && n !== document.documentElement) {
    const v = getComputedStyle(n).getPropertyValue('--font-size-h1').trim();
    if (v) chain.push((n.className || n.tagName) + ' = ' + v);
    n = n.parentElement;
  }
  out.chain = chain;
  return out;
}));
await browser.close();
