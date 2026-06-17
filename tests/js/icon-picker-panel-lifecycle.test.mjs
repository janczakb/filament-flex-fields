import test from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { dirname, join } from 'node:path'

const root = join(dirname(fileURLToPath(import.meta.url)), '../..')
const source = readFileSync(join(root, 'resources/js/components/icon-picker-field.js'), 'utf8')
const dist = readFileSync(join(root, 'resources/dist/components/icon-picker-field.js'), 'utf8')

test('icon picker waits for panelReady with a pending flag instead of $watch cleanup', () => {
    assert.match(source, /pendingResultsRefresh/)
    assert.doesNotMatch(source, /schedulePanelResultsRefresh/)
    assert.doesNotMatch(source, /unwatch\(\)/)
})

test('built icon picker bundle does not call alpine $watch cleanup return value', () => {
    assert.doesNotMatch(dist, /schedulePanelResultsRefresh/)
    assert.doesNotMatch(dist, /let s=\s*this\.\$watch\("panelReady"/)
})
