/**
 * Combobox engine exhaustive matrix — virtualization, create, multi, cascade-ish conflicts.
 */
import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    createComboboxEngine,
    DEFAULT_VIRTUALIZE_THRESHOLD,
    DEFAULT_VIRTUAL_WINDOW_SIZE,
} from '../../resources/js/core/combobox-engine.js'

function makeOptions(count, prefix = 'opt') {
    return Array.from({ length: count }, (_, index) => ({
        value: `${prefix}_${index}`,
        label: `Label ${prefix} ${index}`,
    }))
}

function selectedList(engine) {
    return Array.from(engine.getSnapshot().selectedValues).map(String)
}

const OPTION_COUNTS = [0, 1, 2, 10, 50, DEFAULT_VIRTUALIZE_THRESHOLD, DEFAULT_VIRTUALIZE_THRESHOLD + 1, 200]
const MULTIPLE_FLAGS = [false, true]
const SEARCH_QUERIES = ['', 'Label', 'opt 0', 'zzz_missing', '1', 'LABEL']

describe('combobox engine exhaustive — open/select/clear', () => {
    for (const count of OPTION_COUNTS) {
        for (const multiple of MULTIPLE_FLAGS) {
            it(`options=${count} multiple=${multiple}: select first then clear`, () => {
                const options = makeOptions(count)
                const selectedLog = []
                const engine = createComboboxEngine({
                    options,
                    multiple,
                    searchable: true,
                    onChange: (values) => selectedLog.push([...values]),
                })

                engine.open()
                assert.equal(engine.getSnapshot().open, true)

                if (count === 0) {
                    assert.equal(engine.filteredOptions().options.length, 0)
                    engine.close()
                    engine.destroy()

                    return
                }

                const first = options[0].value
                engine.selectValue(first)

                assert.ok(selectedList(engine).includes(String(first)))

                engine.setSelectedValues([])
                assert.deepEqual(selectedList(engine), [])

                engine.close()
                engine.destroy()
                assert.ok(selectedLog.length >= 1)
            })
        }
    }
})

describe('combobox engine exhaustive — search filter', () => {
    for (const count of [5, 20, 80]) {
        for (const query of SEARCH_QUERIES) {
            it(`filter count=${count} query=${JSON.stringify(query)}`, () => {
                const options = makeOptions(count)
                const engine = createComboboxEngine({
                    options,
                    searchable: true,
                })

                engine.open()
                engine.setQuery(query)
                const { options: filtered, meta } = engine.filteredOptions()

                assert.ok(Array.isArray(filtered))
                assert.equal(meta.total, filtered.length)

                if (query === '') {
                    assert.equal(filtered.length, count)
                }

                if (query === 'zzz_missing') {
                    assert.equal(filtered.length, 0)
                }

                if (query.toLowerCase() === 'label') {
                    assert.equal(filtered.length, count)
                }

                if (query === 'opt 0') {
                    assert.ok(filtered.length >= 1)
                    assert.ok(filtered.every((option) => {
                        const hay = `${option.value} ${option.label}`.toLowerCase()

                        return hay.includes('opt') && hay.includes('0')
                    }))
                }

                engine.destroy()
            })
        }
    }
})

describe('combobox engine exhaustive — virtualization windows', () => {
    for (const count of [DEFAULT_VIRTUALIZE_THRESHOLD - 1, DEFAULT_VIRTUALIZE_THRESHOLD, DEFAULT_VIRTUALIZE_THRESHOLD + 5, 500]) {
        for (const windowSize of [DEFAULT_VIRTUAL_WINDOW_SIZE, 8, 24]) {
            it(`virtualize count=${count} window=${windowSize}`, () => {
                const options = makeOptions(count)
                const engine = createComboboxEngine({
                    options,
                    searchable: true,
                    virtualizeThreshold: DEFAULT_VIRTUALIZE_THRESHOLD,
                    virtualWindowSize: windowSize,
                })

                engine.open()
                const { options: visible, meta } = engine.filteredOptions()

                assert.equal(meta.total, count)

                if (count >= DEFAULT_VIRTUALIZE_THRESHOLD) {
                    assert.ok(visible.length <= Math.min(count, windowSize))
                    assert.ok(visible.length > 0 || count === 0)
                } else {
                    assert.equal(visible.length, count)
                }

                engine.destroy()
            })
        }
    }
})

