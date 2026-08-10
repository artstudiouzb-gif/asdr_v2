const { test, expect } = require('@playwright/test');
const { AxeBuilder } = require('@axe-core/playwright');

/**
 * Автоматический аудит доступности. Раньше он был обязательной ручной
 * проверкой перед релизом — то есть на практике не выполнялся.
 *
 * Проверяются обе темы: контраст ломается именно при переключении, а глазами
 * тёмную тему смотрят реже. Правила — WCAG 2.0/2.1/2.2 уровней A и AA.
 *
 * axe не заменяет ручную проверку: он не оценивает осмысленность alt, порядок
 * табуляции и понятность формулировок. Он ловит регресс.
 */
const PAGES = [
    ['главная', '/'],
    ['лента новостей', '/news'],
    ['поиск', '/search?q=%D1%81%D1%82%D1%80%D0%B0%D1%82%D0%B5%D0%B3%D0%B8%D1%8F'],
    ['узбекская версия', '/uz'],
];

for (const [name, url] of PAGES) {
    for (const theme of ['light', 'dark']) {
        test(`доступность: ${name} (${theme})`, async ({ page }) => {
            await page.goto(url, { waitUntil: 'domcontentloaded' });
            if (theme === 'dark') {
                await page.evaluate(() => document.documentElement.setAttribute('data-theme', 'dark'));
                await page.waitForTimeout(200);
            }
            const result = await new AxeBuilder({ page })
                .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'])
                .analyze();

            const summary = result.violations.map((v) => {
                const where = v.nodes.slice(0, 3).map((n) => n.target.join(' ')).join('; ');
                return `${v.id} (${v.impact}, ${v.nodes.length}): ${v.help} -> ${where}`;
            });
            expect(summary, summary.join('\n')).toEqual([]);
        });
    }
}
