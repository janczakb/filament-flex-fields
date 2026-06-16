import {
    computeMinSkeletonRemaining,
    extractDomain,
    fetchUrlMeta,
    hasPreviewData,
    isScrapeCandidate,
    isValidHttpUrl,
    readUrlMetaCache,
    resolveUrlWithPrefix,
    shouldShowPreviewCard,
    shouldShowPreviewSkeleton,
    shouldShowPreviewThumb,
    stripUrlPrefix,
    writeUrlMetaCache,
    DEFAULT_IMAGE_PRELOAD_TIMEOUT_MS,
} from '../support/url-meta-scrape.js'

export default function linkPreviewFieldFormComponent({
    state,
    statePath,
    initialUrl = '',
    initialPreview = null,
    disabled = false,
    readOnly = false,
    scrapeUrl,
    previewEnabled = true,
    previewDebounce = 500,
    previewMinUrlLength = 10,
    previewMinSkeletonMs = 500,
    previewLayout = 'horizontal',
    showVisitLink = true,
    prefix = null,
    labels = {},
}) {
    return {
        state,
        statePath,
        initialUrl,
        initialPreview,
        prefix,
        inputValue: '',
        disabled,
        readOnly,
        scrapeUrl,
        previewEnabled,
        previewDebounce,
        previewMinUrlLength,
        previewMinSkeletonMs,
        previewLayout,
        showVisitLink,
        labels,
        stateHydrated: false,
        isFetching: false,
        isImagePending: false,
        isMinRevealPending: false,
        error: null,
        preview: {
            title: null,
            description: null,
            image: null,
            domain: null,
        },
        debounceTimer: null,
        requestId: 0,
        lastScrapedUrl: null,
        abortController: null,

        get resolvedUrl() {
            return resolveUrlWithPrefix(this.inputValue, this.prefix)
        },

        get hasPreview() {
            return hasPreviewData(this.preview)
        },

        get showSkeleton() {
            return shouldShowPreviewSkeleton({
                isFetching: this.isFetching,
                isImagePending: this.isImagePending,
                isMinRevealPending: this.isMinRevealPending,
            })
        },

        get isRevealed() {
            return ! this.showSkeleton && this.hasPreview
        },

        get shouldShowThumb() {
            return shouldShowPreviewThumb({
                isFetching: this.isFetching,
                isImagePending: this.isImagePending,
                image: this.preview.image,
            })
        },

        get canVisit() {
            return isValidHttpUrl(this.resolvedUrl)
        },

        get visitUrl() {
            return this.canVisit ? this.resolvedUrl : null
        },

        get shouldShowCard() {
            return shouldShowPreviewCard({
                previewEnabled: this.previewEnabled,
                isFetching: this.isFetching,
                isImagePending: this.isImagePending,
                preview: this.preview,
            })
        },

        init() {
            this.hydrateStateFromInput()

            this.$watch('state', () => {
                if (! this.stateHydrated) {
                    return
                }

                this.syncInputFromState()
            })

            return () => {
                clearTimeout(this.debounceTimer)
                this.abortController?.abort()
            }
        },

        toDisplayValue(url) {
            return stripUrlPrefix(url, this.prefix)
        },

        hydrateStateFromInput() {
            const fallback = String(this.initialUrl ?? '').trim()
            const currentState = String(this.state ?? '').trim()
            const rawUrl = currentState !== '' ? currentState : fallback

            this.inputValue = this.toDisplayValue(rawUrl)

            this.stateHydrated = true

            const resolved = this.resolvedUrl

            if (this.initialPreview && isScrapeCandidate(resolved, this.previewMinUrlLength)) {
                this.revealPreview(this.initialPreview, resolved, { minSkeleton: true })

                return
            }

            if (isScrapeCandidate(resolved, this.previewMinUrlLength)) {
                this.applyPreviewFromCacheOrScrape(resolved, { minSkeleton: true })
            }
        },

        async revealPreview(preview, url, { minSkeleton = false, requestId = null } = {}) {
            const currentRequestId = requestId ?? ++this.requestId
            const startedAt = Date.now()

            this.error = null
            this.isFetching = true
            this.isImagePending = false
            this.isMinRevealPending = false

            await this.commitPreview(preview, url, currentRequestId)

            if (currentRequestId !== this.requestId) {
                return
            }

            if (! this.hasPreview) {
                this.isFetching = false

                return
            }

            if (minSkeleton) {
                const remaining = computeMinSkeletonRemaining(startedAt, this.previewMinSkeletonMs)

                if (remaining > 0) {
                    this.isMinRevealPending = true
                    await new Promise((resolve) => setTimeout(resolve, remaining))
                    this.isMinRevealPending = false
                }
            }

            if (currentRequestId !== this.requestId) {
                return
            }

            this.isFetching = false
        },

        syncInputFromState() {
            const next = typeof this.state === 'string' ? this.state : ''
            const displayNext = this.toDisplayValue(next)

            if (displayNext === this.inputValue) {
                return
            }

            this.inputValue = displayNext

            const resolved = this.resolvedUrl

            if (! resolved) {
                this.resetPreview()

                return
            }

            if (isScrapeCandidate(resolved, this.previewMinUrlLength)) {
                this.applyPreviewFromCacheOrScrape(resolved)
            } else {
                this.resetPreview(false)
            }
        },

        onInput() {
            this.error = null

            const resolved = this.resolvedUrl

            this.state = resolved === '' ? null : resolved

            if (! resolved) {
                this.resetPreview()

                return
            }

            if (! isScrapeCandidate(resolved, this.previewMinUrlLength)) {
                this.resetPreview(false)

                return
            }

            this.scheduleScrape(resolved)
        },

        onBlur() {
            const resolved = this.resolvedUrl

            this.state = resolved === '' ? null : resolved
            this.inputValue = this.toDisplayValue(resolved)
        },

        onImageError() {
            this.preview.image = null
        },

        scheduleScrape(url, immediate = false) {
            if (! this.previewEnabled || this.disabled || this.readOnly) {
                return
            }

            if (this.lastScrapedUrl === url && this.hasPreview) {
                return
            }

            clearTimeout(this.debounceTimer)

            if (immediate || this.previewDebounce <= 0) {
                this.applyPreviewFromCacheOrScrape(url)

                return
            }

            this.debounceTimer = setTimeout(() => {
                this.applyPreviewFromCacheOrScrape(url)
            }, this.previewDebounce)
        },

        applyPreviewFromCacheOrScrape(url, { minSkeleton = false } = {}) {
            const cached = readUrlMetaCache(url)

            if (cached && hasPreviewData(cached)) {
                this.revealPreview(cached, url, { minSkeleton })

                return
            }

            this.scrape(url, { minSkeleton })
        },

        resetPreview(clearDomain = true) {
            this.isFetching = false
            this.isImagePending = false
            this.isMinRevealPending = false
            this.error = null
            this.lastScrapedUrl = null
            this.abortController?.abort()
            this.preview = {
                title: null,
                description: null,
                image: null,
                domain: clearDomain ? null : this.preview.domain,
            }
        },

        scrape(url, { minSkeleton = false } = {}) {
            if (! isScrapeCandidate(url, this.previewMinUrlLength)) {
                return
            }

            const requestId = ++this.requestId

            this.abortController?.abort()
            this.abortController = new AbortController()

            this.isFetching = true
            this.isImagePending = false
            this.isMinRevealPending = false
            this.error = null
            this.preview = {
                title: null,
                description: null,
                image: null,
                domain: extractDomain(url),
            }

            fetchUrlMeta(this.scrapeUrl, url, this.abortController.signal)
                .then(async (data) => {
                    if (requestId !== this.requestId) {
                        return
                    }

                    await this.revealPreview(data, url, { minSkeleton, requestId })
                })
                .catch((error) => {
                    if (requestId !== this.requestId || error?.name === 'AbortError') {
                        return
                    }

                    this.isFetching = false
                    this.isImagePending = false
                    this.isMinRevealPending = false
                    this.error = this.labels.error ?? 'Preview unavailable'
                    this.preview = {
                        title: null,
                        description: null,
                        image: null,
                        domain: null,
                    }
                })
        },

        async commitPreview(preview, url, requestId) {
            const normalized = {
                title: preview.title ?? null,
                description: preview.description ?? null,
                image: preview.image ?? null,
                domain: extractDomain(url),
            }

            if (! hasPreviewData(normalized)) {
                this.isImagePending = false
                this.preview = normalized

                return
            }

            this.lastScrapedUrl = url
            this.preview = normalized

            if (normalized.image) {
                this.isImagePending = true

                const imageLoaded = await this.preloadImage(normalized.image)

                if (requestId !== this.requestId) {
                    return
                }

                this.isImagePending = false

                if (! imageLoaded) {
                    this.preview = {
                        ...normalized,
                        image: null,
                    }
                }

                writeUrlMetaCache(url, {
                    title: this.preview.title,
                    description: this.preview.description,
                    image: this.preview.image,
                })

                return
            }

            this.isImagePending = false

            writeUrlMetaCache(url, {
                title: normalized.title,
                description: normalized.description,
                image: null,
            })
        },

        preloadImage(src) {
            return new Promise((resolve) => {
                const image = new Image()
                image.decoding = 'async'

                let settled = false

                const finish = (success) => {
                    if (settled) {
                        return
                    }

                    settled = true
                    clearTimeout(timer)
                    resolve(success)
                }

                const timer = setTimeout(() => finish(false), DEFAULT_IMAGE_PRELOAD_TIMEOUT_MS)

                image.onload = () => finish(true)
                image.onerror = () => finish(false)
                image.src = src

                if (image.complete && image.naturalWidth > 0) {
                    finish(true)
                }
            })
        },
    }
}
