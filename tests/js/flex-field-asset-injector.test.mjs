import assert from 'node:assert/strict'
import test from 'node:test'

import {
    connectionSaveDataEnabled,
    createFlexFieldAssetInjector,
    normalizeAssetUrl,
    prefersReducedMotion,
    shouldDeferBackgroundAssetPreload,
} from '../../resources/js/core/flex-field-asset-injector.js'
import { installPlaygroundSkeletonDemo } from '../../resources/js/playground/skeleton-demo.js'

function createClassList() {
    const classes = new Set()

    return {
        add: (...names) => {
            for (const name of names) {
                if (name) {
                    classes.add(name)
                }
            }
        },
        remove: (...names) => {
            for (const name of names) {
                if (name) {
                    classes.delete(name)
                }
            }
        },
        contains: (name) => classes.has(name),
    }
}

function createLink({ href, rel = 'stylesheet', attributes = {} }) {
    const listeners = new Map()
    const classList = createClassList()

    const link = {
        rel,
        href,
        parentElement: null,
        dataset: {},
        classList,
        setAttribute(name, value) {
            attributes[name] = value
        },
        getAttribute(name) {
            return attributes[name] ?? null
        },
        hasAttribute(name) {
            return Object.hasOwn(attributes, name)
        },
        addEventListener(type, listener, options = {}) {
            listeners.set(type, { listener, options })
        },
        remove() {
            if (link.parentElement?.children) {
                const index = link.parentElement.children.indexOf(link)

                if (index >= 0) {
                    link.parentElement.children.splice(index, 1)
                }
            }

            link.parentElement = null
        },
        dispatchEvent(type) {
            if (type === 'load' && link.rel === 'stylesheet') {
                link.sheet = {}
            }

            const entry = listeners.get(type)

            if (! entry) {
                return
            }

            entry.listener()
        },
    }

    return link
}

function createElement(tagName) {
    if (tagName === 'link') {
        return createLink({ href: '' })
    }

    const element = {
        tagName,
        children: [],
        attributes: {},
        classList: createClassList(),
        parentElement: null,
        dataset: {},
        style: {},
        id: '',
        get isConnected() {
            return this.parentElement !== null
        },
        appendChild(child) {
            child.parentElement = element
            element.children.push(child)

            return child
        },
        closest(selector) {
            if (selector === '.fi-modal' && this.classList.contains('fi-modal')) {
                return this
            }

            return this.parentElement?.closest?.(selector) ?? null
        },
        querySelector(selector) {
            return this.querySelectorAll(selector)[0] ?? null
        },
        querySelectorAll(selector) {
            const matches = []

            const nodeMatches = (node) => {
                if (node.matches?.(selector)) {
                    return true
                }

                if (selector.includes('stylesheet') && node.rel === 'stylesheet' && node.href?.includes('filament-flex-fields')) {
                    return true
                }

                if (selector.includes('modulepreload') && node.rel === 'modulepreload' && node.href?.includes('filament-flex-fields')) {
                    return true
                }

                return false
            }

            const walk = (node) => {
                if (nodeMatches(node)) {
                    matches.push(node)
                }

                for (const child of node.children ?? []) {
                    walk(child)
                }
            }

            walk(this)

            return matches
        },
        matches(selector) {
            if (selector === '[data-fff-asset-batch]') {
                return this.hasAttribute?.('data-fff-asset-batch') ?? false
            }

            if (selector.startsWith('.')) {
                for (const className of selector.split(/[\s,]+/).filter(Boolean)) {
                    if (! className.startsWith('.')) {
                        continue
                    }

                    if (! this.classList?.contains?.(className.slice(1))) {
                        return false
                    }
                }

                return selector.split(/[\s,]+/).some((token) => token.startsWith('.'))
            }

            return false
        },
        setAttribute(name, value) {
            this.attributes[name] = value
        },
        getAttribute(name) {
            return this.attributes?.[name] ?? null
        },
        hasAttribute(name) {
            return Object.hasOwn(this.attributes ?? {}, name)
        },
        remove() {
            if (this.parentElement?.children) {
                const index = this.parentElement.children.indexOf(this)

                if (index >= 0) {
                    this.parentElement.children.splice(index, 1)
                }
            }

            this.parentElement = null
        },
        contains(node) {
            const walk = (parent) => {
                for (const child of parent.children ?? []) {
                    if (child === node) {
                        return true
                    }

                    if (walk(child)) {
                        return true
                    }
                }

                return false
            }

            return walk(this)
        },
    }

    return element
}

function createAssetBatch(stylesheets, chunks = [], { consumerComponent = null, livewireKey = 'test.consumer' } = {}) {
    const batch = createElement('span')
    batch.attributes = {
        'data-fff-asset-batch': '',
        'data-fff-stylesheets': JSON.stringify(stylesheets),
        'data-fff-chunks': JSON.stringify(chunks),
    }

    const resolvedComponent = consumerComponent ?? (() => {
        for (const href of stylesheets) {
            const match = String(href).match(/flex-fields-([^./]+)\.css/)

            if (match?.[1]) {
                return match[1]
            }
        }

        for (const href of chunks) {
            const match = String(href).match(/flex-fields-([^.]+)\.js/)

            if (match?.[1]) {
                return match[1]
            }
        }

        return null
    })()

    const componentForCrg = resolvedComponent
        ?? ((stylesheets.length > 0 || chunks.length > 0) ? 'flex-asset-batch' : null)

    if (componentForCrg && livewireKey) {
        batch.attributes['data-fff-asset-consumer'] = componentForCrg
        batch.attributes['data-fff-asset-consumer-id'] = livewireKey
        batch.dataset.fffAssetConsumer = componentForCrg
        batch.dataset.fffAssetConsumerId = livewireKey
    }

    return batch
}

function stylesheetHrefs(head) {
    return head.children
        .filter((child) => child.rel === 'stylesheet' && child.href?.includes('filament-flex-fields'))
        .map((child) => child.href)
}

