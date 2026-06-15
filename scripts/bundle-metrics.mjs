import fs from 'node:fs';
import path from 'node:path';
import { gzipSync } from 'node:zlib';

export function fileSizeMetrics(filePath) {
    const buffer = fs.readFileSync(filePath);

    return {
        bytes: buffer.length,
        kb: Number((buffer.length / 1024).toFixed(2)),
        gzipKb: Number((gzipSync(buffer).length / 1024).toFixed(2)),
    };
}

export function collectCssMetrics(cssRoot) {
    const metrics = {};

    for (const file of fs.readdirSync(cssRoot).filter((entry) => entry.endsWith('.css'))) {
        metrics[file] = fileSizeMetrics(path.join(cssRoot, file));
    }

    return metrics;
}

export function collectJsMetrics(componentsRoot) {
    const metrics = {};

    for (const file of fs.readdirSync(componentsRoot).filter((entry) => entry.endsWith('.js'))) {
        metrics[file] = fileSizeMetrics(path.join(componentsRoot, file));
    }

    return metrics;
}

export function readBundleMetrics(metricsPath) {
    if (! fs.existsSync(metricsPath)) {
        return null;
    }

    return JSON.parse(fs.readFileSync(metricsPath, 'utf8'));
}

export function writeBundleMetrics(packageRoot, partialMetrics) {
    const metricsPath = path.join(packageRoot, 'resources/dist/bundle-metrics.json');
    const existing = readBundleMetrics(metricsPath) ?? {
        generatedAt: null,
        css: {},
        js: {},
    };

    const metrics = {
        generatedAt: new Date().toISOString(),
        css: partialMetrics.css ?? existing.css,
        js: partialMetrics.js ?? existing.js,
    };

    fs.mkdirSync(path.dirname(metricsPath), { recursive: true });
    fs.writeFileSync(metricsPath, `${JSON.stringify(metrics, null, 2)}\n`);

    return metricsPath;
}
