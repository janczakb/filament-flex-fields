import esbuild from 'esbuild'
import fs from 'node:fs'
import path from 'node:path'
import { tiptapSharedEsbuildPlugin } from './tiptap-shared-esbuild-plugin.mjs'

const packageRoot = path.resolve(import.meta.dirname, '..')
const outDir = path.join(packageRoot, 'resources/dist/support')

const unbundledEntries = [
    {
        entry: path.join(packageRoot, 'resources/js/support/flex-rich-editor-paste-extension.js'),
        outfile: path.join(outDir, 'flex-rich-editor-paste-extension.js'),
    },
    {
        entry: path.join(packageRoot, 'resources/js/support/flex-rich-editor-block-image-extension.js'),
        outfile: path.join(outDir, 'flex-rich-editor-block-image-extension.js'),
    },
]

const bundledEntries = [
    {
        entry: path.join(packageRoot, 'resources/js/support/flex-rich-editor-youtube-extension.js'),
        outfile: path.join(outDir, 'flex-rich-editor-youtube-extension.js'),
    },
]

fs.mkdirSync(outDir, { recursive: true })

for (const { entry, outfile } of unbundledEntries) {
    await esbuild.build({
        entryPoints: [entry],
        outfile,
        bundle: false,
        format: 'esm',
        platform: 'browser',
        target: ['es2020'],
        minify: true,
    })

    console.log(`Built ${path.relative(packageRoot, outfile)}`)
}

for (const { entry, outfile } of bundledEntries) {
    await esbuild.build({
        entryPoints: [entry],
        outfile,
        bundle: true,
        format: 'esm',
        platform: 'browser',
        target: ['es2020'],
        minify: true,
        plugins: [tiptapSharedEsbuildPlugin()],
    })

    console.log(`Built ${path.relative(packageRoot, outfile)}`)
}
