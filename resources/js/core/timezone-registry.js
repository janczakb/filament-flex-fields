const REGISTRY_ELEMENT_ID = 'fff-timezone-registry-data'

let parsedRegistry = null

function findRegistryElement() {
    const elements = document.querySelectorAll(`#${REGISTRY_ELEMENT_ID}`)

    if (elements.length === 0) {
        return null
    }

    return elements[elements.length - 1]
}

function readRegistryText(element) {
    if (! element) {
        return null
    }

    if (element.tagName === 'TEMPLATE') {
        return element.innerHTML.trim()
    }

    return element.textContent?.trim() ?? null
}

function parseRegistryElement(element) {
    const text = readRegistryText(element)

    if (! text) {
        return null
    }

    try {
        return JSON.parse(text)
    } catch {
        return null
    }
}

function registryHasPools(registry) {
    const pools = registry?.pools ?? {}

    return Object.keys(pools).some((pool) => Object.keys(pools[pool] ?? {}).length > 0)
}

export function resetTimezoneRegistryCache() {
    parsedRegistry = null
}

function readRegistryFromDom() {
    return parseRegistryElement(findRegistryElement())
}

export async function ensureTimezoneRegistry() {
    const fromDom = readRegistryFromDom()

    if (fromDom && registryHasPools(fromDom)) {
        parsedRegistry = fromDom

        return parsedRegistry
    }

    if (parsedRegistry && registryHasPools(parsedRegistry)) {
        return parsedRegistry
    }

    return new Promise((resolve) => {
        requestAnimationFrame(() => {
            const retry = readRegistryFromDom()

            if (retry && registryHasPools(retry)) {
                parsedRegistry = retry
                resolve(parsedRegistry)

                return
            }

            parsedRegistry = parsedRegistry ?? { locale: 'en', pools: {} }
            resolve(parsedRegistry)
        })
    })
}

/**
 * Synchronous registry read for blocking browser-timezone SSR boot.
 *
 * @returns {Record<string, [string, string]>}
 */
export function readTimezoneBootCatalogFromRegistry() {
    const registry = readRegistryFromDom()
    const pool = registry?.pools?.iana ?? {}
    const catalog = {}

    for (const [id, compact] of Object.entries(pool)) {
        if (compact?.l) {
            catalog[id] = [String(compact.l), String(compact.o ?? '')]
        }
    }

    return catalog
}

export function sortTimezonesWithPreferred(timezones, preferredId) {
    if (! preferredId) {
        return timezones
    }

    const index = timezones.findIndex((timezone) => timezone.id === preferredId)

    if (index <= 0) {
        return timezones
    }

    const preferred = timezones[index]

    return [
        preferred,
        ...timezones.slice(0, index),
        ...timezones.slice(index + 1),
    ]
}

function expandTimezone(id, compact, label = null) {
    return {
        id,
        label: label ?? compact.l,
        offset: compact.o ?? '',
        region: compact.r ?? '',
    }
}

export async function resolveTimezonesFromRegistry({
    pool = 'iana',
    timezoneFilterKey = null,
    preferredTimezoneId = null,
    sortPreferredFirst = false,
    locale = null,
}) {
    const registry = await ensureTimezoneRegistry()
    const poolData = registry.pools?.[pool] ?? {}
    const nameMap = locale && locale !== registry.locale
        ? (registry.locale_names?.[locale]?.[pool] ?? {})
        : {}
    let ids

    if (timezoneFilterKey && Array.isArray(registry.filters?.[timezoneFilterKey])) {
        ids = registry.filters[timezoneFilterKey]
    } else {
        ids = Object.keys(poolData)
    }

    let timezones = []

    for (const id of ids) {
        const compact = poolData[id]

        if (compact) {
            timezones.push(expandTimezone(id, compact, nameMap[id] ?? null))
        }
    }

    if (sortPreferredFirst) {
        timezones = sortTimezonesWithPreferred(timezones, preferredTimezoneId)
    }

    return timezones
}

if (typeof document !== 'undefined') {
    document.addEventListener('livewire:navigated', () => {
        resetTimezoneRegistryCache()
    })
}
