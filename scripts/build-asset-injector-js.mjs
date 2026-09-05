import esbuild from 'esbuild'
import fs from 'node:fs'
import path from 'node:path'
import { spawnSync } from 'node:child_process'

const packageRoot = path.resolve(import.meta.dirname, '..')
const entry = path.join(packageRoot, 'resources/js/core/flex-field-asset-injector.js')
const outDir = path.join(packageRoot, 'resources/dist/core')
const outfile = path.join(outDir, 'flex-field-asset-injector.js')
const registryPath = path.join(packageRoot, 'resources/dist/asset-registry.json')
const alpineManifestPath = path.join(packageRoot, 'resources/dist/components/alpine-manifest.json')
const exportRegistryScript = path.join(packageRoot, 'scripts/export-asset-registry.php')

/**
 * Prefer a fresh PHP export whenever alpine-manifest is newer than the
 * committed registry — otherwise the injector keeps preloading orphaned
 * hashed chunks and doubles select-field JS on the playground.
 *
 * @returns {Record<string, unknown>}
 */
function loadEmbeddedRegistry() {
    const shouldRefreshFromPhp = (() => {
        if (! fs.existsSync(exportRegistryScript)) {
            return false
        }

        if (! fs.existsSync(registryPath)) {
            return true
        }

        if (! fs.existsSync(alpineManifestPath)) {
            return false
        }

        return fs.statSync(alpineManifestPath).mtimeMs > fs.statSync(registryPath).mtimeMs
    })()

    if (shouldRefreshFromPhp) {
        const result = spawnSync('php', [exportRegistryScript], {
            cwd: packageRoot,
            encoding: 'utf8',
            maxBuffer: 16 * 1024 * 1024,
        })

        if (result.status === 0 && result.stdout) {
            try {
                const parsed = JSON.parse(result.stdout)

                fs.mkdirSync(path.dirname(registryPath), { recursive: true })
                fs.writeFileSync(registryPath, `${JSON.stringify(parsed, null, 4)}\n`)

                return parsed?.bundles ?? {}
            } catch (error) {
                console.error('[build-asset-injector] PHP registry export returned invalid JSON')
                throw error
            }
        }

        console.error('[build-asset-injector] PHP registry export failed:', (result.stderr || result.stdout || '').trim())
        process.exit(1)
    }

    if (fs.existsSync(registryPath)) {
        try {
            const parsed = JSON.parse(fs.readFileSync(registryPath, 'utf8'))

            if (parsed?.bundles && typeof parsed.bundles === 'object') {
                return parsed.bundles
            }
        } catch {
            // fall through
        }
    }

    return {}
}

const embeddedRegistry = loadEmbeddedRegistry()
const selectChunks = embeddedRegistry?.['select-field']?.chunks

if (Array.isArray(selectChunks)) {
    console.log(`[build-asset-injector] select-field chunks: ${selectChunks.length}`)
}

fs.mkdirSync(outDir, { recursive: true })

await esbuild.build({
    entryPoints: [entry],
    outfile,
    bundle: true,
    format: 'iife',
    globalName: 'FffFlexFieldAssetInjector',
    platform: 'browser',
    target: ['es2020'],
    minify: true,
    define: {
        __FFF_EMBEDDED_ASSET_REGISTRY__: JSON.stringify(embeddedRegistry),
    },
    footer: {
        js: 'window.FffAssetInjector = FffFlexFieldAssetInjector.bootFlexFieldAssetInjector();',
    },
})

console.log(`Built ${path.relative(packageRoot, outfile)}`)
