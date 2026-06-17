import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const packageRoot = path.dirname(path.dirname(fileURLToPath(import.meta.url)))
const docsDir = path.join(packageRoot, 'docs')

/**
 * @param {string} href
 * @returns {string|null}
 */
function toMintlifyHref (href) {
    if (! href || href.startsWith('http://') || href.startsWith('https://') || href.startsWith('mailto:')) {
        return null
    }

    if (href.startsWith('/docs/')) {
        return null
    }

    if (href.startsWith('#')) {
        return null
    }

    const [target, hash = ''] = href.split('#')
    const anchor = hash ? `#${hash}` : ''

    const normalized = target
        .replace(/^\.\//, '')
        .replace(/^\.\.\//, '')

    if (normalized === 'README.md' || normalized === 'README') {
        return `/docs/index${anchor}`
    }

    if (normalized === 'index.md' || normalized === 'index') {
        return `/docs/index${anchor}`
    }

    if (normalized.endsWith('.md')) {
        const slug = normalized.slice(0, -3)

        return `/docs/${slug}${anchor}`
    }

    return null
}

/**
 * @param {string} content
 * @returns {string}
 */
function fixMarkdownLinks (content) {
    return content.replace(/\[([^\]]*)\]\(([^)]+)\)/g, (match, label, href) => {
        const mintlifyHref = toMintlifyHref(href.trim())

        if (mintlifyHref === null) {
            return match
        }

        return `[${label}](${mintlifyHref})`
    })
}

let changedFiles = 0

for (const entry of fs.readdirSync(docsDir, { withFileTypes: true })) {
    if (! entry.isFile() || ! entry.name.endsWith('.md')) {
        continue
    }

    const filePath = path.join(docsDir, entry.name)
    const original = fs.readFileSync(filePath, 'utf8')
    const fixed = fixMarkdownLinks(original)

    if (fixed !== original) {
        fs.writeFileSync(filePath, fixed)
        changedFiles++
        console.log(`fixed: ${entry.name}`)
    }
}

console.log(`done: ${changedFiles} file(s) updated`)
