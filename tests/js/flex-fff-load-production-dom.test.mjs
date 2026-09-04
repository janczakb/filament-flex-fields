import assert from 'node:assert/strict'
import test from 'node:test'

import { getAlpineLoadGate, resetAlpineLoadGateForTests } from '../../resources/js/core/flex-alpine-load-gate.js'
import { createFlexFieldAssetInjector } from '../../resources/js/core/flex-field-asset-injector.js'
import {
    registerFffLoadDirective,
    resetFffLoadDirectiveForTests,
    resolveFffLoadComponentId,
} from '../../resources/js/core/flex-fff-load-directive.js'
import {
    createAssetBatch,
    createDom,
    flushStylesheetLoads,
    stylesheetHrefs,
} from './helpers/flex-field-asset-injector-dom.mjs'

function createProductionSelectDom() {
    const { document, window, head, body } = createDom()

    const wrapper = document.createElement('div')
    wrapper.classList.add('fi-fo-select-wrp', 'fff-select-field-wrapper')

    const batch = createAssetBatch(
        [
            '/css/janczakb/filament-flex-fields/flex-fields-overlay-runtime.css',
            '/css/janczakb/filament-flex-fields/flex-fields-teleported-menu.css',
            '/css/janczakb/filament-flex-fields/flex-fields-select-field.css',
        ],
        ['/js/janczakb/filament-flex-fields/components/select-field.js'],
        { consumerComponent: 'select-field', livewireKey: 'playground.select.color' },
    )

    const lazyGate = document.createElement('div')
    lazyGate.classList.add('fff-lazy-alpine-gate')

    const interactive = document.createElement('div')
    interactive.setAttribute('x-ignore', '')
    interactive.setAttribute('x-fff-load', '')
    interactive.setAttribute(
        'x-fff-load-src',
        '/js/janczakb/filament-flex-fields/components/select-field.js',
    )

    lazyGate.appendChild(interactive)
    wrapper.appendChild(batch)
    wrapper.appendChild(lazyGate)
    body.appendChild(wrapper)

    return { document, window, head, body, wrapper, batch, interactive }
}

function createAlpineStub() {
    return {
        prefixed(name) {
            return `x-${name}`
        },
        skipDuringClone(callback) {
            return callback()
        },
        destroyTree() {},
        initTreeCalls: [],
        initTree(el) {
            this.initTreeCalls.push(el)
        },
        directive(name, callback) {
            this.directiveCallback = callback

            return { before: () => {} }
        },
    }
}

test('resolveFffLoadComponentId finds sibling asset batch under field wrapper', () => {
    const { interactive } = createProductionSelectDom()

    assert.equal(resolveFffLoadComponentId(interactive), 'select-field')
})

test('resolveFffLoadComponentId falls back to entry script name', () => {
    const el = {
        dataset: {},
        closest: () => null,
        getAttribute(name) {
            if (name === 'x-fff-load-src') {
                return '/js/janczakb/filament-flex-fields/components/select-field.js?v=1'
            }

            return null
        },
    }

    assert.equal(resolveFffLoadComponentId(el), 'select-field')
})

test('x-fff-load awaits bundle for production sibling-batch DOM before import', async () => {
    resetFffLoadDirectiveForTests()
    resetAlpineLoadGateForTests()

    const awaited = []
    const imported = []
    const gate = getAlpineLoadGate()
    gate.awaitBundleReady = async (componentId) => {
        awaited.push(componentId)
    }
    gate.importModule = async (url) => {
        imported.push(url)
    }

    const { interactive } = createProductionSelectDom()
    const Alpine = createAlpineStub()
    registerFffLoadDirective(Alpine)

    Alpine.directiveCallback.inline(interactive)
    await Alpine.directiveCallback(interactive, { modifiers: [] }, { cleanup() {} })

    assert.deepEqual(awaited, ['select-field'])
    assert.equal(imported.length, 1)
    assert.match(imported[0], /select-field\.js/)
})

test('boot order: awaitBundleReady blocks until asset injector registers', async () => {
    resetAlpineLoadGateForTests()

    const readyComponents = []
    const pending = getAlpineLoadGate().awaitBundleReady('select-field')

    await new Promise((resolve) => setTimeout(resolve, 8))

    const { document, window } = createProductionSelectDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    injector.awaitBundleReady = async (componentId) => {
        readyComponents.push(componentId)
    }

    globalThis.FffAssetInjector = injector

    await pending

    assert.deepEqual(readyComponents, ['select-field'])

    delete globalThis.FffAssetInjector
})

test('consumer batch acquire loads CSS before select Alpine init in full boot simulation', async () => {
    const { document, window, head, body } = createProductionSelectDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    globalThis.FffAssetInjector = injector
    await injector.boot()
    await flushStylesheetLoads(head)

    assert.equal(stylesheetHrefs(head).length >= 3, true)
    assert.equal(
        stylesheetHrefs(head).some((href) => href.includes('select-field')),
        true,
    )
    assert.equal(injector.getConsumerGraph().getRefCount(
        '/css/janczakb/filament-flex-fields/flex-fields-select-field.css',
    ) > 0, true)

    delete globalThis.FffAssetInjector
})
