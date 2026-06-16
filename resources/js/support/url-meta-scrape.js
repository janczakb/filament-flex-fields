const memoryCache = new Map()

const inFlightRequests = new Map()

const DEFAULT_MAX_ENTRIES = 64

const DEFAULT_TTL_MS = 300_000

const DEFAULT_FETCH_TIMEOUT_MS = 15_000

export const DEFAULT_IMAGE_PRELOAD_TIMEOUT_MS = 5_000

const BLOCKED_SCRAPE_HOSTS = new Set([
    'localhost',
    '127.0.0.1',
    '0.0.0.0',
    '::1',
    'metadata.google.internal',
    'metadata.goog',
])

const PRIVATE_IP_PATTERN = /^(10\.|172\.(1[6-9]|2\d|3[01])\.|192\.168\.|169\.254\.)/

export function isBlockedScrapeHost(hostname) {
    const host = String(hostname ?? '').trim().toLowerCase()

    if (host === '' || BLOCKED_SCRAPE_HOSTS.has(host)) {
        return true
    }

    if (host.endsWith('.localhost') || host.endsWith('.local') || host.endsWith('.internal')) {
        return true
    }

    return PRIVATE_IP_PATTERN.test(host)
}

/**
 * @typedef {{ title?: string|null, description?: string|null, image?: string|null }} UrlMetaPreview
 * @typedef {{ value: UrlMetaPreview, expiresAt: number }} CachedUrlMeta
 */

export function isValidHttpUrl(value) {
    try {
        const url = new URL(value)

        return url.protocol === 'http:' || url.protocol === 'https:'
    } catch {
        return false
    }
}

export function extractDomain(value) {
    try {
        return new URL(value).hostname
    } catch {
        return ''
    }
}

export function isScrapeCandidate(url, minLength = 10) {
    if (! isValidHttpUrl(url)) {
        return false
    }

    if (url.length < minLength) {
        return false
    }

    try {
        const hostname = new URL(url).hostname

        if (isBlockedScrapeHost(hostname)) {
            return false
        }

        return hostname.includes('.')
    } catch {
        return false
    }
}

export function readUrlMetaCache(url) {
    const cached = memoryCache.get(url)

    if (! cached) {
        return null
    }

    if (cached.expiresAt <= Date.now()) {
        memoryCache.delete(url)

        return null
    }

    return cached.value
}

/**
 * @param {string} url
 * @param {UrlMetaPreview} value
 * @param {number} ttlMs
 */
export function writeUrlMetaCache(url, value, ttlMs = DEFAULT_TTL_MS, maxEntries = DEFAULT_MAX_ENTRIES) {
    if (memoryCache.size >= maxEntries) {
        const oldestKey = memoryCache.keys().next().value

        if (oldestKey) {
            memoryCache.delete(oldestKey)
        }
    }

    memoryCache.set(url, {
        value,
        expiresAt: Date.now() + ttlMs,
    })
}

/**
 * @param {...(AbortSignal|undefined|null)} signals
 */
export function mergeAbortSignals(...signals) {
    const controller = new AbortController()

    for (const signal of signals.filter(Boolean)) {
        if (signal.aborted) {
            controller.abort()

            return controller.signal
        }

        signal.addEventListener('abort', () => controller.abort(), { once: true })
    }

    return controller.signal
}

/**
 * @param {string} scrapeUrl
 * @param {string} url
 * @param {AbortSignal|undefined|null} signal
 * @returns {Promise<UrlMetaPreview>}
 */
export async function fetchUrlMeta(scrapeUrl, url, signal) {
    const cached = readUrlMetaCache(url)

    if (cached) {
        return cached
    }

    if (inFlightRequests.has(url)) {
        return inFlightRequests.get(url)
    }

    const request = (async () => {
        const timeoutSignal = typeof AbortSignal.timeout === 'function'
            ? AbortSignal.timeout(DEFAULT_FETCH_TIMEOUT_MS)
            : null

        const response = await fetch(`${scrapeUrl}?url=${encodeURIComponent(url)}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            signal: mergeAbortSignals(signal, timeoutSignal),
        })

        if (! response.ok) {
            throw new Error('url_meta_scrape_failed')
        }

        const data = await response.json()

        const preview = {
            title: normalizePreviewValue(data.title),
            description: normalizePreviewValue(data.description),
            image: normalizePreviewValue(data.image),
        }

        if (hasPreviewData(preview)) {
            writeUrlMetaCache(url, preview)
        }

        return preview
    })().finally(() => {
        inFlightRequests.delete(url)
    })

    inFlightRequests.set(url, request)

    return request
}

export function normalizePreviewValue(value) {
    if (typeof value !== 'string') {
        return null
    }

    const trimmed = value.trim()

    return trimmed === '' ? null : trimmed
}

/**
 * @param {{ title?: string|null, description?: string|null, image?: string|null }} preview
 */
export function hasPreviewData(preview) {
    return !!(
        normalizePreviewValue(preview?.title)
        || normalizePreviewValue(preview?.description)
        || normalizePreviewValue(preview?.image)
    )
}

export function shouldShowPreviewCard({ previewEnabled, isFetching, isImagePending, preview }) {
    if (! previewEnabled) {
        return false
    }

    if (isFetching || isImagePending) {
        return true
    }

    return hasPreviewData(preview)
}

export function shouldShowPreviewThumb({ isFetching, isImagePending, image }) {
    if (isFetching || isImagePending) {
        return true
    }

    return !! normalizePreviewValue(image)
}

export function shouldShowPreviewSkeleton({ isFetching, isImagePending, isMinRevealPending = false }) {
    return isFetching || isImagePending || isMinRevealPending
}

export function computeMinSkeletonRemaining(startedAt, minMs, now = Date.now()) {
    return Math.max(0, minMs - (now - startedAt))
}

export function stripUrlPrefix(value, prefix) {
    if (! prefix || typeof value !== 'string') {
        return typeof value === 'string' ? value.trim() : ''
    }

    const trimmed = value.trim()

    if (trimmed === '') {
        return ''
    }

    if (trimmed.startsWith(prefix)) {
        return trimmed.slice(prefix.length)
    }

    return trimmed
}

export function resolveUrlWithPrefix(value, prefix) {
    const trimmed = typeof value === 'string' ? value.trim() : ''

    if (trimmed === '') {
        return ''
    }

    if (! prefix) {
        return trimmed
    }

    if (/^https?:\/\//i.test(trimmed)) {
        return trimmed
    }

    return `${prefix}${trimmed}`
}
