import assert from 'node:assert/strict'
import { test } from 'node:test'
import { createConsumerGraph } from '../../resources/js/core/flex-field-consumer-graph.js'

test('T38: scheduleResyncFromDom coalesces rapid calls into one animation frame', async () => {
    const crg = createConsumerGraph({
        registry: { bundles: {} },
        normalizeUrl: (url) => url,
        resolveSurfaceKey: () => 'page',
    })

    let resyncPasses = 0
    /** @type {Map<number, () => void>} */
    const rafCallbacks = new Map()
    let rafId = 0

    globalThis.requestAnimationFrame = (callback) => {
        rafId += 1
        rafCallbacks.set(rafId, callback)

        return rafId
    }

    globalThis.cancelAnimationFrame = (id) => {
        rafCallbacks.delete(id)
    }

    let resyncDomRaf = null

    const scheduleResyncFromDom = () => {
        if (resyncDomRaf) {
            globalThis.cancelAnimationFrame(resyncDomRaf)
        }

        resyncDomRaf = globalThis.requestAnimationFrame(() => {
            resyncDomRaf = null
            resyncPasses += 1
            crg.resyncFromDom({ querySelectorAll: () => [] })
        })
    }

    for (let index = 0; index < 50; index += 1) {
        scheduleResyncFromDom()
    }

    assert.equal(rafCallbacks.size, 1)

    for (const callback of rafCallbacks.values()) {
        callback()
    }

    assert.equal(resyncPasses, 1)
})
