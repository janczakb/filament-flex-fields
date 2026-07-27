import assert from 'node:assert/strict'
import test from 'node:test'

import {
    createFlexFieldAssetInjector,
    normalizeAssetUrl,
} from '../../resources/js/core/flex-field-asset-injector.js'
import {
    chunk,
    chunkHrefs,
    closeModal,
    createAssetBatch,
    createDom,
    createElement,
    createLink,
    css,
    flushAssetLoads,
    headCount,
    headHas,
    openModal,
    registerModals,
    stylesheetHrefs,
} from './helpers/flex-field-asset-injector-dom.mjs'

function retainedHas(injector, document, href) {
    return injector.collectRetainedAssetUrls().has(normalizeAssetUrl(href, document.baseURI))
}

async function ensurePage(injector, head, document) {
    const pending = injector.ensureAssets(document, { pageOnly: true })
    await flushAssetLoads(head)
    await pending
}

// ---------------------------------------------------------------------------
// Ownership matrix
// ---------------------------------------------------------------------------

test('logic: page + modal share flex; modal-only switch uninstalls; page flex survives', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const flex = css('flex-text-input')
    const sw = css('switch')

    const page = createElement('form')
    page.appendChild(createAssetBatch([flex]))
    body.appendChild(page)
    await ensurePage(injector, head, document)

    const modal = createElement('div')
    modal.id = 'm1'
    modal.classList.add('fi-modal')
    modal.appendChild(createAssetBatch([flex, sw]))
    body.appendChild(modal)
    registerModals(document, { m1: modal })

    await openModal(injector, head, modal, 'm1')
    assert.equal(headCount(head, 'flex-text-input'), 1)
    assert.equal(headCount(head, 'switch'), 1)

    await closeModal(injector, modal, 'm1')
    assert.equal(headHas(head, 'flex-text-input'), true)
    assert.equal(headHas(head, 'switch'), false)
    assert.equal(retainedHas(injector, document, flex), true)
    assert.equal(retainedHas(injector, document, sw), false)
})

test('logic: modal-only asset never claimed by pageOnly scan of idle modal shell', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const page = createElement('form')
    page.appendChild(createAssetBatch([css('item-card')]))
    body.appendChild(page)

    const shell = createElement('div')
    shell.classList.add('fi-modal')
    shell.appendChild(createAssetBatch([css('switch')]))
    body.appendChild(shell)

    await ensurePage(injector, head, document)

    assert.equal(headHas(head, 'item-card'), true)
    assert.equal(headHas(head, 'switch'), false)
    assert.equal(retainedHas(injector, document, css('switch')), false)
})

test('logic: three identical page batches collapse to a single stylesheet link', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const flex = css('flex-text-input')

    const page = createElement('form')
    page.appendChild(createAssetBatch([flex]))
    page.appendChild(createAssetBatch([flex]))
    page.appendChild(createAssetBatch([flex]))
    body.appendChild(page)

    await ensurePage(injector, head, document)
    assert.equal(headCount(head, 'flex-text-input'), 1)
})

test('logic: relative and absolute URLs normalize to one retained stylesheet', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const relative = css('phone-field')
    const absolute = `https://panel.test${relative}`

    const page = createElement('form')
    page.appendChild(createAssetBatch([relative]))
    page.appendChild(createAssetBatch([absolute]))
    body.appendChild(page)

    await ensurePage(injector, head, document)
    assert.equal(stylesheetHrefs(head).filter((href) => href.includes('phone-field')).length, 1)
})

test('logic: alpine chunks follow the same share / uninstall rules as CSS', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const pageChunk = chunk('shared-CEWOLNQU')
    const modalChunk = chunk('phone-lib-RPEB4A4Q')

    const page = createElement('form')
    page.appendChild(createAssetBatch([], [pageChunk]))
    body.appendChild(page)
    await ensurePage(injector, head, document)

    const modal = createElement('div')
    modal.id = 'chunk-modal'
    modal.classList.add('fi-modal')
    modal.appendChild(createAssetBatch([], [pageChunk, modalChunk]))
    body.appendChild(modal)
    registerModals(document, { 'chunk-modal': modal })

    await openModal(injector, head, modal, 'chunk-modal')
    assert.equal(headCount(head, 'shared-CEWOLNQU'), 1)
    assert.equal(headCount(head, 'phone-lib'), 1)

    await closeModal(injector, modal, 'chunk-modal')
    assert.equal(headHas(head, 'shared-CEWOLNQU'), true)
    assert.equal(headHas(head, 'phone-lib'), false)
    assert.equal(injector.isChunkLoaded(pageChunk), true)
    assert.equal(injector.isChunkLoaded(modalChunk), false)
})

