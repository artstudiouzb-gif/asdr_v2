import { chromium } from '@playwright/test';
const browser = await chromium.launch({ executablePath: '/opt/pw-browsers/chromium' });
const page = await browser.newPage({ viewport: { width: 390, height: 900 } });
await page.goto('http://127.0.0.1:8000/o-nas', { waitUntil: 'networkidle' });
const client = await page.context().newCDPSession(page);
await client.send('DOM.enable'); await client.send('CSS.enable');
const { root } = await client.send('DOM.getDocument');
const { nodeId } = await client.send('DOM.querySelector', { nodeId: root.nodeId, selector: '.content-pagehead__title' });
const m = await client.send('CSS.getMatchedStylesForNode', { nodeId });
console.log('inline:', (m.inlineStyle?.cssProperties || []).map(p => p.name + ':' + p.value).join('; ') || '—');
for (const r of m.matchedCSSRules) {
  const props = (r.rule.style.cssProperties || []).filter(p => /font-size/.test(p.name));
  if (!props.length) continue;
  const origin = r.rule.origin;
  const href = r.rule.styleSheetId;
  console.log(`[${origin}] ${r.rule.media ? '@' + r.rule.media.map(x => x.text).join(',') + ' ' : ''}${r.rule.selectorList.text} { ${props.map(p => p.name + ':' + p.value + (p.important ? ' !important' : '')).join('; ')} }`);
}
await browser.close();
