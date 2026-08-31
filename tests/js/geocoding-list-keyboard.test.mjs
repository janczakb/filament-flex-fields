import assert from 'node:assert/strict'
import test from 'node:test'

import { createGeocodingListKeyboardMixin } from '../../resources/js/core/geocoding-list-keyboard.js'

function createStubComponent(overrides = {}) {
    return {
        searchOpen: true,
        searchResults: [
            { id: 'a', label: 'Alpha' },
            { id: 'b', label: 'Bravo' },
        ],
        highlightedIndex: 0,
        $refs: {},
        selectSearchResult() {},
        ...overrides,
    }
}

test('createGeocodingListKeyboardMixin moves highlight on ArrowDown', () => {
    const mixin = createGeocodingListKeyboardMixin()
    const component = createStubComponent()

    Object.assign(component, mixin)

    mixin.onGeocodingSearchKeydown.call(component, { key: 'ArrowDown', preventDefault() {} })

    assert.equal(component.highlightedIndex, 1)
})

test('createGeocodingListKeyboardMixin selects highlighted item on Enter', () => {
    const mixin = createGeocodingListKeyboardMixin()
    let selected = null

    const component = createStubComponent({
        selectSearchResult(item) {
            selected = item
        },
    })

    Object.assign(component, mixin)

    mixin.onGeocodingSearchKeydown.call(component, { key: 'Enter', preventDefault() {} })

    assert.deepEqual(selected, { id: 'a', label: 'Alpha' })
})

test('createGeocodingListKeyboardMixin closes list on Escape', () => {
    const mixin = createGeocodingListKeyboardMixin()
    const component = createStubComponent({
        closeSearchDropdown() {
            this.searchOpen = false
        },
    })

    Object.assign(component, mixin)

    mixin.onGeocodingSearchKeydown.call(component, { key: 'Escape', preventDefault() {} })

    assert.equal(component.searchOpen, false)
})

test('initGeocodingListKeyboard keeps focus in the search combobox when the list opens', () => {
    const mixin = createGeocodingListKeyboardMixin()
    /** @type {((open: boolean) => void) | null} */
    let openWatcher = null

    const menuOption = {
        focus() {
            throw new Error('Geocoding combobox must not move focus into the teleported list.')
        },
    }

    const component = createStubComponent({
        searchOpen: false,
        $watch(key, callback) {
            if (key === 'searchOpen') {
                openWatcher = callback
            }
        },
        $refs: {
            searchDropdown: {
                querySelectorAll: () => [menuOption],
            },
        },
    })

    Object.assign(component, mixin)
    component.initGeocodingListKeyboard()

    openWatcher?.(true)
})
