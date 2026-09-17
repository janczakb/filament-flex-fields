import crypto from 'node:crypto'
import fs from 'node:fs'
import path from 'node:path'

/**
 * Maintainer tool: verify @xenova/transformers dist against WhisperRuntimeManifest,
 * and optionally materialize a release artifact directory (never package dist/).
 *
 * Usage:
 *   node scripts/build-whisper-runtime.mjs
 *   node scripts/build-whisper-runtime.mjs --write-artifact
 */
const packageRoot = path.resolve(import.meta.dirname, '..')
const sourceDist = path.join(packageRoot, 'node_modules/@xenova/transformers/dist')
const artifactDist = path.join(packageRoot, 'artifacts/whisper-runtime')
const manifestPath = path.join(packageRoot, 'src/Support/WhisperRuntimeManifest.php')

const runtimeFiles = [
    'transformers.min.js',
    'ort-wasm.wasm',
    'ort-wasm-simd.wasm',
    'ort-wasm-threaded.wasm',
    'ort-wasm-simd-threaded.wasm',
]

const writeArtifact = process.argv.includes('--write-artifact')

if (! fs.existsSync(sourceDist)) {
    throw new Error('Missing @xenova/transformers dist files. Run npm install first.')
}

function sha256File(filePath) {
    const hash = crypto.createHash('sha256')
    hash.update(fs.readFileSync(filePath))
    return hash.digest('hex')
}

const computed = {}

for (const file of runtimeFiles) {
    const sourcePath = path.join(sourceDist, file)

    if (! fs.existsSync(sourcePath)) {
        throw new Error(`Missing transformers dist file: ${file}`)
    }

    computed[file] = sha256File(sourcePath)
}

const manifestPhp = fs.readFileSync(manifestPath, 'utf8')
const mismatches = []

for (const [file, hash] of Object.entries(computed)) {
    const match = manifestPhp.match(new RegExp(`'${file}'\\s*=>\\s*'([a-f0-9]{64})'`))

    if (! match) {
        mismatches.push(`${file}: missing from WhisperRuntimeManifest.php`)
        continue
    }

    if (match[1] !== hash) {
        mismatches.push(`${file}: manifest ${match[1]} !== node_modules ${hash}`)
    }
}

if (mismatches.length > 0) {
    console.error('Whisper runtime manifest is out of date. Update WhisperRuntimeManifest::FILES:')
    for (const line of mismatches) {
        console.error(`  - ${line}`)
    }
    console.error('\nComputed FILES map:')
    for (const [file, hash] of Object.entries(computed)) {
        console.error(`        '${file}' => '${hash}',`)
    }
    process.exit(1)
}

const packageDistWhisper = path.join(packageRoot, 'resources/dist/assets/whisper')

if (fs.existsSync(packageDistWhisper)) {
    console.error(`Refusing to keep Whisper binaries in the Composer package: ${packageDistWhisper}`)
    console.error('Remove that directory — runtime is installed via `php artisan fff:whisper:install`.')
    process.exit(1)
}

if (writeArtifact) {
    fs.mkdirSync(artifactDist, { recursive: true })

    for (const file of runtimeFiles) {
        fs.copyFileSync(path.join(sourceDist, file), path.join(artifactDist, file))
    }

    fs.writeFileSync(
        path.join(artifactDist, 'manifest.json'),
        `${JSON.stringify({ package: '@xenova/transformers', files: computed }, null, 2)}\n`,
    )

    console.log(`Wrote Whisper runtime artifact to ${path.relative(packageRoot, artifactDist)} (not shipped in Composer).`)
} else {
    console.log('Whisper runtime manifest matches @xenova/transformers dist (no package binaries).')
}
