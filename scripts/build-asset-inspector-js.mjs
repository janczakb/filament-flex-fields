import esbuild from 'esbuild'
import fs from 'node:fs'
import path from 'node:path'

const packageRoot = path.resolve(import.meta.dirname, '..')
const entry = path.join(packageRoot, 'resources/js/core/flex-field-asset-inspector.js')
const outDir = path.join(packageRoot, 'resources/dist/core')
const outfile = path.join(outDir, 'flex-field-asset-inspector.js')

fs.mkdirSync(outDir, { recursive: true })

await esbuild.build({
    entryPoints: [entry],
    outfile,
    bundle: true,
    format: 'iife',
    globalName: 'FffFlexFieldAssetInspector',
    platform: 'browser',
    target: ['es2020'],
    minify: true,
    footer: {
        js: [
            'window.createAssetInspector = FffFlexFieldAssetInspector.createAssetInspector;',
            'window.FffAssetInspector = { create: FffFlexFieldAssetInspector.createAssetInspector };',
        ].join(''),
    },
})

console.log(`Built ${path.relative(packageRoot, outfile)}`)