test('logic: concurrent loadStylesheet calls share one inflight promise and one DOM link', async () => {
    const { document, window, head } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const href = css('tags-field')

    const a = injector.loadStylesheet(href)
    const b = injector.loadStylesheet(href)
    assert.equal(a, b)
    assert.equal(stylesheetHrefs(head).length, 1)
    await flushAssetLoads(head)
    await a
    assert.equal(stylesheetHrefs(head).length, 1)
})

// ---------------------------------------------------------------------------
// Stacked / nested modals
// ---------------------------------------------------------------------------

test('logic: triple nested A→B→C closes LIFO without tearing parent assets early', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const aCss = css('switch')
    const bCss = css('rating-field')
    const cCss = css('nps-field')

    const a = createElement('div')
    a.id = 'stack-a'
    a.classList.add('fi-modal')
    a.appendChild(createAssetBatch([aCss]))
    body.appendChild(a)

    const b = createElement('div')
    b.id = 'stack-b'
    b.classList.add('fi-modal')
    b.appendChild(createAssetBatch([bCss]))
    body.appendChild(b)

    const c = createElement('div')
    c.id = 'stack-c'
    c.classList.add('fi-modal')
    c.appendChild(createAssetBatch([cCss]))
    body.appendChild(c)

    registerModals(document, { 'stack-a': a, 'stack-b': b, 'stack-c': c })

    await openModal(injector, head, a, 'stack-a')
    a.classList.remove('fi-modal-open')
    await openModal(injector, head, b, 'stack-b')
    b.classList.remove('fi-modal-open')
    await openModal(injector, head, c, 'stack-c')

    assert.deepEqual(injector.getModalOpenStack(), [
        'modal:stack-a',
        'modal:stack-b',
        'modal:stack-c',
    ])
    assert.equal(headHas(head, 'switch'), true)
    assert.equal(headHas(head, 'rating-field'), true)
    assert.equal(headHas(head, 'nps-field'), true)

    await closeModal(injector, c, 'stack-c')
    b.classList.add('fi-modal-open')
    assert.deepEqual(injector.getModalOpenStack(), ['modal:stack-a', 'modal:stack-b'])
    assert.equal(headHas(head, 'nps-field'), false)
    assert.equal(headHas(head, 'rating-field'), true)
    assert.equal(headHas(head, 'switch'), true)

    await closeModal(injector, b, 'stack-b')
    a.classList.add('fi-modal-open')
    assert.deepEqual(injector.getModalOpenStack(), ['modal:stack-a'])
    assert.equal(headHas(head, 'rating-field'), false)
    assert.equal(headHas(head, 'switch'), true)

    await closeModal(injector, a, 'stack-a')
    assert.deepEqual(injector.getModalOpenStack(), [])
    assert.equal(headHas(head, 'switch'), false)
})

test('logic: nested child shares parent switch; close child must keep switch for parent', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const sw = css('switch')
    const rating = css('rating-field')

    const parent = createElement('div')
    parent.id = 'parent'
    parent.classList.add('fi-modal')
    parent.appendChild(createAssetBatch([sw]))
    body.appendChild(parent)

    const child = createElement('div')
    child.id = 'child'
    child.classList.add('fi-modal')
    child.appendChild(createAssetBatch([sw, rating]))
    body.appendChild(child)

    registerModals(document, { parent, child })

    await openModal(injector, head, parent, 'parent')
    parent.classList.remove('fi-modal-open')
    await openModal(injector, head, child, 'child')

    assert.equal(headCount(head, 'switch'), 1)

    await closeModal(injector, child, 'child')
    parent.classList.add('fi-modal-open')

    assert.equal(headHas(head, 'switch'), true)
    assert.equal(headHas(head, 'rating-field'), false)
})

test('logic: page flex + nested A switch + B rating; closes leave page flex always', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const flex = css('flex-text-input')
    const sw = css('switch')
    const rating = css('rating-field')

    const page = createElement('form')
    page.appendChild(createAssetBatch([flex]))
    body.appendChild(page)
    await ensurePage(injector, head, document)

    const a = createElement('div')
    a.id = 'a'
    a.classList.add('fi-modal')
    a.appendChild(createAssetBatch([flex, sw]))
    body.appendChild(a)

    const b = createElement('div')
    b.id = 'b'
    b.classList.add('fi-modal')
    b.appendChild(createAssetBatch([rating]))
    body.appendChild(b)

    registerModals(document, { a, b })

    await openModal(injector, head, a, 'a')
    a.classList.remove('fi-modal-open')
    await openModal(injector, head, b, 'b')

    await closeModal(injector, b, 'b')
    a.classList.add('fi-modal-open')
    assert.equal(headHas(head, 'flex-text-input'), true)
    assert.equal(headHas(head, 'switch'), true)
    assert.equal(headHas(head, 'rating-field'), false)

    await closeModal(injector, a, 'a')
    assert.equal(headHas(head, 'flex-text-input'), true)
    assert.equal(headHas(head, 'switch'), false)
})

