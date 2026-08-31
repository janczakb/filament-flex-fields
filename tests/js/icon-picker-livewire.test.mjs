import test from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { dirname, join } from 'node:path'
import {
    isIconPickerSearchPayload,
} from '../../resources/js/core/icon-picker-livewire.js'

const root = join(dirname(fileURLToPath(import.meta.url)), '../..')
const source = readFileSync(join(root, 'resources/js/components/icon-picker-field.js'), 'utf8')
const livewire = readFileSync(join(root, 'resources/js/core/icon-picker-livewire.js'), 'utf8')

test('icon picker resolves livewire via host wire:id before alpine $wire stub', () => {
    assert.match(livewire, /closest\('\[wire\\\\:id\], \[wire-id\]'\)/)
    assert.match(livewire, /Livewire\.find/)
    assert.match(livewire, /wire\.call\('callSchemaComponentMethod', componentKey, method, params\)/)
    assert.match(livewire, /typeof wire\.get === 'function'/)
})

test('icon picker validates search payloads before applying empty results', () => {
    assert.match(source, /isIconPickerSearchPayload/)
    assert.match(source, /fetchResultsWhenLivewireReady/)

    assert.equal(isIconPickerSearchPayload(null), false)
    assert.equal(isIconPickerSearchPayload({ icons: [] }), true)
    assert.equal(isIconPickerSearchPayload({ previews: [] }), false)
})

test('icon picker retries empty results fetch when panel becomes ready', () => {
    assert.match(source, /fetchResultsWhenLivewireReady/)
})
