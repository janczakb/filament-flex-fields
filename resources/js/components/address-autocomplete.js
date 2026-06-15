import {
    emptyAddressCanonical,
    GeocodingApiError,
    hasStreetAddress,
    isStreetLevelFeature,
    parseGeocodeFeature,
    searchMapboxPlaces,
} from '../support/mapbox-geocoding.js'
import { createExclusiveDropdownMixin } from '../core/flex-dropdown-coordinator.js'
import { createGeocodingListKeyboardMixin } from '../core/geocoding-list-keyboard.js'
import { createSearchableSelectMenuMixin } from '../core/searchable-select-menu.js'

const exclusiveDropdown = createExclusiveDropdownMixin({
    openKey: 'searchOpen',
    closeMethod: 'closeSearchDropdown',
    ownerIdPrefix: 'fff-address-autocomplete',
})

const geocodingDropdown = createSearchableSelectMenuMixin({
    openKey: 'searchOpen',
    readyKey: 'searchDropdownReady',
    triggerRef: 'searchInput',
    menuRef: 'searchDropdown',
    closeMethod: 'closeSearchDropdown',
    ownerIdPrefix: 'fff-address-autocomplete',
})

const geocodingKeyboard = createGeocodingListKeyboardMixin({
    openKey: 'searchOpen',
    resultsKey: 'searchResults',
    menuRef: 'searchDropdown',
    searchRef: 'searchInput',
    optionIdPrefix: 'fff-address-autocomplete-option',
})

