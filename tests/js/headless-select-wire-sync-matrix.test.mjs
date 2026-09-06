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
    wireStateFromEngineValues,
} from '../../resources/js/components/select-field/headless-select-state.js'

/**
 * Configuration matrix for headless Select ↔ Livewire state.
 * Every row must stay green — these are the regressions that bite fill/required.
 */
const FIRST_PICK_CASES = [
    { name: 'null → value', current: null, values: ['1_10'], multiple: false, expect: '1_10' },
    { name: 'empty string → value', current: '', values: ['1_10'], multiple: false, expect: '1_10' },
    { name: 'null → other', current: null, values: ['other'], multiple: false, expect: 'other' },
    { name: 'null → numeric string', current: null, values: ['0'], multiple: false, expect: '0' },
    { name: '[] → first multi', current: [], values: ['a'], multiple: true, expect: ['a'] },
    { name: 'null-ish multi treated as write', current: null, values: ['a', 'b'], multiple: true, expect: ['a', 'b'] },
]

const CLEAR_CASES = [
    { name: 'single clear to null', current: '1_10', values: [], multiple: false, expect: null },
    { name: 'single replace', current: '1_10', values: ['11_50'], multiple: false, expect: '11_50' },
    { name: 'multi clear to []', current: ['a', 'b'], values: [], multiple: true, expect: [] },
    { name: 'multi remove one', current: ['a', 'b'], values: ['a'], multiple: true, expect: ['a'] },
]

const SKIP_CASES = [
    { name: 'unbound undefined skips', current: undefined, values: ['x'], multiple: false, skip: true },
    { name: 'same single skips', current: '1_10', values: ['1_10'], multiple: false, skip: true },
    { name: 'null and empty clear are equal', current: null, values: [], multiple: false, skip: true },
    { name: 'empty string and clear are equal', current: '', values: [], multiple: false, skip: true },
    { name: 'same multi skips', current: ['a', 'b'], values: ['a', 'b'], multiple: true, skip: true },
]

const INIT_HYDRATION_CASES = [
    { state: null, initial: 'published', multiple: false, hydrate: true },
    { state: '', initial: 'published', multiple: false, hydrate: true },
    { state: 'published', initial: 'published', multiple: false, hydrate: false },
    { state: null, initial: null, multiple: false, hydrate: false },
    { state: null, initial: '', multiple: false, hydrate: false },
    { state: undefined, initial: 'published', multiple: false, hydrate: false },
    { state: [], initial: ['jane'], multiple: true, hydrate: true },
    { state: ['jane'], initial: ['jane'], multiple: true, hydrate: false },
    { state: null, initial: ['jane'], multiple: true, hydrate: true },
]

const IGNORE_EMPTY_SYNC_CASES = [
    { next: null, initial: 'published', multiple: false, mutated: false, ignore: true },
    { next: '', initial: 'published', multiple: false, mutated: false, ignore: true },
    { next: null, initial: 'published', multiple: false, mutated: true, ignore: false },
    { next: 'published', initial: 'published', multiple: false, mutated: false, ignore: false },
    { next: [], initial: ['a'], multiple: true, mutated: false, ignore: true },
    { next: [], initial: ['a'], multiple: true, mutated: true, ignore: false },
    { next: null, initial: null, multiple: false, mutated: false, ignore: false },
]

const OPTION_SHAPES = [
    { value: '1_10', label: '1–10' },
    { value: '11_50', label: '11–50' },
    { value: 'other', label: 'Other' },
    { value: 'draft', label: 'Draft' },
    { value: 'published', label: 'Published' },
]

