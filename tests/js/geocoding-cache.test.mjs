import assert from 'node:assert/strict'
import test from 'node:test'

import {
    clearGeocodingCache,
    geocodingCacheKey,
    readGeocodingCache,
    writeGeocodingCache,
} from '../../resources/js/core/geocoding-cache.js'

test('geocodingCacheKey serializes payload', () => {
    assert.equal(geocodingCacheKey('search', { q: 'warsaw' }), 'search:{"q":"warsaw"}')
})

test('writeGeocodingCache stores and reads values', () => {
    clearGeocodingCache()

    const key = geocodingCacheKey('reverse', { lat: 52.1, lng: 21.0 })

    writeGeocodingCache(key, [{ id: 'place-1' }])

    assert.deepEqual(readGeocodingCache(key), [{ id: 'place-1' }])
})

test('writeGeocodingCache evicts oldest entry when max entries exceeded', () => {
    clearGeocodingCache()

    writeGeocodingCache('first', 'a', 2)
    writeGeocodingCache('second', 'b', 2)
    writeGeocodingCache('third', 'c', 2)

    assert.equal(readGeocodingCache('first'), null)
    assert.equal(readGeocodingCache('second'), 'b')
    assert.equal(readGeocodingCache('third'), 'c')
})