describe('combobox engine exhaustive — multi reorder conflicts', () => {
    const permutations = [
        ['a', 'b', 'c'],
        ['c', 'b', 'a'],
        ['b', 'a', 'c'],
        ['a'],
        ['a', 'b'],
        [],
    ]

    for (const order of permutations) {
        it(`setSelectedValues order=${JSON.stringify(order)}`, () => {
            const options = [
                { value: 'a', label: 'A' },
                { value: 'b', label: 'B' },
                { value: 'c', label: 'C' },
            ]
            const engine = createComboboxEngine({
                options,
                multiple: true,
                initialSelectedValues: ['a', 'b', 'c'],
            })

            engine.setSelectedValues(order)
            assert.deepEqual(selectedList(engine), order.map(String))
            engine.destroy()
        })
    }
})

describe('combobox engine exhaustive — create option path', () => {
    for (const multiple of [false, true]) {
        for (const query of ['brand_new', 'x', 'with space']) {
            it(`create multiple=${multiple} query=${JSON.stringify(query)}`, () => {
                const options = makeOptions(3)
                const engine = createComboboxEngine({
                    options,
                    multiple,
                    searchable: true,
                    allowCreate: true,
                    createOptionLabel: (q) => `Create "${q}"`,
                })

                engine.open()
                engine.setQuery(query)

                const sections = engine.smartSections()
                assert.ok(sections.some((section) => section.type === 'create'))

                assert.equal(engine.createInlineOption(query), true)
                assert.ok(selectedList(engine).includes(query.trim()))

                engine.destroy()
            })
        }
    }
})

describe('combobox engine exhaustive — recent & suggested sections', () => {
    for (const query of ['', 'Label']) {
        it(`smartSections query=${JSON.stringify(query)}`, () => {
            const options = makeOptions(8)
            const engine = createComboboxEngine({
                options,
                searchable: true,
                recentValues: ['opt_0', 'opt_1'],
                suggestedValues: ['opt_1', 'opt_2'],
            })

            engine.open()
            engine.setQuery(query)
            const sections = engine.smartSections()

            if (query === '') {
                assert.ok(sections.some((section) => section.type === 'recent'))
                assert.ok(sections.some((section) => section.type === 'suggested'))
            }

            engine.destroy()
        })
    }
})

describe('combobox engine exhaustive — rapid conflict toggles', () => {
    it('hammer single select between two values', () => {
        const options = makeOptions(4)
        const engine = createComboboxEngine({
            options,
            multiple: false,
            onChange: () => {},
        })

        for (let i = 0; i < 50; i++) {
            const value = options[i % options.length].value
            engine.setSelectedValues([value])
            assert.deepEqual(selectedList(engine), [String(value)])
            engine.setSelectedValues([])
            assert.deepEqual(selectedList(engine), [])
        }

        engine.destroy()
    })

    it('hammer multi add/remove', () => {
        const options = makeOptions(6)
        const engine = createComboboxEngine({
            options,
            multiple: true,
        })

        for (let i = 0; i < 40; i++) {
            const pick = options.slice(0, (i % options.length) + 1).map((option) => option.value)
            engine.setSelectedValues(pick)
            assert.deepEqual(selectedList(engine), pick.map(String))
        }

        engine.destroy()
    })
})

describe('combobox engine exhaustive — cascade option swap prune simulation', () => {
    const regionsByCountry = {
        us: ['ca', 'tx'],
        pl: ['mz'],
        '': [],
    }

    for (const country of ['', 'us', 'pl', 'us', 'pl', '']) {
        for (const prior of [[], ['ca'], ['tx'], ['mz'], ['ca', 'tx']]) {
            it(`cascade country=${country || 'empty'} prior=${JSON.stringify(prior)}`, () => {
                const allowed = regionsByCountry[country] ?? []
                const options = allowed.map((value) => ({ value, label: value.toUpperCase() }))
                const engine = createComboboxEngine({
                    options,
                    multiple: false,
                    initialSelectedValues: prior.filter((value) => allowed.includes(value)).slice(0, 1),
                })

                const pruned = prior.filter((value) => allowed.includes(value))
                engine.setSelectedValues(pruned.slice(0, 1))
                assert.ok(selectedList(engine).every((value) => allowed.includes(value)))
                engine.destroy()
            })
        }
    }
})
