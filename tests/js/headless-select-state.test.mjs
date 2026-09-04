import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    hasHeadlessInitialSelection,
    isHeadlessWireStateEmpty,
    normalizeInitialSelectedValues,
    resolveHeadlessBoundState,
    shouldIgnoreEmptyHeadlessWireSync,
} from '../../resources/js/components/select-field/headless-select-state.js'

describe('headless-select-state', () => {
    it('hydrates single select from initial state on first paint', () => {
        const resolved = resolveHeadlessBoundState(null, 'published', false)

        assert.deepEqual(normalizeInitialSelectedValues(resolved, false), ['published'])
    })

    it('does not restore initial state after the user clears a single select', () => {
        const resolved = resolveHeadlessBoundState(null, 'published', false, { fallbackToInitial: false })

        assert.equal(resolved, null)
        assert.deepEqual(normalizeInitialSelectedValues(resolved, false), [])
    })

    it('does not restore initial chips after the user clears a multi select', () => {
        const resolved = resolveHeadlessBoundState([], ['jane', 'john'], true, { fallbackToInitial: false })

        assert.deepEqual(resolved, [])
        assert.deepEqual(normalizeInitialSelectedValues(resolved, true), [])
    })

    it('keeps an explicit multi-select state array even when empty', () => {
        const resolved = resolveHeadlessBoundState([], ['jane', 'john'], true, { fallbackToInitial: false })

        assert.deepEqual(normalizeInitialSelectedValues(resolved, true), [])
    })

    it('ignores a late empty livewire sync while default state is still seeded', () => {
        assert.equal(
            shouldIgnoreEmptyHeadlessWireSync(null, 'published', false, false),
            true,
        )
        assert.equal(
            shouldIgnoreEmptyHeadlessWireSync([], ['jane', 'john'], true, false),
            true,
        )
    })

    it('accepts an empty livewire sync after the user clears the field', () => {
        assert.equal(
            shouldIgnoreEmptyHeadlessWireSync(null, 'published', false, true),
            false,
        )
    })

    it('does not treat intentionally empty defaults as restorable', () => {
        assert.equal(hasHeadlessInitialSelection(null, false), false)
        assert.equal(isHeadlessWireStateEmpty(null, false), true)
        assert.equal(
            shouldIgnoreEmptyHeadlessWireSync(null, null, false, false),
            false,
        )
    })
})
