import esbuild from 'esbuild'
import fs from 'node:fs'
import path from 'node:path'

const packageRoot = path.resolve(import.meta.dirname, '..')
const entry = path.join(packageRoot, 'resources/js/playground/skeleton-demo.js')
const outDir = path.join(packageRoot, 'resources/dist/playground')
const outfile = path.join(outDir, 'skeleton-demo.js')

fs.mkdirSync(outDir, { recursive: true })

await esbuild.build({
    entryPoints: [entry],
    outfile,
    bundle: true,
    format: 'iife',
    globalName: 'FffPlaygroundSkeletonDemo',
    platform: 'browser',
    target: ['es2020'],
    minify: true,
    footer: {
        js: 'if (window.FffAssetInjector) { FffPlaygroundSkeletonDemo.install(window.FffAssetInjector); }',
    },
})

console.log(`Built ${path.relative(packageRoot, outfile)}`)