async function flushStylesheetLoads(head) {
    for (const child of [...head.children]) {
        if (child.rel === 'stylesheet' && typeof child.dispatchEvent === 'function') {
            child.dispatchEvent('load')
        }
    }
}

function createDom() {
    const head = { children: [], appendChild(child) { child.parentElement = head; head.children.push(child) } }
    const body = { children: [], appendChild(child) { child.parentElement = body; body.children.push(child) } }

    const nodes = []

    const document = {
        baseURI: 'https://panel.test/admin',
        readyState: 'complete',
        head,
        body,
        createElement(tagName) {
            const element = createElement(tagName)

            if (! element.children) {
                element.children = []
            }

            nodes.push(element)

            return element
        },
        querySelectorAll(selector) {
            const matches = []
            const seen = new Set()

            const pushMatch = (node) => {
                if (seen.has(node)) {
                    return
                }

                seen.add(node)
                matches.push(node)
            }

            const consider = (node) => {
                if (selector.includes('stylesheet') && node.rel === 'stylesheet' && node.href?.includes('filament-flex-fields')) {
                    pushMatch(node)
                }

                if (selector.includes('modulepreload') && node.rel === 'modulepreload' && node.href?.includes('filament-flex-fields')) {
                    pushMatch(node)
                }

                if (selector.includes('fff-flex-fields-assets-pending') && node.classList?.contains('fff-flex-fields-assets-pending')) {
                    if (selector.includes(':not(.fi-modal)')) {
                        if (! node.classList.contains('fi-modal')) {
                            pushMatch(node)
                        }
                    } else {
                        pushMatch(node)
                    }
                }

                if (selector === '.fi-modal.fff-flex-fields-assets-pending'
                    && node.classList?.contains('fi-modal')
                    && node.classList.contains('fff-flex-fields-assets-pending')) {
                    pushMatch(node)
                }

                if (selector === '.fi-modal.fi-modal-open'
                    && node.classList?.contains('fi-modal')
                    && node.classList.contains('fi-modal-open')) {
                    pushMatch(node)
                }

                if (selector === '.fi-modal:not(.fi-modal-open)'
                    && node.classList?.contains('fi-modal')
                    && ! node.classList.contains('fi-modal-open')) {
                    pushMatch(node)
                }

                if (selector === '[data-fff-asset-batch]' && node.hasAttribute?.('data-fff-asset-batch')) {
                    pushMatch(node)
                }

                if (selector === '[data-fff-asset-consumer]' && node.dataset?.fffAssetConsumer) {
                    pushMatch(node)
                }
            }

            const walk = (node) => {
                consider(node)

                for (const child of node.children ?? []) {
                    walk(child)
                }
            }

            for (const node of nodes) {
                walk(node)
            }

            walk(head)
            walk(body)

            return matches
        },
        getElementById(id) {
            const matches = []

            const walk = (node) => {
                if (node.id === id) {
                    matches.push(node)
                }

                for (const child of node.children ?? []) {
                    walk(child)
                }
            }

            for (const node of nodes) {
                walk(node)
            }

            walk(body)

            return matches[0] ?? null
        },
        addEventListener() {},
    }

    const window = {
        Livewire: null,
        addEventListener() {},
        setTimeout(fn, ms) {
            return globalThis.setTimeout(fn, ms)
        },
        clearTimeout(id) {
            return globalThis.clearTimeout(id)
        },
        location: {
            pathname: '/admin/flex-fields-playground/file-upload',
        },
        localStorage: {
            store: new Map(),
            getItem(key) {
                return this.store.get(key) ?? null
            },
            setItem(key, value) {
                this.store.set(key, String(value))
            },
            removeItem(key) {
                this.store.delete(key)
            },
        },
        performance: {
            getEntriesByName() {
                return []
            },
        },
    }

    return { document, window, nodes, head, body }
}

test('normalizeAssetUrl resolves relative and absolute urls to the same href', () => {
    const baseUri = 'https://panel.test/admin/forms'

    assert.equal(
        normalizeAssetUrl('/css/janczakb/filament-flex-fields/flex-fields-phone-field.css', baseUri),
        'https://panel.test/css/janczakb/filament-flex-fields/flex-fields-phone-field.css',
    )

    assert.equal(
        normalizeAssetUrl('https://panel.test/css/janczakb/filament-flex-fields/flex-fields-phone-field.css', baseUri),
        'https://panel.test/css/janczakb/filament-flex-fields/flex-fields-phone-field.css',
    )
})

test('resolveHoverPreloadScope prefers the nearest field wrapper over the document', () => {
    const { document, window } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const form = createElement('form')
    const field = createElement('div')
    field.classList.add('fi-fo-field-wrp')

    const batch = createElement('span')
    batch.attributes = { 'data-fff-asset-batch': '' }
    field.children.push(batch)
    form.children.push(field)

    const button = createElement('button')
    field.children.push(button)
    button.parentElement = field
    button.closest = (selector) => {
        if (selector === '[data-fff-asset-batch]') {
            return batch
        }

        if (selector === '.fi-fo-field-wrp') {
            return field
        }

        if (selector === 'form') {
            return form
        }

        return null
    }

    assert.equal(injector.resolveHoverPreloadScope(button), field)
})

test('loadStylesheet deduplicates concurrent requests through the inflight cache', async () => {
    const { document, window, head } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const href = '/css/janczakb/filament-flex-fields/flex-fields-phone-field.css'

    const first = injector.loadStylesheet(href)
    const second = injector.loadStylesheet(href)

    assert.equal(first, second)

    const created = head.children.find((child) => child.rel === 'stylesheet')
    assert.ok(created)
    created.dispatchEvent('load')

    await first

    assert.equal(head.children.filter((child) => child.rel === 'stylesheet').length, 1)
    assert.equal(injector.isStylesheetLoaded(href), true)
})

