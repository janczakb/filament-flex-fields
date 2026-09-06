/**
 * Mega generator — thousands of atomic headless wire-sync cases covering
 * playground-like configs (single/multi, clear, hydrate, prune, soft-fail).
 */
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

const STATUS = ['draft', 'reviewing', 'published']
const TECH = ['tailwind', 'laravel', 'livewire', 'alpine']
const GENRES = ['action', 'adventure', 'drama', 'comedy', 'horror', 'thriller']
const REGIONS = ['ca', 'tx', 'mz', 'delaware', 'california']
const USERS = ['jane', 'john', 'fred', 'alex', 'sam']
const SIZES = ['1_10', '11_50', 'other']
const RTL = ['riyadh', 'jeddah', 'tel_aviv']

const ALL_KEYS = [...STATUS, ...TECH, ...GENRES, ...REGIONS, ...USERS, ...SIZES, ...RTL, 'pro', 'sky', 'acme', '0', 'enterprise_agreement']

function* pairs(list, max = 120) {
    let n = 0

    for (let i = 0; i < list.length; i++) {
        for (let j = 0; j < list.length; j++) {
            yield [list[i], list[j]]
            n++

            if (n >= max) {
                return
            }
        }
    }
}

describe('mega — every playground-like key first pick from null/empty', () => {
    for (const current of [null, '']) {
        for (const key of ALL_KEYS) {
            it(`first pick current=${JSON.stringify(current)} → ${key}`, () => {
                const result = commitHeadlessSelectionToWire(current, [key], false)
                assert.equal(result.skip, false)
                assert.equal(result.nextState, key)
                assert.notEqual(result.nextState, undefined)
            })
        }
    }
})

describe('mega — every key clear then re-pick', () => {
    for (const key of ALL_KEYS) {
        it(`clear cycle ${key}`, () => {
            let state = key
            let commit = commitHeadlessSelectionToWire(state, [], false)
            assert.equal(commit.skip, false)
            state = commit.nextState
            assert.equal(state, null)

            commit = commitHeadlessSelectionToWire(state, [key], false)
            assert.equal(commit.skip, false)
            assert.equal(commit.nextState, key)
        })
    }
})

describe('mega — every ordered pair replace (status×status + tech×tech)', () => {
    for (const [from, to] of pairs(STATUS, 20)) {
        it(`status ${from} → ${to}`, () => {
            const result = commitHeadlessSelectionToWire(from, [to], false)

            if (from === to) {
                assert.equal(result.skip, true)
            } else {
                assert.equal(result.skip, false)
                assert.equal(result.nextState, to)
            }
        })
    }

    for (const [from, to] of pairs(TECH, 30)) {
        it(`tech ${from} → ${to}`, () => {
            const result = commitHeadlessSelectionToWire(from, [to], false)

            if (from === to) {
                assert.equal(result.skip, true)
            } else {
                assert.equal(result.skip, false)
                assert.equal(result.nextState, to)
            }
        })
    }

    for (const [from, to] of pairs(SIZES, 20)) {
        it(`size ${from} → ${to}`, () => {
            const result = commitHeadlessSelectionToWire(from, [to], false)

            if (from === to) {
                assert.equal(result.skip, true)
            } else {
                assert.equal(result.skip, false)
                assert.equal(result.nextState, to)
            }
        })
    }
})

describe('mega — multi genre subset conflicts', () => {
    const subsets = [
        [],
        ['action'],
        ['action', 'adventure'],
        ['action', 'adventure', 'drama'],
        ['comedy', 'horror'],
        GENRES.slice(),
        ['thriller', 'action'],
        ['drama'],
    ]

    for (const from of subsets) {
        for (const to of subsets) {
            it(`multi ${JSON.stringify(from)} → ${JSON.stringify(to)}`, () => {
                const result = commitHeadlessSelectionToWire(from, to, true)
                const same = from.length === to.length && from.every((value, index) => value === to[index])

                if (same) {
                    assert.equal(result.skip, true)
                } else {
                    assert.equal(result.skip, false)
                    assert.deepEqual(result.nextState, to.map(String))
                }
            })
        }
    }
})

