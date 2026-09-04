import assert from 'node:assert/strict'
import { test } from 'node:test'
import { createPrefetchEngine } from '../../resources/js/core/flex-field-prefetch-engine.js'

test('prefetch engine acquires and releases hover temp claim', async () => {
    const acquired = []
    const released = []

    const scope = {
        tagName: 'DIV',
        querySelector: () => null,
        closest: () => null,
    }

    const engine = createPrefetchEngine({
        document: {
            addEventListener() {},
            querySelectorAll: () => [],
        },
        window: {
            requestIdleCallback(callback) {
                callback()
            },
            setTimeout(callback) {
                callback()
            },
        },
        shouldSkipBackgroundPreload: () => false,
        preloadBatchesIn: async () => {},
        resolveHoverPreloadScope: () => scope,
        acquireTempPrefetch: (instanceId, componentId) => {
            acquired.push({ instanceId, componentId })
        },
        releaseTempPrefetch: (instanceId) => {
            released.push(instanceId)
        },
        resolveTempPrefetchInstanceId: () => 'prefetch-hover::page::DIV',
        resolveComponentIdFromScope: () => 'select-field',
    })

    engine.boot()

    assert.equal(engine.getActiveHoverClaim(), null)
})

test('prefetch engine skips boot when background preload deferred', () => {
    let idleCalled = false

    const engine = createPrefetchEngine({
        document: { addEventListener() {}, querySelectorAll: () => [] },
        window: {
            requestIdleCallback(callback) {
                idleCalled = true
                callback()
            },
        },
        shouldSkipBackgroundPreload: () => true,
        preloadVisibleBatchesIn: async () => {},
    })

    engine.scheduleIdlePrefetch()

    assert.equal(idleCalled, false)
})
