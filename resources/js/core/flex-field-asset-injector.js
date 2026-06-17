const STYLESHEET_SELECTOR = 'link[rel="stylesheet"][href*="filament-flex-fields"]'
const CHUNK_SELECTOR = 'link[rel="modulepreload"][href*="filament-flex-fields"]'

export function normalizeAssetUrl(url, baseUri = typeof document !== 'undefined' ? document.baseURI : 'http://localhost/') {
    if (! url) {
        return ''
    }

    try {
        return new URL(url, baseUri).href
    } catch {
        return String(url)
    }
}

export function createFlexFieldAssetInjector({ document, window } = {}) {
    if (! document || ! window) {
        throw new Error('FlexField asset injector requires document and window.')
    }

    const loadedStylesheets = new Set()
    const loadedChunks = new Set()
    const inflightRequests = new Map()
    const stylesheetIndex = new Map()
    const chunkIndex = new Map()
    const pendingMorphTargets = new WeakSet()

    const injectorHooks = {
        shouldBatchTriggerPending(batch, defaultCheck) {
            return defaultCheck(batch)
        },
        markPendingStarted() {},
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

        if (! url) {
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
            if (! isInlineEmitAssetLink(link) || link.hasAttribute('data-fff-playground-bundle')) {
                continue
            }

            if (! isDocumentRoot && typeof scope.contains === 'function' && ! scope.contains(link)) {
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
        if (! root?.querySelectorAll) {
            return false
        }

        return [...root.querySelectorAll('[data-fff-asset-batch]')].some((batch) => {
            return injectorHooks.shouldBatchTriggerPending(batch, batchNeedsLoading)
        })
    }

    const indexLink = (link, index) => {
        if (! link?.href) {
            return
        }

        index.set(normalizeAssetUrl(link.href, document.baseURI), link)
    }

    const rebuildIndex = (selector, index) => {
        index.clear()

        for (const link of document.querySelectorAll(selector)) {
            indexLink(link, index)
        }
    }

    const isLinkConnected = (link) => {
        if (! link) {
            return false
        }

        if (typeof link.isConnected === 'boolean') {
            return link.isConnected
        }

        return link.parentElement !== null
    }

    const findAssetLink = (selector, href, index) => {
        const normalizedHref = normalizeAssetUrl(href, document.baseURI)

        if (! normalizedHref) {
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
        if (! link?.href) {
            return
        }

        const normalizedHref = normalizeAssetUrl(link.href, document.baseURI)
        loadedSet.add(normalizedHref)
        index.set(normalizedHref, link)
    }

    const isStylesheetLoaded = (href) => {
        const normalizedHref = normalizeAssetUrl(href, document.baseURI)

        if (! normalizedHref) {
            return true
        }

        if (loadedStylesheets.has(normalizedHref)) {
            return true
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

        if (! normalizedHref) {
            return true
        }

        if (loadedChunks.has(normalizedHref)) {
            return true
        }

        return false
    }

    const isStylesheetLinkReady = (link) => {
        return Boolean(link?.sheet)
    }

    const isChunkLinkReady = (url) => {
        if (! window.performance?.getEntriesByName) {
            return false
        }

        return window.performance.getEntriesByName(url).length > 0
    }

    const waitForExistingLink = (link, url, type, loadedSet, index) => {
        if (! isLinkConnected(link)) {
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
        if (! link || link.parentElement === document.head) {
            return link
        }

        document.head.appendChild(link)

        return link
    }

    const appendAssetLink = (link) => {
        document.head.appendChild(link)
    }

    const purgeLazyAssets = () => {
        for (const link of [...document.querySelectorAll(`${STYLESHEET_SELECTOR}, ${CHUNK_SELECTOR}`)]) {
            if (isProtectedLink(link)) {
                continue
            }

            const url = normalizeAssetUrl(link.href, document.baseURI)

            if (! url) {
                continue
            }

            if (link.rel === 'stylesheet') {
                loadedStylesheets.delete(url)
                stylesheetIndex.delete(url)
            } else {
                loadedChunks.delete(url)
                chunkIndex.delete(url)
            }

            link.remove()
        }

        inflightRequests.clear()
    }

    const loadAsset = (href, type, loadedSet, index, selector, buildElement) => {
        const url = normalizeAssetUrl(href, document.baseURI)

        if (! url) {
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

    const dedupeLinks = (selector, seen, index) => {
        for (const link of document.querySelectorAll(selector)) {
            if (! link.href) {
                continue
            }

            if (isProtectedLink(link)) {
                indexLink(link, index)

                if (link.parentElement !== document.head) {
                    moveLinkToHead(link)
                }

                continue
            }

            const url = normalizeAssetUrl(link.href, document.baseURI)

            if (seen.has(url)) {
                if (! link.dataset.fffInjectedStylesheet && ! link.dataset.fffInjectedChunk) {
                    indexLink(link, index)

                    if (link.parentElement !== document.head) {
                        moveLinkToHead(link)
                    }

                    continue
                }

                link.remove()
                index.delete(url)

                continue
            }

            seen.add(url)
            indexLink(link, index)

            if (link.parentElement !== document.head) {
                moveLinkToHead(link)
            }
        }
    }

    const dedupeDocumentAssets = () => {
        dedupeLinks(STYLESHEET_SELECTOR, loadedStylesheets, stylesheetIndex)
        dedupeLinks(CHUNK_SELECTOR, loadedChunks, chunkIndex)
    }

    const parseJsonAttribute = (element, attribute) => {
        const raw = element?.getAttribute?.(attribute)

        if (! raw) {
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

    const collectBatchAssets = (root) => {
        const stylesheets = []
        const chunks = []

        root.querySelectorAll('[data-fff-asset-batch]').forEach((batch) => {
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

        if (! scope?.querySelectorAll) {
            return
        }

        const promises = []

        for (const link of scope.querySelectorAll(`${STYLESHEET_SELECTOR}, ${CHUNK_SELECTOR}`)) {
            if (! isInlineEmitAssetLink(link) || link.hasAttribute('data-fff-playground-bundle')) {
                continue
            }

            const url = normalizeAssetUrl(link.href, document.baseURI)

            if (! url) {
                continue
            }

            if (link.rel === 'stylesheet' && ! isStylesheetLoaded(url)) {
                promises.push(waitForExistingLink(link, url, 'stylesheet', loadedStylesheets, stylesheetIndex))
            } else if (link.rel === 'modulepreload' && ! isChunkLoaded(url)) {
                promises.push(waitForExistingLink(link, url, 'chunk', loadedChunks, chunkIndex))
            }
        }

        if (promises.length === 0) {
            return
        }

        await Promise.allSettled(promises)
    }

    const ensureAssets = async (root = document) => {
        const scope = resolveAssetScope(root)
        const { stylesheets, chunks } = collectBatchAssets(scope)

        if (stylesheets.length > 0 || chunks.length > 0) {
            await Promise.allSettled([
                ...stylesheets.map((href) => loadStylesheet(href)),
                ...chunks.map((href) => loadChunk(href)),
            ])
        }

        await awaitInlineEmitAssetsIn(scope)
        await awaitInflightAssetLoads()
        dedupeDocumentAssets()
    }

    const resolvePendingTarget = (element) => {
        if (! element || typeof element.closest !== 'function') {
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

        return stylesheets.some((href) => ! isStylesheetLoaded(href))
            || chunks.some((href) => ! isChunkLoaded(href))
    }

    const preloadFromBatch = (batch) => {
        if (! batchNeedsLoading(batch)) {
            return
        }

        for (const href of parseJsonAttribute(batch, 'data-fff-stylesheets')) {
            loadStylesheet(href)
        }

        for (const href of parseJsonAttribute(batch, 'data-fff-chunks')) {
            loadChunk(href)
        }
    }

    const preloadBatchesIn = async (root = document) => {
        const scope = resolveAssetScope(root)

        if (! scope?.querySelectorAll) {
            return
        }

        const promises = []

        for (const batch of scope.querySelectorAll('[data-fff-asset-batch]')) {
            if (! batchNeedsLoading(batch)) {
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

        if (! target?.classList) {
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

        if (! force && target) {
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
            if (! node?.classList) {
                continue
            }

            node.classList.remove('fff-flex-fields-assets-pending')
            node.classList.add('fff-flex-fields-assets-ready')
            pendingMorphTargets.delete(node)
        }
    }

    const hasPendingState = (element) => {
        const target = resolvePendingTarget(element)

        if (! target) {
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

        return document.querySelector('.fi-modal.fi-modal-open')
    }

    const prepareModal = async (event) => {
        const modal = resolveModalRoot(event)

        if (! modal) {
            return
        }

        const needsLoading = rootNeedsAssetLoading(modal)

        if (needsLoading) {
            applyPendingState(modal)
        }

        try {
            await preloadBatchesIn(modal)
            await ensureAssets(modal)
        } finally {
            if (needsLoading) {
                await releasePendingState(modal)
            }
        }
    }

    const beginPendingMorph = ({ el, toEl }) => {
        if (! toEl || typeof toEl.querySelectorAll !== 'function') {
            return null
        }

        if (! el) {
            return null
        }

        const batches = toEl.querySelectorAll('[data-fff-asset-batch]')

        if (batches.length === 0) {
            return null
        }

        const needsLoading = [...batches].some((batch) => {
            return injectorHooks.shouldBatchTriggerPending(batch, batchNeedsLoading)
        })

        if (! needsLoading) {
            return null
        }

        const liveTarget = resolvePendingTarget(el)

        if (! liveTarget?.classList || ! isModalPendingTarget(el)) {
            return null
        }

        applyPendingState(el)

        return liveTarget
    }

    const handleMorphUpdating = (payload) => {
        beginPendingMorph(payload)
    }

    const handleMorphUpdated = ({ el }) => {
        if (! el) {
            return Promise.resolve()
        }

        if (hasPendingState(el)) {
            return ensureAssets(el).finally(async () => {
                await releasePendingState(el)
            })
        }

        ensureAssets(el)

        return void preloadBatchesIn(el)
    }

    const registerLivewireHooks = () => {
        if (! window.Livewire?.hook) {
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

            if (! event.target?.closest) {
                return
            }

            const trigger = event.target.closest('button, a[href], [role="button"], [wire\\:click]')

            if (! trigger) {
                return
            }

            if (hoverPreloadTimer) {
                clearTimeout(hoverPreloadTimer)
            }

            hoverPreloadTimer = setTimeout(() => {
                hoverPreloadTimer = null
                void preloadBatchesIn(document)
            }, 48)
        }, { passive: true })
    }

    const scheduleIdlePreload = (root = document) => {
        if (injectorHooks.shouldSkipBackgroundPreload()) {
            return
        }

        const run = () => {
            void preloadBatchesIn(root)
        }

        if (typeof window.requestIdleCallback === 'function') {
            window.requestIdleCallback(run, { timeout: 2000 })
        } else {
            setTimeout(run, 0)
        }
    }

    const cleanupClosedModalPendingState = async (event) => {
        const modalId = event?.detail?.id

        if (typeof modalId === 'string' && modalId !== '') {
            const modal = document.getElementById(modalId)

            if (modal) {
                await releasePendingState(modal, { force: true })
            }
        }

        for (const modal of document.querySelectorAll('.fi-modal.fff-flex-fields-assets-pending')) {
            if (! modal.classList.contains('fi-modal-open')) {
                await releasePendingState(modal, { force: true })
            }
        }

        for (const stray of document.querySelectorAll('.fff-flex-fields-assets-pending:not(.fi-modal)')) {
            await releasePendingState(stray, { force: true })
        }
    }

    const boot = () => {
        ensureAssets(document)
        scheduleIdlePreload(document)

        document.addEventListener('livewire:navigated', () => {
            ensureAssets(document)
            scheduleIdlePreload(document)
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
        batchNeedsLoading,
        preloadBatchesIn,
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
        stripInlineEmitAssets,
        purgeLazyAssets,
        boot,
    }
}

export function bootFlexFieldAssetInjector(options = {}) {
    const document = options.document ?? globalThis.document
    const window = options.window ?? globalThis.window
    const injector = createFlexFieldAssetInjector({ document, window })

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => injector.boot(), { once: true })
    } else {
        injector.boot()
    }

    return injector
}
