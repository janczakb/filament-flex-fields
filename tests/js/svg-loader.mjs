import { readFileSync } from 'node:fs'

export async function load(url, context, nextLoad) {
    if (url.endsWith('.svg')) {
        const source = readFileSync(new URL(url), 'utf8')

        return {
            format: 'module',
            source: `export default ${JSON.stringify(source)}`,
            shortCircuit: true,
        }
    }

    return nextLoad(url, context)
}
