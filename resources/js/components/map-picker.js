import {
    emptyMapCanonical,
    GeocodingApiError,
    hasCoordinates,
    hasStreetAddress,
    isStreetLevelFeature,
    parseGeocodeFeature,
    reverseGeocodeMapbox,
    searchMapboxPlaces,
} from '../support/mapbox-geocoding.js'
import { createExclusiveDropdownMixin } from '../core/flex-dropdown-coordinator.js'
import { createGeocodingListKeyboardMixin } from '../core/geocoding-list-keyboard.js'
import { createSearchableSelectMenuMixin } from '../core/searchable-select-menu.js'

const exclusiveDropdown = createExclusiveDropdownMixin({
    openKey: 'searchOpen',
    closeMethod: 'closeSearchDropdown',
    ownerIdPrefix: 'fff-map-picker',
})

const geocodingDropdown = createSearchableSelectMenuMixin({
    openKey: 'searchOpen',
    readyKey: 'searchDropdownReady',
    triggerRef: 'searchInput',
    menuRef: 'searchDropdown',
    closeMethod: 'closeSearchDropdown',
    ownerIdPrefix: 'fff-map-picker',
    menuThemeVariant: 'map',
})

const geocodingKeyboard = createGeocodingListKeyboardMixin({
    openKey: 'searchOpen',
    resultsKey: 'searchResults',
    menuRef: 'searchDropdown',
    searchRef: 'searchInput',
    optionIdPrefix: 'fff-map-picker-option',
})

const MAPBOX_JS = 'https://api.mapbox.com/mapbox-gl-js/v3.9.0/mapbox-gl.js'
const MAPBOX_CSS = 'https://api.mapbox.com/mapbox-gl-js/v3.9.0/mapbox-gl.css'

let mapboxAssetsPromise = null

function loadMapboxAssets() {
    if (window.mapboxgl) {
        return Promise.resolve(window.mapboxgl)
    }

    if (mapboxAssetsPromise) {
        return mapboxAssetsPromise
    }

    mapboxAssetsPromise = new Promise((resolve, reject) => {
        if (! document.querySelector(`link[href="${MAPBOX_CSS}"]`)) {
            const link = document.createElement('link')
            link.rel = 'stylesheet'
            link.href = MAPBOX_CSS
            document.head.appendChild(link)
        }

        const script = document.createElement('script')
        script.src = MAPBOX_JS
        script.async = true
        script.onload = () => resolve(window.mapboxgl)
        script.onerror = () => reject(new Error('Failed to load Mapbox GL JS.'))
        document.head.appendChild(script)
    })

    return mapboxAssetsPromise
}

const emptyCanonical = emptyMapCanonical

