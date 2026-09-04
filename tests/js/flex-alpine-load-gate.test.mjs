import assert from 'node:assert/strict'
import test from 'node:test'

import { getAlpineLoadGate, resetAlpineLoadGateForTests } from '../../resources/js/core/flex-alpine-load-gate.js'
import { createFlexFieldAssetInjector, normalizeAssetUrl } from '../../resources/js/core/flex-field-asset-injector.js'

test('alpine load gate registerAlpineData is idempotent', () => {
    resetAlpineLoadGateForTests()

    const gate = getAlpineLoadGate()
    let registrations = 0

    globalThis.Alpine = {
        data(name, factory) {
            assert.equal(name, 'fffDemo')
            registrations += 1

            return factory
        },
    }

    gate.registerAlpineData('fffDemo', () => ({ ok: true }))
    gate.registerAlpineData('fffDemo', () => ({ ok: true }))

    assert.equal(registrations, 1)

    delete globalThis.Alpine
})

test('alpine load gate delegates awaitBundleReady to asset injector', async () => {
    resetAlpineLoadGateForTests()

    const document = {
        baseURI: 'https://panel.test/admin',
        head: { appendChild() {} },
        querySelectorAll: () => [],
    }

    const window = {
        requestAnimationFrame(fn) {
            fn()
        },
        cancelAnimationFrame() {},
    }

    const injector = createFlexFieldAssetInjector({ document, window })
    let readyComponent = null

    injector.awaitBundleReady = async (componentId) => {
        readyComponent = componentId
    }

    globalThis.window = window
    globalThis.FffAssetInjector = injector

    await getAlpineLoadGate().awaitBundleReady('select-field')

    assert.equal(readyComponent, 'select-field')

    delete globalThis.window
    delete globalThis.FffAssetInjector
})

test('alpine load gate falls back to managed asset links when injector is absent', async () => {
    resetAlpineLoadGateForTests()

    const listeners = new Map()
    const link = {
        rel: 'stylesheet',
        href: normalizeAssetUrl('/css/flex-fields-select-field.css', 'https://panel.test/admin'),
        sheet: null,
        addEventListener(type, listener) {
            listeners.set(type, listener)
        },
        removeEventListener(type) {
            listeners.delete(type)
        },
    }

    globalThis.document = {
        baseURI: 'https://panel.test/admin',
        querySelectorAll(selector) {
            if (selector.includes('data-fff-managed-asset')) {
                return [link]
            }

            return []
        },
    }

    globalThis.window = {}

    const pending = getAlpineLoadGate().awaitBundleReady('select-field')
    link.sheet = {}
    listeners.get('load')?.()
    await pending

    delete globalThis.document
    delete globalThis.window
})

test('alpine load gate waits for late asset injector before empty fallback', async () => {
    resetAlpineLoadGateForTests()

    globalThis.document = {
        baseURI: 'https://panel.test/admin',
        querySelectorAll: () => [],
    }

    globalThis.FffAssetInjector = undefined

    const pending = getAlpineLoadGate().awaitBundleReady('select-field')
    let readyComponent = null

    setTimeout(() => {
        const document = {
            baseURI: 'https://panel.test/admin',
            head: { appendChild() {} },
            querySelectorAll: () => [],
        }

        const window = {
            requestAnimationFrame(fn) {
                fn()
            },
            cancelAnimationFrame() {},
        }

        const injector = createFlexFieldAssetInjector({ document, window })
        injector.awaitBundleReady = async (componentId) => {
            readyComponent = componentId
        }

        globalThis.FffAssetInjector = injector
    }, 30)

    await pending

    assert.equal(readyComponent, 'select-field')

    delete globalThis.document
    delete globalThis.FffAssetInjector
})
