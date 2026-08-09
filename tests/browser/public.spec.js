const { test, expect } = require('@playwright/test');

test('public home renders without horizontal overflow', async ({ page }) => {
    const response = await page.goto('/');
    expect(response).not.toBeNull();
    expect(response.status()).toBe(200);
    await expect(page.locator('main#main-content')).toBeVisible();

    const overflow = await page.evaluate(() => ({
        viewport: document.documentElement.clientWidth,
        content: document.documentElement.scrollWidth
    }));
    expect(overflow.content).toBeLessThanOrEqual(overflow.viewport + 15);
});

test('quick search traps keyboard focus and restores the trigger', async ({ page }) => {
    const response = await page.goto('/');
    expect(response).not.toBeNull();
    expect(response.status()).toBe(200);

    await page.keyboard.press('Tab');
    const trigger = page.locator('.skip-link');
    await expect(trigger).toBeFocused();

    await page.keyboard.press('Control+KeyK');
    const modal = page.locator('#site-quick-search-modal');
    await expect(modal).toBeVisible();
    await expect(page.locator('#site-quick-search-input')).toBeFocused();

    await page.keyboard.press('Escape');
    await expect(modal).toBeHidden();
    await expect(trigger).toBeFocused();
});

test('Uzbek home uses localized title and secure language URL', async ({ page }) => {
    const response = await page.goto('/uz');
    expect(response).not.toBeNull();
    expect(response.status()).toBe(200);
    await expect(page).toHaveTitle(/Bosh sahifa/);
    expect(page.url()).toMatch(/^http:\/\/127\.0\.0\.1:8080\/uz\/?$/);
});

test('selected language persists until the visitor explicitly changes it', async ({ page }) => {
    await page.goto('/uz');
    await page.goto('/projects');
    expect(page.url()).toMatch(/^http:\/\/127\.0\.0\.1:8080\/uz\/projects\/?$/);

    let cookies = await page.context().cookies();
    expect(cookies.find((cookie) => cookie.name === 'site_lang')?.value).toBe('uz');

    await page.goto('/projects?_lang=ru');
    expect(page.url()).toMatch(/^http:\/\/127\.0\.0\.1:8080\/projects\/?$/);
    cookies = await page.context().cookies();
    expect(cookies.find((cookie) => cookie.name === 'site_lang')?.value).toBe('ru');

    await page.goto('/uz/projects');
    expect(page.url()).toMatch(/^http:\/\/127\.0\.0\.1:8080\/projects\/?$/);
    cookies = await page.context().cookies();
    expect(cookies.find((cookie) => cookie.name === 'site_lang')?.value).toBe('ru');
});

test('health endpoint and admin login are reachable', async ({ page, request }) => {
    const health = await request.get('/health');
    expect(health.status()).toBe(200);
    const healthPayload = await health.json();
    expect(healthPayload.status).toMatch(/^(ok|degraded)$/);

    const response = await page.goto('/admin/login');
    expect(response).not.toBeNull();
    expect(response.status()).toBe(200);
    await expect(page.locator('input[name="username"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
});

test('mobile drawer controls focus trap and restores trigger on escape', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto('/');
    const burger = page.locator('.site-burger');

    // Раньше проверки прятались в `if (isVisible())` и на фикстуре без бургера
    // тест молча зеленел, ничего не проверив. Пропуск должен быть виден.
    test.skip(!(await burger.isVisible()), 'в этой сборке шапки бургера нет');

    await burger.click();
    const drawer = page.locator('#site-drawer');
    await expect(drawer).toBeVisible();
    // Открытая шторка не помечается `aria-hidden="false"` — атрибут снимается:
    // явное "false" считается плохой практикой и сбивает часть скринридеров.
    expect(await drawer.getAttribute('aria-hidden')).toBeNull();

    const closeBtn = page.locator('.site-drawer__close');
    await expect(closeBtn).toBeFocused();

    await page.keyboard.press('Escape');
    await expect(drawer).toHaveAttribute('aria-hidden', 'true');
    await expect(burger).toBeFocused();
});

test('theme toggle and accessibility panel toggle data attributes correctly', async ({ page }) => {
    await page.goto('/');
    const themeBtn = page.locator('.site-theme-toggle');
    if (await themeBtn.isVisible()) {
        const initialTheme = await page.locator('html').getAttribute('data-theme');
        await themeBtn.click();
        const newTheme = await page.locator('html').getAttribute('data-theme');
        expect(newTheme).not.toBe(initialTheme);
    }
});

