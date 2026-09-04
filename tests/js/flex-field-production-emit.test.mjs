import assert from 'node:assert/strict'
import test from 'node:test'

import { createFlexFieldAssetInjector, normalizeAssetUrl } from '../../resources/js/core/flex-field-asset-injector.js'
import {
    closeModal,
    createDom,
    createElement,
    css,
    flushAssetLoads,
    headCount,
    headHas,
    openModal,
    registerModals,
    stylesheetHrefs,
} from './helpers/flex-field-asset-injector-dom.mjs'
import {
    createProductionEmitBatch,
    mountProductionModalSelect,
    mountProductionPageSelect,
} from './helpers/flex-field-production-emit-dom.mjs'

test('production emit: batch-only page select loads managed stylesheet once', async () => {
    const { document, window, head } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    createProductionEmitBatch({ document })

    const pending = injector.ensureAssets(document, { pageOnly: true })
    await flushAssetLoads(head)
    await pending

    assert.equal(stylesheetHrefs(head).filter((href) => href.includes('select-field')).length, 1)
})

test('T36 / REQ-3 / I27: same livewireKey on page + modal → refCount 2, modal close → refCount 1', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const href = css('select-field')

    mountProductionPageSelect({ document, livewireKey: 'authorId' })

    const pending = injector.ensureAssets(document, { pageOnly: true })
    await flushAssetLoads(head)
    await pending

    const modal = createElement('div')
    modal.id = 'prod-modal'
    modal.classList.add('fi-modal')
    body.appendChild(modal)
    registerModals(document, { 'prod-modal': modal })

    mountProductionModalSelect({ document, modal, livewireKey: 'authorId' })
    await openModal(injector, head, modal, 'prod-modal')

    const url = normalizeAssetUrl(href, document.baseURI)
    assert.equal(injector.getConsumerGraph().getRefCount(url), 2)

    await closeModal(injector, modal, 'prod-modal')
    assert.equal(injector.getConsumerGraph().getRefCount(url), 1)
    assert.equal(headHas(head, 'select-field'), true)
})

test('T37 / I36: duplicate batch nodes same instanceId count URL once in refCount', async () => {
    const { document, window, head } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const href = css('select-field')

    createProductionEmitBatch({ document, livewireKey: 'authorId' })
    createProductionEmitBatch({ document, livewireKey: 'authorId' })

    const pending = injector.ensureAssets(document, { pageOnly: true })
    await flushAssetLoads(head)
    await pending

    const url = normalizeAssetUrl(href, document.baseURI)
    assert.equal(injector.getConsumerGraph().getRefCount(url), 1)
    assert.equal(stylesheetHrefs(head).filter((href) => href.includes('select-field')).length, 1)
})

test('REQ-4 / I6: modal-only production batch uninstalls after close', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const modal = createElement('div')
    modal.id = 'prod-only'
    modal.classList.add('fi-modal')
    body.appendChild(modal)
    registerModals(document, { 'prod-only': modal })

    createProductionEmitBatch({
        document,
        stylesheets: [css('switch')],
        chunks: [],
        consumerComponent: 'switch',
        livewireKey: 'modalOnly',
        parent: modal,
    })

    await openModal(injector, head, modal, 'prod-only')
    assert.equal(headHas(head, 'switch'), true)

    await closeModal(injector, modal, 'prod-only')
    assert.equal(headHas(head, 'switch'), false)
})
