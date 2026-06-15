const REGISTRY_ELEMENT_ID = 'fff-country-registry-data'

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

export function resetCountryRegistryCache() {
    parsedRegistry = null
}

function readRegistryFromDom() {
    return parseRegistryElement(findRegistryElement())
}

export async function ensureCountryRegistry() {
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

export function sortCountriesWithPreferred(countries, preferredCode) {
    if (! preferredCode) {
        return countries
    }

    const preferred = preferredCode.toUpperCase()
    const index = countries.findIndex((country) => country.code === preferred)

    if (index <= 0) {
        return countries
    }

    const preferredCountry = countries[index]

    return [
        preferredCountry,
        ...countries.slice(0, index),
        ...countries.slice(index + 1),
    ]
}

function expandCountry(compact) {
    const dialCode = compact.d ?? ''

    return {
        code: compact.c,
        name: compact.n,
        dial_code: dialCode !== '' ? dialCode : null,
        flag_url: compact.f,
    }
}

export async function resolveCountriesFromRegistry({
    pool,
    allowedCountryCodes = null,
    countryFilterKey = null,
    preferredCountryCode = null,
    sortPreferredFirst = false,
}) {
    const registry = await ensureCountryRegistry()
    const poolData = registry.pools?.[pool] ?? {}
    let codes

    if (countryFilterKey && Array.isArray(registry.filters?.[countryFilterKey])) {
        codes = registry.filters[countryFilterKey]
    } else if (Array.isArray(allowedCountryCodes) && allowedCountryCodes.length > 0) {
        codes = allowedCountryCodes
    } else {
        codes = Object.keys(poolData)
    }

    let countries = []

    for (const code of codes) {
        const compact = poolData[code]

        if (compact) {
            countries.push(expandCountry(compact))
        }
    }

    if (sortPreferredFirst) {
        countries = sortCountriesWithPreferred(countries, preferredCountryCode)
    }

    return countries
}

if (typeof document !== 'undefined') {
    document.addEventListener('livewire:navigated', () => {
        resetCountryRegistryCache()
    })
}
