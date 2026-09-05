import { GeocodingApiError, searchMapboxPlaces } from '../support/mapbox-geocoding.js'

/**
 * Enterprise geocoding combobox — query cache, recent picks, stale-while-revalidate reopen.
 */
export function createGeocodingComboboxMixin({
    minSearchLength = 2,
    searchDebounce = 350,
    maxRecentSelections = 6,
    maxQueryCacheEntries = 16,
    searchInputRef = 'searchInput',
    menuRef = 'searchDropdown',
} = {}) {
    return {
        searchQuery: '',
        selectedLabel: '',
        searchResults: [],
        searchOpen: false,
        searchLoading: false,
        searchRefreshing: false,
        searchHasMinQuery: false,
        searchFocused: false,
        highlightedIndex: -1,
        searchDebounceTimer: null,
        searchBlurTimer: null,
        searchRequestId: 0,
        selectedResultId: null,
        geocodingRecentResults: [],
        lastFetchedQuery: '',
        __geocodingQueryCache: null,

        initGeocodingComboboxState() {
            if (! this.__geocodingQueryCache) {
                this.__geocodingQueryCache = {}
            }
        },

        normalizeGeocodingQuery(query) {
            return String(query ?? '').trim().toLowerCase()
        },

        mapFeaturesToSearchResults(features) {
            return features.map((feature) => ({
                id: feature.id,
                label: feature.place_name,
                feature,
            }))
        },

        readGeocodingQueryCache(query) {
            const key = this.normalizeGeocodingQuery(query)

            if (! key) {
                return []
            }

            const cached = this.__geocodingQueryCache?.[key]

            return Array.isArray(cached) ? cached.map((result) => ({ ...result })) : []
        },

        writeGeocodingQueryCache(query, results) {
            const key = this.normalizeGeocodingQuery(query)

            if (! key || ! Array.isArray(results)) {
                return
            }

            if (! this.__geocodingQueryCache) {
                this.__geocodingQueryCache = {}
            }

            this.__geocodingQueryCache[key] = results.map((result) => ({ ...result }))
            this.lastFetchedQuery = String(query ?? '').trim()

            const keys = Object.keys(this.__geocodingQueryCache)

            if (keys.length <= maxQueryCacheEntries) {
                return
            }

            for (const staleKey of keys.slice(0, keys.length - maxQueryCacheEntries)) {
                delete this.__geocodingQueryCache[staleKey]
            }
        },

        rememberGeocodingRecentResult(result) {
            if (! result?.id) {
                return
            }

            const normalized = { ...result }
            this.geocodingRecentResults = [
                normalized,
                ...this.geocodingRecentResults.filter((entry) => entry.id !== normalized.id),
            ].slice(0, maxRecentSelections)
        },

        restoreGeocodingResultsForCurrentQuery() {
            const query = this.searchQuery.trim()
            const cached = this.readGeocodingQueryCache(query)

            if (cached.length === 0) {
                return false
            }

            this.searchResults = cached
            this.syncGeocodingHighlightForSelected()

            return true
        },

        syncGeocodingHighlightForSelected() {
            if (! this.searchResults.length) {
                this.highlightedIndex = -1

                return
            }

            if (this.selectedResultId) {
                const selectedIndex = this.searchResults.findIndex((result) => result.id === this.selectedResultId)

                if (selectedIndex >= 0) {
                    this.highlightedIndex = selectedIndex

                    return
                }
            }

            if (typeof this.syncGeocodingHighlightedIndex === 'function') {
                this.syncGeocodingHighlightedIndex()
            } else {
                this.highlightedIndex = 0
            }
        },

        updateSearchHasMinQuery() {
            this.searchHasMinQuery = this.searchQuery.trim().length >= minSearchLength
        },

        ensureGeocodingResultsVisible() {
            if (this.searchResults.length > 0) {
                return true
            }

            if (this.restoreGeocodingResultsForCurrentQuery()) {
                return true
            }

            const query = this.searchQuery.trim()

            if (this.selectedResultId) {
                const fromRecent = this.geocodingRecentResults.find((entry) => entry.id === this.selectedResultId)

                if (fromRecent) {
                    this.searchResults = [{ ...fromRecent }]
                    this.syncGeocodingHighlightForSelected()

                    return true
                }
            }

            if (query !== '' && query === this.selectedLabel.trim() && this.geocodingRecentResults.length > 0) {
                this.searchResults = this.geocodingRecentResults.map((entry) => ({ ...entry }))
                this.syncGeocodingHighlightForSelected()

                return true
            }

            return false
        },

        refreshGeocodingDropdownResults({ force = false } = {}) {
            this.updateSearchHasMinQuery()

            if (! this.searchHasMinQuery) {
                this.searchResults = []
                this.searchLoading = false
                this.searchRefreshing = false

                return
            }

            const hasVisibleResults = this.ensureGeocodingResultsVisible()

            if (! force && hasVisibleResults) {
                this.scheduleGeocodingSearch({ background: true })

                return
            }

            this.scheduleGeocodingSearch({ force })
        },

        scheduleGeocodingSearch({ force = false, background = false, immediate = false } = {}) {
            window.clearTimeout(this.searchDebounceTimer)

            this.updateSearchHasMinQuery()

            if (! this.searchHasMinQuery) {
                this.searchLoading = false
                this.searchRefreshing = false

                return
            }

            const hasVisibleResults = this.searchResults.length > 0

            if (background && hasVisibleResults) {
                this.searchRefreshing = true
                this.searchLoading = false
            } else if (! hasVisibleResults || force) {
                this.searchLoading = true
                this.searchRefreshing = false
            } else {
                this.searchRefreshing = true
                this.searchLoading = false
            }

            const delay = immediate ? 0 : searchDebounce

            this.searchDebounceTimer = window.setTimeout(() => {
                this.performGeocodingSearch()
            }, delay)
        },

        dismissGeocodingDropdown({ restoreQuery = true } = {}) {
            window.clearTimeout(this.searchDebounceTimer)
            window.clearTimeout(this.searchBlurTimer)
            this.searchOpen = false
            this.searchLoading = false
            this.searchRefreshing = false
            this.highlightedIndex = -1

            if (restoreQuery) {
                this.searchQuery = this.selectedLabel
            }

            this.updateSearchHasMinQuery()
        },

        onGeocodingSearchInput() {
            this.highlightedIndex = -1
            this.searchOpen = true
            this.updateSearchHasMinQuery()

            const query = this.searchQuery.trim()

            // Below min chars: drop stale results so empty/min-chars chrome is alone
            // (do not leave prior suggestions under the “type at least N” message).
            if (query.length < minSearchLength) {
                this.searchResults = []
                this.searchLoading = false
                this.searchRefreshing = false
                window.clearTimeout(this.searchDebounceTimer)

                return
            }

            const normalized = this.normalizeGeocodingQuery(query)

            if (normalized !== this.normalizeGeocodingQuery(this.lastFetchedQuery)) {
                const cached = this.readGeocodingQueryCache(query)

                if (cached.length > 0) {
                    this.searchResults = cached
                }
            }

            this.scheduleGeocodingSearch()
        },

        onGeocodingSearchFocus() {
            this.searchFocused = true
            this.searchOpen = true
            this.refreshGeocodingDropdownResults()

            this.$nextTick(() => {
                this.$refs[searchInputRef]?.select?.()
            })
        },

        onGeocodingSearchBlur() {
            this.searchFocused = false
            window.clearTimeout(this.searchBlurTimer)

            this.searchBlurTimer = window.setTimeout(() => {
                const active = document.activeElement
                const menu = this.$refs[menuRef]

                if (menu?.contains(active)) {
                    return
                }

                // Mobile sheet owns dismiss via backdrop / handle drag — clicking
                // empty sheet chrome blurs the search input but must not close.
                if (typeof this.shouldDismissGeocodingOnBlur === 'function') {
                    if (! this.shouldDismissGeocodingOnBlur()) {
                        return
                    }
                } else if (
                    this.__fffOverlayMode === 'sheet'
                    || menu?.classList?.contains?.('fff-teleported-menu--sheet')
                    || menu?.classList?.contains?.('fff-overlay-sheet')
                ) {
                    return
                }

                this.dismissGeocodingDropdown()
            }, 120)
        },

        onGeocodingComboboxKeydown(event) {
            if (! this.searchOpen) {
                if (event.key === 'ArrowDown' || event.key === 'Enter') {
                    this.searchOpen = true
                    this.refreshGeocodingDropdownResults()
                }

                return
            }

            if (['ArrowDown', 'ArrowUp', 'Home', 'End', 'Enter', 'Escape'].includes(event.key)) {
                this.onGeocodingSearchKeydown(event)

                if (event.key === 'Escape') {
                    this.dismissGeocodingDropdown()
                    event.target?.blur?.()
                }
            }
        },

        async performGeocodingSearch() {
            const query = this.searchQuery.trim()

            if (! this.searchable || ! this.canGeocode()) {
                this.searchResults = []
                this.searchLoading = false
                this.searchRefreshing = false

                return
            }

            if (query.length < minSearchLength) {
                this.searchLoading = false
                this.searchRefreshing = false

                return
            }

            const requestId = ++this.searchRequestId
            this.geocodeError = null

            try {
                const features = await searchMapboxPlaces({
                    query,
                    accessToken: this.accessToken,
                    geocodeSearchUrl: this.geocodeSearchUrl,
                    countries: this.countries,
                    language: this.language,
                    streetAddressesOnly: this.streetAddressesOnly,
                    types: this.searchTypes,
                })

                if (requestId !== this.searchRequestId) {
                    return
                }

                const results = this.mapFeaturesToSearchResults(features)
                const hadStaleResults = this.searchRefreshing && this.searchResults.length > 0

                if (results.length > 0) {
                    this.searchResults = results
                    this.writeGeocodingQueryCache(query, results)
                    this.syncGeocodingHighlightForSelected()
                } else if (! hadStaleResults) {
                    this.searchResults = results
                    this.highlightedIndex = -1
                }
            } catch (error) {
                if (requestId !== this.searchRequestId) {
                    return
                }

                console.error(error)
                this.highlightedIndex = -1
                this.geocodeError = error instanceof GeocodingApiError
                    ? error.message
                    : this.labels?.geocodeFailed ?? 'Geocoding search failed.'

                if (! this.searchResults.length) {
                    this.searchResults = []
                }
            } finally {
                if (requestId === this.searchRequestId) {
                    this.searchLoading = false
                    this.searchRefreshing = false

                    if (this.searchOpen) {
                        this.scheduleGeocodingDropdownPosition?.()
                    }
                }
            }
        },

        finalizeGeocodingSelection(result) {
            this.selectedResultId = result?.id ?? null
            this.rememberGeocodingRecentResult(result)

            const query = this.searchQuery.trim()
            const label = String(result?.label ?? '').trim()
            const snapshot = this.searchResults.length > 0
                ? this.searchResults.map((entry) => ({ ...entry }))
                : [{ ...result }]

            if (query !== '') {
                this.writeGeocodingQueryCache(query, snapshot)
            }

            if (label !== '' && label !== query) {
                this.writeGeocodingQueryCache(label, snapshot)
            }
        },
    }
}
