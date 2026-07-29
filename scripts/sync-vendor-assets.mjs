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

for (const file of files) {
    const expected = await readFile(file.source);
    if (checkOnly) {
        let current;
        try {
            current = await readFile(file.target);
        } catch {
            throw new Error(`${file.target} is missing. Run npm run build:vendor.`);
        }
        if (!current.equals(expected)) {
            throw new Error(`${file.target} is stale. Run npm run build:vendor.`);
        }
        continue;
    }

    await mkdir(file.target.slice(0, file.target.lastIndexOf('/')), { recursive: true });
    await writeFile(file.target, expected);
}

console.log(`Coloris vendor assets ${checkOnly ? 'verified' : 'synced'}.`);
