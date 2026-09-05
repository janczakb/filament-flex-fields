import {
    emptyAddressCanonical,
    hasStreetAddress,
    isStreetLevelFeature,
    parseGeocodeFeature,
} from '../support/mapbox-geocoding.js'
import { createGeocodingComboboxMixin } from '../core/geocoding-combobox-alpine.js'
import { createGeocodingDropdownMenuMixin } from '../core/geocoding-dropdown-menu.js'
import { createGeocodingListKeyboardMixin } from '../core/geocoding-list-keyboard.js'

const geocodingDropdown = createGeocodingDropdownMenuMixin({
    openKey: 'searchOpen',
    readyKey: 'searchDropdownReady',
    triggerRef: 'searchShell',
    widthRef: 'searchShell',
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
    const geocodingCombobox = createGeocodingComboboxMixin({
        minSearchLength,
        searchDebounce,
    })

    return {
        ...geocodingCombobox,
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
        tokenError: null,

        init() {
            this.initGeocodingComboboxState()
            this.bindGeocodingDropdownMenu()
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

        canGeocode() {
            return Boolean(this.geocodeSearchUrl || this.accessToken)
        },

        onSearchInput() {
            this.onGeocodingSearchInput()
        },

        onSearchFocus() {
            this.onGeocodingSearchFocus()
        },

        onSearchBlur() {
            this.onGeocodingSearchBlur()
        },

        onSearchKeydown(event) {
            this.onGeocodingComboboxKeydown(event)
        },

        closeSearchDropdown() {
            this.dismissGeocodingDropdown()
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

            this.selectedLabel = this.state.place_name ?? result.label
            this.searchQuery = this.selectedLabel
            this.finalizeGeocodingSelection(result)

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

            this.dismissGeocodingDropdown({ restoreQuery: false })
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
            this.selectedResultId = null
            this.geocodingRecentResults = []
            this.searchOpen = false
            this.highlightedIndex = -1
            this.updateSearchHasMinQuery()
        },
    }
}
