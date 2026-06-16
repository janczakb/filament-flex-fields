import esbuild from 'esbuild';
import fs from 'node:fs';
import path from 'node:path';
import { collectJsMetrics, writeBundleMetrics } from './bundle-metrics.mjs';

const packageRoot = path.resolve(import.meta.dirname, '..');
const componentsRoot = path.join(packageRoot, 'resources/js/components');
const jsRoot = path.join(packageRoot, 'resources/js');
const distRoot = path.join(packageRoot, 'resources/dist/components');

const SEMANTIC_CHUNK_RULES = [
    {
        slug: 'flex-fields-emoji',
        matches: (modules) => modules.includes('core/shared-emoji-picker.js'),
    },
    {
        slug: 'flex-fields-audio',
        matches: (modules) => modules.some((modulePath) => (
            modulePath === 'core/format-time.js'
            || modulePath === 'core/waveform-bars.js'
            || modulePath === 'core/audio-waveform.js'
            || modulePath === 'core/audio-playback.js'
        )),
    },
    {
        slug: 'flex-fields-dynamic-bars',
        matches: (modules) => modules.includes('core/dynamic-bars.js'),
    },
    {
        slug: 'flex-fields-mapbox',
        matches: (modules) => modules.includes('support/mapbox-geocoding.js'),
    },
    {
        slug: 'flex-fields-select-menu',
        matches: (modules) => modules.includes('core/searchable-select-menu.js'),
    },
    {
        slug: 'flex-fields-virtualized-list',
        matches: (modules) => modules.includes('core/virtualized-list.js'),
    },
    {
        slug: 'flex-fields-country-registry',
        matches: (modules) => modules.includes('core/country-registry.js'),
    },
    {
        slug: 'flex-fields-country-search',
        matches: (modules) => modules.includes('core/country-search.js'),
    },
    {
        slug: 'flex-fields-country-keyboard',
        matches: (modules) => modules.includes('core/country-list-keyboard.js'),
    },
    {
        slug: 'flex-fields-barcode-scanner',
        matches: (_modules, inputs) => inputs.some((input) => input.includes('@zxing/')),
    },
    {
        slug: 'flex-fields-phone-lib',
        matches: (_modules, inputs) => inputs.some((input) => input.includes('libphonenumber-js')),
    },
];

fs.mkdirSync(distRoot, { recursive: true });

const entryFiles = fs.readdirSync(componentsRoot).filter((file) => file.endsWith('.js'));
const entryNames = entryFiles.map((file) => file.replace(/\.js$/, ''));

for (const file of fs.readdirSync(distRoot)) {
    if (file === 'alpine-manifest.json') {
        continue;
    }

    const target = path.join(distRoot, file);

    if (file.endsWith('.js')) {
        fs.unlinkSync(target);
    }
}

const result = await esbuild.build({
    entryPoints: entryFiles.map((file) => path.join(componentsRoot, file)),
    outdir: distRoot,
    bundle: true,
    splitting: true,
    format: 'esm',
    minify: true,
    entryNames: '[name]',
    chunkNames: 'chunk-[hash]',
    metafile: true,
    loader: {
        '.svg': 'text',
    },
});

function resolveSemanticChunkSlug(modules, inputs = []) {
    for (const rule of SEMANTIC_CHUNK_RULES) {
        if (rule.matches(modules, inputs)) {
            return rule.slug;
        }
    }

    const primaryModule = modules[0] ?? 'shared';

    return `flex-fields-${primaryModule
        .replace(/^core\//, '')
        .replace(/^support\//, '')
        .replace(/\.js$/, '')
        .replace(/[^a-z0-9]+/gi, '-')}`;
}

function buildChunkModules(metafile) {
    const chunkModules = {};

    for (const [outputPath, output] of Object.entries(metafile.outputs)) {
        const fileName = path.basename(outputPath);

        if (! fileName.startsWith('chunk-') || ! fileName.endsWith('.js')) {
            continue;
        }

        chunkModules[fileName] = {
            modules: Object.keys(output.inputs)
                .map((inputPath) => path.relative(jsRoot, inputPath))
                .filter((relativePath) => ! relativePath.startsWith('..'))
                .sort(),
            inputs: Object.keys(output.inputs),
        };
    }

    return chunkModules;
}

