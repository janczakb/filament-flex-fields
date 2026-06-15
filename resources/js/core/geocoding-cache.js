const memoryCache = new Map()

const DEFAULT_MAX_ENTRIES = 128

export function geocodingCacheKey(mode, payload) {
    return `${mode}:${JSON.stringify(payload)}`
}

export function readGeocodingCache(key) {
    return memoryCache.get(key) ?? null
}

export function writeGeocodingCache(key, value, maxEntries = DEFAULT_MAX_ENTRIES) {
    if (memoryCache.size >= maxEntries) {
        const oldestKey = memoryCache.keys().next().value

        if (oldestKey) {
            memoryCache.delete(oldestKey)
        }
    }

    memoryCache.set(key, value)
}

export function clearGeocodingCache() {
    memoryCache.clear()
}
