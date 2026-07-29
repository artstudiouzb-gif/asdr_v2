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

console.log(`Coloris vendor assets ${checkOnly ? 'verified' : 'synced'}.`);
