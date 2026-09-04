import assert from 'node:assert/strict'
import test from 'node:test'

import { getAlpineLoadGate, resetAlpineLoadGateForTests } from '../../resources/js/core/flex-alpine-load-gate.js'
import {
    ensureFffLoadDirectiveRegistered,
    registerFffLoadDirective,
    resetFffLoadDirectiveForTests,
} from '../../resources/js/core/flex-fff-load-directive.js'

const gateCalls = {
    awaited: [],
    imported: [],
}

function stubAlpineLoadGate() {
    resetAlpineLoadGateForTests()
    gateCalls.awaited.length = 0
    gateCalls.imported.length = 0

    const gate = getAlpineLoadGate()
    gate.awaitBundleReady = async (componentId) => {
        gateCalls.awaited.push(componentId)
    }
    gate.importModule = async (url) => {
        gateCalls.imported.push(url)
    }

    return gate
}

function createAlpineStub() {
    return {
        prefixed(name) {
            return `x-${name}`
        },
        skipDuringClone(callback) {
            return callback()
        },
        destroyTreeCalls: [],
        destroyTree(el) {
            this.destroyTreeCalls.push(el)
        },
        initTreeCalls: [],
        initTree(el) {
            this.initTreeCalls.push(el)
        },
        directive(name, callback) {
            assert.equal(name, 'fff-load')
            this.directiveCallback = callback

            return {
                before(other) {
                    assert.equal(other, 'ignore')
                },
            }
        },
    }
}

function createElement() {
    const attributes = {}

    return {
        _x_async: undefined,
        _x_ignore: undefined,
        isConnected: true,
        dataset: { fffAssetConsumer: 'select-field' },
        getAttribute(name) {
            return attributes[name] ?? null
        },
        setAttribute(name, value) {
            attributes[name] = value
        },
        removeAttribute(name) {
            delete attributes[name]
        },
        closest: () => null,
        dispatchEvent: () => {},
    }
}

test('x-fff-load inline handler sets x-ignore before Alpine evaluates x-data', () => {
    resetFffLoadDirectiveForTests()
    const Alpine = createAlpineStub()

    registerFffLoadDirective(Alpine)

    const el = createElement()
    Alpine.directiveCallback.inline(el)

    assert.equal(el._x_async, 'init')
    assert.equal(el._x_ignore, true)
    assert.equal(el.getAttribute('x-ignore'), '')
})

test('x-fff-load loads module then initTree', async () => {
    resetFffLoadDirectiveForTests()
    gateCalls.awaited.length = 0
    gateCalls.imported.length = 0

    stubAlpineLoadGate()

    const Alpine = createAlpineStub()
    registerFffLoadDirective(Alpine)

    const el = createElement()
    el.getAttribute = (name) => {
        if (name === 'x-fff-load-src') {
            return 'https://cdn.test/select-field.js'
        }

        return null
    }

    Alpine.directiveCallback.inline(el)

    await Alpine.directiveCallback(el, { modifiers: [] }, {
        cleanup() {},
    })

    assert.deepEqual(gateCalls.awaited, ['select-field'])
    assert.deepEqual(gateCalls.imported, ['https://cdn.test/select-field.js'])
    assert.equal(el._x_async, 'loaded')
    assert.equal(Alpine.destroyTreeCalls.length, 1)
    assert.equal(Alpine.initTreeCalls.length, 1)
    assert.equal(Alpine.initTreeCalls[0], el)
})

test('ensureFffLoadDirectiveRegistered registers directive only once', () => {
    resetFffLoadDirectiveForTests()

    const Alpine = createAlpineStub()
    let directiveCount = 0

    Alpine.directive = (name, callback) => {
        directiveCount += 1
        Alpine.directiveCallback = callback

        return { before: () => {} }
    }

    assert.equal(ensureFffLoadDirectiveRegistered(Alpine), true)
    assert.equal(ensureFffLoadDirectiveRegistered(Alpine), true)
    assert.equal(directiveCount, 1)
})