test('resolvePendingTarget prefers the closest Filament modal container', () => {
    const { document, window } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const modal = createElement('div')
    modal.classList.add('fi-modal')

    const form = createElement('div')
    form.parentElement = modal
    modal.children.push(form)

    assert.equal(injector.resolvePendingTarget(form), modal)
})

test('batchNeedsLoading returns true when alpine chunks are missing', () => {
    const { document, window } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const batch = createElement('span')
    batch.attributes = {
        'data-fff-stylesheets': '[]',
        'data-fff-chunks': '["/js/janczakb/filament-flex-fields/components/select-field.js"]',
    }

    assert.equal(injector.batchNeedsLoading(batch), true)
})

test('handleMorphUpdating applies pending state to the live modal root before morph', () => {
    const { document, window } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const modal = createElement('div')
    modal.classList.add('fi-modal')

    const el = createElement('div')
    el.parentElement = modal
    modal.children.push(el)

    const toEl = createElement('div')

    const batch = createElement('span')
    batch.attributes = {
        'data-fff-asset-batch': '',
        'data-fff-stylesheets': '["/css/janczakb/filament-flex-fields/flex-fields-phone-field.css"]',
        'data-fff-chunks': '[]',
    }
    toEl.children.push(batch)
    toEl.querySelectorAll = (selector) => (selector === '[data-fff-asset-batch]' ? [batch] : [])

    injector.handleMorphUpdating({ el, toEl })

    assert.equal(modal.classList.contains('fff-flex-fields-assets-pending'), true)
    assert.equal(modal.classList.contains('fff-flex-fields-assets-ready'), false)
    assert.equal(injector.hasPendingState(el), true)
})

test('handleMorphUpdating applies pending state to inline morph targets outside modals', () => {
    const { document, window } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const el = createElement('div')
    const toEl = createElement('div')

    const batch = createElement('span')
    batch.attributes = {
        'data-fff-asset-batch': '',
        'data-fff-stylesheets': '["/css/janczakb/filament-flex-fields/flex-fields-phone-field.css"]',
        'data-fff-chunks': '[]',
    }
    toEl.children.push(batch)
    toEl.querySelectorAll = (selector) => (selector === '[data-fff-asset-batch]' ? [batch] : [])

    injector.handleMorphUpdating({ el, toEl })

    assert.equal(el.classList.contains('fff-flex-fields-assets-pending'), true)
    assert.equal(injector.hasPendingState(el), true)
})

test('cleanupClosedModalPendingState force-releases stray pending loaders after modal close', async () => {
    const { document, window } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const modal = createElement('div')
    modal.classList.add('fi-modal', 'fff-flex-fields-assets-pending')
    modal.id = 'demo-modal'
    modal.dataset.fffPendingStartedAt = String(Date.now())
    document.body.appendChild(modal)

    const stray = createElement('div')
    stray.classList.add('fff-flex-fields-assets-pending')
    stray.dataset.fffPendingStartedAt = String(Date.now())
    document.body.appendChild(stray)

    await injector.cleanupClosedModalPendingState({ detail: { id: 'demo-modal' } })

    assert.equal(modal.classList.contains('fff-flex-fields-assets-pending'), false)
    assert.equal(modal.classList.contains('fff-flex-fields-assets-ready'), true)
    assert.equal(stray.classList.contains('fff-flex-fields-assets-pending'), false)
    assert.equal(stray.classList.contains('fff-flex-fields-assets-ready'), true)
})

test('handleMorphUpdated releases pending modal state after assets load', async () => {
    const { document, window, head } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const modal = createElement('div')
    modal.classList.add('fi-modal', 'fff-flex-fields-assets-pending')

    const el = createElement('div')
    el.parentElement = modal
    modal.children.push(el)

    const batch = createElement('span')
    batch.attributes = {
        'data-fff-asset-batch': '',
        'data-fff-stylesheets': '["/css/janczakb/filament-flex-fields/flex-fields-phone-field.css"]',
        'data-fff-chunks': '[]',
    }
    el.children.push(batch)
    el.querySelectorAll = (selector) => (selector === '[data-fff-asset-batch]' ? [batch] : [])

    injector.beginPendingMorph({ el, toEl: el })

    const morphPromise = injector.handleMorphUpdated({ el })

    const created = head.children.find((child) => child.rel === 'stylesheet')
    created.dispatchEvent('load')

    await morphPromise

    assert.equal(modal.classList.contains('fff-flex-fields-assets-pending'), false)
    assert.equal(modal.classList.contains('fff-flex-fields-assets-ready'), true)
})

test('preloadBatchesIn starts downloads without removing batch markers', async () => {
    const { document, window, head } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const batch = createElement('span')
    batch.attributes = {
        'data-fff-asset-batch': '',
        'data-fff-stylesheets': '["/css/janczakb/filament-flex-fields/flex-fields-phone-field.css"]',
        'data-fff-chunks': '[]',
    }

    const root = createElement('div')
    root.children.push(batch)
    root.querySelectorAll = (selector) => (selector === '[data-fff-asset-batch]' ? [batch] : [])

    const pending = injector.preloadBatchesIn(root)

    const created = head.children.find((child) => child.rel === 'stylesheet')
    assert.ok(created)
    created.dispatchEvent('load')

    await pending

    assert.equal(batch.hasAttribute('data-fff-asset-batch'), true)
    assert.equal(injector.rootNeedsAssetLoading(root), false)
})