test('logic: parent loses fi-modal-open while child open; idle closed shells do not steal ownership', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const idle = createElement('div')
    idle.id = 'idle-shell'
    idle.classList.add('fi-modal')
    idle.appendChild(createAssetBatch([css('tags-field')]))
    body.appendChild(idle)

    const parent = createElement('div')
    parent.id = 'p'
    parent.classList.add('fi-modal')
    parent.appendChild(createAssetBatch([css('switch')]))
    body.appendChild(parent)

    const child = createElement('div')
    child.id = 'c'
    child.classList.add('fi-modal')
    child.appendChild(createAssetBatch([css('rating-field')]))
    body.appendChild(child)

    registerModals(document, { 'idle-shell': idle, p: parent, c: child })

    await openModal(injector, head, parent, 'p')
    parent.classList.remove('fi-modal-open')
    await openModal(injector, head, child, 'c')

    // Mimic old buggy cleanup that swept all closed shells — must NOT wipe parent.
    await injector.cleanupClosedModalPendingState({ detail: { id: 'c' } })
    child.classList.remove('fi-modal-open')

    assert.equal(headHas(head, 'switch'), true)
    assert.equal(headHas(head, 'tags-field'), false)
    assert.equal(retainedHas(injector, document, css('tags-field')), false)
    assert.deepEqual(injector.getModalOpenStack(), ['modal:p'])
})

test('logic: modal-closed without id pops only top of stack (nested close order)', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const a = createElement('div')
    a.id = 'na'
    a.classList.add('fi-modal')
    a.appendChild(createAssetBatch([css('switch')]))
    body.appendChild(a)

    const b = createElement('div')
    b.id = 'nb'
    b.classList.add('fi-modal')
    b.appendChild(createAssetBatch([css('rating-field')]))
    body.appendChild(b)

    registerModals(document, { na: a, nb: b })
    await openModal(injector, head, a, 'na')
    a.classList.remove('fi-modal-open')
    await openModal(injector, head, b, 'nb')

    await injector.cleanupClosedModalPendingState({})
    assert.deepEqual(injector.getModalOpenStack(), ['modal:na'])
    assert.equal(headHas(head, 'switch'), true)
    assert.equal(headHas(head, 'rating-field'), false)
})

test('logic: duplicate modal-closed for same id is idempotent', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const modal = createElement('div')
    modal.id = 'once'
    modal.classList.add('fi-modal')
    modal.appendChild(createAssetBatch([css('switch')]))
    body.appendChild(modal)
    registerModals(document, { once: modal })

    await openModal(injector, head, modal, 'once')
    await closeModal(injector, modal, 'once')
    await injector.cleanupClosedModalPendingState({ detail: { id: 'once' } })

    assert.deepEqual(injector.getModalOpenStack(), [])
    assert.equal(headHas(head, 'switch'), false)
})

test('logic: reopening parent after nested child does not duplicate CSS links', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const sw = css('switch')

    const parent = createElement('div')
    parent.id = 'rp'
    parent.classList.add('fi-modal')
    parent.appendChild(createAssetBatch([sw]))
    body.appendChild(parent)

    const child = createElement('div')
    child.id = 'rc'
    child.classList.add('fi-modal')
    child.appendChild(createAssetBatch([css('rating-field')]))
    body.appendChild(child)

    registerModals(document, { rp: parent, rc: child })

    await openModal(injector, head, parent, 'rp')
    parent.classList.remove('fi-modal-open')
    await openModal(injector, head, child, 'rc')
    await closeModal(injector, child, 'rc')

    parent.classList.add('fi-modal-open')
    parent.appendChild(createAssetBatch([sw]))
    await openModal(injector, head, parent, 'rp')

    assert.equal(headCount(head, 'switch'), 1)
    assert.deepEqual(injector.getModalOpenStack(), ['modal:rp'])
})

test('logic: reopen modal after full close reloads previously uninstalled CSS', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const sw = css('switch')

    const modal = createElement('div')
    modal.id = 'reopen'
    modal.classList.add('fi-modal')
    modal.appendChild(createAssetBatch([sw]))
    body.appendChild(modal)
    registerModals(document, { reopen: modal })

    await openModal(injector, head, modal, 'reopen')
    await closeModal(injector, modal, 'reopen')
    assert.equal(headHas(head, 'switch'), false)
    assert.equal(injector.isStylesheetLoaded(sw), false)

    modal.appendChild(createAssetBatch([sw]))
    await openModal(injector, head, modal, 'reopen')
    assert.equal(headHas(head, 'switch'), true)
    assert.equal(injector.isStylesheetLoaded(sw), true)
})

