import fs from 'node:fs';
import path from 'node:path';
import { readBundleMetrics } from './bundle-metrics.mjs';

const packageRoot = path.resolve(import.meta.dirname, '..');
const metricsPath = path.join(packageRoot, 'resources/dist/bundle-metrics.json');
const manifestPath = path.join(packageRoot, 'resources/dist/components/alpine-manifest.json');
const assetsPhpPath = path.join(packageRoot, 'src/Support/FlexFieldAssets.php');
const readmePath = path.join(packageRoot, 'README.md');

const START_MARKER = '<!-- bundle-summary:start -->';
const END_MARKER = '<!-- bundle-summary:end -->';

/**
 * Representative sample for README — not every bundle.
 * Covers: core baseline, heavy shared chunks, lazy imports, CSS-only, delegated JS, large CSS.
 */
const SHOWCASE_COMPONENTS = [
    'core',
    'phone-field',
    'country-field',
    'flex-text-input',
    'tags-field',
    'rating-field',
    'switch',
    'user-select',
    'map-picker',
    'select-field',
];

/** @type {Record<string, string>} */
const LABEL_OVERRIDES = {
    'core': 'core (always)',
    'flex-text-input': 'FlexTextInput',
    'map-picker': 'MapPickerField',
    'phone-field': 'PhoneField',
    'country-field': 'CountryField',
    'rating-field': 'RatingField',
    'switch': 'SwitchField',
    'tags-field': 'TagsField',
    'user-select': 'UserSelect',
    'select-field': 'SelectField',
};

/** @type {Record<string, { jsEntry?: string, jsNote?: string, cssFile?: string }>} */
const FIELD_OVERRIDES = {
    'core': { cssFile: 'core.css', jsNote: '—' },
    'switch': { jsNote: 'Alpine inline' },
    'user-select': { jsEntry: 'select-field' },
};

function extractStylesheetDependencies(phpSource) {
    const blockMatch = phpSource.match(/public const STYLESHEET_DEPENDENCIES = \[([\s\S]*?)\];/);

    if (! blockMatch) {
        throw new Error('Could not parse STYLESHEET_DEPENDENCIES from FlexFieldAssets.php');
    }

    /** @type {Record<string, string[]>} */
    const dependencies = {};

    for (const match of blockMatch[1].matchAll(/'([^']+)'\s*=>\s*\[([^\]]*)\]/g)) {
        const component = match[1];
        const deps = [...match[2].matchAll(/'([^']+)'/g)].map((dep) => dep[1]);
        dependencies[component] = deps;
    }

    return dependencies;
}

function countProductionBundles(metrics) {
    return Object.keys(metrics.css ?? {})
        .filter((fileName) => fileName.endsWith('.css'))
        .filter((fileName) => ! fileName.startsWith('playground-'))
        .filter((fileName) => ! ['playground.css', 'filament-flex-fields.css'].includes(fileName))
        .length;
}

function componentToLabel(slug) {
    if (LABEL_OVERRIDES[slug]) {
        return LABEL_OVERRIDES[slug];
    }

    return slug
        .split('-')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join('');
}

function chunkSlug(fileName) {
    const match = fileName.match(/^(flex-fields-[a-z-]+)-/);

    return match?.[1]?.replace(/^flex-fields-/, '') ?? fileName.replace(/\.js$/, '');
}

function formatKb(value) {
    return Number(value).toFixed(1).replace(/\.0$/, '');
}

function lookupJs(metrics, fileName) {
    if (! fileName) {
        return null;
    }

    const direct = metrics.js?.[fileName];

    if (direct) {
        return direct;
    }

    const prefix = fileName.replace(/\.js$/, '');
    const hashed = Object.entries(metrics.js ?? {}).find(([name]) => name.startsWith(prefix));

    return hashed?.[1] ?? null;
}

function lookupCss(metrics, fileName) {
    return metrics.css?.[fileName] ?? null;
}

