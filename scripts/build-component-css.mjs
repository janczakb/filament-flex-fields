import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { collectCssMetrics, fileSizeMetrics, writeBundleMetrics } from './bundle-metrics.mjs';

const packageRoot = path.resolve(import.meta.dirname, '..');
const entriesRoot = path.join(packageRoot, 'resources/css/entries');
const distRoot = path.join(packageRoot, 'resources/dist/css');
const foundationOutput = path.join(distRoot, 'utilities-foundation.css');
const coreOutput = path.join(distRoot, 'core.css');
const foundationBuildEntry = path.join(entriesRoot, '.foundation.build.css');

fs.mkdirSync(distRoot, { recursive: true });

fs.writeFileSync(
    foundationBuildEntry,
    '@import "../utilities-baseline.css";\n@import "../base.css";\n',
);

execSync(`npx @tailwindcss/cli -i "${foundationBuildEntry}" -o "${foundationOutput}" --minify`, {
    cwd: packageRoot,
    stdio: 'inherit',
});

fs.unlinkSync(foundationBuildEntry);

function buildScopedEntrySource(entrySource) {
    const scopedLines = entrySource
        .split('\n')
        .filter((line) => {
            const trimmed = line.trim();

            return ! trimmed.includes('utilities-baseline.css')
                && ! /^@import\s+["']\.\.\/base\.css["'];?\s*$/.test(trimmed);
        });

    return [
        '@reference "../utilities-baseline.css";',
        '@reference "../base.css";',
        '',
        scopedLines.join('\n').trim(),
        '',
    ].join('\n');
}

const entries = fs.readdirSync(entriesRoot).filter((file) => (
    file.endsWith('.css') && ! file.startsWith('.scoped-build.') && file !== '.foundation.build.css'
));

for (const entry of entries) {
    const name = entry.replace(/\.css$/, '');
    const input = path.join(entriesRoot, entry);
    const scopedInput = path.join(entriesRoot, `.scoped-build.${entry}`);
    const output = path.join(distRoot, `${name}.css`);

    fs.writeFileSync(scopedInput, buildScopedEntrySource(fs.readFileSync(input, 'utf8')));

    try {
        execSync(`npx @tailwindcss/cli -i "${scopedInput}" -o "${output}" --minify`, {
            cwd: packageRoot,
            stdio: 'pipe',
        });
    } catch (error) {
        const stderr = error.stderr?.toString?.() ?? '';
        const stdout = error.stdout?.toString?.() ?? '';

        throw new Error(`Failed to build ${entry}:\n${stderr || stdout || error.message}`);
    } finally {
        if (fs.existsSync(scopedInput)) {
            fs.unlinkSync(scopedInput);
        }
    }
}

const cssMetrics = collectCssMetrics(distRoot);

if (fs.existsSync(foundationOutput)) {
    cssMetrics['utilities-foundation.css'] = fileSizeMetrics(foundationOutput);
}

if (fs.existsSync(coreOutput)) {
    cssMetrics['core.css'] = fileSizeMetrics(coreOutput);
}

if (fs.existsSync(path.join(distRoot, 'playground.css'))) {
    cssMetrics['playground.css'] = fileSizeMetrics(path.join(distRoot, 'playground.css'));
}

writeBundleMetrics(packageRoot, { css: cssMetrics });

console.log(`Built ${entries.length} scoped component CSS bundles (foundation: utilities-foundation.css).`);