test('logic: anonymous modals (no id) use WeakMap keys and LIFO close', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const parent = createElement('div')
    parent.classList.add('fi-modal', 'fi-modal-open')
    parent.appendChild(createAssetBatch([css('switch')]))
    body.appendChild(parent)

    const child = createElement('div')
    child.classList.add('fi-modal')
    child.appendChild(createAssetBatch([css('rating-field')]))
    body.appendChild(child)

    // No detail.id → resolveModalRoot uses topmost open modal
    let pending = injector.prepareModal({})
    await flushAssetLoads(head)
    await pending
    assert.equal(injector.getModalOpenStack().length, 1)
    assert.equal(headHas(head, 'switch'), true)

    parent.classList.remove('fi-modal-open')
    child.classList.add('fi-modal-open')
    pending = injector.prepareModal({})
    await flushAssetLoads(head)
    await pending

    assert.equal(injector.getModalOpenStack().length, 2)
    assert.equal(headHas(head, 'rating-field'), true)

    await injector.cleanupClosedModalPendingState({})
    assert.equal(injector.getModalOpenStack().length, 1)
    assert.equal(headHas(head, 'rating-field'), false)
    assert.equal(headHas(head, 'switch'), true)
})

// ---------------------------------------------------------------------------
// Morph / SPA tabs
// ---------------------------------------------------------------------------

test('logic: morph inside open modal claims modal owner, not page', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const modal = createElement('div')
    modal.id = 'morph-modal'
    modal.classList.add('fi-modal', 'fi-modal-open')
    body.appendChild(modal)
    registerModals(document, { 'morph-modal': modal })

    await openModal(injector, head, modal, 'morph-modal')

    const field = createElement('div')
    field.classList.add('fi-fo-field-wrp')
    field.appendChild(createAssetBatch([css('currency-field')]))
    modal.appendChild(field)

    const morph = injector.handleMorphUpdated({ el: field })
    await flushAssetLoads(head)
    await morph

    assert.equal(headHas(head, 'currency-field'), true)

    await closeModal(injector, modal, 'morph-modal')
    assert.equal(headHas(head, 'currency-field'), false)
    assert.equal(retainedHas(injector, document, css('currency-field')), false)
})

test('logic: page morph never loads sibling closed-modal batches', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const field = createElement('div')
    field.appendChild(createAssetBatch([css('flex-text-input')]))
    body.appendChild(field)

    const shell = createElement('div')
    shell.classList.add('fi-modal')
    shell.appendChild(createAssetBatch([css('video-field')]))
    body.appendChild(shell)

    const morph = injector.handleMorphUpdated({ el: field })
    await flushAssetLoads(head)
    await morph

    assert.equal(headHas(head, 'flex-text-input'), true)
    assert.equal(headHas(head, 'video-field'), false)
})

test('logic: SPA navigate clears stack and modal retain; loads only next tab batches', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const edit = createElement('form')
    edit.appendChild(createAssetBatch([css('flex-text-input')]))
    body.appendChild(edit)
    await ensurePage(injector, head, document)

    const modal = createElement('div')
    modal.id = 'nav-modal'
    modal.classList.add('fi-modal')
    modal.appendChild(createAssetBatch([css('switch')]))
    body.appendChild(modal)
    registerModals(document, { 'nav-modal': modal })
    await openModal(injector, head, modal, 'nav-modal')

    for (const child of [...head.children]) {
        child.remove()
    }

    edit.remove()
    modal.remove()
    body.children.length = 0

    const video = createElement('form')
    video.appendChild(createAssetBatch([css('item-card')]))
    body.appendChild(video)

    const navigated = injector.handleLivewireNavigated()
    await flushAssetLoads(head)
    await navigated

    assert.deepEqual(injector.getModalOpenStack(), [])
    assert.equal(headHas(head, 'item-card'), true)
    assert.equal(headHas(head, 'flex-text-input'), false)
    assert.equal(headHas(head, 'switch'), false)
    assert.equal(retainedHas(injector, document, css('switch')), false)
})

