import { chromium } from '@playwright/test';
const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
const page = await browser.newPage({ viewport: { width: 1024, height: 900 } });
await page.goto('http://127.0.0.1:8000/news/visual-regression-news', { waitUntil: 'networkidle' });
console.log(JSON.stringify(await page.evaluate(() =>
  [...document.querySelectorAll('.newsdetail__meta-item, .newsdetail__meta')].map(el => {
    const cs = getComputedStyle(el);
    return { cls: el.className.toString().slice(0,34), text: (el.textContent||'').trim().slice(0,24), w: cs.width, fs: cs.fontSize, gap: cs.gap };
  })
)));
await browser.close();
