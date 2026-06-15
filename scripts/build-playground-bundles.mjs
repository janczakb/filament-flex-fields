import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileSizeMetrics, writeBundleMetrics } from './bundle-metrics.mjs';

const packageRoot = path.resolve(import.meta.dirname, '..');
const distRoot = path.join(packageRoot, 'resources/dist/css');
const exportScript = path.join(packageRoot, 'scripts/export-playground-bundles.php');
const monorepoRoot = path.resolve(packageRoot, '../..');

fs.mkdirSync(distRoot, { recursive: true });

const playgroundCssPath = path.join(distRoot, 'playground.css');

if (! fs.existsSync(playgroundCssPath)) {
    throw new Error('Missing resources/dist/css/playground.css. Run build:css:playground first.');
}

const bundleMap = JSON.parse(
    execSync(`php "${exportScript}"`, {
        cwd: monorepoRoot,
        encoding: 'utf8',
    }),
);

const bundleMetrics = {};

for (const [slug, stylesheets] of Object.entries(bundleMap)) {
    const output = path.join(distRoot, `playground-${slug}.css`);
    const chunks = [fs.readFileSync(playgroundCssPath, 'utf8')];

    for (const stylesheet of stylesheets) {
        const stylesheetPath = path.join(distRoot, `${stylesheet}.css`);

        if (! fs.existsSync(stylesheetPath)) {
            throw new Error(`Missing stylesheet bundle "${stylesheet}.css" required by playground-${slug}.css`);
        }

        chunks.push(fs.readFileSync(stylesheetPath, 'utf8'));
    }

    fs.writeFileSync(output, chunks.join('\n'));
    bundleMetrics[`playground-${slug}.css`] = fileSizeMetrics(output);
}

const metricsPath = path.join(packageRoot, 'resources/dist/bundle-metrics.json');
let existingMetrics = {};

if (fs.existsSync(metricsPath)) {
    existingMetrics = JSON.parse(fs.readFileSync(metricsPath, 'utf8'));
}

writeBundleMetrics(packageRoot, {
    css: {
        ...(existingMetrics.css ?? {}),
        ...bundleMetrics,
    },
});

console.log(`Built ${Object.keys(bundleMap).length} playground slug CSS bundles.`);
