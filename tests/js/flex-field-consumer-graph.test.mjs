import assert from 'node:assert/strict'
import test from 'node:test'

import { createConsumerGraph } from '../../resources/js/core/flex-field-consumer-graph.js'

const registry = {
    'select-field': {
        stylesheets: ['/css/flex-fields-select-field.css', '/css/flex-fields-teleported-menu.css'],
        chunks: ['/js/flex-fields-select-menu.js'],
        entry: '/js/select-field.js',
        kind: 'full',
    },
    'user-select': {
        stylesheets: ['/css/flex-fields-user-select.css'],
        chunks: [],
        entry: '/js/user-select.js',
        kind: 'full',
    },
}

test('consumer graph acquires and releases bundle urls', () => {
    const graph = createConsumerGraph({
        registry,
        normalizeUrl: (url) => url,
        resolveSurfaceKey: () => 'page',
    })

    graph.acquire('field::page', 'select-field')

    assert.equal(graph.getRefCount('/css/flex-fields-select-field.css'), 1)
    assert.equal(graph.getRefCount('/js/flex-fields-select-menu.js'), 1)
    assert.equal(graph.getRefCount('/js/select-field.js'), 1)

    graph.release('field::page')

    assert.equal(graph.getRefCount('/css/flex-fields-select-field.css'), 0)
})

test('consumer graph resyncFromDom registers connected consumers', () => {
    const graph = createConsumerGraph({
        registry,
        normalizeUrl: (url) => url,
        resolveSurfaceKey: () => 'page',
    })

    const element = {
        isConnected: true,
        dataset: {
            fffAssetConsumer: 'select-field',
            fffAssetConsumerId: 'lw-1',
        },
    }

    const root = {
        querySelectorAll(selector) {
            return selector === '[data-fff-asset-consumer]' ? [element] : []
        },
    }

    graph.resyncFromDom(root)

    assert.equal(graph.getRefCount('/css/flex-fields-select-field.css'), 1)
    assert.deepEqual(graph.getActiveConsumerIds(), ['lw-1::page'])
})

test('consumer graph onRefZero fires after debounce when refs reach zero', async () => {
    const graph = createConsumerGraph({
        registry: {
            switch: {
                stylesheets: ['/css/flex-fields-switch.css'],
                chunks: [],
                entry: null,
                kind: 'full',
            },
        },
        normalizeUrl: (url) => url,
        resolveSurfaceKey: () => 'page',
    })

    let fired = false
    graph.onRefZero('/css/flex-fields-switch.css', () => {
        fired = true
    })

    graph.acquire('batch::page::switch', 'switch')
    graph.release('batch::page::switch')

    assert.equal(fired, false)

    await new Promise((resolve) => {
        setTimeout(resolve, 200)
    })

    assert.equal(fired, true)
})
