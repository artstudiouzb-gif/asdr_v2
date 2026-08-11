import { chromium } from 'playwright';
const base = 'http://127.0.0.1:8000';
const out = '/tmp/claude-0/-home-user-asdr-v2/d5c2d0c5-fe4c-579d-a563-bb95dabd8080/scratchpad/';
const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
for (const t of process.argv.slice(2)) {
    const [path, name, sel, w] = t.split('##');
    const ctx = await browser.newContext({ viewport: { width: Number(w) || 1600, height: 1200 } });
    const page = await ctx.newPage();
    await page.goto(base + path);
    await page.waitForLoadState('networkidle');
    const el = sel ? await page.$(sel) : null;
    if (sel && !el) { console.log(path, '| НЕ найдено:', sel); await ctx.close(); continue; }
    if (el) { await el.scrollIntoViewIfNeeded(); await page.waitForTimeout(400); await el.screenshot({ path: out + name + '.png' }); }
    else { await page.screenshot({ path: out + name + '.png', fullPage: false }); }
    console.log(path, '->', name, 'ok');
    await ctx.close();
}
await browser.close();
