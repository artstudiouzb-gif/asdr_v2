const crypto = require('node:crypto');
const fs = require('node:fs');
const path = require('node:path');
const { test, expect } = require('@playwright/test');

/*
 * Визуальные регрессы админки.
 *
 * Устроено так же, как витрина публички (visual.spec.js): эталон — не
 * картинка, а срез вычисленных стилей. Пиксельный снимок расходится между
 * машиной и раннером, а срез читается в diff и краснеет ровно на правке
 * оформления.
 *
 * Зачем он админке: её CSS собран слоями — базовый файл, слой переопределений
 * поверх него и отдельные патч-файлы, которые подключает загрузчик. Правка в
 * одном слое молча меняет вид в другом; без эталона это замечали только
 * глазами и через раз.
 *
 * Что снимаем: оболочку (шапка, боковое меню), кнопки, поля формы, таблицу,
 * карточки и два переиспользуемых виджета — поле медиа и поле цвета. То есть
 * набор, из которого собраны все экраны панели, а не сами экраны.
 *
 * Данные фиксированы: tests/browser/seed_admin_visual.php.
 *
 * Обновлять эталон осознанно, вместе с правкой оформления:
 *   npm run test:visual -- --update-snapshots
 */

const BASELINE = path.join(__dirname, 'visual-baseline');

// Те же значения, что в tests/browser/seed_admin_visual.php. Живут только в
// одноразовой тестовой базе; фикстура отказывается работать вне testing.
const ADMIN_USER = 'visual';
const ADMIN_PASSWORD = 'Visual-regression-1';
const ADMIN_TOTP_SECRET = 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP';

/** Темы панели: светлая по умолчанию и единственная тёмная. */
const THEMES = ['default', 'dark_emerald'];

/**
 * Экраны и то, что на каждом из них интересно. Селектор → свойства.
 * «нет на странице» в эталоне — тоже факт: он ловит пропавший компонент.
 */
const SCREENS = [
    {
        name: 'shell',
        url: '/admin',
        probes: [
            ['.admin-topbar', ['backgroundColor', 'color', 'height', 'borderBottomColor']],
            ['.admin-sidebar', ['backgroundColor', 'color', 'width', 'borderRightColor']],
            ['.admin-nav-item', ['color', 'fontSize', 'fontWeight', 'padding', 'borderRadius']],
            ['.form-card', ['backgroundColor', 'borderColor', 'borderRadius', 'boxShadow', 'padding']],
            ['.stat-card', ['backgroundColor', 'borderColor', 'borderRadius', 'padding']],
            ['.stat-card__value', ['color', 'fontSize', 'fontWeight']],
            ['.btn', ['backgroundColor', 'color', 'borderColor', 'borderRadius', 'fontSize', 'padding']],
            ['.btn--primary', ['backgroundColor', 'color', 'borderColor', 'borderRadius']],
        ],
    },
    {
        name: 'list',
        url: '/admin/news',
        probes: [
            ['.data-table th', ['backgroundColor', 'color', 'fontSize', 'fontWeight', 'padding', 'borderBottomColor']],
            ['.data-table td', ['color', 'fontSize', 'padding', 'borderBottomColor']],
            ['.badge', ['backgroundColor', 'color', 'borderRadius', 'fontSize', 'padding']],
            ['.list-filter', ['color', 'fontSize', 'borderRadius', 'padding']],
            ['.btn--small', ['fontSize', 'padding', 'borderRadius']],
        ],
    },
    {
        name: 'form',
        url: '/admin/news/create',
        probes: [
            ['.form-field > label', ['color', 'fontSize', 'fontWeight', 'marginBottom']],
            ['input[type="text"]', ['backgroundColor', 'color', 'borderColor', 'borderRadius', 'height', 'fontSize']],
            ['textarea', ['backgroundColor', 'color', 'borderColor', 'borderRadius', 'fontSize']],
            ['select', ['backgroundColor', 'color', 'borderColor', 'borderRadius', 'height']],
            ['.form-hint', ['color', 'fontSize']],
            ['.form-card', ['backgroundColor', 'borderColor', 'borderRadius', 'padding']],
        ],
    },
    {
        // «Настройки сайта»: четыре поля медиа видны сразу, без раскрытия
        // секций — в конструкторе шапки те же поля лежат в свёрнутых панелях,
        // и снимок ловил бы стили скрытого элемента.
        name: 'image-field',
        url: '/admin/settings',
        probes: [
            ['.image-field', ['backgroundColor', 'borderColor', 'borderRadius', 'padding']],
            ['.image-field > label', ['color', 'fontSize', 'fontWeight', 'marginBottom']],
            ['.image-field__row', ['display', 'gap', 'alignItems']],
            ['.image-field__preview', ['width', 'height', 'borderColor', 'borderRadius', 'backgroundColor']],
            ['.image-field__name', ['color', 'fontSize', 'fontWeight']],
            ['.image-field__controls', ['display', 'gap', 'flexWrap']],
            ['.image-field__url', ['fontFamily', 'height', 'borderRadius']],
        ],
    },
    {
        // Конструктор подвала: цвета фона и градиента — поля
        // AdminUi::colorField. Панель показывается при режиме фона «Свой
        // цвет», поэтому перед снимком выбираем его — как это делает редактор.
        name: 'color-field',
        url: '/admin/footer',
        prepare: async (page) => {
            // Конструктор перерисовывает форму после инициализации, поэтому
            // ждём поле явно, а не полагаемся на момент загрузки. Видимость
            // панели проверяем следом: снимок скрытого элемента отдал бы
            // «auto» вместо размеров и молча испортил бы эталон.
            const mode = page.locator('#bg_mode');
            await expect(mode).toBeVisible();
            await mode.selectOption('color');
            await expect(page.locator('.colorfield').first()).toBeVisible();
        },
        probes: [
            ['.colorfield', ['minWidth']],
            ['.colorfield > label', ['color', 'fontSize', 'fontWeight']],
            ['.colorfield .clr-field', ['display', 'width']],
            ['.colorfield .clr-field input', ['backgroundColor', 'color', 'borderColor', 'borderRadius', 'paddingLeft', 'fontFamily']],
            ['.colorfield__off', ['color', 'fontSize', 'gap', 'marginTop']],
        ],
    },
    {
        name: 'design',
        url: '/admin/design',
        probes: [
            ['.admin-tab-btn', ['backgroundColor', 'color', 'borderColor', 'borderRadius', 'fontSize', 'padding']],
            ['.design-card', ['backgroundColor', 'borderColor', 'borderRadius', 'padding']],
            ['.design-card__label', ['color', 'fontSize', 'fontWeight']],
            ['.design-opt__label', ['color', 'fontSize', 'fontWeight']],
            ['.clr-field', ['width', 'color']],
        ],
    },
];

