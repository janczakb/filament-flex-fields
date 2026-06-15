import assert from 'node:assert/strict'
import test from 'node:test'

import { resolveMapboxSearchTypes } from '../../resources/js/support/mapbox-geocoding.js'

test('resolveMapboxSearchTypes returns poi filter when configured', () => {
    assert.equal(resolveMapboxSearchTypes({ types: ['poi'] }), 'poi')
})

test('resolveMapboxSearchTypes returns null for all types by default', () => {
    assert.equal(resolveMapboxSearchTypes(), null)
    assert.equal(resolveMapboxSearchTypes({ types: null }), null)
    assert.equal(resolveMapboxSearchTypes({ types: [] }), null)
})

test('streetAddressesOnly overrides explicit search types', () => {
    assert.equal(resolveMapboxSearchTypes({ types: ['poi'], streetAddressesOnly: true }), 'address')
})
