export const ICON_PICKER_SVG_BATCH_SIZE = 48

export const ICON_PICKER_SEARCH_CACHE_MAX_ENTRIES = 32

export function trimSearchResultsCache(cache, maxEntries = ICON_PICKER_SEARCH_CACHE_MAX_ENTRIES) {
    if (! (cache instanceof Map)) {
        return
    }

    while (cache.size > maxEntries) {
        const firstKey = cache.keys().next().value

        if (firstKey === undefined) {
            break
        }

        cache.delete(firstKey)
    }
}
