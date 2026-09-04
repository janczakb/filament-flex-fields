/**
 * Unified prefetch tiers for FFART (hover intent, viewport, idle, high).
 */

const HOVER_INTENT_MS = 48

/**
 * @param {{
 *   shouldSkipBackgroundPreload?: () => boolean,
 *   preloadBatchesIn?: (root?: Element | Document) => Promise<void>,
 *   preloadVisibleBatchesIn?: (root?: Element | Document) => Promise<void>,
 *   resolveHoverPreloadScope?: (trigger: Element) => Element | null,
 *   acquireTempPrefetch?: (instanceId: string, componentId: string) => void,
 *   releaseTempPrefetch?: (instanceId: string) => void,
 *   resolveTempPrefetchInstanceId?: (scope: Element) => string,
 *   resolveComponentIdFromScope?: (scope: Element) => string,
 *   window?: Window,
 *   document?: Document,
 * }} options
 */
export function createPrefetchEngine(options = {}) {
    const win = options.window ?? globalThis.window
    const doc = options.document ?? globalThis.document
    const shouldSkip = options.shouldSkipBackgroundPreload ?? (() => false)
    const preloadBatchesIn = options.preloadBatchesIn ?? (async () => {})
    const preloadVisibleBatchesIn = options.preloadVisibleBatchesIn ?? (async () => {})
    const resolveHoverPreloadScope = options.resolveHoverPreloadScope ?? (() => null)
    const acquireTempPrefetch = options.acquireTempPrefetch ?? (() => {})
    const releaseTempPrefetch = options.releaseTempPrefetch ?? (() => {})
    const resolveTempPrefetchInstanceId = options.resolveTempPrefetchInstanceId
        ?? ((scope) => `prefetch-hover::${scope?.tagName ?? 'unknown'}`)
    const resolveComponentIdFromScope = options.resolveComponentIdFromScope ?? (() => 'prefetch')

    /** @type {ReturnType<typeof setTimeout> | null} */
    let hoverTimer = null
    /** @type {{ scope: Element, instanceId: string } | null} */
    let activeHoverClaim = null
    /** @type {IntersectionObserver | null} */
    let viewportObserver = null
    let idleScheduled = false

    const clearHoverClaim = () => {
        if (activeHoverClaim) {
            releaseTempPrefetch(activeHoverClaim.instanceId)
            activeHoverClaim = null
        }
    }

    const registerHoverPreload = () => {
        doc.addEventListener('mouseover', (event) => {
            if (shouldSkip() || !event.target?.closest) {
                return
            }

            const trigger = event.target.closest('button, a[href], [role="button"], [wire\\:click]')

            if (!trigger) {
                return
            }

            if (hoverTimer) {
                clearTimeout(hoverTimer)
            }

            hoverTimer = setTimeout(() => {
                hoverTimer = null

                const scope = resolveHoverPreloadScope(trigger)

                if (!scope) {
                    return
                }

                clearHoverClaim()

                const instanceId = resolveTempPrefetchInstanceId(scope)
                acquireTempPrefetch(instanceId, resolveComponentIdFromScope(scope))
                activeHoverClaim = { scope, instanceId }

                void preloadBatchesIn(scope)
            }, HOVER_INTENT_MS)
        }, { passive: true })

        doc.addEventListener('mouseout', (event) => {
            if (!activeHoverClaim?.scope || !event.target?.closest) {
                return
            }

            const related = event.relatedTarget

            if (related && activeHoverClaim.scope.contains(related)) {
                return
            }

            if (hoverTimer) {
                clearTimeout(hoverTimer)
                hoverTimer = null
            }

            clearHoverClaim()
        }, { passive: true })
    }

    const observeViewportBatches = () => {
        if (shouldSkip() || typeof IntersectionObserver !== 'function') {
            return
        }

        viewportObserver = new IntersectionObserver((entries) => {
            for (const entry of entries) {
                if (!entry.isIntersecting) {
                    continue
                }

                const batch = entry.target

                if (batch?.matches?.('[data-fff-asset-batch]')) {
                    void preloadBatchesIn(batch.parentElement ?? batch)
                }

                viewportObserver?.unobserve(entry.target)
            }
        }, { rootMargin: '120px 0px' })

        for (const batch of doc.querySelectorAll('[data-fff-asset-batch]')) {
            viewportObserver.observe(batch)
        }
    }

    const scheduleIdlePrefetch = () => {
        if (shouldSkip() || idleScheduled) {
            return
        }

        idleScheduled = true

        const run = () => {
            idleScheduled = false
            void preloadVisibleBatchesIn(doc)
        }

        if (typeof win.requestIdleCallback === 'function') {
            win.requestIdleCallback(run, { timeout: 2000 })
        } else {
            win.setTimeout(run, 250)
        }
    }

    const boot = () => {
        registerHoverPreload()
        observeViewportBatches()
        scheduleIdlePrefetch()
    }

    const disconnect = () => {
        viewportObserver?.disconnect()
        viewportObserver = null
        clearHoverClaim()

        if (hoverTimer) {
            clearTimeout(hoverTimer)
            hoverTimer = null
        }
    }

    return {
        boot,
        disconnect,
        scheduleIdlePrefetch,
        observeViewportBatches,
        getActiveHoverClaim: () => activeHoverClaim,
    }
}
