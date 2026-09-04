const STYLESHEET_SELECTOR = 'link[rel="stylesheet"][href*="filament-flex-fields"]'
const CHUNK_SELECTOR = 'link[rel="modulepreload"][href*="filament-flex-fields"]'

/** Hard cap for pending skeleton visibility (modal / morph). Assets may still load after. */
export const MAX_PENDING_VISIBLE_MS = 300

const ASSETS_SKELETON_CLASS = 'fff-flex-fields-assets-skeleton'

import { createConsumerGraph } from './flex-field-consumer-graph.js'
import { createPrefetchEngine } from './flex-field-prefetch-engine.js'
import { bootSegmentOverflowElements } from './segment-overflow-position.js'
import { bootTimezoneBrowserSsrDefaults } from './timezone-browser-ssr-boot.js'
import './flex-fff-load-directive.js'

const embeddedAssetRegistry = typeof __FFF_EMBEDDED_ASSET_REGISTRY__ !== 'undefined'
    ? __FFF_EMBEDDED_ASSET_REGISTRY__
    : {}

export { bootSegmentOverflowElements }
export { bootTimezoneBrowserSsrDefaults }

export function normalizeAssetUrl(url, baseUri = typeof document !== 'undefined' ? document.baseURI : 'http://localhost/') {
    if (!url) {
        return ''
    }

    try {
        return new URL(url, baseUri).href
    } catch {
        return String(url)
    }
}

export function prefersReducedMotion(windowRef = typeof window !== 'undefined' ? window : undefined) {
    try {
        return windowRef?.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches === true
    } catch {
        return false
    }
}

export function connectionSaveDataEnabled(windowRef = typeof window !== 'undefined' ? window : undefined) {
    try {
        const navigatorRef = windowRef?.navigator

        if (!navigatorRef) {
            return false
        }

        const connection = navigatorRef.connection
            ?? navigatorRef.mozConnection
            ?? navigatorRef.webkitConnection

        return connection?.saveData === true
    } catch {
        return false
    }
}

export function shouldDeferBackgroundAssetPreload(windowRef = typeof window !== 'undefined' ? window : undefined) {
    return prefersReducedMotion(windowRef) || connectionSaveDataEnabled(windowRef)
}

