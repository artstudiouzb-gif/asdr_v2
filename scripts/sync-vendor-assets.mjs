import { mkdir, readFile, writeFile } from 'node:fs/promises';
import process from 'node:process';

const files = [
    {
        source: 'node_modules/@melloware/coloris/dist/coloris.min.css',
        target: 'public/assets/vendor/coloris/coloris.min.css',
    },
    {
        source: 'node_modules/@melloware/coloris/dist/umd/coloris.min.js',
        target: 'public/assets/vendor/coloris/coloris.min.js',
    },
    {
        source: 'node_modules/@melloware/coloris/LICENSE',
        target: 'public/assets/vendor/coloris/LICENSE',
    },
    {
        source: 'node_modules/@tabler/icons-sprite/dist/tabler-sprite-nostroke.svg',
        target: 'public/assets/vendor/tabler/tabler-sprite.svg',
    },
    {
        source: 'node_modules/@tabler/icons-sprite/LICENSE',
        target: 'public/assets/vendor/tabler/LICENSE',
    },
];

const checkOnly = process.argv.includes('--check');

async function readText(path) {
    return (await readFile(path, 'utf8')).replace(/\r\n?/g, '\n');
}

for (const file of files) {
    // npm-пакеты могут содержать CRLF, а Git нормализует отслеживаемые
    // текстовые файлы в LF. Сравниваем и записываем канонический LF, чтобы
    // check:vendor давал одинаковый результат локально и в GitHub Actions.
    const expected = await readText(file.source);
    if (checkOnly) {
        let current;
        try {
            current = await readText(file.target);
        } catch {
            throw new Error(`${file.target} is missing. Run npm run build:vendor.`);
        }
        if (current !== expected) {
            throw new Error(`${file.target} is stale. Run npm run build:vendor.`);
        }
        continue;
    }

    await mkdir(file.target.slice(0, file.target.lastIndexOf('/')), { recursive: true });
    await writeFile(file.target, expected);
}

const tablerSprite = await readText('node_modules/@tabler/icons-sprite/dist/tabler-sprite-nostroke.svg');
const tablerNames = Array.from(
    tablerSprite.matchAll(/<symbol id="tabler-([^"]+)"/g),
    (match) => match[1]
);
const tablerCatalog = `${JSON.stringify({
    version: 1,
    source: '@tabler/icons-sprite',
    icons: tablerNames,
}, null, 2)}\n`;
const tablerCatalogTarget = 'public/assets/vendor/tabler/tabler-icons.json';

if (checkOnly) {
    let current;
    try {
        current = await readText(tablerCatalogTarget);
    } catch {
        throw new Error(`${tablerCatalogTarget} is missing. Run npm run build:vendor.`);
    }
    if (current !== tablerCatalog) {
        throw new Error(`${tablerCatalogTarget} is stale. Run npm run build:vendor.`);
    }
} else {
    await mkdir(tablerCatalogTarget.slice(0, tablerCatalogTarget.lastIndexOf('/')), { recursive: true });
    await writeFile(tablerCatalogTarget, tablerCatalog);
}

console.log(`Coloris and Tabler vendor assets ${checkOnly ? 'verified' : 'synced'}.`);