export default function mapPickerFormComponent({
    state,
    accessToken,
    geocodeSearchUrl = null,
    geocodeReverseUrl = null,
    defaultCenter,
    defaultZoom,
    searchable,
    countries,
    language = 'en',
    streetAddressesOnly,
    searchTypes = null,
    minSearchLength = 2,
    searchDebounce = 350,
    labels,
    readOnly,
}) {
    return {
        ...exclusiveDropdown,
        ...geocodingDropdown,
        ...geocodingKeyboard,
        state,
        accessToken,
        geocodeSearchUrl,
        geocodeReverseUrl,
        defaultCenter,
        defaultZoom,
        searchable,
        countries,
        language,
        streetAddressesOnly,
        searchTypes,
        minSearchLength,
        searchDebounce,
        labels,
        readOnly,
        selectionError: null,
        geocodeError: null,
        map: null,
        marker: null,
        mapReady: false,
        mapLoading: true,
        mapError: null,
        searchQuery: '',
        selectedLabel: '',
        searchResults: [],
        searchOpen: false,
        searchDropdownReady: false,
        searchLoading: false,
        searchHasMinQuery: false,
        searchFocused: false,
        highlightedIndex: -1,
        summaryLabel: null,
        searchDebounceTimer: null,

        init() {
            this.wireExclusiveFlexDropdown()
            this.bindSelectMenuLifecycle({ wireExclusive: false })
            this.initGeocodingListKeyboard()
            this.searchRequestId = 0

            this.syncSummary()
            this.syncSearchInputFromState()
            this.updateSearchHasMinQuery()

            this.$watch('searchQuery', () => {
                this.updateSearchHasMinQuery()
            })

            this.$watch('state', () => {
                this.syncSummary()
                this.syncSearchInputFromState()
            })

            this.$nextTick(() => this.bootstrapMap())
        },

        updateSearchHasMinQuery() {
            this.searchHasMinQuery = this.searchQuery.trim().length >= this.minSearchLength
        },

        destroy() {
            this.map?.remove()
            this.map = null
            this.marker = null
        },

        syncSummary() {
            const parts = [
                this.state?.place_name,
                this.state?.street,
                this.state?.city,
                this.state?.country_name ?? this.state?.country,
            ].filter((value) => value !== null && value !== undefined && String(value).trim() !== '')

            this.summaryLabel = parts.length > 0 ? String(parts[0]) : null
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

        async bootstrapMap() {
            if (! this.accessToken) {
                this.mapError = labels.missingToken
                this.mapLoading = false

                return
            }

            this.mapLoading = true

            try {
                const mapboxgl = await loadMapboxAssets()
                mapboxgl.accessToken = this.accessToken

                const center = hasCoordinates(this.state)
                    ? [Number(this.state.lng), Number(this.state.lat)]
                    : [Number(this.defaultCenter[1]), Number(this.defaultCenter[0])]

                this.map = new mapboxgl.Map({
                    container: this.$refs.mapCanvas,
                    style: 'mapbox://styles/mapbox/streets-v12',
                    center,
                    zoom: this.defaultZoom,
                    attributionControl: false,
                })

                this.map.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'bottom-right')

                this.map.on('load', async () => {
                    this.mapReady = true
                    this.mapLoading = false
                    this.map.resize()

                    if (hasCoordinates(this.state)) {
                        this.placeMarker(Number(this.state.lng), Number(this.state.lat), false)

                        return
                    }

                    await this.geocodeInitialPlace()
                })

                if (! this.readOnly) {
                    this.map.on('click', (event) => {
                        this.selectCoordinates(event.lngLat.lng, event.lngLat.lat)
                    })
                }
            } catch (error) {
                this.mapError = labels.loadFailed
                this.mapLoading = false
                console.error(error)
            }
        },

        createMarkerElement() {
            const element = document.createElement('div')
            element.className = 'fff-map-picker__marker'
            element.innerHTML = `
                <span class="fff-map-picker__marker-pin" aria-hidden="true"></span>
                <span class="fff-map-picker__marker-shadow" aria-hidden="true"></span>
            `

            return element
        },

        animateMarkerDrop() {
            const element = this.marker?.getElement?.()

            if (! element) {
                return
            }

            element.classList.remove('is-dropping')
            void element.offsetWidth
            element.classList.add('is-dropping')
        },

        placeMarker(lng, lat, animate = true) {
            if (! this.map || ! window.mapboxgl) {
                return
            }

            if (! this.marker) {
                this.marker = new window.mapboxgl.Marker({
                    element: this.createMarkerElement(),
                    anchor: 'bottom',
                    draggable: ! this.readOnly,
                })
                    .setLngLat([lng, lat])
                    .addTo(this.map)

                if (! this.readOnly) {
                    this.marker.on('dragend', () => {
                        const position = this.marker.getLngLat()
                        this.selectCoordinates(position.lng, position.lat, false)
                    })
                }
            } else {
                this.marker.setLngLat([lng, lat])
            }

            if (animate) {
                this.animateMarkerDrop()
            }
        },

        flyToLocation(lng, lat, zoom = null) {
            if (! this.map) {
                return
            }

            this.map.flyTo({
                center: [lng, lat],
                zoom: zoom ?? Math.max(this.defaultZoom, 14),
                duration: 1400,
                essential: true,
                curve: 1.35,
            })
        },

        async selectCoordinates(lng, lat, animate = true) {
            if (this.readOnly) {
                return
            }

            const reverse = await this.reverseGeocode(lng, lat)

            if (this.streetAddressesOnly && ! hasStreetAddress(reverse)) {
                this.selectionError = this.labels.streetAddressRequired

                if (hasCoordinates(this.state)) {
                    this.marker?.setLngLat([Number(this.state.lng), Number(this.state.lat)])
                }

                return
            }

            this.selectionError = null

            this.placeMarker(lng, lat, animate)

            if (animate) {
                this.flyToLocation(lng, lat)
            } else {
                this.map?.easeTo({ center: [lng, lat], duration: 0 })
            }

            this.state = {
                ...emptyCanonical(),
                ...reverse,
            }

            this.syncSearchInputFromState()
        },

        async geocodeInitialPlace() {
            const query = this.buildLabelFromState().trim()

            if (! query || query.length < this.minSearchLength || ! this.canGeocode()) {
                return
            }

            try {
                const features = await searchMapboxPlaces({
                    query,
                    accessToken: this.accessToken,
                    geocodeSearchUrl: this.geocodeSearchUrl,
                    countries: this.countries,
                    language: this.language,
                    limit: 1,
                    autocomplete: false,
                    streetAddressesOnly: this.streetAddressesOnly,
                    types: this.searchTypes,
                })
                const feature = features[0]

                if (! feature) {
                    return
                }

                const parsed = parseGeocodeFeature(feature)

                if (this.streetAddressesOnly && ! hasStreetAddress(parsed)) {
                    return
                }

                if (! Number.isFinite(parsed.lat) || ! Number.isFinite(parsed.lng)) {
                    return
                }

                this.state = {
                    ...emptyCanonical(),
                    ...this.state,
                    ...parsed,
                }

                this.placeMarker(parsed.lng, parsed.lat, false)
                this.map?.easeTo({
                    center: [parsed.lng, parsed.lat],
                    zoom: this.defaultZoom,
                    duration: 0,
                })
                this.syncSearchInputFromState()
            } catch (error) {
                console.error(error)
            }
        },

        async reverseGeocode(lng, lat) {
            this.geocodeError = null

            try {
                const parsed = await reverseGeocodeMapbox({
                    lng,
                    lat,
                    accessToken: this.accessToken,
                    geocodeReverseUrl: this.geocodeReverseUrl,
                    countries: this.countries,
                    language: this.language,
                    streetAddressesOnly: this.streetAddressesOnly,
                    types: this.searchTypes,
                })

                if (parsed) {
                    return {
                        ...parsed,
                        lat: parsed.lat ?? lat,
                        lng: parsed.lng ?? lng,
                    }
                }
            } catch (error) {
                console.error(error)
                this.geocodeError = error instanceof GeocodingApiError
                    ? error.message
                    : this.labels.geocodeFailed ?? 'Reverse geocoding failed.'
            }

            return {
                lat,
                lng,
            }
        },

        canGeocode() {
            return Boolean(this.geocodeSearchUrl || this.accessToken)
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

                return
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

        async selectSearchResult(result) {
            if (this.streetAddressesOnly && ! isStreetLevelFeature(result.feature)) {
                this.selectionError = this.labels.streetAddressRequired

                return
            }

            const parsed = parseGeocodeFeature(result.feature)

            if (! Number.isFinite(parsed.lat) || ! Number.isFinite(parsed.lng)) {
                return
            }

            this.selectionError = null

            this.state = {
                ...emptyCanonical(),
                ...parsed,
            }

            this.selectedLabel = parsed.place_name ?? result.label
            this.searchQuery = this.selectedLabel
            this.searchOpen = false
            this.searchLoading = false
            this.searchResults = []
            this.highlightedIndex = -1
            this.updateSearchHasMinQuery()
            window.clearTimeout(this.searchDebounceTimer)

            this.placeMarker(parsed.lng, parsed.lat, true)
            this.flyToLocation(parsed.lng, parsed.lat)
        },
    }
}