function renameChunksToSemanticNames(chunkModules) {
    const renameMap = {};

    for (const [originalName, chunk] of Object.entries(chunkModules)) {
        const hash = originalName.replace(/^chunk-/, '').replace(/\.js$/, '');
        const slug = resolveSemanticChunkSlug(chunk.modules, chunk.inputs);
        const semanticName = `${slug}-${hash}.js`;

        renameMap[originalName] = semanticName;
    }

    for (const [originalName, semanticName] of Object.entries(renameMap)) {
        const originalPath = path.join(distRoot, originalName);
        const semanticPath = path.join(distRoot, semanticName);

        if (! fs.existsSync(originalPath)) {
            continue;
        }

        fs.renameSync(originalPath, semanticPath);
    }

    for (const file of fs.readdirSync(distRoot)) {
        if (! file.endsWith('.js')) {
            continue;
        }

        const outputPath = path.join(distRoot, file);
        let source = fs.readFileSync(outputPath, 'utf8');
        let updated = false;

        for (const [originalName, semanticName] of Object.entries(renameMap)) {
            const nextSource = source.replaceAll(`./${originalName}`, `./${semanticName}`);

            if (nextSource !== source) {
                source = nextSource;
                updated = true;
            }
        }

        if (updated) {
            fs.writeFileSync(outputPath, source);
        }
    }

    const semanticChunkModules = {};

    for (const [originalName, chunk] of Object.entries(chunkModules)) {
        semanticChunkModules[renameMap[originalName] ?? originalName] = chunk.modules;
    }

    return { renameMap, semanticChunkModules };
}

function extractChunkImports(source) {
    return [
        ...source.matchAll(/from"\.\/(flex-fields-[^"]+\.js)"/g),
        ...source.matchAll(/import"\.\/(flex-fields-[^"]+\.js)"/g),
        ...source.matchAll(/import\("\.\/(flex-fields-[^"]+\.js)"\)/g),
    ].map((match) => match[1]);
}

const chunkModules = buildChunkModules(result.metafile);
const { semanticChunkModules } = renameChunksToSemanticNames(chunkModules);

const manifest = {};

for (const entry of entryNames) {
    const outputPath = path.join(distRoot, `${entry}.js`);

    if (! fs.existsSync(outputPath)) {
        continue;
    }

    const source = fs.readFileSync(outputPath, 'utf8');

    manifest[entry] = [...new Set(extractChunkImports(source))];
}

const sharedChunks = new Set();

for (const chunks of Object.values(manifest)) {
    if (! Array.isArray(chunks)) {
        continue;
    }

    for (const chunk of chunks) {
        sharedChunks.add(chunk);
    }
}

manifest.__shared_chunks__ = [...sharedChunks].sort();
manifest.__chunk_modules__ = semanticChunkModules;

fs.writeFileSync(
    path.join(distRoot, 'alpine-manifest.json'),
    `${JSON.stringify(manifest, null, 2)}\n`,
);

writeBundleMetrics(packageRoot, { js: collectJsMetrics(distRoot) });

const audioSource = path.join(packageRoot, 'resources/audio/barcode-scan-success.mp3');
const audioDistDir = path.join(packageRoot, 'resources/dist/audio');
const audioDist = path.join(audioDistDir, 'barcode-scan-success.mp3');

if (fs.existsSync(audioSource)) {
    fs.mkdirSync(audioDistDir, { recursive: true });
    fs.copyFileSync(audioSource, audioDist);
}

const outputCount = Object.keys(result.metafile.outputs).length;

console.log(
    `Successfully built ${entryFiles.length} Javascript components (${outputCount} output files, ${sharedChunks.size} shared chunks).`,
);
