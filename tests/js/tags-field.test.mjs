import test from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const root = join(dirname(fileURLToPath(import.meta.url)), '../..')
const tagsJs = readFileSync(join(root, 'resources/js/components/tags-field.js'), 'utf8')
const tagsBlade = readFileSync(join(root, 'resources/views/forms/components/tags-field.blade.php'), 'utf8')

test('tags field reorderable items expose a sortable handle', () => {
    assert.match(tagsBlade, /x-sortable-handle/)
    assert.match(tagsBlade, /x-bind:x-sortable-item="index"/)
})

test('tags field reopens static suggestions after createTag closes the menu', () => {
    assert.match(tagsJs, /this\.closeSuggestionsMenu\(\)/)
    assert.match(
        tagsJs,
        /closeSuggestionsMenu\(\);[\s\S]*\$nextTick\?\.\(\(\) => \{[\s\S]*shouldShowSuggestions\(\)[\s\S]*openSuggestionsMenu\(\)/,
    )
})