test('prepareModal skips pending state when modal assets are already loaded', async () => {
    const { document, window, head } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const href = '/css/janczakb/filament-flex-fields/flex-fields-phone-field.css'

    const modal = createElement('div')
    modal.classList.add('fi-modal')
    modal.id = 'test-modal'

    const batch = createElement('span')
    batch.attributes = {
        'data-fff-asset-batch': '',
        'data-fff-stylesheets': `["${href}"]`,
        'data-fff-chunks': '[]',
    }
    modal.children.push(batch)
    modal.querySelectorAll = (selector) => (selector === '[data-fff-asset-batch]' ? [batch] : [])

    document.getElementById = (id) => (id === 'test-modal' ? modal : null)

    const preload = injector.preloadBatchesIn(modal)
    const created = head.children.find((child) => child.rel === 'stylesheet')
    created.dispatchEvent('load')
    await preload

    assert.equal(injector.rootNeedsAssetLoading(modal), false)

    await injector.prepareModal({ detail: { id: 'test-modal' } })

    assert.equal(modal.classList.contains('fff-flex-fields-assets-pending'), false)
    assert.equal(modal.classList.contains('fff-flex-fields-assets-ready'), false)
    assert.equal(injector.rootNeedsAssetLoading(modal), false)
})

test('prepareModal applies pending skeleton state when modal assets still need loading', async () => {
    const { document, window, head } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const modal = createElement('div')
    modal.classList.add('fi-modal')
    modal.id = 'pending-modal'

    const batch = createElement('span')
    batch.attributes = {
        'data-fff-asset-batch': '',
        'data-fff-stylesheets': '["/css/janczakb/filament-flex-fields/flex-fields-phone-field.css"]',
        'data-fff-chunks': '[]',
    }
    modal.children.push(batch)
    modal.querySelectorAll = (selector) => (selector === '[data-fff-asset-batch]' ? [batch] : [])

    document.getElementById = (id) => (id === 'pending-modal' ? modal : null)

    const modalPromise = injector.prepareModal({ detail: { id: 'pending-modal' } })

    assert.equal(modal.classList.contains('fff-flex-fields-assets-pending'), true)

    const created = head.children.find((child) => child.rel === 'stylesheet')
    created.dispatchEvent('load')

    await modalPromise

    assert.equal(modal.classList.contains('fff-flex-fields-assets-pending'), false)
    assert.equal(modal.classList.contains('fff-flex-fields-assets-ready'), true)
})

test('pending skeleton auto-releases within MAX_PENDING_VISIBLE_MS even if CSS is still loading', async () => {
    const { document, window, head } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const modal = createElement('div')
    modal.classList.add('fi-modal')
    modal.id = 'slow-pending-modal'

    const batch = createElement('span')
    batch.attributes = {
        'data-fff-asset-batch': '',
        'data-fff-stylesheets': '["/css/janczakb/filament-flex-fields/flex-fields-schedule-field.css"]',
        'data-fff-chunks': '[]',
    }
    modal.children.push(batch)
    modal.querySelectorAll = (selector) => (selector === '[data-fff-asset-batch]' ? [batch] : [])

    document.getElementById = (id) => (id === 'slow-pending-modal' ? modal : null)

    const modalPromise = injector.prepareModal({ detail: { id: 'slow-pending-modal' } })

    assert.equal(modal.classList.contains('fff-flex-fields-assets-pending'), true)

    await new Promise((resolve) => setTimeout(resolve, 80))
    assert.equal(modal.classList.contains('fff-flex-fields-assets-pending'), true)

    await new Promise((resolve) => setTimeout(resolve, 280))

    assert.equal(modal.classList.contains('fff-flex-fields-assets-pending'), false)
    assert.equal(modal.classList.contains('fff-flex-fields-assets-ready'), true)

    const created = head.children.find((child) => child.rel === 'stylesheet')
    assert.ok(created)
    created.dispatchEvent('load')

    await modalPromise
})

test('protected stylesheet links are never removed during dedupe', async () => {
    const { document, window, head } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const href = 'https://panel.test/css/janczakb/filament-flex-fields/flex-fields-phone-field.css'

    const protectedLink = createLink({
        href,
        attributes: {
            'data-fff-stylesheet': 'phone-field',
        },
    })

    head.appendChild(protectedLink)
    protectedLink.dispatchEvent('load')

    await injector.ensureAssets(document)

    assert.equal(head.children.includes(protectedLink), true)
})