/** Окно выбора медиа: снимается отдельно — оно открывается поверх формы. */
const MEDIA_MODAL = {
    name: 'media-modal',
    url: '/admin/settings',
    probes: [
        ['.media-modal', ['backgroundColor']],
        ['.media-modal__dialog', ['backgroundColor', 'borderColor', 'borderRadius', 'boxShadow', 'gridTemplateColumns']],
        ['.media-modal__rail', ['backgroundColor', 'borderRightColor', 'padding']],
        ['.media-modal__navitem', ['color', 'fontSize', 'fontWeight', 'padding', 'borderRadius']],
        ['.media-modal__toolbar', ['backgroundColor', 'borderBottomColor', 'padding', 'gap']],
        ['.media-modal__search', ['backgroundColor', 'color', 'borderColor', 'borderRadius', 'height']],
        ['.media-modal__grid', ['backgroundColor', 'gridTemplateColumns', 'gap', 'padding']],
        ['.media-modal__dropcard', ['backgroundColor', 'borderColor', 'borderRadius']],
        ['.media-modal__footer', ['backgroundColor', 'borderTopColor', 'padding']],
    ],
};

/**
 * Код приложения-аутентификатора. Считаем сами, чтобы вход шёл обычным путём,
 * со вторым фактором: подсовывать тесту особый режим авторизации значило бы
 * снимать не тот экран, который видит редактор.
 */
function totp(secret, timeSlice = Math.floor(Date.now() / 1000 / 30)) {
    const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    let bits = '';
    for (const char of secret.toUpperCase().replace(/=+$/, '')) {
        const index = alphabet.indexOf(char);
        if (index === -1) { continue; }
        bits += index.toString(2).padStart(5, '0');
    }
    const key = Buffer.from((bits.match(/.{8}/g) || []).map((byte) => parseInt(byte, 2)));

    const counter = Buffer.alloc(8);
    counter.writeUInt32BE(Math.floor(timeSlice / 2 ** 32), 0);
    counter.writeUInt32BE(timeSlice >>> 0, 4);

    const digest = crypto.createHmac('sha1', key).update(counter).digest();
    const offset = digest[digest.length - 1] & 0x0f;
    const binary = ((digest[offset] & 0x7f) << 24)
        | ((digest[offset + 1] & 0xff) << 16)
        | ((digest[offset + 2] & 0xff) << 8)
        | (digest[offset + 3] & 0xff);

    return String(binary % 1000000).padStart(6, '0');
}

