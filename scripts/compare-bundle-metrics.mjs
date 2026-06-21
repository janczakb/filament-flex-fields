import path from 'node:path';
import { readBundleMetrics } from './bundle-metrics.mjs';

const packageRoot = path.resolve(import.meta.dirname, '..');
const metricsPath = path.join(packageRoot, 'resources/dist/bundle-metrics.json');
const committed = readBundleMetrics(metricsPath);

if (! committed) {
    console.error('Missing committed bundle-metrics.json. Run npm run build first.');
    process.exit(1);
}

const current = readBundleMetrics(metricsPath);

function formatDelta(before, after) {
    const delta = Number((after - before).toFixed(2));

    if (delta === 0) {
        return '0';
    }

    return `${delta > 0 ? '+' : ''}${delta}`;
}

let regressions = 0;

for (const section of ['css', 'js']) {
    const before = committed[section] ?? {};
    const after = current?.[section] ?? before;

    for (const [file, metrics] of Object.entries(after)) {
        const previous = before[file];

        if (! previous) {
            console.log(`[bundle-metrics] new ${section} file: ${file} (${metrics.gzipKb} KB gzip)`);
            continue;
        }

        const gzipDelta = formatDelta(previous.gzipKb, metrics.gzipKb);

        if (gzipDelta !== '0') {
            console.log(`[bundle-metrics] ${section}/${file}: gzip ${previous.gzipKb} -> ${metrics.gzipKb} KB (${gzipDelta} KB)`);
        }

        if (metrics.gzipKb > previous.gzipKb + 0.5) {
            regressions++;
        }
    }
}

if (regressions > 0) {
    console.warn(`[bundle-metrics] ${regressions} gzip regression(s) over 0.5 KB detected vs committed metrics.`);
}

process.exit(0);
