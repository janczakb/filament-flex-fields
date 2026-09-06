/**
 * Exhaustive Cartesian matrices for headless Select ↔ Livewire wire-sync.
 * Generated atomics — intentionally huge; each case is independent.
 */
import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    canWriteHeadlessWireState,
    commitHeadlessSelectionToWire,
    isHeadlessWireStateEmpty,
    normalizeInitialSelectedValues,
    pruneSelectedValuesToAllowed,
    resolveHeadlessBoundState,
    shouldApplyRemoteOptionsFromFetchResult,
    shouldHydrateInitialSelectionToWire,
    shouldIgnoreEmptyHeadlessWireSync,
    wireStateFromEngineValues,
} from '../../resources/js/components/select-field/headless-select-state.js'

/** Playground-like option keys from select-field hub. */
const PLAYGROUND_KEYS = Object.freeze([
    'published', 'draft', 'reviewing', 'tailwind', 'laravel', 'livewire', 'alpine',
    'action', 'adventure', 'drama', 'comedy', 'horror', 'thriller',
    'california', 'texas', 'delaware', 'jane', 'john', 'fred',
    'usa', 'pro', 'sky', 'mint', 'acme', 'us', 'pl', 'ca', 'tx', 'mz',
    'riyadh', 'jeddah', 'tel_aviv', '1_10', '11_50', 'other', 'enterprise_agreement',
    '0', 'custom_created', 'recent_a', 'suggested_b',
])

const SINGLE_CURRENTS = Object.freeze([
    null, undefined, '', 'published', 'draft', 'tailwind', '1_10', 'other', '0', 'not_in_list',
])

const MULTI_CURRENTS = Object.freeze([
    null, undefined, [], ['published'], ['tailwind', 'laravel'], ['jane', 'john'], ['a', 'b', 'c'],
])

const SINGLE_ENGINE_VALUES = Object.freeze([
    [],
    ['published'],
    ['draft'],
    ['1_10'],
    ['other'],
    ['custom_created'],
    ['not_in_list'],
    ['0'],
])

const MULTI_ENGINE_VALUES = Object.freeze([
    [],
    ['tailwind'],
    ['tailwind', 'laravel'],
    ['laravel', 'tailwind'],
    ['jane', 'john', 'fred'],
    ['action', 'adventure', 'drama', 'comedy'],
    ['california', 'texas'],
])

const INITIAL_SINGLES = Object.freeze([null, '', 'published', 'draft', 'tailwind', '1_10'])
const INITIAL_MULTIS = Object.freeze([null, [], ['jane'], ['tailwind', 'laravel'], ['california', 'texas']])

const ALLOWED_SETS = Object.freeze([
    [],
    ['published', 'draft'],
    ['tailwind', 'laravel', 'livewire', 'alpine'],
    ['1_10', '11_50', 'other'],
    ['jane', 'john'],
    PLAYGROUND_KEYS.slice(0, 12),
])

function assertCommitInvariants(current, values, multiple, result) {
    if (! canWriteHeadlessWireState(current)) {
        assert.equal(result.skip, true)

        return
    }

    const expected = wireStateFromEngineValues(values, multiple)

    if (multiple) {
        const currentList = Array.isArray(current) ? current.map(String) : null
        const same = currentList !== null
            && currentList.length === expected.length
            && currentList.every((value, index) => value === expected[index])

        if (same) {
            assert.equal(result.skip, true)
        } else {
            assert.equal(result.skip, false)
            assert.deepEqual(result.nextState, expected)
            assert.ok(Array.isArray(result.nextState))
        }

        return
    }

    if (String(current ?? '') === String(expected ?? '')) {
        assert.equal(result.skip, true)
    } else {
        assert.equal(result.skip, false)
        assert.equal(result.nextState, expected)
        if (expected !== null) {
            assert.equal(typeof result.nextState, 'string')
        }
    }
}

describe('select headless cartesian — single commit', () => {
    for (const current of SINGLE_CURRENTS) {
        for (const values of SINGLE_ENGINE_VALUES) {
            it(`single current=${JSON.stringify(current)} values=${JSON.stringify(values)}`, () => {
                const result = commitHeadlessSelectionToWire(current, values, false)
                assertCommitInvariants(current, values, false, result)
            })
        }
    }
})