test('logic: isStylesheetLoaded false after Livewire drops link; ensure re-injects from batch', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const href = css('item-card')

    const load = injector.loadStylesheet(href)
    await flushAssetLoads(head)
    await load
    head.children.find((child) => child.href?.includes('item-card')).remove()
    assert.equal(injector.isStylesheetLoaded(href), false)

    body.appendChild(createAssetBatch([href]))
    const navigated = injector.handleLivewireNavigated()
    await flushAssetLoads(head)
    await navigated
    assert.equal(injector.isStylesheetLoaded(href), true)
})

test('logic: SPA navigate while head still has stale CSS does not duplicate that URL', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const href = css('flex-text-input')

    const load = injector.loadStylesheet(href)
    await flushAssetLoads(head)
    await load

    body.appendChild(createAssetBatch([href]))
    const navigated = injector.handleLivewireNavigated()
    await flushAssetLoads(head)
    await navigated

    assert.equal(headCount(head, 'flex-text-input'), 1)
})

// ---------------------------------------------------------------------------
// Deduplicate / protected / purge boundaries
// ---------------------------------------------------------------------------

test('logic: dedupe removes true duplicate links but keeps first inject', async () => {
    const { document, window, head } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const href = normalizeAssetUrl(css('slug-field'), document.baseURI)

    const first = createLink({ href })
    first.sheet = {}
    first.parentElement = head
    head.children.push(first)

    const second = createLink({ href })
    second.sheet = {}
    second.parentElement = head
    head.children.push(second)

    injector.resyncLoadedAssetsFromDocument()
    // force dedupe via ensure with empty page
    await injector.ensureAssets(document, { pageOnly: true })

    assert.equal(stylesheetHrefs(head).filter((h) => h.includes('slug-field')).length, 1)
})

test('logic: playground bundle survives uninstallUnretainedAssets', async () => {
    const { document, window, head } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const bundle = createLink({
        href: normalizeAssetUrl('/css/janczakb/filament-flex-fields/flex-fields-playground-switch.css', document.baseURI),
        attributes: { 'data-fff-playground-bundle': '' },
    })
    bundle.sheet = {}
    head.appendChild(bundle)

    injector.claimAssetUrls('modal:x', [normalizeAssetUrl(css('switch'), document.baseURI)])
    injector.releaseModalOwnership('modal:x')
    injector.uninstallUnretainedAssets()

    assert.equal(head.children.includes(bundle), true)
})

test('logic: protected inline emit stylesheet is never dropped as a dedupe false-positive', async () => {
    const { document, window, head } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const href = normalizeAssetUrl(css('emoji-picker'), document.baseURI)

    const emit = createLink({
        href,
        attributes: { 'data-fff-stylesheet': '' },
    })
    emit.sheet = {}
    head.appendChild(emit)

    const inject = injector.loadStylesheet(href)
    await flushAssetLoads(head)
    await inject

    await injector.ensureAssets(document, { pageOnly: true })
    assert.ok(head.children.some((child) => child.hasAttribute?.('data-fff-stylesheet')))
})

test('logic: pageOnly ensure after modal open does not sticky-retain modal-only URLs onto page', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const flex = css('flex-text-input')
    const sw = css('switch')

    const page = createElement('form')
    page.appendChild(createAssetBatch([flex]))
    body.appendChild(page)
    await ensurePage(injector, head, document)

    const modal = createElement('div')
    modal.id = 'sticky'
    modal.classList.add('fi-modal')
    // leftover unconsumed batch still in shell
    modal.appendChild(createAssetBatch([sw]))
    body.appendChild(modal)
    registerModals(document, { sticky: modal })

    await openModal(injector, head, modal, 'sticky')
    // another page-only scan while modal (and maybe its sibling shells) exist
    page.appendChild(createAssetBatch([flex]))
    await ensurePage(injector, head, document)

    await closeModal(injector, modal, 'sticky')
    assert.equal(headHas(head, 'flex-text-input'), true)
    assert.equal(headHas(head, 'switch'), false)
})

test('logic: empty modal open/close does not throw or poison stack', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const modal = createElement('div')
    modal.id = 'empty'
    modal.classList.add('fi-modal')
    body.appendChild(modal)
    registerModals(document, { empty: modal })

    await openModal(injector, head, modal, 'empty')
    assert.deepEqual(injector.getModalOpenStack(), ['modal:empty'])
    await closeModal(injector, modal, 'empty')
    assert.deepEqual(injector.getModalOpenStack(), [])
})

test('logic: close unknown modal id does not wipe unrelated stack entries', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const modal = createElement('div')
    modal.id = 'alive'
    modal.classList.add('fi-modal')
    modal.appendChild(createAssetBatch([css('switch')]))
    body.appendChild(modal)
    registerModals(document, { alive: modal })

    await openModal(injector, head, modal, 'alive')
    await injector.cleanupClosedModalPendingState({ detail: { id: 'ghost-missing' } })

    assert.deepEqual(injector.getModalOpenStack(), ['modal:alive'])
    assert.equal(headHas(head, 'switch'), true)
})

