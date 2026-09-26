const { test, expect } = require('@playwright/test');
const { PUBLIC_PAGES } = require('./pages');

/**
 * Строгий style-src (без 'unsafe-inline') идёт заголовком Report-Only, пока
 * его не включат настройкой security.csp_strict_style. Браузер поднимает
 * событие securitypolicyviolation и для пробной политики — значит, любое
 * нарушение стилей на публичной странице видно здесь до того, как строгую
 * политику включат на боевом сайте.
 */
for (const [name, url] of [['витрина компонентов', '/visual-regression'], ...PUBLIC_PAGES]) {
    test(`нет нарушений style-src: ${name}`, async ({ page }) => {
        await page.addInitScript(() => {
            window.__cspViolations = [];
            document.addEventListener('securitypolicyviolation', (event) => {
                window.__cspViolations.push(`${event.effectiveDirective} ${event.blockedURI} ${event.sourceFile}:${event.lineNumber}`);
            });
        });
        const response = await page.goto(url, { waitUntil: 'load' });
        test.skip(response === null || response.status() === 404, `${url} — нет в этой сборке контента`);

        const violations = await page.evaluate(() => window.__cspViolations.filter((v) => v.startsWith('style-src')));
        expect(violations, `${url}: нарушения стилей`).toEqual([]);
    });
}
