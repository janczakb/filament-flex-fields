/**
 * Consumer Reference Graph — refCount per URL from connected DOM consumers (FFART F1).
 */

const REF_ZERO_DEBOUNCE_MS = 150

/**
 * @param {{
 *   registry?: Record<string, { stylesheets?: string[], chunks?: string[], entry?: string|null, kind?: string }>,
 *   normalizeUrl?: (url: string) => string,
 *   resolveSurfaceKey?: (el: Element|null) => string,
 *   isConsumerActive?: (el: Element) => boolean,
 *   onAcquire?: (instanceId: string, componentId: string, urls: string[]) => void,
 *   onRelease?: (instanceId: string, componentId: string, urls: string[]) => void,
 * }} options
 */
export function createConsumerGraph(options = {}) {
    const registry = options.registry ?? {}
    const normalizeUrl = options.normalizeUrl ?? ((url) => String(url ?? ''))
    const resolveSurfaceKey = options.resolveSurfaceKey ?? (() => 'page')
    const isConsumerActive = options.isConsumerActive ?? ((el) => el?.isConnected !== false)
    const onAcquire = options.onAcquire ?? null
    const onRelease = options.onRelease ?? null

    /** @type {Map<string, number>} */
    const refCounts = new Map()
    /** @type {Map<string, { instanceId: string, componentId: string, element: Element|null }>} */
    const activeConsumers = new Map()
    /** @type {Map<string, Set<(url: string) => void>>} */
    const refZeroCallbacks = new Map()
    /** @type {Map<string, ReturnType<typeof setTimeout>>} */
    const refZeroTimers = new Map()

    const resolveCanonicalComponent = (componentId) => {
        if (registry[componentId]) {
            return componentId
        }

        return componentId
    }

    const bundleFor = (componentId) => {
        const canonical = resolveCanonicalComponent(componentId)
        const bundle = registry[canonical]

        if (!bundle) {
            return { componentId: canonical, stylesheets: [], chunks: [], entry: null, kind: 'full' }
        }

        return {
            componentId: canonical,
            stylesheets: [...(bundle.stylesheets ?? [])],
            chunks: [...(bundle.chunks ?? [])],
            entry: bundle.entry ?? null,
            kind: bundle.kind ?? 'full',
        }
    }

    const urlsForBundle = (bundle) => {
        const urls = []

        for (const sheet of bundle.stylesheets ?? []) {
            urls.push(sheet)
        }

        for (const chunk of bundle.chunks ?? []) {
            urls.push(chunk)
        }

        if (bundle.entry) {
            urls.push(bundle.entry)
        }

        return urls
    }

    const bumpRef = (url, delta) => {
        const normalized = normalizeUrl(url)

        if (!normalized) {
            return
        }

        const next = Math.max(0, (refCounts.get(normalized) ?? 0) + delta)
        refCounts.set(normalized, next)

        if (next === 0) {
            scheduleRefZero(normalized)
        } else {
            clearRefZeroTimer(normalized)
        }
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

    const urlsForElement = (el) => {
        if (!el?.hasAttribute?.('data-fff-asset-batch')) {
            return null
        }

        return [
            ...parseJsonAttribute(el, 'data-fff-stylesheets'),
            ...parseJsonAttribute(el, 'data-fff-chunks'),
        ]
    }

    const clearRefZeroTimer = (url) => {
        const timer = refZeroTimers.get(url)

        if (timer) {
            clearTimeout(timer)
            refZeroTimers.delete(url)
        }
    }

    const scheduleRefZero = (url) => {
        clearRefZeroTimer(url)

        const timer = setTimeout(() => {
            refZeroTimers.delete(url)

            if ((refCounts.get(url) ?? 0) === 0) {
                for (const callback of refZeroCallbacks.get(url) ?? []) {
                    callback(url)
                }
            }
        }, REF_ZERO_DEBOUNCE_MS)

        refZeroTimers.set(url, timer)
    }

    const readConsumerComponent = (el) => {
        return el?.dataset?.fffAssetConsumer
            ?? el?.getAttribute?.('data-fff-asset-consumer')
            ?? null
    }

    const readConsumerId = (el) => {
        return el?.dataset?.fffAssetConsumerId
            ?? el?.getAttribute?.('data-fff-asset-consumer-id')
            ?? null
    }

    const resolveConsumerInstanceId = (el) => {
        if (!el) {
            return null
        }

        const base = readConsumerId(el) ?? readConsumerComponent(el)

        if (!base) {
            return null
        }

        const surface = resolveSurfaceKey(el)

        return `${base}::${surface}`
    }

    const acquire = (instanceId, componentId, explicitUrls = null) => {
        if (!instanceId || !componentId) {
            return
        }

        const bundle = bundleFor(componentId)
        const urls = explicitUrls?.length
            ? explicitUrls
            : urlsForBundle(bundle)

        for (const url of urls) {
            bumpRef(url, 1)
        }

        activeConsumers.set(instanceId, {
            instanceId,
            componentId: bundle.componentId,
            element: null,
            urls: [...urls],
        })

        onAcquire?.(instanceId, bundle.componentId, [...urls])
    }

    const release = (instanceId) => {
        const consumer = activeConsumers.get(instanceId)

        if (!consumer) {
            return
        }

        const urls = consumer.urls?.length
            ? consumer.urls
            : urlsForBundle(bundleFor(consumer.componentId))

        for (const url of urls) {
            bumpRef(url, -1)
        }

        onRelease?.(instanceId, consumer.componentId, [...urls])

        activeConsumers.delete(instanceId)
    }

    const registerElement = (el) => {
        const componentId = readConsumerComponent(el)

        if (!componentId) {
            return null
        }

        const instanceId = resolveConsumerInstanceId(el)

        if (!instanceId) {
            return null
        }

        const batchUrls = urlsForElement(el)
        const nextUrls = batchUrls?.length ? batchUrls : null
        const existing = activeConsumers.get(instanceId)

        if (existing) {
            const currentUrls = existing.urls?.length
                ? existing.urls
                : urlsForBundle(bundleFor(existing.componentId))
            const resolvedNextUrls = nextUrls ?? urlsForBundle(bundleFor(componentId))
            const normalizedCurrent = new Set(currentUrls.map((url) => normalizeUrl(url)))
            const normalizedNext = new Set(resolvedNextUrls.map((url) => normalizeUrl(url)))
            const sameUrls = normalizedCurrent.size === normalizedNext.size
                && [...normalizedCurrent].every((url) => normalizedNext.has(url))

            if (!sameUrls) {
                release(instanceId)
                acquire(instanceId, componentId, nextUrls)
            }
        } else {
            acquire(instanceId, componentId, nextUrls)
        }

        const consumer = activeConsumers.get(instanceId)

        if (consumer) {
            consumer.element = el
        }

        return instanceId
    }

    const resyncFromDom = (root = typeof document !== 'undefined' ? document : null) => {
        if (!root?.querySelectorAll) {
            return
        }

        const connected = new Set()

        for (const el of root.querySelectorAll('[data-fff-asset-consumer]')) {
            if (!isConsumerActive(el)) {
                continue
            }

            const instanceId = registerElement(el)

            if (instanceId) {
                connected.add(instanceId)
            }
        }

        for (const [instanceId, consumer] of [...activeConsumers.entries()]) {
            if (instanceId.startsWith('batch::')) {
                continue
            }

            if (!connected.has(instanceId)) {
                release(instanceId)
            }
        }
    }

    return {
        resolveConsumerInstanceId,
        resolveCanonicalComponent,
        bundleFor,
        acquire,
        release,
        registerElement,
        attachConsumerElement(instanceId, element) {
            const consumer = activeConsumers.get(instanceId)

            if (consumer) {
                consumer.element = element
            }
        },
        resyncFromDom,
        getRefCount(url) {
            return refCounts.get(normalizeUrl(url)) ?? 0
        },
        getRetainedUrls() {
            return [...refCounts.entries()]
                .filter(([, count]) => count > 0)
                .map(([url]) => url)
        },
        getConsumersForUrl(url) {
            const normalized = normalizeUrl(url)
            const matches = []

            for (const [instanceId, consumer] of activeConsumers.entries()) {
                const urls = consumer.urls?.length
                    ? consumer.urls
                    : urlsForBundle(bundleFor(consumer.componentId))

                if (urls.some((candidate) => normalizeUrl(candidate) === normalized)) {
                    matches.push({ ...consumer, instanceId, connected: consumer.element?.isConnected ?? false })
                }
            }

            return matches
        },
        onRefZero(url, callback) {
            const normalized = normalizeUrl(url)
            const set = refZeroCallbacks.get(normalized) ?? new Set()
            set.add(callback)
            refZeroCallbacks.set(normalized, set)
        },
        getActiveConsumerIds() {
            return [...activeConsumers.keys()]
        },
        reset() {
            refCounts.clear()
            activeConsumers.clear()

            for (const timer of refZeroTimers.values()) {
                clearTimeout(timer)
            }

            refZeroTimers.clear()
        },
    }
}
