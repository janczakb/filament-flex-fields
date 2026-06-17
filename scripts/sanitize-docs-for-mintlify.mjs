import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const packageRoot = path.dirname(path.dirname(fileURLToPath(import.meta.url)))
const docsDir = path.join(packageRoot, 'docs')

function escapeInlineCode (content) {
    return content.replace(/`([^`\n]+)`/g, (match, inner) => {
        if (! inner.includes('<') && ! inner.includes('>')) {
            return match
        }

        const escaped = inner
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')

        return `\`${escaped}\``
    })
}

function escapeBareAngleBrackets (content) {
    const lines = content.split('\n')
    let inFence = false

    return lines.map((line) => {
        if (line.trim().startsWith('```')) {
            inFence = ! inFence

            return line
        }

        if (inFence) {
            return line
        }

        if (! line.includes('<')) {
            return line
        }

        if (line.trim().startsWith('<!--')) {
            return line
        }

        return line
            .replace(/<([a-zA-Z@/][^>]*?)>/g, (match, tag) => `&lt;${tag}&gt;`)
    }).join('\n')
}

function sanitizeFile (filePath) {
    const original = fs.readFileSync(filePath, 'utf8')
    const sanitized = escapeBareAngleBrackets(escapeInlineCode(original))

    if (sanitized !== original) {
        fs.writeFileSync(filePath, sanitized)
        console.log(`sanitized: ${path.relative(packageRoot, filePath)}`)
    }
}

for (const entry of fs.readdirSync(docsDir, { withFileTypes: true })) {
    if (entry.isFile() && entry.name.endsWith('.md')) {
        sanitizeFile(path.join(docsDir, entry.name))
    }
}
