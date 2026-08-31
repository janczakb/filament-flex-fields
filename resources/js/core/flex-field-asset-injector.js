const STYLESHEET_SELECTOR = 'link[rel="stylesheet"][href*="filament-flex-fields"]'
const CHUNK_SELECTOR = 'link[rel="modulepreload"][href*="filament-flex-fields"]'

import { bootSegmentOverflowElements } from './segment-overflow-position.js'

export { bootSegmentOverflowElements }

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

    /** URLs required by the non-modal page (forms, resource tabs, etc.). */
    const pageRetainedUrls = new Set()
    /** modalOwnerKey → Set of asset URLs claimed while that modal was open. */
    const modalOwnedUrls = new Map()
    /**
     * Open Filament modal stack (nested actions). Parent stays retained even when
     * Filament temporarily drops `fi-modal-open` while a child modal is visible.
     */
    const modalOpenStack = []
    const anonymousModalKeys = new WeakMap()
    let anonymousModalSequence = 0

    const injectorHooks = {
        shouldBatchTriggerPending(batch, defaultCheck) {
            return defaultCheck(batch)
        },
        markPendingStarted() { },
        getPendingReleaseDelayMs() {
            return 0
        },
        shouldSkipBackgroundPreload() {
            return false
        },
    }

    const registerInjectorHooks = (partial = {}) => {
        Object.assign(injectorHooks, partial)
    }

    const isProtectedLink = (link) => {
        return link?.hasAttribute?.('data-fff-playground-bundle')
            || link?.hasAttribute?.('data-fff-stylesheet')
            || link?.hasAttribute?.('data-fff-alpine-chunk')
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

    const loadAsset = (href, type, loadedSet, index, selector, buildElement) => {
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
            const link = buildElement(url)
            link.setAttribute('data-navigate-track', '')

            link.addEventListener('load', () => {
                rememberLoadedLink(link, loadedSet, index)
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

    const loadStylesheet = (href) => {
        return loadAsset(href, 'stylesheet', loadedStylesheets, stylesheetIndex, STYLESHEET_SELECTOR, (url) => {
            const link = document.createElement('link')
            link.rel = 'stylesheet'
            link.href = url
            link.dataset.fffInjectedStylesheet = 'true'

            return link
        })
    }

    const loadChunk = (href) => {
        return loadAsset(href, 'chunk', loadedChunks, chunkIndex, CHUNK_SELECTOR, (url) => {
            const link = document.createElement('link')
            link.rel = 'modulepreload'
            link.href = url
            link.dataset.fffInjectedChunk = 'true'

            return link
        })
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

    const claimAssetUrls = (ownerKey, urls) => {
        if (!ownerKey || !urls?.length) {
            return
        }

        if (ownerKey === 'page') {
            for (const url of urls) {
                pageRetainedUrls.add(url)
            }

            return
        }

        let owned = modalOwnedUrls.get(ownerKey)

        if (!owned) {
            owned = new Set()
            modalOwnedUrls.set(ownerKey, owned)
        }

        for (const url of urls) {
            owned.add(url)
        }
    }

    const releaseModalOwnership = (ownerKey) => {
        if (!ownerKey || ownerKey === 'page') {
            return
        }

        modalOwnedUrls.delete(ownerKey)
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

            releaseModalOwnership(ownerKey)

            return ownerKey
        }

        const top = modalOpenStack.pop()

        if (top) {
            releaseModalOwnership(top)
        }

        return top ?? null
    }

    const collectRetainedAssetUrls = () => {
        const retained = new Set(pageRetainedUrls)

        for (const urls of modalOwnedUrls.values()) {
            for (const url of urls) {
                retained.add(url)
            }
        }

        return retained
    }

    /**
     * Remove Flex Fields CSS/JS that no page or open modal still retains.
     * Shared assets used by the main form/tab survive modal teardown.
     */
    const uninstallUnretainedAssets = () => {
        const retained = collectRetainedAssetUrls()

        for (const link of [...document.querySelectorAll(`${STYLESHEET_SELECTOR}, ${CHUNK_SELECTOR}`)]) {
            // Never tear down server-emitted / playground links. Ownership cleanup
            // only targets injector-created orphans (`data-fff-injected-*`).
            if (!isLinkConnected(link) || isProtectedLink(link)) {
                continue
            }

            const isInjectorCreated = link.dataset?.fffInjectedStylesheet === 'true'
                || link.dataset?.fffInjectedChunk === 'true'

            if (!isInjectorCreated) {
                continue
            }

            const url = normalizeAssetUrl(link.href, document.baseURI)

            if (!url || retained.has(url)) {
                continue
            }

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

        const peeked = peekBatchAssets(scope, { excludeModals })
        // Claim emit URLs only while links are still under a concrete scope
        // (modal / field root). Scanning whole `document` would sticky-retain
        // modal CSS that was already moved into <head>.
        const inlineUrls = scope === document
            ? []
            : collectInlineEmitUrls(scope, { excludeModals })
        const claimedUrls = [...peeked.stylesheets, ...peeked.chunks, ...inlineUrls]
        claimAssetUrls(ownerKey, claimedUrls)

        const { stylesheets, chunks } = collectBatchAssets(scope, { excludeModals })

        if (stylesheets.length > 0 || chunks.length > 0) {
            await Promise.allSettled([
                ...stylesheets.map((href) => loadStylesheet(href)),
                ...chunks.map((href) => loadChunk(href)),
            ])
            // Re-claim the exact URLs that were loaded in this pass (covers any
            // normalization drift between peek and collect).
            claimAssetUrls(ownerKey, [...stylesheets, ...chunks])
        }

        await awaitInlineEmitAssetsIn(scope)
        await awaitInflightAssetLoads()
        dedupeDocumentAssets()
        bootSegmentOverflowElements(scope === document ? document : scope)
    }

    const handleLivewireNavigated = async () => {
        // Snapshot before clear: boot() may already have consumed batches before
        // Livewire's synthetic first `livewire:navigated` fires after paint.
        const previousPageRetain = [...pageRetainedUrls]
        const bodyBatches = peekBatchAssets(document, { excludeModals: true })
        const bodyEmitUrls = collectInlineEmitUrlsFromBody()

        modalOwnedUrls.clear()
        modalOpenStack.length = 0
        pageRetainedUrls.clear()
        resyncLoadedAssetsFromDocument()
        await ensureAssets(document, { pageOnly: true })

        claimAssetUrls('page', [
            ...bodyBatches.stylesheets,
            ...bodyBatches.chunks,
            ...bodyEmitUrls,
        ])

        // Synthetic first navigated after boot: restore page retain when the new
        // document no longer exposes batch markers (already consumed).
        if (pageRetainedUrls.size === 0 && previousPageRetain.length > 0) {
            claimAssetUrls('page', previousPageRetain)
        }

        void preloadVisibleBatchesIn(document)
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

    const applyPendingState = (element) => {
        const target = resolvePendingTarget(element)

        if (!target?.classList) {
            return null
        }

        target.classList.add('fff-flex-fields-assets-pending')
        target.classList.remove('fff-flex-fields-assets-ready')

        injectorHooks.markPendingStarted(target)

        pendingMorphTargets.add(target)

        if (element !== target) {
            pendingMorphTargets.add(element)
        }

        return target
    }

    const releasePendingState = async (element, { force = false } = {}) => {
        const target = resolvePendingTarget(element)
        const nodes = new Set([target, element].filter(Boolean))

        if (!force && target) {
            const delayMs = await Promise.resolve(injectorHooks.getPendingReleaseDelayMs(target, {
                force,
                element,
                awaitInflightAssetLoads,
            }))

            if (delayMs > 0) {
                await new Promise((resolve) => {
                    window.setTimeout(resolve, delayMs)
                })
            }
        }

        if (target?.dataset?.fffPendingStartedAt) {
            delete target.dataset.fffPendingStartedAt
        }

        for (const node of nodes) {
            if (!node?.classList) {
                continue
            }

            node.classList.remove('fff-flex-fields-assets-pending')
            node.classList.add('fff-flex-fields-assets-ready')
            pendingMorphTargets.delete(node)
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

        if (hasPendingState(el)) {
            return ensureAssets(el).finally(async () => {
                await releasePendingState(el)
            })
        }

        return ensureAssets(el).then(() => preloadBatchesIn(el))
    }

    const registerLivewireHooks = () => {
        if (!window.Livewire?.hook) {
            return
        }

        window.Livewire.hook('morph.updating', handleMorphUpdating)
        window.Livewire.hook('morph.updated', handleMorphUpdated)
    }

    const registerHoverPreload = () => {
        let hoverPreloadTimer = null

        document.addEventListener('mouseover', (event) => {
            if (injectorHooks.shouldSkipBackgroundPreload()) {
                return
            }

            if (!event.target?.closest) {
                return
            }

            const trigger = event.target.closest('button, a[href], [role="button"], [wire\\:click]')

            if (!trigger) {
                return
            }

            if (hoverPreloadTimer) {
                clearTimeout(hoverPreloadTimer)
            }

            hoverPreloadTimer = setTimeout(() => {
                hoverPreloadTimer = null

                const scope = resolveHoverPreloadScope(trigger)

                if (!scope) {
                    return
                }

                void preloadBatchesIn(scope)
            }, 48)
        }, { passive: true })
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
                popModalOpenStack(resolveModalOwnerKey(modal))
            } else {
                popModalOpenStack(`modal:${modalId}`)
            }
        } else {
            // No detail.id — pop the topmost stacked modal only (LIFO), mirroring
            // Filament's nested close order. Do not wipe sibling/parent owners.
            popModalOpenStack(null)

            for (const modal of document.querySelectorAll('.fi-modal.fff-flex-fields-assets-pending')) {
                if (!modal.classList.contains('fi-modal-open')) {
                    await releasePendingState(modal, { force: true })
                }
            }
        }

        for (const stray of document.querySelectorAll('.fff-flex-fields-assets-pending:not(.fi-modal)')) {
            await releasePendingState(stray, { force: true })
        }

        // Drop only assets that neither the page nor any still-retained modal owns.
        uninstallUnretainedAssets()
    }

    const boot = () => {
        void ensureAssets(document, { pageOnly: true })

        document.addEventListener('livewire:navigated', () => {
            void handleLivewireNavigated()
        })

        window.addEventListener('x-modal-opened', prepareModal)
        window.addEventListener('modal-closed', cleanupClosedModalPendingState)
        registerHoverPreload()

        if (window.Livewire?.hook) {
            registerLivewireHooks()
        } else {
            document.addEventListener('livewire:init', registerLivewireHooks, { once: true })
        }
    }

    return {
        normalizeAssetUrl: (url) => normalizeAssetUrl(url, document.baseURI),
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
        claimAssetUrls,
        releaseModalOwnership,
        collectRetainedAssetUrls,
        getModalOpenStack: () => [...modalOpenStack],
        uninstallUnretainedAssets,
        stripInlineEmitAssets,
        purgeLazyAssets,
        resyncLoadedAssetsFromDocument,
        handleLivewireNavigated,
        bootSegmentOverflowElements,
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