test('loadStylesheet resolves immediately when an existing inline link is already loaded', async () => {
    const { document, window, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const href = '/css/janczakb/filament-flex-fields/flex-fields-phone-field.css'

    const existing = createLink({
        href,
        attributes: {
            'data-fff-stylesheet': 'phone-field',
        },
    })
    existing.sheet = {}
    body.appendChild(existing)

    await injector.loadStylesheet(href)

    assert.equal(injector.isStylesheetLoaded(href), true)
    assert.equal(body.children.filter((child) => child.rel === 'stylesheet').length, 1)
})

test('handleMorphUpdated releases pending modal state when inline stylesheet is already loaded', async () => {
    const { document, window, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const modal = createElement('div')
    modal.classList.add('fi-modal')

    const el = createElement('div')
    el.parentElement = modal
    modal.children.push(el)

    const href = '/css/janczakb/filament-flex-fields/flex-fields-phone-field.css'

    const batch = createElement('span')
    batch.attributes = {
        'data-fff-asset-batch': '',
        'data-fff-stylesheets': `["${href}"]`,
        'data-fff-chunks': '[]',
    }
    el.children.push(batch)
    el.querySelectorAll = (selector) => (selector === '[data-fff-asset-batch]' ? [batch] : [])

    const toEl = createElement('div')
    toEl.children.push(batch)
    toEl.querySelectorAll = (selector) => (selector === '[data-fff-asset-batch]' ? [batch] : [])

    injector.handleMorphUpdating({ el, toEl })

    const inline = createLink({
        href,
        attributes: {
            'data-fff-stylesheet': 'phone-field',
        },
    })
    inline.sheet = {}
    body.appendChild(inline)

    await injector.handleMorphUpdated({ el })

    assert.equal(modal.classList.contains('fff-flex-fields-assets-pending'), false)
    assert.equal(modal.classList.contains('fff-flex-fields-assets-ready'), true)
})

test('skeleton demo keeps pending visible for the minimum display duration while CSS loads', async () => {
    const { document, window, head } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const demo = installPlaygroundSkeletonDemo(injector, { window })
    const href = '/css/janczakb/filament-flex-fields/flex-fields-schedule-field.css'

    demo.enable()
    assert.equal(demo.isEnabled(), true)

    const modal = createElement('div')
    modal.classList.add('fi-modal')
    document.body.appendChild(modal)

    const el = createElement('div')
    el.parentElement = modal
    modal.children.push(el)

    const inline = createLink({
        href,
        attributes: {
            'data-fff-stylesheet': 'schedule-field',
        },
    })

    const batch = createElement('span')
    batch.attributes = {
        'data-fff-asset-batch': '',
        'data-fff-stylesheets': `["${href}"]`,
        'data-fff-chunks': '[]',
    }
    el.children.push(batch)
    el.querySelectorAll = (selector) => {
        if (selector === '[data-fff-asset-batch]') {
            return [batch]
        }

        if (selector.includes('stylesheet') || selector.includes('modulepreload')) {
            return el.children.filter((child) => child.rel === 'stylesheet' || child.rel === 'modulepreload')
        }

        return []
    }

    const toEl = createElement('div')
    toEl.children.push(batch)
    toEl.querySelectorAll = (selector) => (selector === '[data-fff-asset-batch]' ? [batch] : [])

    injector.handleMorphUpdating({ el, toEl })

    assert.equal(modal.classList.contains('fff-flex-fields-assets-pending'), true)

    el.children.push(inline)
    inline.parentElement = el

    const morphPromise = injector.handleMorphUpdated({ el })

    assert.equal(el.children.includes(inline), true)

    const created = head.children.find((child) => child.rel === 'stylesheet') ?? inline
    created.sheet = {}
    created.dispatchEvent('load')

    await new Promise((resolve) => setTimeout(resolve, 100))
    assert.equal(modal.classList.contains('fff-flex-fields-assets-pending'), true)
    assert.equal(head.children.some((child) => child.href === href), true)

    await new Promise((resolve) => setTimeout(resolve, 350))

    await morphPromise

    assert.equal(modal.classList.contains('fff-flex-fields-assets-pending'), false)
    assert.equal(modal.classList.contains('fff-flex-fields-assets-ready'), true)
})

test('ensureAssets waits for in-flight loads after batch markers were already consumed', async () => {
    const { document, window, head } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const href = '/css/janczakb/filament-flex-fields/flex-fields-schedule-field.css'

    const loadPromise = injector.loadStylesheet(href)

    let ensureResolved = false
    const ensurePromise = injector.ensureAssets(document).then(() => {
        ensureResolved = true
    })

    await new Promise((resolve) => setTimeout(resolve, 50))
    assert.equal(ensureResolved, false)

    const created = head.children.find((child) => child.rel === 'stylesheet')
    assert.ok(created)
    created.dispatchEvent('load')

    await loadPromise
    await ensurePromise

    assert.equal(ensureResolved, true)
    assert.equal(injector.isStylesheetLoaded(href), true)
    assert.equal(head.children.some((child) => child.rel === 'stylesheet'), true)
})

test('ensureAssets unblocks pending targets even when a stylesheet fails to load', async () => {
    const { document, window, head } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    const modal = createElement('div')
    modal.classList.add('fi-modal', 'fff-flex-fields-assets-pending')

    const batch = createElement('span')
    batch.attributes = {
        'data-fff-asset-batch': '',
        'data-fff-stylesheets': '["/css/janczakb/filament-flex-fields/missing.css"]',
        'data-fff-chunks': '[]',
    }

    document.body.children.push(modal)
    modal.children.push(batch)
    modal.querySelectorAll = (selector) => (selector === '[data-fff-asset-batch]' ? [batch] : [])

    const pending = injector.ensureAssets(modal)
    const created = head.children.find((child) => child.rel === 'stylesheet')
    created.dispatchEvent('error')

    await pending

    await injector.releasePendingState(modal)

    assert.equal(modal.classList.contains('fff-flex-fields-assets-pending'), false)
    assert.equal(modal.classList.contains('fff-flex-fields-assets-ready'), true)
})

test('isStylesheetLoaded forgets stale cache after Livewire removes the head link (resource tabs)', async () => {
    const { document, window, head } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const href = '/css/janczakb/filament-flex-fields/flex-fields-item-card.css'

    const firstLoad = injector.loadStylesheet(href)
    const first = head.children.find((child) => child.rel === 'stylesheet')
    assert.ok(first)
    first.dispatchEvent('load')
    await firstLoad

    assert.equal(injector.isStylesheetLoaded(href), true)

    // Simulate wire:navigate / Filament resource tab: Livewire drops navigate-tracked CSS.
    first.remove()

    assert.equal(injector.isStylesheetLoaded(href), false)

    const secondLoad = injector.loadStylesheet(href)
    const second = head.children.find((child) => child.rel === 'stylesheet')
    assert.ok(second)
    second.dispatchEvent('load')
    await secondLoad

    assert.equal(injector.isStylesheetLoaded(href), true)
})

test('handleLivewireNavigated reloads missing CSS from asset batches after SPA tab swap', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const href = '/css/janczakb/filament-flex-fields/flex-fields-item-card.css'

    const previousLoad = injector.loadStylesheet(href)
    const previous = head.children.find((child) => child.rel === 'stylesheet')
    previous.dispatchEvent('load')
    await previousLoad
    previous.remove()

    assert.equal(injector.isStylesheetLoaded(href), false)

    const batch = createAssetBatch([href])
    body.appendChild(batch)

    const navigated = injector.handleLivewireNavigated()
    const created = head.children.find((child) => child.rel === 'stylesheet')
    assert.ok(created)
    created.dispatchEvent('load')

    await navigated

    assert.equal(injector.isStylesheetLoaded(href), true)
    assert.equal(head.children.some((child) => child.href?.includes('item-card')), true)
})

test('enterprise: page-only boot loads page batches and never duplicates stylesheets', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const flexHref = '/css/janczakb/filament-flex-fields/flex-fields-flex-text-input.css'

    const page = createElement('form')
    page.appendChild(createAssetBatch([flexHref]))
    page.appendChild(createAssetBatch([flexHref]))
    body.appendChild(page)

    const ensure = injector.ensureAssets(document, { pageOnly: true })
    await flushStylesheetLoads(head)
    await ensure

    assert.equal(stylesheetHrefs(head).filter((href) => href.includes('flex-text-input')).length, 1)
    assert.equal(injector.isStylesheetLoaded(flexHref), true)
    assert.equal(injector.getConsumerGraph().getRefCount(normalizeAssetUrl(flexHref, document.baseURI)) > 0, true)
})