test('logic: two siblings share asset; closing both finally uninstalls once', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const sw = css('switch')

    const a = createElement('div')
    a.id = 's1'
    a.classList.add('fi-modal')
    a.appendChild(createAssetBatch([sw]))
    body.appendChild(a)

    const b = createElement('div')
    b.id = 's2'
    b.classList.add('fi-modal')
    b.appendChild(createAssetBatch([sw]))
    body.appendChild(b)

    registerModals(document, { s1: a, s2: b })
    await openModal(injector, head, a, 's1')
    await openModal(injector, head, b, 's2')
    assert.equal(headCount(head, 'switch'), 1)

    await closeModal(injector, a, 's1')
    assert.equal(headHas(head, 'switch'), true)

    await closeModal(injector, b, 's2')
    assert.equal(headHas(head, 'switch'), false)
})

test('logic: page retain + two modals overlapping URL; uninstall only after all three owners gone', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const shared = css('flex-text-input')

    const page = createElement('form')
    page.appendChild(createAssetBatch([shared]))
    body.appendChild(page)
    await ensurePage(injector, head, document)

    const a = createElement('div')
    a.id = 't1'
    a.classList.add('fi-modal')
    a.appendChild(createAssetBatch([shared]))
    body.appendChild(a)

    const b = createElement('div')
    b.id = 't2'
    b.classList.add('fi-modal')
    b.appendChild(createAssetBatch([shared]))
    body.appendChild(b)

    registerModals(document, { t1: a, t2: b })
    await openModal(injector, head, a, 't1')
    await openModal(injector, head, b, 't2')
    assert.equal(headCount(head, 'flex-text-input'), 1)

    await closeModal(injector, a, 't1')
    assert.equal(headHas(head, 'flex-text-input'), true)
    await closeModal(injector, b, 't2')
    assert.equal(headHas(head, 'flex-text-input'), true)

    // page still retains — navigate away to drop page retain
    for (const child of [...head.children]) {
        child.remove()
    }
    page.remove()
    body.children.length = 0
    body.appendChild(createAssetBatch([css('item-card')]))

    const navigated = injector.handleLivewireNavigated()
    await flushAssetLoads(head)
    await navigated

    assert.equal(headHas(head, 'flex-text-input'), false)
    assert.equal(headHas(head, 'item-card'), true)
})

test('logic: closing mid-stack parent while child remains keeps child assets', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const a = createElement('div')
    a.id = 'mid-a'
    a.classList.add('fi-modal')
    a.appendChild(createAssetBatch([css('switch')]))
    body.appendChild(a)

    const b = createElement('div')
    b.id = 'mid-b'
    b.classList.add('fi-modal')
    b.appendChild(createAssetBatch([css('rating-field')]))
    body.appendChild(b)

    registerModals(document, { 'mid-a': a, 'mid-b': b })
    await openModal(injector, head, a, 'mid-a')
    a.classList.remove('fi-modal-open')
    await openModal(injector, head, b, 'mid-b')

    // Unusual close of parent while child still "open" in stack
    await injector.cleanupClosedModalPendingState({ detail: { id: 'mid-a' } })

    assert.deepEqual(injector.getModalOpenStack(), ['modal:mid-b'])
    assert.equal(headHas(head, 'rating-field'), true)
    assert.equal(headHas(head, 'switch'), false)
})

test('logic: deep stack of 4 unique assets unwinds without collisions', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const names = ['switch', 'rating-field', 'nps-field', 'tags-field']
    const modals = {}

    for (const [index, name] of names.entries()) {
        const modal = createElement('div')
        const id = `d${index}`
        modal.id = id
        modal.classList.add('fi-modal')
        modal.appendChild(createAssetBatch([css(name)]))
        body.appendChild(modal)
        modals[id] = modal
    }

    registerModals(document, modals)

    for (const [index] of names.entries()) {
        if (index > 0) {
            modals[`d${index - 1}`].classList.remove('fi-modal-open')
        }

        await openModal(injector, head, modals[`d${index}`], `d${index}`)
    }

    assert.equal(injector.getModalOpenStack().length, 4)

    for (let index = names.length - 1; index >= 0; index -= 1) {
        await closeModal(injector, modals[`d${index}`], `d${index}`)

        for (let keep = 0; keep < index; keep += 1) {
            assert.equal(headHas(head, names[keep]), true, `expected ${names[keep]} retained`)
        }

        assert.equal(headHas(head, names[index]), false, `expected ${names[index]} gone`)
    }

    assert.deepEqual(injector.getModalOpenStack(), [])
    assert.equal(chunkHrefs(head).length + stylesheetHrefs(head).length, 0)
})

