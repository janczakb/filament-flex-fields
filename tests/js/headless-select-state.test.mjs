import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    canWriteHeadlessWireState,
    commitHeadlessSelectionToWire,
    hasHeadlessInitialSelection,
    isHeadlessWireStateEmpty,
    normalizeInitialSelectedValues,
    pruneSelectedValuesToAllowed,
    resolveHeadlessBoundState,
    shouldApplyRemoteOptionsFromFetchResult,
    shouldHydrateInitialSelectionToWire,
    shouldIgnoreEmptyHeadlessWireSync,
} from '../../resources/js/components/select-field/headless-select-state.js'

describe('headless-select-state', () => {
    it('allows writing the first pick when single-select wire state is still null', () => {
        assert.equal(canWriteHeadlessWireState(null), true)
        assert.equal(canWriteHeadlessWireState(''), true)
        assert.equal(canWriteHeadlessWireState('1_10'), true)
        assert.equal(canWriteHeadlessWireState(undefined), false)
    })

    it('commits first pick from null through commitHeadlessSelectionToWire', () => {
        const result = commitHeadlessSelectionToWire(null, ['1_10'], false)

        assert.equal(result.skip, false)
        assert.equal(result.nextState, '1_10')
    })

    it('hydrates SSR default into wire when state is still empty', () => {
        assert.equal(shouldHydrateInitialSelectionToWire(null, 'published', false), true)

        const pending = normalizeInitialSelectedValues(
            resolveHeadlessBoundState(null, 'published', false),
            false,
        )
        const commit = commitHeadlessSelectionToWire(null, pending, false)

        assert.deepEqual(pending, ['published'])
        assert.equal(commit.nextState, 'published')
    })

    it('does not apply empty remote options on soft-failed fetches', () => {
        assert.equal(shouldApplyRemoteOptionsFromFetchResult(null), false)
        assert.equal(shouldApplyRemoteOptionsFromFetchResult([]), true)
    })

    it('prunes selected values to the allowed remote option keys', () => {
        assert.deepEqual(
            pruneSelectedValuesToAllowed(['keep', 'drop'], ['keep', 'other']),
            ['keep'],
        )
    })

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
