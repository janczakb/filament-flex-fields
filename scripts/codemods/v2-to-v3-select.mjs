#!/usr/bin/env node
/**
 * Flex Fields v2 → v3 Select migration helper.
 *
 * Usage:
 *   node scripts/codemods/v2-to-v3-select.mjs /path/to/app
 *   node scripts/codemods/v2-to-v3-select.mjs /path/to/app --write
 */
import { readdir, readFile, writeFile } from 'node:fs/promises'
import path from 'node:path'

const args = process.argv.slice(2)
const targetRoot = args.find((arg) => ! arg.startsWith('--')) ?? process.cwd()
const shouldWrite = args.includes('--write')

/** @type {Array<{ name: string, pattern?: RegExp, apply: (content: string, file: string) => { content: string, changed: boolean, hint?: string } }>} */
const transforms = [
    {
        name: 'remove-redundant-native-false',
        apply(content) {
            const next = content.replace(/->native\s*\(\s*false\s*\)/g, '')

            return {
                content: next,
                changed: next !== content,
                hint: 'Removed redundant ->native(false) (headless Select is default in v3).',
            }
        },
    },
    {
        name: 'remove-legacy-coordinator-registration',
        apply(content) {
            const next = content
                .replace(/Alpine\.data\(\s*['"]fffSelectFieldCoordinator['"][\s\S]*?\)\s*\n/g, '')
                .replace(/import\s+fffSelectFieldCoordinator\s+from\s+['"][^'"]+select-field-legacy[^'"]*['"];\s*\n/g, '')

            return {
                content: next,
                changed: next !== content,
                hint: 'Removed fffSelectFieldCoordinator registration (use headless SelectField).',
            }
        },
    },
    {
        name: 'replace-tom-select-data-attribute',
        apply(content) {
            const next = content.replace(/data-fff-select-coordinator/g, 'data-fff-select-attached')

            return {
                content: next,
                changed: next !== content,
                hint: 'Renamed data-fff-select-coordinator → data-fff-select-attached.',
            }
        },
    },
    {
        name: 'ensure-headless-config-comment',
        pattern: /SelectField::make\(/g,
        apply(content, file) {
            if (! /SelectField::make\(/.test(content) || ! /\.php$/.test(file)) {
                return { content, changed: false }
            }

            if (content.includes('select.use_headless_engine') || content.includes('fff:v3:upgrade')) {
                return { content, changed: false }
            }

            const hint = 'Confirm select.use_headless_engine (or run php artisan fff:v3:upgrade).'

            if (content.includes('// fff:v3:')) {
                return { content, changed: false, hint }
            }

            const next = content.replace(
                /(SelectField::make\([^\n]+\)\n)/,
                `$1            // fff:v3: headless SelectField — verify config/select.use_headless_engine\n`,
            )

            return {
                content: next,
                changed: next !== content,
                hint,
            }
        },
    },
]

async function walk(dir, files = []) {
    const entries = await readdir(dir, { withFileTypes: true })

    for (const entry of entries) {
        if (entry.name === 'vendor' || entry.name === 'node_modules' || entry.name === '.git') {
            continue
        }

        const full = path.join(dir, entry.name)

        if (entry.isDirectory()) {
            await walk(full, files)
        } else if (/\.(php|blade\.php|js)$/.test(entry.name)) {
            files.push(full)
        }
    }

    return files
}

const files = await walk(targetRoot)
const report = []

for (const file of files) {
    const original = await readFile(file, 'utf8')
    let next = original
    const hints = []
    let changed = false

    for (const transform of transforms) {
        if (transform.pattern && ! transform.pattern.test(original)) {
            transform.pattern.lastIndex = 0

            continue
        }

        transform.pattern && (transform.pattern.lastIndex = 0)

        const result = transform.apply(next, file)

        if (result.hint) {
            hints.push(result.hint)
        }

        if (result.changed) {
            changed = true
            next = result.content
            hints.push(`Applied transform: ${transform.name}`)
        }
    }

    if (hints.length === 0) {
        continue
    }

    report.push({ file, hints, changed })

    if (shouldWrite && changed) {
        await writeFile(file, next, 'utf8')
    }
}

console.log(`Scanned ${files.length} files under ${targetRoot}`)

for (const entry of report) {
    console.log(`\n${entry.file}`)

    for (const hint of entry.hints) {
        console.log(`  - ${hint}`)
    }
}

if (report.length === 0) {
    console.log('\nNo v2 Select migration hints found.')
} elseif (! shouldWrite) {
    console.log('\nRe-run with --write to apply safe automatic transforms.')
}