function formatJsSummary(metrics, manifest, field) {
    if (field.jsNote) {
        return field.jsNote;
    }

    const manifestKey = field.jsEntry ?? field.component;
    const entry = lookupJs(metrics, `${manifestKey}.js`);
    const parts = [];

    if (entry) {
        parts.push(`${formatKb(entry.kb)} (gzip ${formatKb(entry.gzipKb)})`);
    }

    for (const chunkFile of manifest[manifestKey] ?? []) {
        const chunk = lookupJs(metrics, chunkFile);

        if (! chunk) {
            continue;
        }

        const slug = chunkSlug(chunkFile);
        const lazyTag = slug === 'emoji' ? ' lazy' : '';

        parts.push(`+ ${slug} ${formatKb(chunk.kb)} (gzip ${formatKb(chunk.gzipKb)})${lazyTag}`);
    }

    return parts.length > 0 ? parts.join(' ') : '—';
}

function formatCssSummary(metrics, field) {
    const cssFile = field.cssFile ?? `${field.component}.css`;
    const primary = lookupCss(metrics, cssFile);

    if (! primary) {
        return '—';
    }

    let summary = `${formatKb(primary.kb)} (gzip ${formatKb(primary.gzipKb)})`;

    if (field.cssDeps?.length) {
        const depTotal = field.cssDeps.reduce((total, dep) => {
            const depMetrics = lookupCss(metrics, `${dep}.css`);

            return total + (depMetrics?.kb ?? 0);
        }, 0);

        summary += ` + deps ${formatKb(depTotal)}`;
    }

    return summary;
}

function buildShowcaseFields(stylesheetDependencies) {
    return SHOWCASE_COMPONENTS.map((component) => ({
        label: componentToLabel(component),
        component,
        cssDeps: stylesheetDependencies[component] ?? [],
        ...FIELD_OVERRIDES[component] ?? {},
    }));
}

function buildTable(metrics, manifest, stylesheetDependencies) {
    const fields = buildShowcaseFields(stylesheetDependencies);
    const totalBundles = countProductionBundles(metrics);
    const lines = [
        '| Field / component | JS (KB) | CSS (KB) |',
        '|-------------------|--------:|---------:|',
    ];

    for (const field of fields) {
        lines.push(
            `| ${field.label} | ${formatJsSummary(metrics, manifest, field)} | ${formatCssSummary(metrics, field)} |`,
        );
    }

    lines.push('');
    lines.push(`Sample bundles (${fields.length} of **${totalBundles}** production CSS files). Full per-file metrics — every component, shared chunk, and gzip size — live in [\`resources/dist/bundle-metrics.json\`](resources/dist/bundle-metrics.json) (regenerated on \`npm run build\`). JS = entry + preloaded chunks from \`alpine-manifest.json\`; CSS \`+ deps\` = declared stylesheet dependencies.`);

    return lines.join('\n');
}

function updateReadme(table) {
    const readme = fs.readFileSync(readmePath, 'utf8');

    if (! readme.includes(START_MARKER) || ! readme.includes(END_MARKER)) {
        throw new Error(`README.md is missing ${START_MARKER} / ${END_MARKER} markers.`);
    }

    const pattern = new RegExp(`${START_MARKER}[\\s\\S]*?${END_MARKER}`);
    const replacement = `${START_MARKER}\n${table}\n${END_MARKER}`;

    fs.writeFileSync(readmePath, readme.replace(pattern, replacement));
}

const metrics = readBundleMetrics(metricsPath);

if (! metrics) {
    console.error('Missing bundle metrics. Run npm run build first.');
    process.exit(1);
}

const phpSource = fs.readFileSync(assetsPhpPath, 'utf8');
const stylesheetDependencies = extractStylesheetDependencies(phpSource);
const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
const table = buildTable(metrics, manifest, stylesheetDependencies);

updateReadme(table);

console.log(`Updated README bundle summary (${SHOWCASE_COMPONENTS.length} sample rows).`);
