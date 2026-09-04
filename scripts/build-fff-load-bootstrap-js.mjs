import esbuild from 'esbuild'
import fs from 'node:fs'
import path from 'node:path'

const packageRoot = path.resolve(import.meta.dirname, '..')
const entry = path.join(packageRoot, 'resources/js/core/flex-fff-load-bootstrap.js')
const outDir = path.join(packageRoot, 'resources/dist/core')
const outfile = path.join(outDir, 'flex-fff-load-bootstrap.js')

fs.mkdirSync(outDir, { recursive: true })

await esbuild.build({
    entryPoints: [entry],
    outfile,
    bundle: true,
    format: 'iife',
    platform: 'browser',
    target: ['es2020'],
    minify: true,
})

console.log(`Built ${path.relative(packageRoot, outfile)}`)