test('logic: resolveAssetOwnerKey maps nested field to modal and orphan field to page', async () => {
    const { document, window, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const modal = createElement('div')
    modal.id = 'own'
    modal.classList.add('fi-modal')
    const nested = createElement('div')
    modal.appendChild(nested)
    body.appendChild(modal)

    const orphan = createElement('div')
    body.appendChild(orphan)

    assert.equal(injector.resolveAssetOwnerKey(nested), 'modal:own')
    assert.equal(injector.resolveAssetOwnerKey(orphan), 'page')
    assert.equal(injector.resolveAssetOwnerKey(document), 'page')
})

test('logic: preloadBatchesIn does not consume batches; ensureAssets still owns them later', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const href = css('slug-field')

    const page = createElement('form')
    const batch = createAssetBatch([href])
    page.appendChild(batch)
    body.appendChild(page)

    const preload = injector.preloadBatchesIn(page)
    await flushAssetLoads(head)
    await preload

    assert.equal(page.querySelectorAll('[data-fff-asset-batch]').length, 1)
    assert.equal(headHas(head, 'slug-field'), true)

    await ensurePage(injector, head, document)
    assert.equal(retainedHas(injector, document, href), true)
    assert.equal(headCount(head, 'slug-field'), 1)
})

test('logic: parallel ensureAssets(page) + prepareModal share one link and distinct owners', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const flex = css('flex-text-input')
    const sw = css('switch')

    const page = createElement('form')
    page.appendChild(createAssetBatch([flex]))
    body.appendChild(page)

    const modal = createElement('div')
    modal.id = 'race'
    modal.classList.add('fi-modal', 'fi-modal-open')
    modal.appendChild(createAssetBatch([flex, sw]))
    body.appendChild(modal)
    registerModals(document, { race: modal })

    const pageJob = injector.ensureAssets(document, { pageOnly: true })
    const modalJob = injector.prepareModal({ detail: { id: 'race' } })
    await flushAssetLoads(head)
    await Promise.all([pageJob, modalJob])

    assert.equal(headCount(head, 'flex-text-input'), 1)
    assert.equal(headCount(head, 'switch'), 1)
    assert.ok(injector.getModalOpenStack().includes('modal:race'))

    await closeModal(injector, modal, 'race')
    assert.equal(headHas(head, 'flex-text-input'), true)
    assert.equal(headHas(head, 'switch'), false)
})

test('logic: realistic Video tab + action modal + nested confirm modal lifecycle', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const videoPageCss = css('video-field')
    const flex = css('flex-text-input')
    const sw = css('switch')
    const rating = css('rating-field')

    // Video resource tab page
    const page = createElement('form')
    page.appendChild(createAssetBatch([videoPageCss, flex]))
    body.appendChild(page)
    await ensurePage(injector, head, document)

    // Primary action modal (has flex + switch)
    const action = createElement('div')
    action.id = 'yacht-video-action'
    action.classList.add('fi-modal')
    action.appendChild(createAssetBatch([flex, sw]))
    body.appendChild(action)

    // Nested confirm modal (rating only)
    const confirm = createElement('div')
    confirm.id = 'yacht-video-confirm'
    confirm.classList.add('fi-modal')
    confirm.appendChild(createAssetBatch([rating]))
    body.appendChild(confirm)

    registerModals(document, {
        'yacht-video-action': action,
        'yacht-video-confirm': confirm,
    })

    await openModal(injector, head, action, 'yacht-video-action')
    assert.equal(headCount(head, 'flex-text-input'), 1)
    assert.equal(headHas(head, 'switch'), true)

    action.classList.remove('fi-modal-open')
    await openModal(injector, head, confirm, 'yacht-video-confirm')
    assert.equal(headHas(head, 'rating-field'), true)
    assert.equal(headHas(head, 'switch'), true)

    await closeModal(injector, confirm, 'yacht-video-confirm')
    action.classList.add('fi-modal-open')
    assert.equal(headHas(head, 'rating-field'), false)
    assert.equal(headHas(head, 'switch'), true)
    assert.equal(headHas(head, 'video-field'), true)

    await closeModal(injector, action, 'yacht-video-action')
    assert.equal(headHas(head, 'switch'), false)
    assert.equal(headHas(head, 'flex-text-input'), true)
    assert.equal(headHas(head, 'video-field'), true)

    // Navigate to Edit tab
    for (const child of [...head.children]) {
        child.remove()
    }
    page.remove()
    action.remove()
    confirm.remove()
    body.children.length = 0

    const edit = createElement('form')
    edit.appendChild(createAssetBatch([flex, css('phone-field')]))
    body.appendChild(edit)

    const navigated = injector.handleLivewireNavigated()
    await flushAssetLoads(head)
    await navigated

    assert.deepEqual(injector.getModalOpenStack(), [])
    assert.equal(headHas(head, 'flex-text-input'), true)
    assert.equal(headHas(head, 'phone-field'), true)
    assert.equal(headHas(head, 'video-field'), false)
    assert.equal(headHas(head, 'switch'), false)
})