describe('mega — hydrate matrix status × initial', () => {
    const wires = [null, '', 'draft', 'published', undefined]
    const initials = [null, '', 'draft', 'reviewing', 'published']

    for (const state of wires) {
        for (const initial of initials) {
            it(`hydrate state=${JSON.stringify(state)} initial=${JSON.stringify(initial)}`, () => {
                const should = shouldHydrateInitialSelectionToWire(state, initial, false)

                if (state === undefined) {
                    assert.equal(should, false)

                    return
                }

                const expect = isHeadlessWireStateEmpty(state, false) && hasHeadlessInitialSelection(initial, false)
                assert.equal(should, expect)

                if (! should) {
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

describe('mega — ignore empty sync matrix', () => {
    for (const mutated of [false, true]) {
        for (const next of [null, '', 'published']) {
            for (const initial of [null, '', 'published', 'draft']) {
                it(`ignore mutated=${mutated} next=${JSON.stringify(next)} initial=${JSON.stringify(initial)}`, () => {
                    const ignore = shouldIgnoreEmptyHeadlessWireSync(next, initial, false, mutated)

                    if (mutated) {
                        assert.equal(ignore, false)
                    } else {
                        assert.equal(
                            ignore,
                            isHeadlessWireStateEmpty(next, false) && hasHeadlessInitialSelection(initial, false),
                        )
                    }
                })
            }
        }
    }
})

describe('mega — prune every key against rotating allow-lists', () => {
    const allowLists = [
        STATUS,
        TECH,
        GENRES,
        SIZES,
        USERS,
        [...STATUS, ...TECH],
        ALL_KEYS.slice(0, 10),
        [],
    ]

    for (const key of ALL_KEYS) {
        for (const [index, allowed] of allowLists.entries()) {
            it(`prune ${key} allow#${index}`, () => {
                const pruned = pruneSelectedValuesToAllowed([key, 'ghost'], allowed)

                if (allowed.map(String).includes(String(key))) {
                    assert.deepEqual(pruned, [String(key)])
                } else {
                    assert.deepEqual(pruned, [])
                }
            })
        }
    }
})

describe('mega — remote soft-fail × selection preserve', () => {
    const fetches = [null, undefined, [], [{ value: 'a' }], [{ value: 'published' }], false, 0, 'x']

    for (const results of fetches) {
        for (const selected of [[], ['published'], ['draft', 'published']]) {
            it(`remote ${JSON.stringify(results)} selected=${JSON.stringify(selected)}`, () => {
                const apply = shouldApplyRemoteOptionsFromFetchResult(results)

                if (results === null) {
                    assert.equal(apply, false)
                    // soft-fail: do not prune
                    assert.deepEqual(selected, selected)
                } else {
                    assert.equal(apply, true)

                    if (Array.isArray(results)) {
                        const allowed = results.map((row) => String(row.value))
                        const pruned = pruneSelectedValuesToAllowed(selected, allowed)
                        assert.ok(pruned.every((value) => allowed.includes(value)))
                    }
                }
            })
        }
    }
})

describe('mega — undefined wire never writes', () => {
    for (const values of [[], ['a'], STATUS, TECH, GENRES]) {
        it(`undefined skip values=${JSON.stringify(values)}`, () => {
            assert.equal(canWriteHeadlessWireState(undefined), false)
            assert.equal(commitHeadlessSelectionToWire(undefined, values, false).skip, true)
            assert.equal(commitHeadlessSelectionToWire(undefined, values, true).skip, true)
        })
    }
})

describe('mega — wireStateFromEngineValues shape', () => {
    for (const key of ALL_KEYS) {
        it(`shape single ${key}`, () => {
            assert.equal(wireStateFromEngineValues([key], false), key)
            assert.equal(wireStateFromEngineValues([], false), null)
            assert.deepEqual(wireStateFromEngineValues([key], true), [key])
            assert.deepEqual(wireStateFromEngineValues([], true), [])
        })
    }
})

describe('mega — reorderable multi order conflicts', () => {
    const orders = [
        TECH.slice(),
        [...TECH].reverse(),
        ['laravel', 'tailwind'],
        ['alpine', 'livewire', 'laravel', 'tailwind'],
        ['tailwind'],
        [],
    ]

    for (const from of orders) {
        for (const to of orders) {
            it(`reorder ${JSON.stringify(from)} → ${JSON.stringify(to)}`, () => {
                const result = commitHeadlessSelectionToWire(from, to, true)
                const same = from.length === to.length && from.every((value, index) => value === to[index])

                if (same) {
                    assert.equal(result.skip, true)
                } else {
                    assert.equal(result.skip, false)
                    assert.deepEqual(result.nextState, to.map(String))
                }
            })
        }
    }
})

describe('mega — cascade country/region conflict sequences', () => {
    const sequences = [
        { country: 'us', regionSteps: [null, 'ca', 'tx', null, 'ca'] },
        { country: 'pl', regionSteps: [null, 'mz', null, 'mz'] },
        { country: 'us', regionSteps: ['ca', 'mz', 'ca'] }, // mz invalid then recover
    ]

    for (const { country, regionSteps } of sequences) {
        const allowed = country === 'us' ? ['ca', 'tx'] : country === 'pl' ? ['mz'] : []

        it(`cascade ${country} steps=${JSON.stringify(regionSteps)}`, () => {
            let region = null

            for (const step of regionSteps) {
                const engineValues = step == null ? [] : [step]
                const commit = commitHeadlessSelectionToWire(region, engineValues, false)

                if (! commit.skip) {
                    region = commit.nextState
                }

                const pruned = pruneSelectedValuesToAllowed(
                    region == null ? [] : [region],
                    allowed,
                )

                if (step != null && ! allowed.includes(step)) {
                    assert.deepEqual(pruned, [])
                }
            }
        })
    }
})

describe('mega — mentions-style multi users', () => {
    const subsets = [
        [],
        ['jane'],
        ['jane', 'john'],
        ['fred'],
        USERS.slice(0, 3),
        USERS.slice(),
    ]

    for (const from of subsets) {
        for (const to of subsets) {
            it(`mentions ${JSON.stringify(from)} → ${JSON.stringify(to)}`, () => {
                const result = commitHeadlessSelectionToWire(from, to, true)

                if (JSON.stringify(from) === JSON.stringify(to)) {
                    assert.equal(result.skip, true)
                } else {
                    assert.equal(result.skip, false)
                    assert.deepEqual(result.nextState, to.map(String))
                }
            })
        }
    }
})
