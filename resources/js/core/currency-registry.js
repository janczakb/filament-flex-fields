const REGISTRY_ELEMENT_ID = 'fff-currency-registry-data'

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

export function resetCurrencyRegistryCache() {
    parsedRegistry = null
}

function readRegistryFromDom() {
    return parseRegistryElement(findRegistryElement())
}

export async function ensureCurrencyRegistry() {
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

function expandCurrency(code, compact) {
    return {
        code,
        symbol: compact.s ?? '',
        name: compact.n ?? '',
        decimals: Number(compact.d ?? 2),
        locale: compact.l ?? 'en',
    }
}

export async function resolveCurrenciesFromRegistry({
    pool = 'iso',
    currencyFilterKey = null,
}) {
    const registry = await ensureCurrencyRegistry()
    const poolData = registry.pools?.[pool] ?? {}
    let codes

    if (currencyFilterKey && Array.isArray(registry.filters?.[currencyFilterKey])) {
        codes = registry.filters[currencyFilterKey]
    } else {
        codes = Object.keys(poolData)
    }

    const currencies = []

    for (const code of codes) {
        const compact = poolData[code]

        if (compact) {
            currencies.push(expandCurrency(code, compact))
        }
    }

    return currencies
}

if (typeof document !== 'undefined') {
    document.addEventListener('livewire:navigated', () => {
        resetCurrencyRegistryCache()
    })
}
