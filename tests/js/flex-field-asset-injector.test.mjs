import assert from 'node:assert/strict'
import test from 'node:test'

import {
    createFlexFieldAssetInjector,
    normalizeAssetUrl,
} from '../../resources/js/core/flex-field-asset-injector.js'
import { installPlaygroundSkeletonDemo } from '../../resources/js/playground/skeleton-demo.js'

function createClassList() {
    const classes = new Set()

    return {
        add: (name) => classes.add(name),
        remove: (name) => classes.delete(name),
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
            if (link.parentElement?.contains?.(link)) {
                const index = link.parentElement.children.indexOf(link)
                link.parentElement.children.splice(index, 1)
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

    return {
        tagName,
        children: [],
        classList: createClassList(),
        parentElement: null,
        dataset: {},
        id: '',
        closest(selector) {
            if (selector === '.fi-modal' && this.classList.contains('fi-modal')) {
                return this
            }

            return this.parentElement?.closest?.(selector) ?? null
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

            if (selector === '.fi-modal') {
                return this.classList.contains('fi-modal')
            }

            return false
        },
        setAttribute() {},
        getAttribute(name) {
            return this.attributes?.[name] ?? null
        },
        hasAttribute(name) {
            return Object.hasOwn(this.attributes ?? {}, name)
        },
        remove() {},
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

            const consider = (node) => {
                if (selector.includes('stylesheet') && node.rel === 'stylesheet' && node.href?.includes('filament-flex-fields')) {
                    matches.push(node)
                }

                if (selector.includes('modulepreload') && node.rel === 'modulepreload' && node.href?.includes('filament-flex-fields')) {
                    matches.push(node)
                }

                if (selector.includes('fff-flex-fields-assets-pending') && node.classList?.contains('fff-flex-fields-assets-pending')) {
                    if (selector.includes(':not(.fi-modal)')) {
                        if (! node.classList.contains('fi-modal')) {
                            matches.push(node)
                        }
                    } else {
                        matches.push(node)
                    }
                }

                if (selector === '.fi-modal.fff-flex-fields-assets-pending'
                    && node.classList?.contains('fi-modal')
                    && node.classList.contains('fff-flex-fields-assets-pending')) {
                    matches.push(node)
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

test('handleMorphUpdating skips pending state when morph target is outside a modal', () => {
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

    assert.equal(toEl.classList.contains('fff-flex-fields-assets-pending'), false)
    assert.equal(injector.hasPendingState(el), false)
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

    await new Promise((resolve) => setTimeout(resolve, 1800))

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