test('enterprise: modal close uninstalls modal-only assets but retains shared page assets', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const flexHref = '/css/janczakb/filament-flex-fields/flex-fields-flex-text-input.css'
    const switchHref = '/css/janczakb/filament-flex-fields/flex-fields-switch.css'

    const page = createElement('form')
    page.appendChild(createAssetBatch([flexHref]))
    body.appendChild(page)

    const pageEnsure = injector.ensureAssets(document, { pageOnly: true })
    await flushStylesheetLoads(head)
    await pageEnsure

    const modal = createElement('div')
    modal.id = 'action-modal'
    modal.classList.add('fi-modal', 'fi-modal-open')
    modal.appendChild(createAssetBatch([flexHref, switchHref]))
    body.appendChild(modal)
    document.getElementById = (id) => (id === 'action-modal' ? modal : null)

    const modalEnsure = injector.prepareModal({ detail: { id: 'action-modal' } })
    await flushStylesheetLoads(head)
    await modalEnsure

    assert.equal(stylesheetHrefs(head).filter((href) => href.includes('flex-text-input')).length, 1)
    assert.equal(stylesheetHrefs(head).filter((href) => href.includes('switch')).length, 1)

    modal.classList.remove('fi-modal-open')
    await injector.cleanupClosedModalPendingState({ detail: { id: 'action-modal' } })

    assert.equal(stylesheetHrefs(head).some((href) => href.includes('flex-text-input')), true)
    assert.equal(stylesheetHrefs(head).some((href) => href.includes('switch')), false)
    assert.equal(injector.isStylesheetLoaded(flexHref), true)
    assert.equal(injector.isStylesheetLoaded(switchHref), false)
})

test('enterprise: pageOnly ensure ignores modal batches so closed modal CSS is not sticky-retained by page', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const pageHref = '/css/janczakb/filament-flex-fields/flex-fields-item-card.css'
    const modalOnlyHref = '/css/janczakb/filament-flex-fields/flex-fields-switch.css'

    const page = createElement('form')
    page.appendChild(createAssetBatch([pageHref]))
    body.appendChild(page)

    const modal = createElement('div')
    modal.id = 'orphaned-modal'
    modal.classList.add('fi-modal')
    modal.appendChild(createAssetBatch([modalOnlyHref]))
    body.appendChild(modal)

    const ensure = injector.ensureAssets(document, { pageOnly: true })
    await flushStylesheetLoads(head)
    await ensure

    assert.equal(stylesheetHrefs(head).some((href) => href.includes('item-card')), true)
    assert.equal(stylesheetHrefs(head).some((href) => href.includes('switch')), false)
    assert.equal(
        injector.getConsumerGraph().getRefCount(normalizeAssetUrl(modalOnlyHref, document.baseURI)) > 0,
        false,
    )
})

test('enterprise: two open modals share an asset; closing one does not uninstall while the other retains it', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const switchHref = '/css/janczakb/filament-flex-fields/flex-fields-switch.css'

    const first = createElement('div')
    first.id = 'modal-a'
    first.classList.add('fi-modal', 'fi-modal-open')
    first.appendChild(createAssetBatch([switchHref]))
    body.appendChild(first)

    const second = createElement('div')
    second.id = 'modal-b'
    second.classList.add('fi-modal', 'fi-modal-open')
    second.appendChild(createAssetBatch([switchHref]))
    body.appendChild(second)

    document.getElementById = (id) => {
        if (id === 'modal-a') {
            return first
        }

        if (id === 'modal-b') {
            return second
        }

        return null
    }

    const openA = injector.prepareModal({ detail: { id: 'modal-a' } })
    await flushStylesheetLoads(head)
    await openA

    const openB = injector.prepareModal({ detail: { id: 'modal-b' } })
    await flushStylesheetLoads(head)
    await openB

    assert.equal(stylesheetHrefs(head).filter((href) => href.includes('switch')).length, 1)

    first.classList.remove('fi-modal-open')
    await injector.cleanupClosedModalPendingState({ detail: { id: 'modal-a' } })

    assert.equal(stylesheetHrefs(head).some((href) => href.includes('switch')), true)
    assert.equal(injector.isStylesheetLoaded(switchHref), true)

    second.classList.remove('fi-modal-open')
    await injector.cleanupClosedModalPendingState({ detail: { id: 'modal-b' } })

    assert.equal(stylesheetHrefs(head).some((href) => href.includes('switch')), false)
    assert.equal(injector.isStylesheetLoaded(switchHref), false)
})