describe('headless select wire-sync matrix', () => {
    for (const row of FIRST_PICK_CASES) {
        it(`first pick: ${row.name}`, () => {
            const result = commitHeadlessSelectionToWire(row.current, row.values, row.multiple)

            assert.equal(result.skip, false)
            assert.deepEqual(result.nextState, row.expect)
        })
    }

    for (const row of CLEAR_CASES) {
        it(`clear/replace: ${row.name}`, () => {
            const result = commitHeadlessSelectionToWire(row.current, row.values, row.multiple)

            assert.equal(result.skip, false)
            assert.deepEqual(result.nextState, row.expect)
        })
    }

    for (const row of SKIP_CASES) {
        it(`skip: ${row.name}`, () => {
            const result = commitHeadlessSelectionToWire(row.current, row.values, row.multiple)

            assert.equal(result.skip, true)
        })
    }

    for (const row of INIT_HYDRATION_CASES) {
        it(`init hydrate state=${JSON.stringify(row.state)} initial=${JSON.stringify(row.initial)} multi=${row.multiple}`, () => {
            assert.equal(
                shouldHydrateInitialSelectionToWire(row.state, row.initial, row.multiple),
                row.hydrate,
            )

            if (! row.hydrate) {
                return
            }

            const pending = normalizeInitialSelectedValues(
                resolveHeadlessBoundState(row.state, row.initial, row.multiple),
                row.multiple,
            )
            const commit = commitHeadlessSelectionToWire(row.state, pending, row.multiple)

            assert.equal(commit.skip, false)
            assert.deepEqual(
                commit.nextState,
                wireStateFromEngineValues(pending, row.multiple),
            )
        })
    }

    for (const row of IGNORE_EMPTY_SYNC_CASES) {
        it(`ignore empty wire sync mutated=${row.mutated} next=${JSON.stringify(row.next)}`, () => {
            assert.equal(
                shouldIgnoreEmptyHeadlessWireSync(row.next, row.initial, row.multiple, row.mutated),
                row.ignore,
            )
        })
    }

    for (const option of OPTION_SHAPES) {
        it(`label/value pair "${option.label}" commits value key not label`, () => {
            const result = commitHeadlessSelectionToWire(null, [option.value], false)

            assert.equal(result.skip, false)
            assert.equal(result.nextState, option.value)
            assert.notEqual(result.nextState, option.label)
        })
    }

    it('canWrite allows null/empty and blocks only undefined', () => {
        assert.equal(canWriteHeadlessWireState(null), true)
        assert.equal(canWriteHeadlessWireState(''), true)
        assert.equal(canWriteHeadlessWireState([]), true)
        assert.equal(canWriteHeadlessWireState(0), true)
        assert.equal(canWriteHeadlessWireState(false), true)
        assert.equal(canWriteHeadlessWireState(undefined), false)
    })

    it('empty helpers agree for single and multi', () => {
        assert.equal(isHeadlessWireStateEmpty(null, false), true)
        assert.equal(isHeadlessWireStateEmpty('', false), true)
        assert.equal(isHeadlessWireStateEmpty('x', false), false)
        assert.equal(isHeadlessWireStateEmpty([], true), true)
        assert.equal(isHeadlessWireStateEmpty(['x'], true), false)
        assert.equal(hasHeadlessInitialSelection(null, false), false)
        assert.equal(hasHeadlessInitialSelection('x', false), true)
        assert.equal(hasHeadlessInitialSelection([], true), false)
        assert.equal(hasHeadlessInitialSelection(['x'], true), true)
    })

    it('failed dynamic option fetch must not apply empty remote options', () => {
        assert.equal(shouldApplyRemoteOptionsFromFetchResult(null), false)
        assert.equal(shouldApplyRemoteOptionsFromFetchResult([]), true)
        assert.equal(shouldApplyRemoteOptionsFromFetchResult([{ value: 'a', label: 'A' }]), true)
    })

    it('prunes only missing option keys and keeps valid selection', () => {
        assert.deepEqual(
            pruneSelectedValuesToAllowed(['1_10', 'gone', 'other'], ['1_10', 'other', '11_50']),
            ['1_10', 'other'],
        )
        assert.deepEqual(
            pruneSelectedValuesToAllowed(['1_10'], []),
            [],
        )
        assert.deepEqual(
            pruneSelectedValuesToAllowed([], ['1_10']),
            [],
        )
    })

    it('simulates fill required select: empty → pick → clear → pick again', () => {
        let state = null

        const apply = (values) => {
            const commit = commitHeadlessSelectionToWire(state, values, false)

            if (! commit.skip) {
                state = commit.nextState
            }

            return state
        }

        assert.equal(isHeadlessWireStateEmpty(state, false), true)
        assert.equal(apply(['1_10']), '1_10')
        assert.equal(isHeadlessWireStateEmpty(state, false), false)
        assert.equal(apply([]), null)
        assert.equal(isHeadlessWireStateEmpty(state, false), true)
        assert.equal(apply(['200_plus']), '200_plus')
        assert.equal(state, '200_plus')
    })

    it('simulates multi fill: empty → add → remove → clear', () => {
        let state = []

        const apply = (values) => {
            const commit = commitHeadlessSelectionToWire(state, values, true)

            if (! commit.skip) {
                state = commit.nextState
            }

            return state
        }

        assert.deepEqual(apply(['php']), ['php'])
        assert.deepEqual(apply(['php', 'react']), ['php', 'react'])
        assert.deepEqual(apply(['react']), ['react'])
        assert.deepEqual(apply([]), [])
        assert.equal(isHeadlessWireStateEmpty(state, true), true)
    })

    it('SSR default hydration then user clear then empty wire sync', () => {
        let state = null
        const initial = 'published'
        let userMutated = false

        assert.equal(shouldHydrateInitialSelectionToWire(state, initial, false), true)

        const pending = normalizeInitialSelectedValues(
            resolveHeadlessBoundState(state, initial, false),
            false,
        )
        const hydrate = commitHeadlessSelectionToWire(state, pending, false)
        assert.equal(hydrate.skip, false)
        state = hydrate.nextState
        assert.equal(state, 'published')

        // User clears
        userMutated = true
        const cleared = commitHeadlessSelectionToWire(state, [], false)
        state = cleared.nextState
        assert.equal(state, null)

        // Late empty Livewire echo must be accepted after user clear
        assert.equal(
            shouldIgnoreEmptyHeadlessWireSync(null, initial, false, userMutated),
            false,
        )
    })
})
