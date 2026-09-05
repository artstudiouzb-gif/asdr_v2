import { chromium } from '@playwright/test';
const width = Number(process.argv[3]) || 1440;
const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
const page = await browser.newPage({ viewport: { width, height: 900 } });
await page.goto(process.argv[2], { waitUntil: 'networkidle' });
const snap = await page.evaluate(() => {
  const props = ['fontSize','fontWeight','lineHeight','letterSpacing','color','backgroundColor','borderRadius','padding','margin','gap','width','maxWidth'];
  const out = {}; let i = 0;
  document.querySelectorAll('body *').forEach(el => {
    if (i > 2000) return;
    const cs = getComputedStyle(el);
    const cls = (typeof el.className === 'string' ? el.className : '').trim().split(/\s+/).slice(0,2).join('.');
    out[(i++) + ':' + (cls || el.tagName.toLowerCase())] = props.map(p => cs[p]).join('|');
  });
  return out;
});
await browser.close();
process.stdout.write(JSON.stringify(snap));