export default function addressAutocompleteFormComponent({
    state,
    accessToken,
    geocodeSearchUrl = null,
    geocodeReverseUrl = null,
    searchable,
    countries,
    language,
    streetAddressesOnly,
    searchTypes = null,
    labels,
    readOnly,
    minSearchLength = 2,
    searchDebounce = 350,
}) {
    return {
        ...exclusiveDropdown,
        ...geocodingDropdown,
        ...geocodingKeyboard,
        state,
        accessToken,
        geocodeSearchUrl,
        geocodeReverseUrl,
        searchable,
        countries,
        language,
        streetAddressesOnly,
        searchTypes,
        labels,
        readOnly,
        minSearchLength,
        searchDebounce,
        selectionError: null,
        geocodeError: null,
        searchQuery: '',
        selectedLabel: '',
        searchResults: [],
        searchOpen: false,
        searchDropdownReady: false,
        searchLoading: false,
        searchHasMinQuery: false,
        searchFocused: false,
        highlightedIndex: -1,
        searchDebounceTimer: null,
        searchRequestId: 0,
        tokenError: null,

        init() {
            this.wireExclusiveFlexDropdown()
            this.bindSelectMenuLifecycle({ wireExclusive: false })
            this.initGeocodingListKeyboard()
            this.syncSearchInputFromState()
            this.updateSearchHasMinQuery()

            if (! this.canGeocode()) {
                this.tokenError = labels.missingToken
            }

            this.$watch('searchQuery', () => {
                this.updateSearchHasMinQuery()
            })

            this.$watch('state', () => {
                this.syncSearchInputFromState()
            })
        },

        updateSearchHasMinQuery() {
            this.searchHasMinQuery = this.searchQuery.trim().length >= this.minSearchLength
        },

        buildLabelFromState() {
            const parts = [
                this.state?.place_name,
                this.state?.street,
                this.state?.city,
                this.state?.country_name ?? this.state?.country,
            ].filter((value) => value !== null && value !== undefined && String(value).trim() !== '')

            return parts.length > 0 ? String(parts[0]) : ''
        },

        syncSearchInputFromState() {
            this.selectedLabel = this.buildLabelFromState()

            if (! this.searchFocused) {
                this.searchQuery = this.selectedLabel
            }
        },

        scheduleSearch() {
            window.clearTimeout(this.searchDebounceTimer)

            if (! this.searchHasMinQuery) {
                this.searchLoading = false
                this.searchResults = []

                return
            }

            this.searchLoading = true
            this.searchResults = []

            this.searchDebounceTimer = window.setTimeout(() => {
                this.performSearch()
            }, this.searchDebounce)
        },

        onSearchInput() {
            this.highlightedIndex = -1
            this.searchOpen = true
            this.updateSearchHasMinQuery()
            this.scheduleSearch()
        },

        onSearchFocus() {
            this.searchFocused = true
            this.searchOpen = true

            this.$nextTick(() => {
                this.$refs.searchInput?.select?.()
            })
        },

        onSearchBlur() {
            this.searchFocused = false
            window.clearTimeout(this.searchDebounceTimer)

            window.setTimeout(() => {
                this.searchOpen = false
                this.searchLoading = false
                this.highlightedIndex = -1
                this.searchQuery = this.selectedLabel
                this.searchResults = []
                this.updateSearchHasMinQuery()
            }, 150)
        },

        canGeocode() {
            return Boolean(this.geocodeSearchUrl || this.accessToken)
        },

        onSearchKeydown(event) {
            if (! this.searchOpen) {
                if (event.key === 'ArrowDown' || event.key === 'Enter') {
                    this.searchOpen = true
                    this.scheduleSearch()
                }

                return
            }

            if (['ArrowDown', 'ArrowUp', 'Home', 'End', 'Enter', 'Escape'].includes(event.key)) {
                this.onGeocodingSearchKeydown(event)

                if (event.key === 'Escape') {
                    this.highlightedIndex = -1
                    this.searchQuery = this.selectedLabel
                    this.searchResults = []
                    event.target?.blur?.()
                }
            }
        },

        async performSearch() {
            const query = this.searchQuery.trim()

            if (! this.searchable || ! this.canGeocode()) {
                this.searchResults = []
                this.searchLoading = false

                return
            }

            if (query.length < this.minSearchLength) {
                this.searchResults = []
                this.searchLoading = false

                return
            }

            const requestId = ++this.searchRequestId
            this.searchLoading = true
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

                this.searchResults = features.map((feature) => ({
                    id: feature.id,
                    label: feature.place_name,
                    feature,
                }))
                this.syncGeocodingHighlightedIndex()
            } catch (error) {
                if (requestId !== this.searchRequestId) {
                    return
                }

                console.error(error)
                this.searchResults = []
                this.highlightedIndex = -1
                this.geocodeError = error instanceof GeocodingApiError
                    ? error.message
                    : this.labels.geocodeFailed ?? 'Geocoding search failed.'
            } finally {
                if (requestId === this.searchRequestId) {
                    this.searchLoading = false
                }
            }
        },

        closeSearchDropdown() {
            this.searchOpen = false
            this.searchLoading = false
            this.highlightedIndex = -1
            window.clearTimeout(this.searchDebounceTimer)
        },

        selectSearchResult(result) {
            if (this.streetAddressesOnly && ! isStreetLevelFeature(result.feature)) {
                this.selectionError = this.labels.streetAddressRequired

                return
            }

            const parsed = parseGeocodeFeature(result.feature)

            if (this.streetAddressesOnly && ! hasStreetAddress(parsed)) {
                this.selectionError = this.labels.streetAddressRequired

                return
            }

            this.selectionError = null

            this.state = {
                ...emptyAddressCanonical(),
                street: parsed.street,
                city: parsed.city,
                region: parsed.region,
                postcode: parsed.postcode,
                country: parsed.country,
                country_name: parsed.country_name,
                place_name: parsed.place_name ?? result.label,
            }

            this.selectedLabel = this.state.place_name ?? result.label
            this.searchQuery = this.selectedLabel
            this.searchOpen = false
            this.searchLoading = false
            this.searchResults = []
            this.highlightedIndex = -1
            this.updateSearchHasMinQuery()
            window.clearTimeout(this.searchDebounceTimer)
        },

        clearSelection() {
            if (this.readOnly) {
                return
            }

            this.selectionError = null
            this.state = emptyAddressCanonical()
            this.selectedLabel = ''
            this.searchQuery = ''
            this.searchResults = []
            this.searchOpen = false
            this.highlightedIndex = -1
            this.updateSearchHasMinQuery()
        },
    }
}
