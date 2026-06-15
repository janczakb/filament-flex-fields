function normalizeSearchQuery(query) {
    return String(query ?? '')
        .trim()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
}

function rankCountryMatch(country, query) {
    const normalizedQuery = normalizeSearchQuery(query)
    const code = country.code.toLowerCase()
    const name = normalizeSearchQuery(country.name)
    const dialCode = String(country.dial_code ?? '').replace('+', '')

    if (code === normalizedQuery) {
        return 0
    }

    if (name.startsWith(normalizedQuery)) {
        return 1
    }

    if (code.startsWith(normalizedQuery)) {
        return 2
    }

    if (dialCode.startsWith(normalizedQuery.replace('+', ''))) {
        return 3
    }

    if (name.includes(normalizedQuery) || code.includes(normalizedQuery) || dialCode.includes(normalizedQuery)) {
        return 4
    }

    return 99
}

export function createCountrySearchMixin({
    queryKey = 'countrySearch',
    debouncedQueryKey = 'countrySearchDebounced',
    sourceKey = 'countries',
    debounceMs = 100,
} = {}) {
    return {
        [debouncedQueryKey]: '',
        countrySearchTimer: null,
        countrySearchCache: new Map(),

        initCountrySearch() {
            this[debouncedQueryKey] = this[queryKey] ?? ''

            this.$watch(queryKey, (value) => {
                clearTimeout(this.countrySearchTimer)

                this.countrySearchTimer = setTimeout(() => {
                    this[debouncedQueryKey] = value

                    if (typeof this.resetVirtualListScroll === 'function') {
                        this.resetVirtualListScroll()
                    }

                    if (typeof this.resetCountryListKeyboard === 'function') {
                        this.resetCountryListKeyboard()
                    }
                }, debounceMs)
            })
        },

        filteredCountries() {
            const countries = this[sourceKey] ?? []
            const query = normalizeSearchQuery(this[debouncedQueryKey])

            if (! query) {
                return countries
            }

            const cacheKey = `${query}:${countries.length}:${countries[0]?.code ?? ''}`

            if (this.countrySearchCache.has(cacheKey)) {
                return this.countrySearchCache.get(cacheKey)
            }

            const results = countries
                .map((country) => ({ country, rank: rankCountryMatch(country, query) }))
                .filter((entry) => entry.rank < 99)
                .sort((left, right) => left.rank - right.rank || left.country.name.localeCompare(right.country.name))
                .map((entry) => entry.country)

            if (this.countrySearchCache.size > 48) {
                this.countrySearchCache.clear()
            }

            this.countrySearchCache.set(cacheKey, results)

            return results
        },
    }
}
