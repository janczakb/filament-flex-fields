import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    pruneKnownSelectedToState,
    resolveSelectedValueKeys,
    syncKnownSelectedFromState,
} from '../../resources/js/components/select-field/select-field-selection-ux.js'

describe('select-field selection UX helpers', () => {
    it('resolves multiple and single selected keys without allocating for empty multi', () => {
        assert.deepEqual(resolveSelectedValueKeys({ isMultiple: true, state: [] }), [])
        assert.deepEqual(resolveSelectedValueKeys({ isMultiple: true, state: [1, 'b'] }), ['1', 'b'])
        assert.deepEqual(resolveSelectedValueKeys({ isMultiple: false, state: null }), [])
        assert.deepEqual(resolveSelectedValueKeys({ isMultiple: false, state: 42 }), ['42'])
    })

    it('syncs known selected set from current state', () => {
        const select = { isMultiple: true, state: ['a', 2] }

        syncKnownSelectedFromState(select)

        assert.deepEqual([...select.__fffKnownSelected].sort(), ['2', 'a'])
    })

    it('prunes known keys that left the selection', () => {
        const select = {
            isMultiple: true,
            state: ['keep'],
            __fffKnownSelected: new Set(['keep', 'gone']),
        }

        pruneKnownSelectedToState(select)

        assert.deepEqual([...select.__fffKnownSelected], ['keep'])
    })

    it('clears known set when multi state empties', () => {
        const select = {
            isMultiple: true,
            state: [],
            __fffKnownSelected: new Set(['a']),
        }

        pruneKnownSelectedToState(select)

        assert.equal(select.__fffKnownSelected.size, 0)
    })

    it('prunes single-select known keys to the active value', () => {
        const select = {
            isMultiple: false,
            state: 'active',
            __fffKnownSelected: new Set(['active', 'stale']),
        }

        pruneKnownSelectedToState(select)

        assert.deepEqual([...select.__fffKnownSelected], ['active'])
    })
})
