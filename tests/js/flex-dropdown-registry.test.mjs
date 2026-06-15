import assert from 'node:assert/strict'
import test from 'node:test'

import { createFlexDropdownRegistry } from '../../resources/js/core/flex-dropdown-registry.js'

test('openExclusive closes every other open dropdown controller', () => {
    const registry = createFlexDropdownRegistry()
    let countryOpen = true
    let phoneOpen = true
    let currencyOpen = false

    registry.register('country', () => ({
        isOpen: () => countryOpen,
        close: () => {
            countryOpen = false
        },
    }))

    registry.register('phone', () => ({
        isOpen: () => phoneOpen,
        close: () => {
            phoneOpen = false
        },
    }))

    registry.register('currency', () => ({
        isOpen: () => currencyOpen,
        close: () => {
            currencyOpen = false
        },
    }))

    registry.openExclusive('phone')

    assert.equal(countryOpen, false)
    assert.equal(phoneOpen, true)
    assert.equal(currencyOpen, false)
})

test('unregister removes controller from exclusive closing', () => {
    const registry = createFlexDropdownRegistry()
    let countryOpen = true

    const unregister = registry.register('country', () => ({
        isOpen: () => countryOpen,
        close: () => {
            countryOpen = false
        },
    }))

    unregister()

    registry.register('phone', () => ({
        isOpen: () => false,
        close: () => {},
    }))

    registry.openExclusive('phone')

    assert.equal(countryOpen, true)
    assert.equal(registry.size(), 1)
})

test('controller factories are resolved when closing', () => {
    const registry = createFlexDropdownRegistry()
    const seen = []

    registry.register('country', () => {
        seen.push('resolve')

        return {
            isOpen: () => true,
            close: () => {},
        }
    })

    registry.openExclusive('other')

    assert.deepEqual(seen, ['resolve'])
})
