import path from 'node:path';
import { readBundleMetrics } from './bundle-metrics.mjs';

const packageRoot = path.resolve(import.meta.dirname, '..');
const metricsPath = path.join(packageRoot, 'resources/dist/bundle-metrics.json');
const metrics = readBundleMetrics(metricsPath);

if (! metrics) {
    console.error('Missing bundle metrics. Run npm run build first.');
    process.exit(1);
}

/** @type {Set<string>} */
const HEAVY_INTERSECT_FIELDS = new Set([
    'flex-date-time-field',
    'flex-file-upload',
    'select-field',
    'user-select',
    'barcode-scanner-field',
    'icon-picker-field',
]);

/** @type {Record<string, { rawKb?: number, gzipKb?: number }>} */
const CSS_BUDGETS = {
    'core.css': { rawKb: 80, gzipKb: 50 },
    'utilities-foundation.css': { rawKb: 35, gzipKb: 10 },
    // v3 headless Select + teleported menu chrome grew picker CSS; budgets track current dist + ~10% headroom.
    'phone-field.css': { rawKb: 40, gzipKb: 12 },
    'country-field.css': { rawKb: 36, gzipKb: 10 },
    'select-field.css': { rawKb: 106, gzipKb: 14 },
    'user-display.css': { rawKb: 32, gzipKb: 7 },
    'tag-chips.css': { rawKb: 33, gzipKb: 7 },
    'user-select.css': { rawKb: 15, gzipKb: 3 },
    'flex-text-input.css': { rawKb: 48, gzipKb: 12 },
    'playground.css': { rawKb: 60, gzipKb: 20 },
    'rich-editor-field.css': { rawKb: 45, gzipKb: 12 },
    'flex-date-time-field.css': { rawKb: 36, gzipKb: 6 },
    'flex-file-upload.css': { rawKb: 60, gzipKb: 8 },
    'barcode-scanner-field.css': { rawKb: 12, gzipKb: 3 },
    'icon-picker-field.css': { rawKb: 22, gzipKb: 4 },
};

/** @type {Record<string, { rawKb?: number, gzipKb?: number }>} */
const JS_BUDGETS = {
    'flex-fields-phone-lib': { rawKb: 195, gzipKb: 48 },
    'flex-fields-emoji': { rawKb: 120, gzipKb: 40 },
    'flex-fields-mapbox': { rawKb: 20, gzipKb: 8 },
    'flex-fields-select-menu': { rawKb: 20, gzipKb: 6 },
    'flex-fields-barcode-scanner': { rawKb: 460, gzipKb: 125 },
    'barcode-scanner-field.js': { rawKb: 24, gzipKb: 7 },
    'phone-field.js': { rawKb: 20, gzipKb: 8 },
    'country-field.js': { rawKb: 15, gzipKb: 6 },
    'flex-rich-editor.js': { rawKb: 35, gzipKb: 12 },
    'flex-rich-editor-youtube-extension.js': { rawKb: 10, gzipKb: 4 },
    'flex-date-time-field.js': { rawKb: 48, gzipKb: 13 },
    'flex-file-upload.js': { rawKb: 26, gzipKb: 8 },
    'flex-slider.js': { rawKb: 38, gzipKb: 13 },
    'icon-picker-field.js': { rawKb: 35, gzipKb: 9 },
    'select-field.js': { rawKb: 53, gzipKb: 14 },
    'user-select.js': { rawKb: 53, gzipKb: 14 },
};

const violations = [];

function checkBudget(label, fileName, sizes, budget) {
    if (! budget) {
        return;
    }

    if (budget.rawKb !== undefined && sizes.kb > budget.rawKb) {
        violations.push(`${label} ${fileName}: raw ${sizes.kb}KB exceeds ${budget.rawKb}KB`);
    }

    if (budget.gzipKb !== undefined && sizes.gzipKb > budget.gzipKb) {
        violations.push(`${label} ${fileName}: gzip ${sizes.gzipKb}KB exceeds ${budget.gzipKb}KB`);
    }
}

for (const [fileName, sizes] of Object.entries(metrics.css ?? {})) {
    checkBudget('CSS', fileName, sizes, CSS_BUDGETS[fileName]);
}

for (const [fileName, sizes] of Object.entries(metrics.js ?? {})) {
    const semanticBudget = Object.entries(JS_BUDGETS).find(([prefix]) => fileName.startsWith(prefix) || fileName === prefix)?.[1];

    checkBudget('JS', fileName, sizes, semanticBudget ?? JS_BUDGETS[fileName]);
}

if (violations.length > 0) {
    console.error('Bundle budget violations:');
    violations.forEach((violation) => console.error(` - ${violation}`));
    process.exit(1);
}

console.log(`All bundle budgets passed (${Object.keys(metrics.css ?? {}).length} CSS, ${Object.keys(metrics.js ?? {}).length} JS files).`);
