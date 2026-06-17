import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const packageRoot = path.dirname(path.dirname(fileURLToPath(import.meta.url)))
const docsDir = path.join(packageRoot, 'docs')

const skipFiles = new Set(['index.md'])

/**
 * @param {string} content
 * @returns {string|null}
 */
function addFrontmatterAndDropLeadingH1 (content) {
    if (content.startsWith('---\n')) {
        return null
    }

    const match = content.match(/^# ([^\n]+)\n\n/)

    if (! match) {
        return null
    }

    const title = match[1].replace(/"/g, '\\"')
    const rest = content.slice(match[0].length)

    return `---\ntitle: "${title}"\n---\n\n${rest}`
}

let changedFiles = 0

for (const entry of fs.readdirSync(docsDir, { withFileTypes: true })) {
    if (! entry.isFile() || ! entry.name.endsWith('.md') || skipFiles.has(entry.name)) {
        continue
    }

    const filePath = path.join(docsDir, entry.name)
    const original = fs.readFileSync(filePath, 'utf8')
    const fixed = addFrontmatterAndDropLeadingH1(original)

    if (fixed === null || fixed === original) {
        continue
    }

    fs.writeFileSync(filePath, fixed)
    changedFiles++
    console.log(`updated: ${entry.name}`)
}

console.log(`done: ${changedFiles} file(s) updated`)