async function login(page) {
    await page.goto('/admin/login', { waitUntil: 'domcontentloaded' });
    await page.fill('#username', ADMIN_USER);
    await page.fill('#password', ADMIN_PASSWORD);
    await page.click('form button[type="submit"], form input[type="submit"]');

    await page.waitForURL(/\/admin(\/login\/2fa)?$/, { timeout: 15000 });
    if (page.url().includes('/admin/login/2fa')) {
        await page.fill('#code', totp(ADMIN_TOTP_SECRET));
        await page.click('form button[type="submit"], form input[type="submit"]');
        await page.waitForURL(/\/admin\/?$/, { timeout: 15000 });
    }
}

/**
 * Тема панели — атрибут на <html>, весь остальной CSS смотрит только на него.
 * Поэтому переключаем атрибутом: сохранение настройки в базе снимало бы тот
 * же самый вид, но за две лишние загрузки страницы.
 */
async function applyTheme(page, theme) {
    await page.evaluate((value) => {
        document.documentElement.setAttribute('data-admin-theme', value);
    }, theme);
}

async function freezeTransitions(page) {
    await page.addStyleTag({
        content: '*, *::before, *::after { transition: none !important; animation: none !important; }',
    });
}

async function collect(page, probes) {
    return page.evaluate((list) => {
        const out = {};
        for (const [selector, props] of list) {
            const el = document.querySelector(selector);
            if (!el) {
                out[selector] = 'нет на странице';
                continue;
            }
            const cs = getComputedStyle(el);
            const row = {};
            for (const prop of props) {
                row[prop] = cs[prop];
            }
            out[selector] = row;
        }
        return out;
    }, probes);
}

function compare(actual, name, updating) {
    const file = path.join(BASELINE, name + '.json');
    const text = JSON.stringify(actual, null, 2) + '\n';

    if (updating || !fs.existsSync(file)) {
        fs.mkdirSync(BASELINE, { recursive: true });
        fs.writeFileSync(file, text);
        return;
    }

    expect(actual, 'оформление админки изменилось — сверьте правку и обновите эталон')
        .toEqual(JSON.parse(fs.readFileSync(file, 'utf8')));
}

function isUpdating(testInfo) {
    const mode = testInfo.config.updateSnapshots;

    return process.env.UPDATE_VISUAL === '1' || (mode !== undefined && mode !== 'none' && mode !== 'missing');
}

// Панель — инструмент за большим экраном: мобильный проект её не снимает,
// иначе эталон удваивается ради раскладки, которой редактор не пользуется.
//
// Вход делаем один раз на весь файл и работаем в одной вкладке. Шаг TOTP
// одноразовый (users.totp_last_step): пять параллельных входов в одно
// тридцатисекундное окно предъявили бы один и тот же код, и прошёл бы только
// первый — остальные висли бы на второй форме.
test.describe('@visual админка', () => {
    test.describe.configure({ mode: 'serial' });

    let context = null;
    let page = null;

    test.beforeAll(async ({ browser }, testInfo) => {
        if (testInfo.project.use.isMobile) { return; }
        context = await browser.newContext({ viewport: testInfo.project.use.viewport || { width: 1440, height: 900 } });
        page = await context.newPage();
        await login(page);
    });

    test.afterAll(async () => {
        if (context) { await context.close(); }
        context = null;
        page = null;
    });

    test.skip(({ isMobile }) => Boolean(isMobile), 'админку снимаем только на десктопе');

    for (const screen of SCREENS) {
        test(screen.name, async ({}, testInfo) => {
            await page.goto(screen.url, { waitUntil: 'networkidle' });
            await freezeTransitions(page);
            if (screen.prepare) { await screen.prepare(page); }

            for (const theme of THEMES) {
                await applyTheme(page, theme);
                await page.waitForTimeout(120);
                compare(
                    await collect(page, screen.probes),
                    `admin-${screen.name}-${theme}`,
                    isUpdating(testInfo)
                );
            }
        });
    }

    test(MEDIA_MODAL.name, async ({}, testInfo) => {
        await page.goto(MEDIA_MODAL.url, { waitUntil: 'networkidle' });
        await freezeTransitions(page);

        // Часть полей лежит в свёрнутых секциях конструктора: берём видимую
        // кнопку, иначе клик уходит в скрытый элемент и висит до таймаута.
        await page.locator('[data-media-pick]:visible').first().click();
        await expect(page.locator('[data-media-modal]')).toBeVisible();
        // Ждём ответа библиотеки: до него сетка занята сообщением «Загрузка…».
        await expect(page.locator('[data-media-grid]')).toHaveAttribute('aria-busy', 'false');

        for (const theme of THEMES) {
            await applyTheme(page, theme);
            await page.waitForTimeout(120);
            compare(
                await collect(page, MEDIA_MODAL.probes),
                `admin-${MEDIA_MODAL.name}-${theme}`,
                isUpdating(testInfo)
            );
        }
    });
});