test('logic: claimAssetUrls page cannot be wiped by releaseModalOwnership or stack pop', async () => {
    const { document, window, head } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const href = normalizeAssetUrl(css('cover-card'), document.baseURI)

    injector.claimAssetUrls('page', [href])
    injector.claimAssetUrls('modal:tmp', [href])

    const link = createLink({ href })
    link.sheet = {}
    head.appendChild(link)
    injector.resyncLoadedAssetsFromDocument()

    injector.releaseModalOwnership('modal:tmp')
    injector.uninstallUnretainedAssets()
    assert.equal(head.children.includes(link), true)

    await injector.cleanupClosedModalPendingState({ detail: { id: 'tmp' } })
    assert.equal(head.children.includes(link), true)
    assert.equal(retainedHas(injector, document, css('cover-card')), true)
})

test('logic: empty modal-closed on empty stack is a no-op', async () => {
    const { document, window } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    await injector.cleanupClosedModalPendingState({})
    await injector.cleanupClosedModalPendingState({ detail: { id: 'missing' } })
    assert.deepEqual(injector.getModalOpenStack(), [])
})

test('logic: rapid open/close x10 same modal never leaves duplicate links or stack residue', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const sw = css('switch')

    const modal = createElement('div')
    modal.id = 'rapid'
    modal.classList.add('fi-modal')
    body.appendChild(modal)
    registerModals(document, { rapid: modal })

    for (let i = 0; i < 10; i += 1) {
        modal.appendChild(createAssetBatch([sw]))
        await openModal(injector, head, modal, 'rapid')
        assert.equal(headCount(head, 'switch'), 1)
        await closeModal(injector, modal, 'rapid')
        assert.equal(headHas(head, 'switch'), false)
        assert.deepEqual(injector.getModalOpenStack(), [])
    }
})

test('logic: uninstall never removes server-emitted data-fff-stylesheet links', async () => {
    const { document, window, head } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const href = normalizeAssetUrl(css('item-card'), document.baseURI)

    const emit = createLink({
        href,
        attributes: { 'data-fff-stylesheet': 'item-card' },
    })
    emit.sheet = {}
    head.appendChild(emit)

    // Empty retain + modal close must not strip server emit CSS.
    await injector.cleanupClosedModalPendingState({ detail: { id: 'ghost' } })

    assert.equal(head.children.includes(emit), true)
    assert.equal(injector.isStylesheetLoaded(href), true)
})

test('logic: boot then synthetic livewire:navigated still retains page CSS after batches were consumed', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const href = css('item-card')

    const page = createElement('form')
    page.appendChild(createAssetBatch([href]))
    body.appendChild(page)

    const bootEnsure = injector.ensureAssets(document, { pageOnly: true })
    await flushAssetLoads(head)
    await bootEnsure

    assert.equal(retainedHas(injector, document, href), true)
    assert.equal(headHas(head, 'item-card'), true)

    // Livewire SPA init fires livewire:navigated after first paint; batches are gone.
    await injector.handleLivewireNavigated()

    assert.equal(retainedHas(injector, document, href), true)

    // Modal close must NOT uninstall page CSS that the form still needs.
    await injector.cleanupClosedModalPendingState({ detail: { id: 'ghost' } })
    assert.equal(retainedHas(injector, document, href), true)
    assert.equal(headHas(head, 'item-card'), true)
})

test('logic: CSS+chunk bundle for modal uninstalls both when page retained neither', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const sheet = css('phone-field')
    const js = chunk('phone-lib-RPEB4A4Q')

    const modal = createElement('div')
    modal.id = 'pair'
    modal.classList.add('fi-modal')
    modal.appendChild(createAssetBatch([sheet], [js]))
    body.appendChild(modal)
    registerModals(document, { pair: modal })

    await openModal(injector, head, modal, 'pair')
    assert.equal(headHas(head, 'phone-field'), true)
    assert.equal(headHas(head, 'phone-lib'), true)

    await closeModal(injector, modal, 'pair')
    assert.equal(headHas(head, 'phone-field'), false)
    assert.equal(headHas(head, 'phone-lib'), false)
})