describe('select headless cartesian — multi commit', () => {
    for (const current of MULTI_CURRENTS) {
        for (const values of MULTI_ENGINE_VALUES) {
            it(`multi current=${JSON.stringify(current)} values=${JSON.stringify(values)}`, () => {
                const result = commitHeadlessSelectionToWire(current, values, true)
                assertCommitInvariants(current, values, true, result)
            })
        }
    }
})

describe('select headless cartesian — init hydrate single', () => {
    for (const state of SINGLE_CURRENTS) {
        for (const initial of INITIAL_SINGLES) {
            it(`hydrate single state=${JSON.stringify(state)} initial=${JSON.stringify(initial)}`, () => {
                const should = shouldHydrateInitialSelectionToWire(state, initial, false)

                if (! should) {
                    assert.ok(
                        ! canWriteHeadlessWireState(state)
                        || ! isHeadlessWireStateEmpty(state, false)
                        || ! (initial != null && initial !== ''),
                    )

                    return
                }

                const pending = normalizeInitialSelectedValues(
                    resolveHeadlessBoundState(state, initial, false),
                    false,
                )
                const commit = commitHeadlessSelectionToWire(state, pending, false)
                assert.equal(commit.skip, false)
                assert.equal(commit.nextState, String(initial))
            })
        }
    }
})

describe('select headless cartesian — init hydrate multi', () => {
    for (const state of MULTI_CURRENTS) {
        for (const initial of INITIAL_MULTIS) {
            it(`hydrate multi state=${JSON.stringify(state)} initial=${JSON.stringify(initial)}`, () => {
                const should = shouldHydrateInitialSelectionToWire(state, initial, true)

                if (! should) {
                    return
                }

                const pending = normalizeInitialSelectedValues(
                    resolveHeadlessBoundState(state, initial, true),
                    true,
                )
                const commit = commitHeadlessSelectionToWire(state, pending, true)
                assert.equal(commit.skip, false)
                assert.deepEqual(commit.nextState, pending)
                assert.ok(pending.length > 0)
            })
        }
    }
})

describe('select headless cartesian — ignore empty wire sync', () => {
    for (const mutated of [false, true]) {
        for (const next of [null, '', 'published', 'draft']) {
            for (const initial of INITIAL_SINGLES) {
                it(`ignore single mutated=${mutated} next=${JSON.stringify(next)} initial=${JSON.stringify(initial)}`, () => {
                    const ignore = shouldIgnoreEmptyHeadlessWireSync(next, initial, false, mutated)

                    if (mutated) {
                        assert.equal(ignore, false)

                        return
                    }

                    const expectIgnore = isHeadlessWireStateEmpty(next, false)
                        && (initial != null && initial !== '')
                    assert.equal(ignore, expectIgnore)
                })
            }
        }
    }

    for (const mutated of [false, true]) {
        for (const next of [null, [], ['jane'], ['a', 'b']]) {
            for (const initial of INITIAL_MULTIS) {
                it(`ignore multi mutated=${mutated} next=${JSON.stringify(next)} initial=${JSON.stringify(initial)}`, () => {
                    const ignore = shouldIgnoreEmptyHeadlessWireSync(next, initial, true, mutated)

                    if (mutated) {
                        assert.equal(ignore, false)

                        return
                    }

                    const expectIgnore = isHeadlessWireStateEmpty(next, true)
                        && Array.isArray(initial) && initial.length > 0
                    assert.equal(ignore, expectIgnore)
                })
            }
        }
    }
})

describe('select headless cartesian — prune vs allowed options', () => {
    for (const selected of MULTI_ENGINE_VALUES) {
        for (const allowed of ALLOWED_SETS) {
            it(`prune selected=${JSON.stringify(selected)} allowedLen=${allowed.length}`, () => {
                const pruned = pruneSelectedValuesToAllowed(selected, allowed)
                const allowedSet = new Set(allowed.map(String))

                assert.ok(pruned.every((value) => allowedSet.has(String(value))))
                assert.ok(pruned.length <= selected.length)
                // Order preserved for survivors
                const survivors = selected.map(String).filter((value) => allowedSet.has(value))
                assert.deepEqual(pruned, survivors)
            })
        }
    }
})

describe('select headless cartesian — remote fetch soft-fail', () => {
    for (const results of [null, undefined, [], [{ value: 'a', label: 'A' }], 'bad', 0, false]) {
        it(`shouldApplyRemoteOptionsFromFetchResult(${JSON.stringify(results)})`, () => {
            assert.equal(
                shouldApplyRemoteOptionsFromFetchResult(results),
                results !== null,
            )
        })
    }
})

