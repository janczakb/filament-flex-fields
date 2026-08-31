import fs from 'node:fs';
import path from 'node:path';

const packageRoot = path.resolve(import.meta.dirname, '..');
const sourceDist = path.join(packageRoot, 'node_modules/@xenova/transformers/dist');
const targetDist = path.join(packageRoot, 'resources/dist/assets/whisper');

const runtimeFiles = [
    'transformers.min.js',
    'transformers.min.js.map',
    'ort-wasm.wasm',
    'ort-wasm-simd.wasm',
    'ort-wasm-threaded.wasm',
    'ort-wasm-simd-threaded.wasm',
];

if (! fs.existsSync(sourceDist)) {
    throw new Error('Missing @xenova/transformers dist files. Run npm install first.');
}

fs.mkdirSync(targetDist, { recursive: true });

for (const file of runtimeFiles) {
    const sourcePath = path.join(sourceDist, file);

    if (! fs.existsSync(sourcePath)) {
        continue;
    }

    fs.copyFileSync(sourcePath, path.join(targetDist, file));
}

console.log(`Copied Whisper runtime assets to ${path.relative(packageRoot, targetDist)}.`);
