import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import esbuild from 'esbuild'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const packageRoot = path.resolve(__dirname, '..')
const entry = path.join(packageRoot, 'resources/js/core/timezone-browser-ssr-boot.js')
const outDir = path.join(packageRoot, 'resources/dist/core')
const outfile = path.join(outDir, 'timezone-browser-ssr-boot.js')

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
