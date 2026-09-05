import { chromium } from '@playwright/test';
const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
for (const w of [390, 412, 560, 640, 720]) {
  const page = await browser.newPage({ viewport: { width: w, height: 900 } });
  await page.goto('http://127.0.0.1:8000/visual-regression', { waitUntil: 'domcontentloaded' });
  const v = await page.$eval('.counter__value', el => getComputedStyle(el).fontSize).catch(() => 'нет элемента');
  console.log(w + 'px ->', v);
  await page.close();
}
await browser.close();
