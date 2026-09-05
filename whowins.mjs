import { chromium } from '@playwright/test';
const [,, url, selector, width] = process.argv;
const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
const page = await browser.newPage({ viewport: { width: Number(width) || 390, height: 900 } });
await page.goto(url, { waitUntil: 'networkidle' });
const client = await page.context().newCDPSession(page);
await client.send('DOM.enable'); await client.send('CSS.enable');
const { root } = await client.send('DOM.getDocument');
const { nodeId } = await client.send('DOM.querySelector', { nodeId: root.nodeId, selector });
const m = await client.send('CSS.getMatchedStylesForNode', { nodeId });
for (const r of m.matchedCSSRules) {
  const fs = (r.rule.style.cssProperties || []).find(p => p.name === 'font-size');
  if (!fs) continue;
  const sheet = m.cssKeyframesRules ? '' : '';
  console.log(`${r.rule.media ? '@media ' + r.rule.media.map(x => x.text).join(',') + ' ' : ''}${r.rule.selectorList.text}  ->  ${fs.value}${fs.disabled ? ' (disabled)' : ''}`);
}
console.log('computed:', await page.$eval(selector, el => getComputedStyle(el).fontSize));
await browser.close();