test('enterprise: SPA tab navigation rebuilds page retain set and does not keep previous tab modal leftovers', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const editHref = '/css/janczakb/filament-flex-fields/flex-fields-flex-text-input.css'
    const modalOnlyHref = '/css/janczakb/filament-flex-fields/flex-fields-switch.css'
    const videoHref = '/css/janczakb/filament-flex-fields/flex-fields-item-card.css'

    const editPage = createElement('form')
    editPage.appendChild(createAssetBatch([editHref]))
    body.appendChild(editPage)

    const editEnsure = injector.ensureAssets(document, { pageOnly: true })
    await flushStylesheetLoads(head)
    await editEnsure

    const modal = createElement('div')
    modal.id = 'edit-action'
    modal.classList.add('fi-modal', 'fi-modal-open')
    modal.appendChild(createAssetBatch([modalOnlyHref]))
    body.appendChild(modal)
    document.getElementById = (id) => (id === 'edit-action' ? modal : null)

    const modalEnsure = injector.prepareModal({ detail: { id: 'edit-action' } })
    await flushStylesheetLoads(head)
    await modalEnsure

    assert.equal(stylesheetHrefs(head).some((href) => href.includes('switch')), true)

    // Simulate Livewire wire:navigate to resource Video tab: drop head CSS + page DOM.
    for (const child of [...head.children]) {
        child.remove()
    }

    editPage.remove()
    modal.remove()
    body.children.length = 0

    const videoPage = createElement('form')
    videoPage.appendChild(createAssetBatch([videoHref]))
    body.appendChild(videoPage)

    assert.equal(injector.isStylesheetLoaded(editHref), false)
    assert.equal(injector.isStylesheetLoaded(videoHref), false)

    const navigated = injector.handleLivewireNavigated()
    await flushStylesheetLoads(head)
    await navigated

    assert.equal(stylesheetHrefs(head).some((href) => href.includes('item-card')), true)
    assert.equal(stylesheetHrefs(head).some((href) => href.includes('flex-text-input')), false)
    assert.equal(stylesheetHrefs(head).some((href) => href.includes('switch')), false)
    assert.equal(injector.isStylesheetLoaded(videoHref), true)
    assert.equal(
        injector.getConsumerGraph().getRefCount(normalizeAssetUrl(videoHref, document.baseURI)) > 0,
        true,
    )
    assert.equal(
        injector.getConsumerGraph().getRefCount(normalizeAssetUrl(modalOnlyHref, document.baseURI)) > 0,
        false,
    )
})

test('enterprise: page morph does not claim modal-only batches sitting in a closed modal shell', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const pageHref = '/css/janczakb/filament-flex-fields/flex-fields-flex-text-input.css'
    const modalOnlyHref = '/css/janczakb/filament-flex-fields/flex-fields-switch.css'

    const pageField = createElement('div')
    pageField.classList.add('fi-fo-field-wrp')
    pageField.appendChild(createAssetBatch([pageHref]))
    body.appendChild(pageField)

    const modal = createElement('div')
    modal.classList.add('fi-modal')
    modal.appendChild(createAssetBatch([modalOnlyHref]))
    body.appendChild(modal)

    const morph = injector.handleMorphUpdated({ el: pageField })
    await flushStylesheetLoads(head)
    await morph

    assert.equal(stylesheetHrefs(head).some((href) => href.includes('flex-text-input')), true)
    assert.equal(stylesheetHrefs(head).some((href) => href.includes('switch')), false)
    assert.equal(
        injector.getConsumerGraph().getRefCount(normalizeAssetUrl(modalOnlyHref, document.baseURI)) > 0,
        false,
    )
})

test('enterprise: stacked nested modals keep parent assets while child is open and after child closes', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const pageFlexHref = '/css/janczakb/filament-flex-fields/flex-fields-flex-text-input.css'
    const parentSwitchHref = '/css/janczakb/filament-flex-fields/flex-fields-switch.css'
    const childRatingHref = '/css/janczakb/filament-flex-fields/flex-fields-rating-field.css'

    const page = createElement('form')
    page.appendChild(createAssetBatch([pageFlexHref]))
    body.appendChild(page)

    const pageEnsure = injector.ensureAssets(document, { pageOnly: true })
    await flushStylesheetLoads(head)
    await pageEnsure

    const parent = createElement('div')
    parent.id = 'parent-action-modal'
    parent.classList.add('fi-modal', 'fi-modal-open')
    parent.appendChild(createAssetBatch([pageFlexHref, parentSwitchHref]))
    body.appendChild(parent)

    const child = createElement('div')
    child.id = 'child-action-modal'
    child.classList.add('fi-modal')
    child.appendChild(createAssetBatch([childRatingHref]))
    body.appendChild(child)

    document.getElementById = (id) => {
        if (id === 'parent-action-modal') {
            return parent
        }

        if (id === 'child-action-modal') {
            return child
        }

        return null
    }

    const openParent = injector.prepareModal({ detail: { id: 'parent-action-modal' } })
    await flushStylesheetLoads(head)
    await openParent

    assert.deepEqual(injector.getModalOpenStack(), ['modal:parent-action-modal'])
    assert.equal(stylesheetHrefs(head).some((href) => href.includes('switch')), true)

    // Filament nested action: parent loses fi-modal-open while child is shown.
    parent.classList.remove('fi-modal-open')
    child.classList.add('fi-modal-open')

    const openChild = injector.prepareModal({ detail: { id: 'child-action-modal' } })
    await flushStylesheetLoads(head)
    await openChild

    assert.deepEqual(injector.getModalOpenStack(), [
        'modal:parent-action-modal',
        'modal:child-action-modal',
    ])
    assert.equal(stylesheetHrefs(head).filter((href) => href.includes('flex-text-input')).length, 1)
    assert.equal(stylesheetHrefs(head).some((href) => href.includes('switch')), true)
    assert.equal(stylesheetHrefs(head).some((href) => href.includes('rating-field')), true)

    // Closing the child must NOT uninstall parent-only switch — parent returns next.
    child.classList.remove('fi-modal-open')
    await injector.cleanupClosedModalPendingState({ detail: { id: 'child-action-modal' } })
    parent.classList.add('fi-modal-open')

    assert.deepEqual(injector.getModalOpenStack(), ['modal:parent-action-modal'])
    assert.equal(stylesheetHrefs(head).some((href) => href.includes('switch')), true)
    assert.equal(stylesheetHrefs(head).some((href) => href.includes('rating-field')), false)
    assert.equal(stylesheetHrefs(head).some((href) => href.includes('flex-text-input')), true)

    // Closing the parent finally drops switch; page flex stays.
    parent.classList.remove('fi-modal-open')
    await injector.cleanupClosedModalPendingState({ detail: { id: 'parent-action-modal' } })

    assert.deepEqual(injector.getModalOpenStack(), [])
    assert.equal(stylesheetHrefs(head).some((href) => href.includes('switch')), false)
    assert.equal(stylesheetHrefs(head).some((href) => href.includes('flex-text-input')), true)
})

