/**
 * Extra thousand-scale matrix: every ALL_KEYS × clear/hydrate/prune/conflict hammer.
 */
import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    commitHeadlessSelectionToWire,
    pruneSelectedValuesToAllowed,
    shouldHydrateInitialSelectionToWire,
    shouldIgnoreEmptyHeadlessWireSync,
    wireStateFromEngineValues,
} from '../../resources/js/components/select-field/headless-select-state.js'
import { createComboboxEngine } from '../../resources/js/core/combobox-engine.js'

const KEYS = Object.freeze([
    'published', 'draft', 'reviewing', 'tailwind', 'laravel', 'livewire', 'alpine',
    'action', 'adventure', 'drama', 'comedy', 'horror', 'thriller',
    'california', 'texas', 'delaware', 'jane', 'john', 'fred',
    'usa', 'pro', 'sky', 'mint', 'acme', 'us', 'pl', 'ca', 'tx', 'mz',
    'riyadh', 'jeddah', 'tel_aviv', '1_10', '11_50', 'other', 'enterprise_agreement',
    '0', 'custom_created', 'recent_a', 'suggested_b', 'dog', 'cat',
])

describe('thousand — engine selectValue → wire commit bridge', () => {
    for (const key of KEYS) {
        for (const from of [null, '', key === 'draft' ? 'published' : 'draft']) {
            it(`bridge ${JSON.stringify(from)} → ${key}`, () => {
                const options = KEYS.map((value) => ({ value, label: value }))
                const engine = createComboboxEngine({
                    options,
                    multiple: false,
                    initialSelectedValues: from ? [from] : [],
                })

                engine.selectValue(key)
                const values = Array.from(engine.getSnapshot().selectedValues).map(String)
                const commit = commitHeadlessSelectionToWire(from, values, false)

                assert.equal(commit.skip, false)
                assert.equal(commit.nextState, key)
                assert.equal(wireStateFromEngineValues(values, false), key)
                engine.destroy()
            })
        }
    }
})

describe('thousand — multi engine bridge for tech stacks', () => {
    const stacks = [
        [],
        ['tailwind'],
        ['tailwind', 'laravel'],
        ['laravel', 'livewire', 'alpine'],
        ['alpine', 'tailwind'],
    ]

    for (const from of stacks) {
        for (const to of stacks) {
            it(`multi bridge ${JSON.stringify(from)} → ${JSON.stringify(to)}`, () => {
                const options = ['tailwind', 'laravel', 'livewire', 'alpine'].map((value) => ({ value, label: value }))
                const engine = createComboboxEngine({
                    options,
                    multiple: true,
                    initialSelectedValues: from,
                })

                engine.setSelectedValues(to)
                const values = Array.from(engine.getSnapshot().selectedValues).map(String)
                const commit = commitHeadlessSelectionToWire(from, values, true)

                if (JSON.stringify(from) === JSON.stringify(to)) {
                    assert.equal(commit.skip, true)
                } else {
                    assert.equal(commit.skip, false)
                    assert.deepEqual(commit.nextState, to.map(String))
                }

                engine.destroy()
            })
        }
    }
})

describe('thousand — triple conflict A→B→A for every key', () => {
    for (const key of KEYS) {
        const other = key === 'published' ? 'draft' : 'published'

        it(`triple ${key}`, () => {
            let state = null

            for (const values of [[key], [other], [key], [], [key]]) {
                const commit = commitHeadlessSelectionToWire(state, values, false)

                if (! commit.skip) {
                    state = commit.nextState
                }
            }

            assert.equal(state, key)
        })
    }
})

describe('thousand — hydrate then user clear must write null', () => {
    for (const key of KEYS) {
        it(`hydrate-clear ${key}`, () => {
            assert.equal(shouldHydrateInitialSelectionToWire(null, key, false), true)

            let state = null
            let commit = commitHeadlessSelectionToWire(state, [key], false)
            state = commit.nextState

            // user mutated — empty sync must not be ignored
            assert.equal(shouldIgnoreEmptyHeadlessWireSync(null, key, false, true), false)

            commit = commitHeadlessSelectionToWire(state, [], false)
            assert.equal(commit.skip, false)
            assert.equal(commit.nextState, null)
        })
    }
})

describe('thousand — prune after cascade option shrink', () => {
    for (const selected of KEYS) {
        for (const keep of [true, false]) {
            it(`prune ${selected} keep=${keep}`, () => {
                const allowed = keep ? [selected, 'ghost'] : ['ghost']
                const pruned = pruneSelectedValuesToAllowed([selected], allowed)

                assert.deepEqual(pruned, keep ? [selected] : [])
            })
        }
    }
})
