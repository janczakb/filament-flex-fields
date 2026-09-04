/**
 * Dev-time inspector for Flex Fields FFART runtime (v3).
 */

const LEGACY_SYMBOLS = [
    'pageRetainedUrls',
    'modalOwnedUrls',
    'claimAssetUrls',
    'collectRetainedAssetUrls',
    'claimOverlay',
    'releaseOverlay',
]

const PERFORMANCE_MARKS = [
    'fff:acquire',
    'fff:load',
    'fff:release',
    'fff:uninstall',
    'fff:barrier',
]

export const INVARIANT_IDS = Array.from({ length: 38 }, (_value, index) => `I${index + 1}`)

export function evaluateInvariants(ctx) {
    const {
        duplicates,
        refCounts,
        consumers,
        modalStack,
        inflight,
        legacySymbols,
        urls,
        document,
        injector,
    } = ctx

    const pass = (ok) => (ok ? 'pass' : 'fail')
    const skip = () => 'skip'

    const managedLinks = document?.querySelectorAll?.('link[data-fff-managed-asset]') ?? []
    const legacyInline = document?.querySelectorAll?.('link[data-fff-stylesheet]') ?? []
    const playgroundBundles = document?.querySelectorAll?.('link[data-fff-playground-bundle]') ?? []
    const coreLinks = urls.filter((url) => url.includes('flex-fields-core.css'))
    const inflightSet = new Set(inflight)
    const hrefCounts = new Map()

    for (const url of urls) {
        hrefCounts.set(url, (hrefCounts.get(url) ?? 0) + 1)
    }

    const connectedConsumers = consumers.filter((consumer) => consumer.connected)
    const instanceIds = connectedConsumers.map((consumer) => `${consumer.id}::${consumer.surface}`)
    const uniqueInstanceIds = new Set(instanceIds)

    const performanceMarks = typeof globalThis?.performance?.getEntriesByName === 'function'
        ? PERFORMANCE_MARKS.filter((name) => globalThis.performance.getEntriesByName(name).length > 0)
        : []

    return {
        I1: pass(duplicates.length === 0),
        I2: pass(inflight.length === new Set(inflight).size),
        I3: pass(Object.values(refCounts).every((count) => Number.isFinite(count) && count >= 0)),
        I4: skip(),
        I5: skip(),
        I6: skip(),
        I7: pass(modalStack.length === new Set(modalStack).size),
        I8: skip(),
        I9: skip(),
        I10: skip(),
        I11: skip(),
        I12: pass([...playgroundBundles].every((link) => link.isConnected !== false)),
        I13: skip(),
        I14: skip(),
        I15: skip(),
        I16: pass(coreLinks.length <= 1),
        I17: skip(),
        I18: skip(),
        I19: skip(),
        I20: skip(),
        I21: skip(),
        I22: skip(),
        I23: skip(),
        I24: skip(),
        I25: skip(),
        I26: skip(),
        I27: pass(instanceIds.length === 0 || uniqueInstanceIds.size === instanceIds.length),
        I28: skip(),
        I29: skip(),
        I30: skip(),
        I31: pass(typeof injector?.getInflightUrls === 'function'),
        I32: pass(!legacySymbols.includes('pageRetainedUrls') && !legacySymbols.includes('modalOwnedUrls')),
        I33: pass([...hrefCounts.values()].every((count) => count <= 1)),
        I34: pass(legacySymbols.length === 0),
        I35: pass(typeof injector?.scheduleResyncFromDom === 'function'),
        I36: skip(),
        I37: pass(typeof injector?.getConsumerGraph === 'function'),
        I38: skip(),
        performanceMarks,
        managedLinkCount: managedLinks.length,
        legacyInlineCount: legacyInline.length,
        retainedUrlCount: Object.keys(refCounts).length,
    }
}

/**
 * @param {{
 *   getInjector?: () => object | null,
 *   getLoadedUrls?: () => string[],
 *   document?: Document,
 * }} options
 */
export function createAssetInspector({ getInjector = () => null, getLoadedUrls = () => [], document = globalThis.document } = {}) {
    if (typeof getLoadedUrls !== 'function') {
        throw new TypeError('createAssetInspector requires getLoadedUrls to be a function.')
    }

    const listUrls = () => getLoadedUrls().filter((url) => typeof url === 'string' && url !== '')

    const duplicateHrefs = () => {
        const seen = new Map()
        const duplicates = []

        for (const url of listUrls()) {
            if (seen.has(url)) {
                if (!duplicates.includes(url)) {
                    duplicates.push(url)
                }

                continue
            }

            seen.set(url, true)
        }

        return duplicates
    }

    const inspect = () => {
        const injector = getInjector?.() ?? null
        const crg = injector?.getConsumerGraph?.() ?? null
        const urls = listUrls()
        const duplicates = duplicateHrefs()
        const refCounts = {}
        const consumers = []
        const inflight = injector?.getInflightUrls?.() ?? []
        const modalStack = injector?.getModalOpenStack?.() ?? []
        const legacySymbols = LEGACY_SYMBOLS.filter((symbol) => {
            return injector != null && symbol in injector
        })

        if (crg?.getRetainedUrls) {
            for (const url of crg.getRetainedUrls()) {
                refCounts[url] = crg.getRefCount(url)
            }
        }

        if (document?.querySelectorAll) {
            for (const el of document.querySelectorAll('[data-fff-asset-consumer]')) {
                consumers.push({
                    id: el.dataset?.fffAssetConsumerId ?? null,
                    component: el.dataset?.fffAssetConsumer ?? null,
                    connected: el.isConnected !== false,
                    surface: injector?.resolveAssetOwnerKey?.(el) ?? null,
                })
            }
        }

        const evaluated = evaluateInvariants({
            duplicates,
            refCounts,
            consumers,
            modalStack,
            inflight,
            legacySymbols,
            urls,
            document,
            injector,
        })

        const {
            performanceMarks,
            managedLinkCount,
            legacyInlineCount,
            retainedUrlCount,
            ...invariantResults
        } = evaluated

        const failing = Object.entries(invariantResults)
            .filter(([, value]) => value === 'fail')
            .map(([key]) => key)

        return {
            invariants: invariantResults,
            legacySymbols,
            refCounts,
            consumers,
            duplicates,
            modalStack,
            inflight,
            performanceMarks,
            managedLinkCount,
            legacyInlineCount,
            retainedUrlCount,
            slaViolations: duplicates.length > 0 ? ['duplicate-link-href'] : [],
            failingInvariants: failing,
            urls,
        }
    }

    return {
        listUrls,
        duplicateHrefs,
        inspect,
        evaluateInvariants,
    }
}

if (typeof window !== 'undefined') {
    window.createAssetInspector = createAssetInspector
    window.FffAssetInspector = {
        create: createAssetInspector,
        inspect() {
            const injector = window.FffAssetInjector ?? window.__fffFlexFieldAssetInjector ?? null

            return createAssetInspector({
                getInjector: () => injector,
                getLoadedUrls: () => {
                    if (!document) {
                        return []
                    }

                    return [...document.querySelectorAll('link[href*="filament-flex-fields"]')]
                        .map((link) => link.href)
                },
            }).inspect()
        },
    }
}
