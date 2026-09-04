import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    createCascadingAdapter,
    createComboboxEngine,
    DEFAULT_VIRTUALIZE_THRESHOLD,
    DEFAULT_VIRTUAL_WINDOW_SIZE,
} from '../../resources/js/core/combobox-engine.js'

const sampleOptions = [
    { value: 'br', label: 'Brasil' },
    { value: 'us', label: 'United States' },
    { value: 'pt', label: 'Portugal' },
    { value: 'sp', label: 'S\u00e3o Paulo' },
]

describe('combobox-engine', () => {
    it('opens, closes, and toggles the menu', () => {
        const engine = createComboboxEngine({ options: sampleOptions })

        assert.equal(engine.getSnapshot().open, false)

        engine.open()
        assert.equal(engine.getSnapshot().open, true)

        engine.close()
        assert.equal(engine.getSnapshot().open, false)
        assert.equal(engine.getSnapshot().query, '')

        engine.toggle()
        assert.equal(engine.getSnapshot().open, true)

        engine.toggle()
        assert.equal(engine.getSnapshot().open, false)

        engine.destroy()
    })

    it('highlights the selected option when opening', () => {
        const engine = createComboboxEngine({
            options: sampleOptions,
            initialSelectedValues: ['pt'],
        })

        engine.open()
        assert.equal(engine.getSnapshot().highlightedIndex, 2)

        engine.destroy()
    })

    it('filters options with accent-insensitive search', () => {
        const engine = createComboboxEngine({ options: sampleOptions })

        engine.open()
        engine.setQuery('sao paulo')

        const { options, meta } = engine.filteredOptions()

        assert.equal(options.length, 1)
        assert.equal(options[0].value, 'sp')
        assert.equal(meta.total, 1)

        engine.setQuery('port')
        assert.deepEqual(
            engine.filteredOptions().options.map((option) => option.value),
            ['pt'],
        )

        engine.destroy()
    })

    it('reorders multiple selected values without treating order as unchanged', () => {
        const selected = []
        const engine = createComboboxEngine({
            options: sampleOptions,
            multiple: true,
            initialSelectedValues: ['br', 'us', 'pt'],
            onChange: (values) => selected.push([...values]),
        })

        engine.setSelectedValues(['pt', 'br', 'us'])

        assert.deepEqual(Array.from(engine.getSnapshot().selectedValues), ['pt', 'br', 'us'])
        assert.deepEqual(selected.at(-1), ['pt', 'br', 'us'])

        engine.setSelectedValues(['pt', 'br', 'us'])
        assert.deepEqual(selected.length, 1)

        engine.destroy()
    })

    it('supports multiple selection without closing the menu', () => {
        const selected = []
        const engine = createComboboxEngine({
            options: sampleOptions,
            multiple: true,
            onChange: (values) => selected.push([...values]),
        })

        engine.open()
        engine.selectValue('br')
        engine.selectValue('pt')

        assert.deepEqual(Array.from(engine.getSnapshot().selectedValues), ['br', 'pt'])
        assert.equal(engine.getSnapshot().open, true)
        assert.deepEqual(selected.at(-1), ['br', 'pt'])

        engine.deselectValue('br')
        assert.deepEqual(Array.from(engine.getSnapshot().selectedValues), ['pt'])

        engine.destroy()
    })

    it('selects a single value and closes the menu', () => {
        const engine = createComboboxEngine({ options: sampleOptions })

        engine.open()
        engine.selectValue('us')

        assert.deepEqual(Array.from(engine.getSnapshot().selectedValues), ['us'])
        assert.equal(engine.getSnapshot().open, false)

        engine.destroy()
    })

    it('returns a virtualized window when option count exceeds the threshold', () => {
        const options = Array.from({ length: DEFAULT_VIRTUALIZE_THRESHOLD + 25 }, (_, index) => ({
            value: `opt-${index}`,
            label: `Option ${index}`,
        }))

        const engine = createComboboxEngine({
            options,
            virtualizeThreshold: DEFAULT_VIRTUALIZE_THRESHOLD,
            virtualWindowSize: DEFAULT_VIRTUAL_WINDOW_SIZE,
        })

        engine.open()
        engine.moveHighlight(0)

        let result = engine.filteredOptions()

        assert.equal(result.meta.total, options.length)
        assert.equal(result.options.length, DEFAULT_VIRTUAL_WINDOW_SIZE)
        assert.equal(result.meta.startIndex, 0)
        assert.equal(result.meta.endIndex, DEFAULT_VIRTUAL_WINDOW_SIZE)

        engine.setVirtualWindowStart(50)
        result = engine.filteredOptions()

        assert.equal(result.meta.startIndex, 50)
        assert.equal(result.meta.endIndex, 100)
        assert.equal(result.options.length, DEFAULT_VIRTUAL_WINDOW_SIZE)
        assert.equal(result.options[0].value, 'opt-50')

        engine.destroy()
    })

    it('keeps a fixed window size for 10k options', () => {
        const options = Array.from({ length: 10_000 }, (_, index) => ({
            value: `opt-${index}`,
            label: `Option ${index}`,
        }))

        const engine = createComboboxEngine({
            options,
            virtualizeThreshold: DEFAULT_VIRTUALIZE_THRESHOLD,
            virtualWindowSize: DEFAULT_VIRTUAL_WINDOW_SIZE,
        })

        engine.open()
        engine.setVirtualWindowStart(4_950)

        const result = engine.filteredOptions()

        assert.equal(result.meta.total, 10_000)
        assert.equal(result.options.length, DEFAULT_VIRTUAL_WINDOW_SIZE)
        assert.equal(result.meta.startIndex, 4_950)
        assert.equal(result.options[0].value, 'opt-4950')

        engine.destroy()
    })

    it('createCascadingAdapter loads children for a parent value', async () => {
        const loadOptions = createCascadingAdapter({
            parentGet: () => 'country-us',
            loadChildren: async (parent) => [
                { value: `${parent}-ca`, label: 'California' },
                { value: `${parent}-tx`, label: 'Texas' },
            ],
        })

        assert.deepEqual(await loadOptions(), [
            { value: 'country-us-ca', label: 'California' },
            { value: 'country-us-tx', label: 'Texas' },
        ])

        const emptyLoader = createCascadingAdapter({
            parentGet: () => null,
            loadChildren: async () => [{ value: 'ignored', label: 'Ignored' }],
        })

        assert.deepEqual(await emptyLoader(), [])
    })

    it('builds smart suggest sections with recent, suggested, and create rows', () => {
        const engine = createComboboxEngine({
            options: sampleOptions,
            recentValues: ['us'],
            suggestedValues: ['pt'],
            allowCreate: true,
        })

        engine.setQuery('zzzz')
        const sections = engine.smartSections()

        assert.equal(sections.some((section) => section.type === 'create'), true)

        engine.setQuery('')
        const idleSections = engine.smartSections()

        assert.equal(idleSections[0]?.type, 'recent')
        assert.equal(idleSections[1]?.type, 'suggested')
        assert.equal(idleSections[2]?.type, 'options')
        assert.equal(
            idleSections[2].options.some((option) => option.value === 'us'),
            false,
            'pinned recent values must not repeat in the main options section',
        )
        assert.equal(
            idleSections[2].options.some((option) => option.value === 'pt'),
            false,
            'pinned suggested values must not repeat in the main options section',
        )

        engine.destroy()
    })

    it('omits empty recent section when pinned keys are missing from options', () => {
        const engine = createComboboxEngine({
            options: sampleOptions,
            recentValues: ['missing-recent'],
            suggestedValues: ['us'],
        })

        const sections = engine.smartSections()

        assert.equal(sections.some((section) => section.type === 'recent'), false)
        assert.equal(sections[0]?.type, 'suggested')

        engine.destroy()
    })
})
