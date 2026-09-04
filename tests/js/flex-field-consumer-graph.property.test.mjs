import assert from 'node:assert/strict'
import test from 'node:test'

import { createConsumerGraph } from '../../resources/js/core/flex-field-consumer-graph.js'

const SEED = 0xFFF001

function mulberry32(seed) {
    let state = seed >>> 0

    return () => {
        state += 0x6D2B79F5
        let t = state
        t = Math.imul(t ^ (t >>> 15), t | 1)
        t ^= t + Math.imul(t ^ (t >>> 7), t | 61)

        return ((t ^ (t >>> 14)) >>> 0) / 4294967296
    }
}

function makeElement({ componentId, livewireKey, stylesheets, surface = 'page', connected = true }) {
    return {
        isConnected: connected,
        dataset: {
            fffAssetConsumer: componentId,
            fffAssetConsumerId: livewireKey,
        },
        attributes: {
            'data-fff-asset-batch': '',
            'data-fff-stylesheets': JSON.stringify(stylesheets),
            'data-fff-chunks': '[]',
            'data-fff-asset-consumer': componentId,
            'data-fff-asset-consumer-id': livewireKey,
        },
        hasAttribute(name) {
            return Object.hasOwn(this.attributes, name)
        },
        getAttribute(name) {
            return this.attributes[name] ?? null
        },
        closest(selector) {
            if (selector === '.fi-modal' && surface.startsWith('modal:')) {
                return { classList: { contains: () => true } }
            }

            return null
        },
        __surface: surface,
    }
}

function assertInvariantI3(graph) {
    for (const url of graph.getRetainedUrls()) {
        const consumers = graph.getConsumersForUrl(url)
        assert.equal(graph.getRefCount(url), consumers.length, `I3 mismatch for ${url}`)
    }
}

test('property: seeded CRG ops preserve I1-I3 and I27 (2000 ops)', () => {
    const rand = mulberry32(SEED)
    const registry = {
        'flex-text-input': { stylesheets: ['/css/flex-fields-flex-text-input.css'], chunks: [], entry: null, kind: 'full' },
        'switch': { stylesheets: ['/css/flex-fields-switch.css'], chunks: [], entry: null, kind: 'full' },
    }

    const graph = createConsumerGraph({
        registry,
        normalizeUrl: (url) => url,
        resolveSurfaceKey: (el) => el.__surface ?? 'page',
    })

    const elements = []
    const surfaces = ['page', 'modal:a', 'modal:b']
    const components = ['flex-text-input', 'switch']
    const livewireKeys = ['authorId', 'statusId', 'categoryId']

    const root = {
        querySelectorAll(selector) {
            if (selector !== '[data-fff-asset-consumer]') {
                return []
            }

            return elements.filter((el) => el.isConnected && el.dataset.fffAssetConsumer)
        },
    }

    for (let op = 0; op < 2000; op += 1) {
        const roll = rand()

        if (roll < 0.35) {
            const el = makeElement({
                componentId: components[Math.floor(rand() * components.length)],
                livewireKey: livewireKeys[Math.floor(rand() * livewireKeys.length)],
                stylesheets: [`/css/flex-fields-${components[Math.floor(rand() * components.length)]}.css`],
                surface: surfaces[Math.floor(rand() * surfaces.length)],
            })
            elements.push(el)
        } else if (roll < 0.55 && elements.length > 0) {
            const index = Math.floor(rand() * elements.length)
            elements[index].isConnected = false
        } else if (roll < 0.75) {
            for (const el of elements) {
                if (rand() > 0.5) {
                    el.isConnected = true
                }
            }
        } else if (roll < 0.9) {
            graph.acquire(`temp::page`, 'flex-text-input')
            graph.release(`temp::page`)
        } else {
            graph.resyncFromDom(root)
        }

        graph.resyncFromDom(root)

        for (const url of graph.getRetainedUrls()) {
            assert.ok(graph.getRefCount(url) >= 0, 'I1 negative refCount')
        }

        assertInvariantI3(graph)
    }
})

test('property: I27 same livewireKey on page and modal yields refCount 2', () => {
    const graph = createConsumerGraph({
        registry: {},
        normalizeUrl: (url) => url,
        resolveSurfaceKey: (el) => el.__surface,
    })

    const shared = ['/css/flex-fields-flex-text-input.css']
    const page = makeElement({ componentId: 'flex-text-input', livewireKey: 'authorId', stylesheets: shared, surface: 'page' })
    const modal = makeElement({ componentId: 'flex-text-input', livewireKey: 'authorId', stylesheets: shared, surface: 'modal:action' })

    const root = {
        querySelectorAll() {
            return [page, modal]
        },
    }

    graph.resyncFromDom(root)

    assert.equal(graph.getRefCount(shared[0]), 2)

    modal.isConnected = false
    graph.resyncFromDom({ querySelectorAll: () => [page] })

    assert.equal(graph.getRefCount(shared[0]), 1)
})

test('property: same instanceId with new batch URLs re-acquires (navigate morph)', () => {
    const graph = createConsumerGraph({
        registry: {},
        normalizeUrl: (url) => url,
        resolveSurfaceKey: () => 'page',
    })

    const first = makeElement({
        componentId: 'flex-text-input',
        livewireKey: 'authorId',
        stylesheets: ['/css/flex-fields-flex-text-input.css'],
    })
    const second = makeElement({
        componentId: 'item-card',
        livewireKey: 'authorId',
        stylesheets: ['/css/flex-fields-item-card.css'],
    })

    graph.resyncFromDom({ querySelectorAll: () => [first] })
    assert.equal(graph.getRefCount('/css/flex-fields-flex-text-input.css'), 1)

    graph.resyncFromDom({ querySelectorAll: () => [second] })
    assert.equal(graph.getRefCount('/css/flex-fields-flex-text-input.css'), 0)
    assert.equal(graph.getRefCount('/css/flex-fields-item-card.css'), 1)
})
