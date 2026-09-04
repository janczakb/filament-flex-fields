import assert from 'node:assert/strict'
import test from 'node:test'

import { createFlexFieldAssetInjector } from '../../resources/js/core/flex-field-asset-injector.js'
import {
    closeModal,
    createAssetBatch,
    createDom,
    createElement,
    css,
    flushAssetLoads,
    headCount,
    headHas,
    openModal,
    registerModals,
} from './helpers/flex-field-asset-injector-dom.mjs'

test('chaos: modal close uninstalls modal-only CSS even when opened briefly', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const modalOnly = css('switch')

    const modal = createElement('div')
    modal.id = 'chaos-inflight'
    modal.classList.add('fi-modal')
    modal.appendChild(createAssetBatch([modalOnly]))
    body.appendChild(modal)
    registerModals(document, { 'chaos-inflight': modal })

    await openModal(injector, head, modal, 'chaos-inflight')
    assert.equal(headHas(head, 'switch'), true)

    await closeModal(injector, modal, 'chaos-inflight')

    assert.equal(headHas(head, 'switch'), false)
    assert.equal(injector.getInflightUrls().length, 0)
})

test('chaos: double modal-closed LIFO does not poison stack', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const parent = createElement('div')
    parent.id = 'chaos-parent'
    parent.classList.add('fi-modal')
    parent.appendChild(createAssetBatch([css('switch')]))
    body.appendChild(parent)

    const child = createElement('div')
    child.id = 'chaos-child'
    child.classList.add('fi-modal')
    child.appendChild(createAssetBatch([css('rating-field')]))
    body.appendChild(child)

    registerModals(document, { 'chaos-parent': parent, 'chaos-child': child })

    await openModal(injector, head, parent, 'chaos-parent')
    parent.classList.remove('fi-modal-open')
    await openModal(injector, head, child, 'chaos-child')

    await injector.cleanupClosedModalPendingState({})
    await injector.cleanupClosedModalPendingState({})

    assert.deepEqual(injector.getModalOpenStack(), [])
})

test('chaos: navigate during page load swaps retained URLs without duplicates', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const page = createElement('form')
    page.appendChild(createAssetBatch([css('flex-text-input')]))
    body.appendChild(page)

    const boot = injector.ensureAssets(document, { pageOnly: true })
    await flushAssetLoads(head)
    await boot

    page.remove()
    body.appendChild(createAssetBatch([css('item-card')]))

    const navigated = injector.handleLivewireNavigated()
    await flushAssetLoads(head)
    await navigated

    assert.equal(headHas(head, 'flex-text-input'), false)
    assert.equal(headHas(head, 'item-card'), true)
    assert.equal(headCount(head, 'item-card'), 1)
})

test('chaos: rapid open/close x20 keeps modal stack empty and no duplicate switch links', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const sw = css('switch')

    const modal = createElement('div')
    modal.id = 'chaos-rapid'
    modal.classList.add('fi-modal')
    body.appendChild(modal)
    registerModals(document, { 'chaos-rapid': modal })

    for (let i = 0; i < 20; i += 1) {
        modal.children.length = 0
        modal.appendChild(createAssetBatch([sw]))
        await openModal(injector, head, modal, 'chaos-rapid')
        await closeModal(injector, modal, 'chaos-rapid')
    }

    assert.deepEqual(injector.getModalOpenStack(), [])
    assert.equal(headCount(head, 'switch'), 0)
})
