import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { collectCssMetrics, fileSizeMetrics, writeBundleMetrics } from './bundle-metrics.mjs';

const packageRoot = path.resolve(import.meta.dirname, '..');
const entriesRoot = path.join(packageRoot, 'resources/css/entries');
const distRoot = path.join(packageRoot, 'resources/dist/css');

fs.mkdirSync(distRoot, { recursive: true });

const entries = fs.readdirSync(entriesRoot).filter((file) => file.endsWith('.css'));

for (const entry of entries) {
    const name = entry.replace(/\.css$/, '');
    const input = path.join(entriesRoot, entry);
    const output = path.join(distRoot, `${name}.css`);

    execSync(`npx @tailwindcss/cli -i "${input}" -o "${output}" --minify`, {
        cwd: packageRoot,
        stdio: 'inherit',
    });
}

const cssMetrics = collectCssMetrics(distRoot);

if (fs.existsSync(path.join(distRoot, 'core.css'))) {
    cssMetrics['core.css'] = fileSizeMetrics(path.join(distRoot, 'core.css'));
}

if (fs.existsSync(path.join(distRoot, 'playground.css'))) {
    cssMetrics['playground.css'] = fileSizeMetrics(path.join(distRoot, 'playground.css'));
}

writeBundleMetrics(packageRoot, { css: cssMetrics });

console.log(`Built ${entries.length} component CSS bundles.`);