export function createFlexFieldAssetInjector({ document, window } = {}) {
    if (!document || !window) {
        throw new Error('FlexField asset injector requires document and window.')
    }

    const loadedStylesheets = new Set()
    const loadedChunks = new Set()
    const inflightRequests = new Map()
    const stylesheetIndex = new Map()
    const chunkIndex = new Map()
    const pendingMorphTargets = new WeakSet()
    /** @type {WeakMap<object, number>} */
    const pendingMaxReleaseTimers = new WeakMap()

    /**
     * Open Filament modal stack (nested actions). Parent stays retained even when
     * Filament temporarily drops `fi-modal-open` while a child modal is visible.
     */
    const modalOpenStack = []
    const anonymousModalKeys = new WeakMap()
    let anonymousModalSequence = 0
    let criticalPreloadsInjected = false

    /** Select-family pickers that benefit from overlay-runtime + teleported-menu preloads (L5). */
    const SELECT_FAMILY_COMPONENTS = new Set([
        'select-field',
        'user-select',
        'icon-picker-field',
        'country-field',
        'timezone-field',
        'currency-field',
        'phone-field',
    ])

    const CRITICAL_PRELOAD_COMPONENTS = ['overlay-runtime', 'teleported-menu']

    const markFff = (name, detail = undefined) => {
        if (typeof window?.performance?.mark !== 'function') {
            return
        }

        try {
            window.performance.mark(name, detail ? { detail } : undefined)
        } catch {
            // ignore unsupported detail payloads
        }
    }

    const injectorHooks = {
        shouldBatchTriggerPending(batch, defaultCheck) {
            return defaultCheck(batch)
        },
        markPendingStarted() { },
        getPendingReleaseDelayMs() {
            return 0
        },
        shouldSkipBackgroundPreload() {
            return shouldDeferBackgroundAssetPreload(window)
        },
    }

    const registerInjectorHooks = (partial = {}) => {
        Object.assign(injectorHooks, partial)
    }

    const isInlineEmitAssetLink = (link) => {
        return link?.hasAttribute?.('data-fff-stylesheet')
            || link?.hasAttribute?.('data-fff-alpine-chunk')
    }

    const forgetLoadedAsset = (link) => {
        const url = normalizeAssetUrl(link.href, document.baseURI)

        if (!url) {
            return
        }

        if (link.rel === 'stylesheet') {
            loadedStylesheets.delete(url)
            stylesheetIndex.delete(url)
        } else {
            loadedChunks.delete(url)
            chunkIndex.delete(url)
        }
    }

    const stripInlineEmitAssets = (root = document) => {
        const scope = root?.querySelectorAll ? root : document
        const isDocumentRoot = scope === document

        for (const link of [...document.querySelectorAll(`${STYLESHEET_SELECTOR}, ${CHUNK_SELECTOR}`)]) {
            if (!isInlineEmitAssetLink(link) || link.hasAttribute('data-fff-playground-bundle')) {
                continue
            }

            if (!isDocumentRoot && typeof scope.contains === 'function' && !scope.contains(link)) {
                continue
            }

            forgetLoadedAsset(link)
            link.remove()
        }
    }

    const batchHasAssetUrls = (batch) => {
        return parseJsonAttribute(batch, 'data-fff-stylesheets').length > 0
            || parseJsonAttribute(batch, 'data-fff-chunks').length > 0
    }

    const rootNeedsAssetLoading = (root = document) => {
        if (!root?.querySelectorAll) {
            return false
        }

        return [...root.querySelectorAll('[data-fff-asset-batch]')].some((batch) => {
            return injectorHooks.shouldBatchTriggerPending(batch, batchNeedsLoading)
        })
    }

    const isLinkConnected = (link) => {
        if (!link) {
            return false
        }

        if (typeof link.isConnected === 'boolean') {
            return link.isConnected
        }

        return link.parentElement !== null
    }

    const indexLink = (link, index) => {
        if (!link?.href) {
            return
        }

        index.set(normalizeAssetUrl(link.href, document.baseURI), link)
    }

    const rebuildIndex = (selector, index) => {
        index.clear()

        for (const link of document.querySelectorAll(selector)) {
            if (! isLinkConnected(link)) {
                continue
            }

            indexLink(link, index)
        }
    }

    const findAssetLink = (selector, href, index) => {
        const normalizedHref = normalizeAssetUrl(href, document.baseURI)

        if (!normalizedHref) {
            return null
        }

        const cached = index.get(normalizedHref)

        if (cached && isLinkConnected(cached)) {
            return cached
        }

        if (cached) {
            index.delete(normalizedHref)
        }

        rebuildIndex(selector, index)

        return index.get(normalizedHref) ?? null
    }

    const rememberLoadedLink = (link, loadedSet, index) => {
        if (!link?.href) {
            return
        }

        const normalizedHref = normalizeAssetUrl(link.href, document.baseURI)
        loadedSet.add(normalizedHref)
        index.set(normalizedHref, link)
    }

    const forgetStylesheetUrl = (url) => {
        loadedStylesheets.delete(url)
        stylesheetIndex.delete(url)
    }

    const forgetChunkUrl = (url) => {
        loadedChunks.delete(url)
        chunkIndex.delete(url)
    }

    /**
     * Trust DOM connectivity over in-memory Sets.
     * Livewire SPA navigation (`wire:navigate`, resource tabs) can remove
     * `data-navigate-track` links while the injector still marks URLs as loaded.
     */
    const isStylesheetLoaded = (href) => {
        const normalizedHref = normalizeAssetUrl(href, document.baseURI)

        if (!normalizedHref) {
            return true
        }

        if (loadedStylesheets.has(normalizedHref)) {
            const existing = findAssetLink(STYLESHEET_SELECTOR, normalizedHref, stylesheetIndex)

            // Livewire SPA navigation may drop navigate-tracked <link> tags while
            // this Set still thinks they are loaded — verify connectivity first.
            if (existing && isLinkConnected(existing)) {
                return true
            }

            forgetStylesheetUrl(normalizedHref)
        }

        const existing = findAssetLink(STYLESHEET_SELECTOR, normalizedHref, stylesheetIndex)

        if (existing?.sheet) {
            rememberLoadedLink(existing, loadedStylesheets, stylesheetIndex)

            return true
        }

        return false
    }

    const isChunkLoaded = (href) => {
        const normalizedHref = normalizeAssetUrl(href, document.baseURI)

        if (!normalizedHref) {
            return true
        }

        if (loadedChunks.has(normalizedHref)) {
            const existing = findAssetLink(CHUNK_SELECTOR, normalizedHref, chunkIndex)

            if (existing && isLinkConnected(existing)) {
                return true
            }

            forgetChunkUrl(normalizedHref)
        }

        return false
    }

    const isStylesheetLinkReady = (link) => {
        return Boolean(link?.sheet)
    }

    const isChunkLinkReady = (url) => {
        const link = findAssetLink(CHUNK_SELECTOR, url, chunkIndex)

        if (link?.rel === 'modulepreload' && link.loaded === true) {
            return true
        }

        if (window.performance?.getEntriesByName) {
            return window.performance.getEntriesByName(url).length > 0
        }

        return false
    }

    const waitForExistingLink = (link, url, type, loadedSet, index) => {
        if (!isLinkConnected(link)) {
            index.delete(url)

            return Promise.reject(new Error(`Stale ${type} link: ${url}`))
        }

        if (type === 'stylesheet' && isStylesheetLinkReady(link)) {
            rememberLoadedLink(link, loadedSet, index)

            return Promise.resolve()
        }

        if (type === 'chunk' && isChunkLinkReady(url)) {
            rememberLoadedLink(link, loadedSet, index)

            return Promise.resolve()
        }

        if (inflightRequests.has(url)) {
            return inflightRequests.get(url)
        }

        const promise = new Promise((resolve, reject) => {
            const finish = () => {
                rememberLoadedLink(link, loadedSet, index)
                resolve()
            }

            link.addEventListener('load', finish, { once: true })

            link.addEventListener('error', () => {
                reject(new Error(`Failed to load ${type}: ${url}`))
            }, { once: true })

            if (type === 'stylesheet' && isStylesheetLinkReady(link)) {
                finish()
            } else if (type === 'chunk' && isChunkLinkReady(url)) {
                finish()
            }
        }).finally(() => {
            inflightRequests.delete(url)
        })

        inflightRequests.set(url, promise)

        return promise
    }

    const moveLinkToHead = (link) => {
        if (!link || link.parentElement === document.head) {
            return link
        }

        document.head.appendChild(link)

        return link
    }

    const appendAssetLink = (link) => {
        document.head.appendChild(link)
    }

    /**
     * Rebuild loaded caches from the current document only.
     * Safe after Filament resource-tab / wire:navigate swaps which drop
     * previously injected or tracked stylesheets from `<head>`.
     */
    const resyncLoadedAssetsFromDocument = () => {
        loadedStylesheets.clear()
        loadedChunks.clear()
        stylesheetIndex.clear()
        chunkIndex.clear()
        inflightRequests.clear()

        for (const link of document.querySelectorAll(STYLESHEET_SELECTOR)) {
            if (!isLinkConnected(link) || !link.href) {
                continue
            }

            const url = normalizeAssetUrl(link.href, document.baseURI)

            if (!url) {
                continue
            }

            stylesheetIndex.set(url, link)

            if (link.sheet) {
                loadedStylesheets.add(url)
            }
        }

        for (const link of document.querySelectorAll(CHUNK_SELECTOR)) {
            if (!isLinkConnected(link) || !link.href) {
                continue
            }

            const url = normalizeAssetUrl(link.href, document.baseURI)

            if (!url) {
                continue
            }

            chunkIndex.set(url, link)
            loadedChunks.add(url)
        }
    }

    const purgeLazyAssets = () => {
        for (const link of [...document.querySelectorAll(`${STYLESHEET_SELECTOR}, ${CHUNK_SELECTOR}`)]) {
            if (isProtectedLink(link)) {
                continue
            }

            const url = normalizeAssetUrl(link.href, document.baseURI)

            if (!url) {
                continue
            }

            if (link.rel === 'stylesheet') {
                forgetStylesheetUrl(url)
            } else {
                forgetChunkUrl(url)
            }

            link.remove()
        }

        inflightRequests.clear()
    }

    const loadAsset = (href, type, loadedSet, index, selector, buildElement, { priority = 'auto' } = {}) => {
        const url = normalizeAssetUrl(href, document.baseURI)

        if (!url) {
            return Promise.resolve()
        }

        if (inflightRequests.has(url)) {
            return inflightRequests.get(url)
        }

        if (type === 'stylesheet' ? isStylesheetLoaded(url) : isChunkLoaded(url)) {
            return Promise.resolve()
        }

        const existing = findAssetLink(selector, url, index)

        if (existing) {
            if (type === 'stylesheet' && isStylesheetLinkReady(existing)) {
                rememberLoadedLink(existing, loadedSet, index)

                return Promise.resolve()
            }

            if (type === 'chunk' && isChunkLinkReady(url)) {
                rememberLoadedLink(existing, loadedSet, index)

                return Promise.resolve()
            }

            return waitForExistingLink(existing, url, type, loadedSet, index)
        }

        const promise = new Promise((resolve, reject) => {
            markFff('fff:load', { url, type })

            dropStalePreloadForUrl(url)

            const link = buildElement(url)
            link.setAttribute('data-navigate-track', '')

            if (priority === 'high' && 'fetchPriority' in link) {
                link.fetchPriority = 'high'
            }

            link.addEventListener('load', () => {
                rememberLoadedLink(link, loadedSet, index)
                ensureRefZeroHandler(url)
                resolve()
            }, { once: true })

            link.addEventListener('error', () => {
                reject(new Error(`Failed to load ${type}: ${url}`))
            }, { once: true })

            appendAssetLink(link)
        }).finally(() => {
            inflightRequests.delete(url)
        })

        inflightRequests.set(url, promise)

        return promise
    }

    const loadStylesheet = (href, componentId = null, { priority = 'auto' } = {}) => {
        return loadAsset(href, 'stylesheet', loadedStylesheets, stylesheetIndex, STYLESHEET_SELECTOR, (url) => {
            const link = document.createElement('link')
            link.rel = 'stylesheet'
            link.href = url
            link.dataset.fffInjectedStylesheet = 'true'
            link.dataset.fffManagedAsset = 'true'

            if (componentId) {
                link.dataset.fffComponent = componentId
            }

            ensureRefZeroHandler(url)

            return link
        }, { priority })
    }

    const loadChunk = (href, componentId = null, { priority = 'auto' } = {}) => {
        return loadAsset(href, 'chunk', loadedChunks, chunkIndex, CHUNK_SELECTOR, (url) => {
            const link = document.createElement('link')
            link.rel = 'modulepreload'
            link.href = url
            link.dataset.fffInjectedChunk = 'true'
            link.dataset.fffManagedAsset = 'true'

            if (componentId) {
                link.dataset.fffComponent = componentId
            }

            ensureRefZeroHandler(url)

            return link
        }, { priority })
    }

    const dedupeLinks = (selector, index, loadedSet) => {
        // Important: use a fresh scan Set. Reusing `loadedStylesheets` as the "seen"
        // set incorrectly removes the first injected <link> right after it loads
        // (URL already present in the loaded cache ≠ duplicate DOM node).
        const seenInDocument = new Set()

        for (const link of document.querySelectorAll(selector)) {
            if (!link.href || ! isLinkConnected(link)) {
                continue
            }

            const url = normalizeAssetUrl(link.href, document.baseURI)

            if (!url) {
                continue
            }

            if (isProtectedLink(link)) {
                indexLink(link, index)
                loadedSet.add(url)

                if (link.parentElement !== document.head) {
                    moveLinkToHead(link)
                }

                seenInDocument.add(url)

                continue
            }

            if (seenInDocument.has(url)) {
                // True duplicate in the document — drop the extra injected copy only.
                link.remove()

                continue
            }

            seenInDocument.add(url)
            indexLink(link, index)
            loadedSet.add(url)

            if (link.parentElement !== document.head) {
                moveLinkToHead(link)
            }
        }
    }

    const dedupeDocumentAssets = () => {
        dedupeLinks(STYLESHEET_SELECTOR, stylesheetIndex, loadedStylesheets)
        dedupeLinks(CHUNK_SELECTOR, chunkIndex, loadedChunks)
    }

    const parseJsonAttribute = (element, attribute) => {
        const raw = element?.getAttribute?.(attribute)

        if (!raw) {
            return []
        }

        try {
            const parsed = JSON.parse(raw)

            return Array.isArray(parsed) ? parsed : []
        } catch {
            return []
        }
    }

    const uniqueNormalizedUrls = (urls) => {
        return [...new Set(urls.map((url) => normalizeAssetUrl(url, document.baseURI)).filter(Boolean))]
    }

    const resolveAssetScope = (root = document) => {
        return resolvePendingTarget(root) ?? root
    }

    const resolveModalOwnerKey = (modal) => {
        if (!modal) {
            return null
        }

        if (typeof modal.id === 'string' && modal.id !== '') {
            return `modal:${modal.id}`
        }

        if (anonymousModalKeys.has(modal)) {
            return anonymousModalKeys.get(modal)
        }

        anonymousModalSequence += 1
        const key = `modal:anon-${anonymousModalSequence}`
        anonymousModalKeys.set(modal, key)

        return key
    }

    const resolveAssetOwnerKey = (root = document) => {
        if (!root || root === document) {
            return 'page'
        }

        if (root.classList?.contains?.('fi-modal')) {
            return resolveModalOwnerKey(root)
        }

        if (typeof root.closest === 'function') {
            const modal = root.closest('.fi-modal')

            if (modal) {
                return resolveModalOwnerKey(modal)
            }
        }

        return 'page'
    }

    const dropStalePreloadForUrl = (url) => {
        if (!document?.querySelectorAll) {
            return
        }

        for (const link of document.querySelectorAll('link[rel="preload"][as="style"]')) {
            if (normalizeAssetUrl(link.href, document.baseURI) === url) {
                link.remove()
            }
        }
    }

    const isStylesheetUrl = (url) => /\.css(?:\?|#|$)/i.test(String(url ?? ''))

    const loadAcquiredUrls = (componentId, urls) => {
        const loads = []

        for (const href of urls) {
            const url = normalizeAssetUrl(href, document.baseURI)

            if (!url) {
                continue
            }

            if (isStylesheetUrl(url)) {
                loads.push(loadStylesheet(url, componentId, { priority: 'high' }))
            } else {
                loads.push(loadChunk(url, componentId, { priority: 'high' }))
            }
        }

        if (loads.length === 0) {
            return Promise.resolve()
        }

        return Promise.allSettled(loads)
    }
    const injectCriticalPreloadsForSelectFamily = () => {
        if (criticalPreloadsInjected) {
            return Promise.resolve()
        }

        criticalPreloadsInjected = true

        const loads = []

        for (const componentId of CRITICAL_PRELOAD_COMPONENTS) {
            const bundle = embeddedAssetRegistry[componentId]

            if (!bundle?.stylesheets?.length) {
                continue
            }

            for (const href of bundle.stylesheets) {
                loads.push(loadStylesheet(href, componentId, { priority: 'high' }))
            }
        }

        if (loads.length === 0) {
            return Promise.resolve()
        }

        return Promise.allSettled(loads)
    }

    const crg = createConsumerGraph({
        registry: embeddedAssetRegistry,
        normalizeUrl: (url) => normalizeAssetUrl(url, document.baseURI),
        resolveSurfaceKey: resolveAssetOwnerKey,
        onAcquire: (_instanceId, componentId, urls) => {
            for (const url of urls) {
                markFff('fff:acquire', { url })
            }

            if (SELECT_FAMILY_COMPONENTS.has(componentId)) {
                void injectCriticalPreloadsForSelectFamily()
            }

            void loadAcquiredUrls(componentId, urls)
        },
        onRelease: (_instanceId, _componentId, urls) => {
            for (const url of urls) {
                markFff('fff:release', { url })
            }
        },
        isConsumerActive: (el) => {
            if (el?.isConnected === false) {
                return false
            }

            const modal = el?.closest?.('.fi-modal')

            if (!modal) {
                return true
            }

            // Modal ownership follows the LIFO open stack — not fi-modal-open alone.
            // Parent modals lose fi-modal-open while a child is open but stay retained via stack.
            // LIFO close pops the owner before Filament always removes fi-modal-open.
            return modalOpenStack.includes(resolveModalOwnerKey(modal))
        },
    })

    let resyncDomRaf = null

    const scheduleResyncFromDom = (root = document, { excludeModals = false } = {}) => {
        if (typeof window.requestAnimationFrame !== 'function') {
            crg.resyncFromDom(root)

            return
        }

        if (resyncDomRaf) {
            window.cancelAnimationFrame(resyncDomRaf)
        }

        resyncDomRaf = window.requestAnimationFrame(() => {
            resyncDomRaf = null
            crg.resyncFromDom(root)
        })
    }

    const refZeroHandlersRegistered = new Set()

    const ensureRefZeroHandler = (url) => {
        const normalized = normalizeAssetUrl(url, document.baseURI)

        if (!normalized || refZeroHandlersRegistered.has(normalized)) {
            return
        }

        refZeroHandlersRegistered.add(normalized)
        crg.onRefZero(normalized, () => {
            uninstallUnretainedAssets()
        })
    }

    const isCoreStylesheetLink = (link) => {
        const url = normalizeAssetUrl(link?.href, document.baseURI)

        return url.includes('flex-fields-core.css')
    }

    const isProtectedLink = (link) => {
        return link?.hasAttribute?.('data-fff-playground-bundle')
            || isCoreStylesheetLink(link)
    }

    const isManagedAssetLink = (link) => {
        return link?.hasAttribute?.('data-fff-managed-asset')
            || link?.dataset?.fffInjectedStylesheet === 'true'
            || link?.dataset?.fffInjectedChunk === 'true'
    }

    const pushModalOpenStack = (ownerKey) => {
        if (!ownerKey || ownerKey === 'page') {
            return
        }

        // Re-opening the same modal (e.g. return from nested child) must not
        // duplicate the stack entry.
        if (modalOpenStack[modalOpenStack.length - 1] === ownerKey) {
            return
        }

        const existingIndex = modalOpenStack.indexOf(ownerKey)

        if (existingIndex >= 0) {
            modalOpenStack.splice(existingIndex, 1)
        }

        modalOpenStack.push(ownerKey)
    }

    const popModalOpenStack = (ownerKey = null) => {
        if (ownerKey) {
            const index = modalOpenStack.lastIndexOf(ownerKey)

            if (index >= 0) {
                modalOpenStack.splice(index, 1)
            }

            return ownerKey
        }

        const top = modalOpenStack.pop()

        return top ?? null
    }

    /**
     * Remove Flex Fields CSS/JS that no consumer still retains.
     * Shared assets used by the main form/tab survive modal teardown.
     */
    const uninstallUnretainedAssets = () => {
        for (const link of [...document.querySelectorAll(`${STYLESHEET_SELECTOR}, ${CHUNK_SELECTOR}`)]) {
            if (!isLinkConnected(link) || isProtectedLink(link) || !isManagedAssetLink(link)) {
                continue
            }

            const url = normalizeAssetUrl(link.href, document.baseURI)

            if (!url || crg.getRefCount(url) > 0) {
                continue
            }

            markFff('fff:uninstall', { url })
            forgetLoadedAsset(link)
            link.remove()
        }
    }

    const isBatchInsideModal = (batch) => {
        return typeof batch?.closest === 'function' && Boolean(batch.closest('.fi-modal'))
    }

    const peekBatchAssets = (root, { excludeModals = false } = {}) => {
        const stylesheets = []
        const chunks = []

        if (!root?.querySelectorAll) {
            return { stylesheets: [], chunks: [] }
        }

        root.querySelectorAll('[data-fff-asset-batch]').forEach((batch) => {
            if (excludeModals && isBatchInsideModal(batch)) {
                return
            }

            stylesheets.push(...parseJsonAttribute(batch, 'data-fff-stylesheets'))
            chunks.push(...parseJsonAttribute(batch, 'data-fff-chunks'))
        })

        return {
            stylesheets: uniqueNormalizedUrls(stylesheets),
            chunks: uniqueNormalizedUrls(chunks),
        }
    }

    const collectInlineEmitUrls = (root, { excludeModals = false } = {}) => {
        const urls = []

        if (!root?.querySelectorAll) {
            return urls
        }

        for (const link of root.querySelectorAll(`${STYLESHEET_SELECTOR}, ${CHUNK_SELECTOR}`)) {
            if (!isInlineEmitAssetLink(link) || link.hasAttribute('data-fff-playground-bundle')) {
                continue
            }

            if (excludeModals && typeof link.closest === 'function' && link.closest('.fi-modal')) {
                continue
            }

            const url = normalizeAssetUrl(link.href, document.baseURI)

            if (url) {
                urls.push(url)
            }
        }

        return uniqueNormalizedUrls(urls)
    }

    const collectBatchAssets = (root, { excludeModals = false } = {}) => {
        const stylesheets = []
        const chunks = []

        if (!root?.querySelectorAll) {
            return { stylesheets: [], chunks: [] }
        }

        root.querySelectorAll('[data-fff-asset-batch]').forEach((batch) => {
            if (excludeModals && isBatchInsideModal(batch)) {
                return
            }

            stylesheets.push(...parseJsonAttribute(batch, 'data-fff-stylesheets'))
            chunks.push(...parseJsonAttribute(batch, 'data-fff-chunks'))

            if (batch.dataset?.fffAssetConsumer) {
                return
            }

            batch.remove()
        })

        return {
            stylesheets: uniqueNormalizedUrls(stylesheets),
            chunks: uniqueNormalizedUrls(chunks),
        }
    }

    const awaitInflightAssetLoads = async () => {
        if (inflightRequests.size === 0) {
            return
        }

        markFff('fff:barrier', { count: inflightRequests.size })
        await Promise.allSettled([...inflightRequests.values()])
    }

    const awaitInlineEmitAssetsIn = async (root = document) => {
        const scope = resolveAssetScope(root)

        if (!scope?.querySelectorAll) {
            return
        }

        const promises = []

        for (const link of scope.querySelectorAll(`${STYLESHEET_SELECTOR}, ${CHUNK_SELECTOR}`)) {
            if (!isInlineEmitAssetLink(link) || link.hasAttribute('data-fff-playground-bundle')) {
                continue
            }

            const url = normalizeAssetUrl(link.href, document.baseURI)

            if (!url) {
                continue
            }

            if (link.rel === 'stylesheet' && !isStylesheetLoaded(url)) {
                promises.push(waitForExistingLink(link, url, 'stylesheet', loadedStylesheets, stylesheetIndex))
            } else if (link.rel === 'modulepreload' && !isChunkLoaded(url)) {
                promises.push(waitForExistingLink(link, url, 'chunk', loadedChunks, chunkIndex))
            }
        }

        if (promises.length === 0) {
            return
        }

        await Promise.allSettled(promises)
    }

    const collectInlineEmitUrlsFromBody = () => {
        if (!document.body?.querySelectorAll) {
            return []
        }

        const urls = []

        for (const link of document.body.querySelectorAll(`${STYLESHEET_SELECTOR}, ${CHUNK_SELECTOR}`)) {
            if (!isInlineEmitAssetLink(link) || link.hasAttribute('data-fff-playground-bundle')) {
                continue
            }

            if (typeof link.closest === 'function' && link.closest('.fi-modal')) {
                continue
            }

            const url = normalizeAssetUrl(link.href, document.baseURI)

            if (url) {
                urls.push(url)
            }
        }

        return uniqueNormalizedUrls(urls)
    }

    const ensureAssets = async (root = document, { pageOnly = false } = {}) => {
        const scope = resolveAssetScope(root)
        const ownerKey = pageOnly ? 'page' : resolveAssetOwnerKey(scope)
        const excludeModals = pageOnly || ownerKey === 'page'

        crg.resyncFromDom(document)

        const { stylesheets, chunks } = collectBatchAssets(scope, { excludeModals })

        if (stylesheets.length > 0 || chunks.length > 0) {
            await Promise.allSettled([
                ...stylesheets.map((href) => loadStylesheet(href)),
                ...chunks.map((href) => loadChunk(href)),
            ])
        }

        await awaitInlineEmitAssetsIn(scope)
        await awaitInflightAssetLoads()
        dedupeDocumentAssets()
        bootSegmentOverflowElements(scope === document ? document : scope)
        bootTimezoneBrowserSsrDefaults(scope === document ? document : scope)
        crg.resyncFromDom(document)
        scheduleResyncFromDom(document, { excludeModals })
    }

    const handleLivewireNavigated = async () => {
        modalOpenStack.length = 0

        resyncLoadedAssetsFromDocument()
        await ensureAssets(document, { pageOnly: true })
        scheduleResyncFromDom(document, { excludeModals: true })
        uninstallUnretainedAssets()

        void preloadVisibleBatchesIn(document)
        prefetchEngine.scheduleIdlePrefetch()
    }

    const inferComponentIdFromBatchElement = (batch) => {
        if (batch?.dataset?.fffAssetConsumer) {
            return batch.dataset.fffAssetConsumer
        }

        const stylesheets = parseJsonAttribute(batch, 'data-fff-stylesheets')

        for (const href of stylesheets) {
            const match = String(href).match(/flex-fields-([^./]+)\.css/)

            if (match?.[1]) {
                return match[1]
            }
        }

        return 'unknown-batch'
    }

    const awaitBundleReady = async (componentId) => {
        const canonical = crg.resolveCanonicalComponent(componentId)
        const bundle = crg.bundleFor(canonical)
        const hasRegistryBundle = (bundle.stylesheets?.length ?? 0) > 0
            || (bundle.chunks?.length ?? 0) > 0
            || Boolean(bundle.entry)

        if (hasRegistryBundle) {
            await Promise.allSettled([
                ...(bundle.stylesheets ?? []).map((href) => loadStylesheet(href, canonical)),
                ...(bundle.chunks ?? []).map((href) => loadChunk(href, canonical)),
            ])

            if (bundle.entry) {
                await loadChunk(bundle.entry, canonical)
            }

            scheduleResyncFromDom(document)

            return
        }

        if (!document?.querySelectorAll) {
            return
        }

        for (const batch of document.querySelectorAll('[data-fff-asset-batch]')) {
            const batchComponent = inferComponentIdFromBatchElement(batch)

            if (batchComponent !== canonical && crg.resolveCanonicalComponent(batchComponent) !== canonical) {
                continue
            }

            const stylesheets = parseJsonAttribute(batch, 'data-fff-stylesheets')
            const chunks = parseJsonAttribute(batch, 'data-fff-chunks')

            await Promise.allSettled([
                ...stylesheets.map((href) => loadStylesheet(href, canonical)),
                ...chunks.map((href) => loadChunk(href, canonical)),
            ])

            scheduleResyncFromDom(document)

            return
        }
    }

    const resolvePendingTarget = (element) => {
        if (!element || typeof element.closest !== 'function') {
            return element ?? null
        }

        return element.closest('.fi-modal') ?? element
    }

    const isModalPendingTarget = (element) => {
        const target = resolvePendingTarget(element)

        return Boolean(target?.classList?.contains('fi-modal'))
    }

    const batchNeedsLoading = (batch) => {
        const stylesheets = parseJsonAttribute(batch, 'data-fff-stylesheets')
        const chunks = parseJsonAttribute(batch, 'data-fff-chunks')

        return stylesheets.some((href) => !isStylesheetLoaded(href))
            || chunks.some((href) => !isChunkLoaded(href))
    }

    const preloadFromBatch = (batch) => {
        if (!batchNeedsLoading(batch)) {
            return
        }

        for (const href of parseJsonAttribute(batch, 'data-fff-stylesheets')) {
            loadStylesheet(href)
        }

        for (const href of parseJsonAttribute(batch, 'data-fff-chunks')) {
            loadChunk(href)
        }
    }

    const resolveHoverPreloadScope = (trigger) => {
        if (!trigger?.closest) {
            return null
        }

        const batch = trigger.closest('[data-fff-asset-batch]')

        if (batch?.parentElement) {
            return batch.parentElement
        }

        return trigger.closest('.fi-fo-field-wrp')
            ?? trigger.closest('form')
            ?? null
    }

    const isElementInViewport = (element) => {
        if (!element?.getBoundingClientRect) {
            return false
        }

        const rect = element.getBoundingClientRect()

        return rect.bottom > 0
            && rect.right > 0
            && rect.top < (window.innerHeight || document.documentElement?.clientHeight || 0)
            && rect.left < (window.innerWidth || document.documentElement?.clientWidth || 0)
    }

    const collectVisibleAssetBatches = (root = document) => {
        if (!root?.querySelectorAll) {
            return []
        }

        return [...root.querySelectorAll('[data-fff-asset-batch]')].filter((batch) => {
            return isElementInViewport(batch)
        })
    }

    const preloadVisibleBatchesIn = async (root = document) => {
        if (injectorHooks.shouldSkipBackgroundPreload()) {
            return
        }

        const batches = collectVisibleAssetBatches(root)

        if (batches.length === 0) {
            return
        }

        const promises = []

        for (const batch of batches) {
            if (!batchNeedsLoading(batch)) {
                continue
            }

            for (const href of parseJsonAttribute(batch, 'data-fff-stylesheets')) {
                promises.push(loadStylesheet(href))
            }

            for (const href of parseJsonAttribute(batch, 'data-fff-chunks')) {
                promises.push(loadChunk(href))
            }
        }

        if (promises.length === 0) {
            return
        }

        await Promise.allSettled(promises)
    }

    const preloadBatchesIn = async (root = document) => {
        const scope = resolveAssetScope(root)

        if (!scope?.querySelectorAll) {
            return
        }

        const promises = []

        for (const batch of scope.querySelectorAll('[data-fff-asset-batch]')) {
            if (!batchNeedsLoading(batch)) {
                continue
            }

            for (const href of parseJsonAttribute(batch, 'data-fff-stylesheets')) {
                promises.push(loadStylesheet(href))
            }

            for (const href of parseJsonAttribute(batch, 'data-fff-chunks')) {
                promises.push(loadChunk(href))
            }
        }

        if (promises.length === 0) {
            return
        }

        await Promise.allSettled(promises)
    }

    const clearPendingMaxReleaseTimer = (target) => {
        if (! target) {
            return
        }

        const timer = pendingMaxReleaseTimers.get(target)

        if (timer != null) {
            window.clearTimeout?.(timer)
            pendingMaxReleaseTimers.delete(target)
        }
    }

    const removeAssetsSkeletonOverlay = (root) => {
        if (! root?.querySelectorAll) {
            return
        }

        for (const node of root.querySelectorAll(`.${ASSETS_SKELETON_CLASS}`)) {
            node.remove()
        }
    }

    const mountAssetsSkeletonOverlay = (target) => {
        if (! target || typeof document?.createElement !== 'function') {
            return
        }

        const host = target.classList?.contains('fi-modal')
            ? (target.querySelector?.('.fi-modal-window') ?? null)
            : target

        if (! host || typeof host.appendChild !== 'function') {
            return
        }

        removeAssetsSkeletonOverlay(host)

        const overlay = document.createElement('div')
        overlay.className = ASSETS_SKELETON_CLASS
        overlay.setAttribute('aria-hidden', 'true')

        const bones = [
            ['fff-flex-fields-assets-skeleton__bone fff-flex-fields-assets-skeleton__bone--label', '5.5rem'],
            ['fff-flex-fields-assets-skeleton__bone fff-flex-fields-assets-skeleton__bone--field', null],
            ['fff-flex-fields-assets-skeleton__bone fff-flex-fields-assets-skeleton__bone--label', '7rem'],
            ['fff-flex-fields-assets-skeleton__bone fff-flex-fields-assets-skeleton__bone--field', null],
        ]

        for (const [className, width] of bones) {
            const bone = document.createElement('div')
            bone.className = className

            if (width) {
                if (! bone.style) {
                    bone.style = {}
                }

                bone.style.width = width
            }

            overlay.appendChild(bone)
        }

        host.appendChild(overlay)
    }

    const schedulePendingMaxRelease = (target) => {
        if (! target) {
            return
        }

        clearPendingMaxReleaseTimer(target)

        const timer = window.setTimeout(() => {
            pendingMaxReleaseTimers.delete(target)

            if (! target.classList?.contains('fff-flex-fields-assets-pending')) {
                return
            }

            // Soft reveal after the cap — CSS/JS may still be arriving.
            void releasePendingState(target, { force: true })
        }, MAX_PENDING_VISIBLE_MS)

        pendingMaxReleaseTimers.set(target, timer)
    }

    const applyPendingState = (element) => {
        const target = resolvePendingTarget(element)

        if (!target?.classList) {
            return null
        }

        target.classList.add('fff-flex-fields-assets-pending')
        target.classList.remove('fff-flex-fields-assets-ready')

        if (target.dataset) {
            target.dataset.fffPendingStartedAt = String(Date.now())
        }

        injectorHooks.markPendingStarted(target)

        pendingMorphTargets.add(target)

        if (element !== target) {
            pendingMorphTargets.add(element)
        }

        mountAssetsSkeletonOverlay(target)
        schedulePendingMaxRelease(target)

        return target
    }

    const releasePendingState = async (element, { force = false } = {}) => {
        const target = resolvePendingTarget(element)
        const nodes = new Set([target, element].filter(Boolean))

        clearPendingMaxReleaseTimer(target)

        if (!force && target) {
            const delayMs = await Promise.resolve(injectorHooks.getPendingReleaseDelayMs(target, {
                force,
                element,
                awaitInflightAssetLoads,
            }))

            // Never hold the skeleton longer than the production hard cap.
            const remainingCap = target.dataset?.fffPendingStartedAt
                ? Math.max(0, MAX_PENDING_VISIBLE_MS - (Date.now() - Number(target.dataset.fffPendingStartedAt)))
                : 0
            const waitMs = Math.min(Math.max(0, delayMs), remainingCap)

            if (waitMs > 0) {
                await new Promise((resolve) => {
                    window.setTimeout(resolve, waitMs)
                })
            }
        }

        if (target?.dataset?.fffPendingStartedAt) {
            delete target.dataset.fffPendingStartedAt
        }

        removeAssetsSkeletonOverlay(target)

        for (const node of nodes) {
            if (!node?.classList) {
                continue
            }

            node.classList.remove('fff-flex-fields-assets-pending')
            node.classList.add('fff-flex-fields-assets-ready')
            pendingMorphTargets.delete(node)
            removeAssetsSkeletonOverlay(node)
        }
    }

    const hasPendingState = (element) => {
        const target = resolvePendingTarget(element)

        if (!target) {
            return false
        }

        return pendingMorphTargets.has(target)
            || pendingMorphTargets.has(element)
            || target.classList.contains('fff-flex-fields-assets-pending')
            || (element !== target && element?.classList?.contains('fff-flex-fields-assets-pending'))
    }

    const resolveModalRoot = (event) => {
        const modalId = event?.detail?.id

        if (typeof modalId === 'string' && modalId !== '') {
            return document.getElementById(modalId)
        }

        // Stacked Filament actions: prefer the topmost open modal, not the first in DOM.
        const openModals = document.querySelectorAll('.fi-modal.fi-modal-open')

        if (openModals.length === 0) {
            return null
        }

        return openModals[openModals.length - 1]
    }

    const prepareModal = async (event) => {
        const modal = resolveModalRoot(event)

        if (!modal) {
            return
        }

        pushModalOpenStack(resolveModalOwnerKey(modal))

        const needsLoading = rootNeedsAssetLoading(modal)

        if (needsLoading) {
            applyPendingState(modal)
        }

        try {
            await preloadBatchesIn(modal)
            // Claims modal ownership for every batch/emit URL (shared page assets
            // are loaded once; revoke-safe on close via retain sets).
            await ensureAssets(modal)
        } finally {
            if (needsLoading) {
                await releasePendingState(modal)
            }
        }

        void preloadVisibleBatchesIn(modal)
    }

    const beginPendingMorph = ({ el, toEl }) => {
        if (!toEl || typeof toEl.querySelectorAll !== 'function') {
            return null
        }

        if (!el) {
            return null
        }

        const batches = toEl.querySelectorAll('[data-fff-asset-batch]')

        if (batches.length === 0) {
            return null
        }

        const needsLoading = [...batches].some((batch) => {
            return injectorHooks.shouldBatchTriggerPending(batch, batchNeedsLoading)
        })

        if (!needsLoading) {
            return null
        }

        const liveTarget = resolvePendingTarget(el)

        if (!liveTarget?.classList) {
            return null
        }

        applyPendingState(el)

        return liveTarget
    }

    const handleMorphUpdating = (payload) => {
        beginPendingMorph(payload)
    }

    const handleMorphUpdated = ({ el }) => {
        if (!el) {
            return Promise.resolve()
        }

        const finish = () => {
            const excludeModals = resolveAssetOwnerKey(el) === 'page'
            scheduleResyncFromDom(document, { excludeModals })
        }

        if (hasPendingState(el)) {
            return ensureAssets(el).finally(async () => {
                await releasePendingState(el)
                finish()
            })
        }

        return ensureAssets(el).then(() => {
            finish()

            if (injectorHooks.shouldSkipBackgroundPreload()) {
                return
            }

            return preloadBatchesIn(el)
        })
    }

    const registerLivewireHooks = () => {
        if (!window.Livewire?.hook) {
            return
        }

        window.Livewire.hook('morph.updating', handleMorphUpdating)
        window.Livewire.hook('morph.updated', handleMorphUpdated)
    }

    const cleanupClosedModalPendingState = async (event) => {
        const modalId = event?.detail?.id

        // CRITICAL (stacked / nested Filament action modals):
        // When modal B opens on top of A, Filament commonly drops `fi-modal-open`
        // from A while A stays mounted and will return when B closes.
        // Pop ONLY the closed modal from the open stack — never every closed shell.
        if (typeof modalId === 'string' && modalId !== '') {
            const modal = document.getElementById(modalId)

            if (modal) {
                await releasePendingState(modal, { force: true })
                const ownerKey = resolveModalOwnerKey(modal)
                popModalOpenStack(ownerKey)
            } else {
                popModalOpenStack(`modal:${modalId}`)
            }
        } else {
            // No detail.id — pop the topmost stacked modal only (LIFO), mirroring
            // Filament's nested close order. Do not wipe sibling/parent owners.
            const popped = popModalOpenStack(null)

            if (popped) {
                // LIFO modal stack pop only — CRG resync handles consumer release.
            }

            for (const modal of document.querySelectorAll('.fi-modal.fff-flex-fields-assets-pending')) {
                if (!modal.classList.contains('fi-modal-open')) {
                    await releasePendingState(modal, { force: true })
                }
            }
        }

        for (const stray of document.querySelectorAll('.fff-flex-fields-assets-pending:not(.fi-modal)')) {
            await releasePendingState(stray, { force: true })
        }

        scheduleResyncFromDom(document)
        crg.resyncFromDom(document)
        uninstallUnretainedAssets()
    }

    const prefetchEngine = createPrefetchEngine({
        window,
        document,
        shouldSkipBackgroundPreload: () => injectorHooks.shouldSkipBackgroundPreload(),
        preloadBatchesIn,
        preloadVisibleBatchesIn,
        resolveHoverPreloadScope,
        acquireTempPrefetch: (instanceId, componentId) => {
            if (!crg.getActiveConsumerIds().includes(instanceId)) {
                crg.acquire(instanceId, componentId)
            }
        },
        releaseTempPrefetch: (instanceId) => {
            if (crg.getActiveConsumerIds().includes(instanceId)) {
                crg.release(instanceId)
            }
        },
        resolveTempPrefetchInstanceId: (scope) => `prefetch-hover::${resolveAssetOwnerKey(scope)}::${scope?.tagName ?? 'scope'}`,
        resolveComponentIdFromScope: (scope) => {
            const batch = scope?.querySelector?.('[data-fff-asset-batch][data-fff-asset-consumer]')
                ?? scope?.closest?.('[data-fff-asset-batch][data-fff-asset-consumer]')

            if (batch) {
                return inferComponentIdFromBatchElement(batch)
            }

            return 'prefetch'
        },
    })

    const boot = () => {
        void ensureAssets(document, { pageOnly: true })

        document.addEventListener('livewire:navigated', () => {
            void handleLivewireNavigated()
        })

        window.addEventListener('x-modal-opened', prepareModal)
        window.addEventListener('modal-closed', cleanupClosedModalPendingState)
        prefetchEngine.boot()

        if (window.Livewire?.hook) {
            registerLivewireHooks()
        } else {
            document.addEventListener('livewire:init', registerLivewireHooks, { once: true })
        }
    }

    return {
        normalizeAssetUrl: (url) => normalizeAssetUrl(url, document.baseURI),
        prefersReducedMotion: () => prefersReducedMotion(window),
        connectionSaveDataEnabled: () => connectionSaveDataEnabled(window),
        shouldDeferBackgroundAssetPreload: () => shouldDeferBackgroundAssetPreload(window),
        isStylesheetLoaded,
        isChunkLoaded,
        loadStylesheet,
        loadChunk,
        ensureAssets,
        awaitInflightAssetLoads,
        resolvePendingTarget,
        resolveAssetOwnerKey,
        batchNeedsLoading,
        preloadBatchesIn,
        preloadVisibleBatchesIn,
        resolveHoverPreloadScope,
        rootNeedsAssetLoading,
        prepareModal,
        applyPendingState,
        releasePendingState,
        hasPendingState,
        beginPendingMorph,
        handleMorphUpdating,
        handleMorphUpdated,
        registerInjectorHooks,
        isModalPendingTarget,
        cleanupClosedModalPendingState,
        getConsumerGraph: () => crg,
        scheduleResyncFromDom,
        awaitBundleReady,
        getModalOpenStack: () => [...modalOpenStack],
        getInflightUrls: () => [...inflightRequests.keys()],
        injectCriticalPreloadsForSelectFamily,
        uninstallUnretainedAssets,
        stripInlineEmitAssets,
        purgeLazyAssets,
        resyncLoadedAssetsFromDocument,
        handleLivewireNavigated,
        bootSegmentOverflowElements,
        bootTimezoneBrowserSsrDefaults,
        boot,
    }
}

export function bootFlexFieldAssetInjector(options = {}) {
    const document = options.document ?? globalThis.document
    const window = options.window ?? globalThis.window

    // Filament may render the injector via package assets and SCRIPTS_AFTER.
    // Guard so SPA boot + event listeners are registered exactly once.
    if (window.__fffFlexFieldAssetInjector) {
        return window.__fffFlexFieldAssetInjector
    }

    const injector = createFlexFieldAssetInjector({ document, window })
    window.__fffFlexFieldAssetInjector = injector

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => injector.boot(), { once: true })
    } else {
        injector.boot()
    }

    return injector
}
