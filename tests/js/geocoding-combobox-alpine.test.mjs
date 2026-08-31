import assert from 'node:assert/strict'
import test from 'node:test'

import { createGeocodingComboboxMixin } from '../../resources/js/core/geocoding-combobox-alpine.js'

globalThis.window = {
    clearTimeout() {},
    setTimeout(callback) {
        callback()

        return 1
    },
}

function createComponent(overrides = {}) {
    const mixin = createGeocodingComboboxMixin({ minSearchLength: 2, searchDebounce: 0 })

    return {
        ...mixin,
        searchable: true,
        accessToken: 'token',
        geocodeSearchUrl: null,
        countries: ['PL'],
        language: 'en',
        streetAddressesOnly: false,
        searchTypes: null,
        labels: {},
        searchQuery: 'warsaw',
        selectedLabel: '',
        searchOpen: false,
        searchLoading: false,
        searchRefreshing: false,
        searchHasMinQuery: true,
        searchFocused: false,
        highlightedIndex: -1,
        selectedResultId: null,
        geocodingRecentResults: [],
        lastFetchedQuery: '',
        searchDebounceTimer: null,
        searchBlurTimer: null,
        searchRequestId: 0,
        geocodeError: null,
        $refs: {},
        $nextTick(callback) {
            callback()
        },
        canGeocode() {
            return true
        },
        syncGeocodingHighlightedIndex() {
            this.highlightedIndex = 0
        },
        ...overrides,
    }
}

test('dismissGeocodingDropdown keeps search results for instant reopen', () => {
    const component = createComponent({
        searchResults: [{ id: '1', label: 'Warsaw, Poland' }],
        searchOpen: true,
    })

    component.initGeocodingComboboxState()
    component.dismissGeocodingDropdown()

    assert.equal(component.searchOpen, false)
    assert.equal(component.searchResults.length, 1)
})

test('restoreGeocodingResultsForCurrentQuery reads query cache', () => {
    const component = createComponent()

    component.initGeocodingComboboxState()
    component.writeGeocodingQueryCache('warsaw', [
        { id: '1', label: 'Warsaw, Poland' },
        { id: '2', label: 'Warsaw Avenue' },
    ])

    component.searchQuery = 'Warsaw'
    const restored = component.restoreGeocodingResultsForCurrentQuery()

    assert.equal(restored, true)
    assert.equal(component.searchResults.length, 2)
})

test('refreshGeocodingDropdownResults restores cached results without clearing', () => {
    const component = createComponent()
    let scheduled = false

    component.initGeocodingComboboxState()
    component.writeGeocodingQueryCache('warsaw', [{ id: '1', label: 'Warsaw, Poland' }])
    component.scheduleGeocodingSearch = () => {
        scheduled = true
    }

    component.refreshGeocodingDropdownResults()

    assert.equal(component.searchResults.length, 1)
    assert.equal(scheduled, true)
})

test('finalizeGeocodingSelection stores recent picks and selected id', () => {
    const component = createComponent({
        searchQuery: 'Krakow, Poland',
        searchResults: [
            { id: 'abc', label: 'Krakow, Poland', feature: {} },
            { id: 'def', label: 'Krakow Airport', feature: {} },
        ],
    })
    const result = { id: 'abc', label: 'Krakow, Poland', feature: {} }

    component.initGeocodingComboboxState()
    component.finalizeGeocodingSelection(result)

    assert.equal(component.selectedResultId, 'abc')
    assert.equal(component.geocodingRecentResults.length, 1)
    assert.equal(component.geocodingRecentResults[0].label, 'Krakow, Poland')
    assert.equal(component.readGeocodingQueryCache('Krakow, Poland').length, 2)
})

test('ensureGeocodingResultsVisible falls back to recent selection', () => {
    const component = createComponent({
        searchQuery: 'Krakow, Poland',
        selectedLabel: 'Krakow, Poland',
        selectedResultId: 'abc',
        geocodingRecentResults: [{ id: 'abc', label: 'Krakow, Poland', feature: {} }],
    })

    component.initGeocodingComboboxState()
    const restored = component.ensureGeocodingResultsVisible()

    assert.equal(restored, true)
    assert.equal(component.searchResults.length, 1)
    assert.equal(component.searchResults[0].label, 'Krakow, Poland')
})
