import test from 'node:test'
import assert from 'node:assert/strict'
import { trimSearchResultsCache } from '../../resources/js/core/icon-picker-cache.js'
import { createIconPickerSvgLoader } from '../../resources/js/core/icon-picker-svg-loader.js'

test('trimSearchResultsCache keeps the newest entries', () => {
    const cache = new Map()

    for (let index = 1; index <= 40; index += 1) {
        cache.set(`key-${index}`, { page: index })
    }

    trimSearchResultsCache(cache, 32)

    assert.equal(cache.size, 32)
    assert.equal(cache.has('key-1'), false)
    assert.equal(cache.has('key-8'), false)
    assert.equal(cache.has('key-9'), true)
    assert.equal(cache.has('key-40'), true)
})

test('createIconPickerSvgLoader batches svg requests and skips cached icons', async () => {
    const fetchSvgs = async (icons) => icons.map((name) => ({ name, html: `<svg>${name}</svg>` }))
    let cache = { 'heroicon-o-star': '<svg>cached</svg>' }
    let fetched = []

    const loader = createIconPickerSvgLoader({
        getSvgCache: () => cache,
        patchSvgCache: (updates) => {
            Object.assign(cache, updates)
        },
        fetchSvgs: async (icons) => {
            fetched = icons

            return fetchSvgs(icons)
        },
        batchDelayMs: 0,
    })

    loader.queueIcons(['heroicon-o-star', 'heroicon-o-heart'])
    await new Promise((resolve) => setTimeout(resolve, 0))

    assert.deepEqual(fetched, ['heroicon-o-heart'])
    assert.equal(cache['heroicon-o-heart'], '<svg>heroicon-o-heart</svg>')

    loader.disconnect()
})

test('createIconPickerSvgLoader splits oversized queues into multiple batches', async () => {
    const batches = []
    let cache = {}

    const loader = createIconPickerSvgLoader({
        getSvgCache: () => cache,
        patchSvgCache: (updates) => {
            Object.assign(cache, updates)
        },
        fetchSvgs: async (icons) => {
            batches.push(icons.length)

            return icons.map((name) => ({ name, html: `<svg>${name}</svg>` }))
        },
        batchDelayMs: 0,
        batchSize: 2,
    })

    loader.queueIcons(['a', 'b', 'c', 'd', 'e'])
    await new Promise((resolve) => setTimeout(resolve, 0))
    await new Promise((resolve) => setTimeout(resolve, 0))
    await new Promise((resolve) => setTimeout(resolve, 0))

    assert.deepEqual(batches, [2, 2, 1])
    assert.equal(cache.e, '<svg>e</svg>')

    loader.disconnect()
})
