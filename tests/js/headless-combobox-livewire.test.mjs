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
