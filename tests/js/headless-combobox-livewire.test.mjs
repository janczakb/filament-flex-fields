import assert from 'node:assert/strict'
import { test } from 'node:test'

import { createComboboxEngine } from '../../resources/js/core/combobox-engine.js'
import { createHeadlessComboboxLivewireMixin } from '../../resources/js/components/select-field/headless-combobox-livewire.js'

function makeHost(overrides = {}) {
    const host = {
        options: [],
        flatOptions: [],
        _virtualFlatRows: [{ type: 'stale' }],
        virtualScrollTick: 0,
        comboboxQuery: 'rick',
        comboboxSelectedValues: [],
        $nextTick: (callback) => {
            callback()

            return Promise.resolve()
        },
        markKnownOptionChecksVisible() {},
        scheduleMenuPositionAfterLayout() {},
        syncDropdownOverflowChrome() {},
        _syncFromEngine() {},
        _engine: createComboboxEngine({
            options: [],
            searchable: true,
            filterFn: () => true,
        }),
        ...createHeadlessComboboxLivewireMixin({
            hasDynamicSearchResults: true,
            hasPaginatedSearchResults: true,
        }),
        ...overrides,
    }

    return host
}

test('applyRemoteOptions replaces options and busts the virtual row cache', () => {
    const host = makeHost()

    host.applyRemoteOptions([
        { value: '1', label: 'Rick Sanchez' },
        { value: '2', label: 'Birdperson' },
    ])

    assert.equal(host.options.length, 2)
    assert.equal(host.flatOptions.length, 2)
    assert.deepEqual(host._virtualFlatRows, [])
    assert.equal(host.virtualScrollTick, 1)
    assert.equal(host._engine.filteredOptions().options.length, 2)
})

test('appendRemoteOptions concatenates unique pages for paginated search', () => {
    const host = makeHost({
        options: [{ value: '1', label: 'Rick Sanchez' }],
        flatOptions: [{ value: '1', label: 'Rick Sanchez' }],
    })

    host.appendRemoteOptions([
        { value: '1', label: 'Rick Sanchez' },
        { value: '3', label: 'Summer Smith' },
    ])

    assert.deepEqual(host.options.map((option) => option.value), ['1', '3'])
    assert.equal(host.virtualScrollTick, 1)
})

test('fetchDynamicOptions soft-fails without wiping painted options or selection', async () => {
    let applied = 0
    const host = makeHost({
        hasDynamicOptions: true,
        hasDynamicSearchResults: false,
        options: [{ value: '1_10', label: '1–10' }],
        flatOptions: [{ value: '1_10', label: '1–10' }],
        comboboxSelectedValues: ['1_10'],
        optionsLoading: false,
        _dynamicOptionsFetchInFlight: false,
        beginDynamicOptionsFetchGuard() {},
        endDynamicOptionsFetchGuard() {},
        async callSchemaMethod() {
            return null
        },
        applyRemoteOptions(...args) {
            applied += 1

            return createHeadlessComboboxLivewireMixin({
                hasDynamicOptions: true,
                hasDynamicSearchResults: false,
            }).applyRemoteOptions.call(this, ...args)
        },
    })

    const ok = await host.fetchDynamicOptions()

    assert.equal(ok, false)
    assert.equal(applied, 0)
    assert.deepEqual(host.comboboxSelectedValues, ['1_10'])
    assert.equal(host.options.length, 1)
})

test('fetchDynamicOptions catch path also soft-fails without wiping', async () => {
    let applied = 0
    const host = makeHost({
        hasDynamicOptions: true,
        hasDynamicSearchResults: false,
        options: [{ value: '1_10', label: '1–10' }],
        flatOptions: [{ value: '1_10', label: '1–10' }],
        comboboxSelectedValues: ['1_10'],
        optionsLoading: false,
        _dynamicOptionsFetchInFlight: false,
        beginDynamicOptionsFetchGuard() {},
        endDynamicOptionsFetchGuard() {},
        async callSchemaMethod() {
            throw new Error('network')
        },
        applyRemoteOptions() {
            applied += 1
        },
    })

    const ok = await host.fetchDynamicOptions()

    assert.equal(ok, false)
    assert.equal(applied, 0)
    assert.deepEqual(host.comboboxSelectedValues, ['1_10'])
})

test('applyRemoteOptions prunes invalid selection without marking user mutation', () => {
    const host = makeHost({
        hasDynamicOptions: true,
        hasDynamicSearchResults: false,
        comboboxSelectedValues: ['keep', 'gone'],
        _userHasMutatedSelection: false,
        _programmaticSelectionWrite: false,
    })

    host._engine = createComboboxEngine({
        options: [
            { value: 'keep', label: 'Keep' },
            { value: 'gone', label: 'Gone' },
        ],
        multiple: true,
        initialSelectedValues: ['keep', 'gone'],
        onChange: (values) => {
            if (! host._programmaticSelectionWrite) {
                host._userHasMutatedSelection = true
            }

            host.comboboxSelectedValues = values
        },
    })

    host.applyRemoteOptions([{ value: 'keep', label: 'Keep' }])

    assert.deepEqual(host.comboboxSelectedValues, ['keep'])
    assert.equal(host._userHasMutatedSelection, false)
})