describe('select headless cartesian — conflict sequences (single)', () => {
    const sequences = [
        [['published'], [], ['draft']],
        [['draft'], ['published'], ['draft']],
        [[], ['1_10'], [], ['other'], ['1_10']],
        [['published'], ['not_in_list'], ['published']],
        [['custom_created'], [], ['custom_created']],
        [[], [], ['published']],
        [['a'], ['b'], ['c'], ['a']],
    ]

    for (const [index, sequence] of sequences.entries()) {
        for (const start of [null, '', 'published']) {
            it(`conflict seq#${index} start=${JSON.stringify(start)} steps=${JSON.stringify(sequence)}`, () => {
                let state = start

                for (const values of sequence) {
                    const commit = commitHeadlessSelectionToWire(state, values, false)

                    if (! commit.skip) {
                        state = commit.nextState
                    }

                    const expected = wireStateFromEngineValues(values, false)

                    if (canWriteHeadlessWireState(start === undefined ? undefined : state) || state !== undefined) {
                        if (String(state ?? '') !== String(expected ?? '') && commit.skip === false) {
                            assert.equal(state, expected)
                        }
                    }
                }

                const last = sequence[sequence.length - 1]
                const finalExpected = wireStateFromEngineValues(last, false)

                if (canWriteHeadlessWireState(state) || state !== undefined) {
                    // After last write that wasn't a no-op, state matches last engine values
                    const lastCommit = commitHeadlessSelectionToWire(
                        wireStateFromEngineValues(sequence[sequence.length - 2] ?? [], false),
                        last,
                        false,
                    )

                    if (! lastCommit.skip || String(state ?? '') === String(finalExpected ?? '')) {
                        assert.equal(
                            String(state ?? ''),
                            String(finalExpected ?? ''),
                        )
                    }
                }
            })
        }
    }
})

describe('select headless cartesian — conflict sequences (multi)', () => {
    const sequences = [
        [['tailwind'], ['tailwind', 'laravel'], ['laravel'], []],
        [[], ['jane'], ['jane', 'john'], ['john'], ['jane', 'john', 'fred']],
        [['a', 'b'], ['b', 'a'], ['a'], ['a', 'b', 'c']],
        [['california', 'texas'], [], ['delaware'], ['california', 'texas', 'delaware']],
    ]

    for (const [index, sequence] of sequences.entries()) {
        for (const start of [[], ['tailwind'], null]) {
            it(`multi conflict seq#${index} start=${JSON.stringify(start)}`, () => {
                let state = start

                for (const values of sequence) {
                    const commit = commitHeadlessSelectionToWire(state, values, true)

                    if (! commit.skip) {
                        state = commit.nextState
                    }
                }

                const finalExpected = wireStateFromEngineValues(sequence[sequence.length - 1], true)
                assert.deepEqual(state, finalExpected)
            })
        }
    }
})

describe('select headless cartesian — playground key round-trips', () => {
    for (const key of PLAYGROUND_KEYS) {
        it(`playground key ${key}: null → key → clear → key`, () => {
            let state = null
            let commit = commitHeadlessSelectionToWire(state, [key], false)
            assert.equal(commit.skip, false)
            state = commit.nextState
            assert.equal(state, key)
            assert.equal(isHeadlessWireStateEmpty(state, false), false)

            commit = commitHeadlessSelectionToWire(state, [], false)
            assert.equal(commit.skip, false)
            state = commit.nextState
            assert.equal(state, null)
            assert.equal(isHeadlessWireStateEmpty(state, false), true)

            commit = commitHeadlessSelectionToWire(state, [key], false)
            state = commit.nextState
            assert.equal(state, key)
        })
    }
})

describe('select headless cartesian — label must never become wire value', () => {
    const pairs = [
        ['1_10', '1–10'],
        ['11_50', '11–50'],
        ['published', 'Published'],
        ['draft', 'Draft'],
        ['tailwind', 'Tailwind CSS'],
        ['enterprise_agreement', 'Enterprise Agreement (very long label for truncation demos)'],
        ['tel_aviv', 'תל אביב'],
        ['riyadh', 'الرياض'],
    ]

    for (const [value, label] of pairs) {
        it(`value=${value} label=${label}`, () => {
            const commit = commitHeadlessSelectionToWire(null, [value], false)
            assert.equal(commit.nextState, value)
            assert.notEqual(commit.nextState, label)
        })
    }
})