test('enterprise: modal-closed without id pops only the top stacked modal (LIFO)', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const parentHref = '/css/janczakb/filament-flex-fields/flex-fields-switch.css'
    const childHref = '/css/janczakb/filament-flex-fields/flex-fields-rating-field.css'

    const parent = createElement('div')
    parent.id = 'stack-a'
    parent.classList.add('fi-modal', 'fi-modal-open')
    parent.appendChild(createAssetBatch([parentHref]))
    body.appendChild(parent)

    const child = createElement('div')
    child.id = 'stack-b'
    child.classList.add('fi-modal')
    child.appendChild(createAssetBatch([childHref]))
    body.appendChild(child)

    document.getElementById = (id) => {
        if (id === 'stack-a') {
            return parent
        }

        if (id === 'stack-b') {
            return child
        }

        return null
    }

    const openParent = injector.prepareModal({ detail: { id: 'stack-a' } })
    await flushStylesheetLoads(head)
    await openParent

    parent.classList.remove('fi-modal-open')
    child.classList.add('fi-modal-open')

    const openChild = injector.prepareModal({ detail: { id: 'stack-b' } })
    await flushStylesheetLoads(head)
    await openChild

    await injector.cleanupClosedModalPendingState({})

    assert.deepEqual(injector.getModalOpenStack(), ['modal:stack-a'])
    assert.equal(stylesheetHrefs(head).some((href) => href.includes('switch')), true)
    assert.equal(stylesheetHrefs(head).some((href) => href.includes('rating-field')), false)
})

test('enterprise: returning to parent via x-modal-opened does not duplicate head links', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const parentHref = '/css/janczakb/filament-flex-fields/flex-fields-switch.css'

    const parent = createElement('div')
    parent.id = 'return-parent'
    parent.classList.add('fi-modal', 'fi-modal-open')
    parent.appendChild(createAssetBatch([parentHref]))
    body.appendChild(parent)

    document.getElementById = (id) => (id === 'return-parent' ? parent : null)

    const firstOpen = injector.prepareModal({ detail: { id: 'return-parent' } })
    await flushStylesheetLoads(head)
    await firstOpen

    parent.classList.remove('fi-modal-open')
    // Nested child would open here… then Filament resurfaces the parent:
    parent.classList.add('fi-modal-open')
    parent.appendChild(createAssetBatch([parentHref]))

    const reopen = injector.prepareModal({ detail: { id: 'return-parent' } })
    await flushStylesheetLoads(head)
    await reopen

    assert.deepEqual(injector.getModalOpenStack(), ['modal:return-parent'])
    assert.equal(stylesheetHrefs(head).filter((href) => href.includes('switch')).length, 1)
})

test('background preload defers when saveData or reduced motion is enabled', async () => {
    const { document, window, head } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })

    window.matchMedia = () => ({ matches: true })
    assert.equal(prefersReducedMotion(window), true)
    assert.equal(shouldDeferBackgroundAssetPreload(window), true)

    const batch = createElement('span')
    batch.attributes = {
        'data-fff-asset-batch': '',
        'data-fff-stylesheets': '["/css/janczakb/filament-flex-fields/flex-fields-phone-field.css"]',
        'data-fff-chunks': '[]',
    }
    batch.getBoundingClientRect = () => ({
        top: 0,
        left: 0,
        bottom: 100,
        right: 100,
    })

    document.querySelectorAll = (selector) => (
        selector === '[data-fff-asset-batch]' ? [batch] : []
    )

    await injector.preloadVisibleBatchesIn(document)

    assert.equal(head.children.length, 0)

    window.matchMedia = () => ({ matches: false })
    window.navigator = { connection: { saveData: true } }

    assert.equal(connectionSaveDataEnabled(window), true)
    assert.equal(shouldDeferBackgroundAssetPreload(window), true)

    await injector.preloadVisibleBatchesIn(document)

    assert.equal(head.children.length, 0)
})

test('L5: runtime critical preload hook loads overlay stylesheets for select-family acquire path', async () => {
    const { document, window, head, body } = createDom()
    const injector = createFlexFieldAssetInjector({ document, window })
    const selectHref = '/css/janczakb/filament-flex-fields/flex-fields-select-field.css'
    const overlayHref = '/css/janczakb/filament-flex-fields/flex-fields-overlay-runtime.css'
    const menuHref = '/css/janczakb/filament-flex-fields/flex-fields-teleported-menu.css'

    body.appendChild(createAssetBatch([selectHref, overlayHref, menuHref], [], {
        consumerComponent: 'select-field',
        livewireKey: 'select-1',
    }))

    injector.getConsumerGraph().resyncFromDom(document)
    await flushStylesheetLoads(head)

    assert.equal(typeof injector.injectCriticalPreloadsForSelectFamily, 'function')
    assert.equal(stylesheetHrefs(head).some((href) => href.includes('select-field')), true)
    assert.equal(stylesheetHrefs(head).some((href) => href.includes('overlay-runtime')), true)
    assert.equal(stylesheetHrefs(head).some((href) => href.includes('teleported-menu')), true)
    assert.equal(head.children.some((child) => child.rel === 'preload'), false)

    await injector.injectCriticalPreloadsForSelectFamily()
    await flushStylesheetLoads(head)

    assert.equal(stylesheetHrefs(head).filter((href) => href.includes('overlay-runtime')).length, 1)
})
