import esbuild from 'esbuild'
import fs from 'node:fs'
import path from 'node:path'
import { spawnSync } from 'node:child_process'

const packageRoot = path.resolve(import.meta.dirname, '..')
const entry = path.join(packageRoot, 'resources/js/core/flex-field-asset-injector.js')
const outDir = path.join(packageRoot, 'resources/dist/core')
const outfile = path.join(outDir, 'flex-field-asset-injector.js')
const registryPath = path.join(packageRoot, 'resources/dist/asset-registry.json')
const exportRegistryScript = path.join(packageRoot, 'scripts/export-asset-registry.php')

function loadEmbeddedRegistry() {
    if (fs.existsSync(registryPath)) {
        try {
            const parsed = JSON.parse(fs.readFileSync(registryPath, 'utf8'))

            if (parsed?.bundles && typeof parsed.bundles === 'object') {
                return parsed.bundles
            }
        } catch {
            // fall through to PHP export
        }
    }

    if (fs.existsSync(exportRegistryScript)) {
        const result = spawnSync('php', [exportRegistryScript], {
            cwd: packageRoot,
            encoding: 'utf8',
        })

        if (result.status === 0 && result.stdout) {
            try {
                const parsed = JSON.parse(result.stdout)

                return parsed?.bundles ?? {}
            } catch {
                return {}
            }
        }
    }

    return {}
}

const embeddedRegistry = loadEmbeddedRegistry()

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
