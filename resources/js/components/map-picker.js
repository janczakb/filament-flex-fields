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
import { createGeocodingComboboxMixin } from '../core/geocoding-combobox-alpine.js'
import { createGeocodingDropdownMenuMixin } from '../core/geocoding-dropdown-menu.js'
import { createGeocodingListKeyboardMixin } from '../core/geocoding-list-keyboard.js'

const geocodingDropdown = createGeocodingDropdownMenuMixin({
    openKey: 'searchOpen',
    readyKey: 'searchDropdownReady',
    triggerRef: 'searchWrap',
    widthRef: 'searchShell',
    menuRef: 'searchDropdown',
    closeMethod: 'closeSearchDropdown',
    menuThemeVariant: 'map',
    menuGap: 8,
    ownerIdPrefix: 'fff-map-picker',
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
        summaryLabel: null,

        init() {
            this.initGeocodingComboboxState()
            this.bindGeocodingDropdownMenu()
            this.initGeocodingListKeyboard()

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
            this.teardownGeocodingDropdownMenu()
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
                        this.dismissSearchDropdown()
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

        dismissSearchDropdown(options = {}) {
            this.dismissGeocodingDropdown(options)
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

        scheduleSearch() {
            this.scheduleGeocodingSearch()
        },

        async performSearch() {
            await this.performGeocodingSearch()
        },

        closeSearchDropdown() {
            this.dismissSearchDropdown()
            this.$refs.searchInput?.blur?.()
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

            this.selectedLabel = parsed.place_name ?? result.label
            this.searchQuery = this.selectedLabel
            this.finalizeGeocodingSelection(result)

            this.state = {
                ...emptyCanonical(),
                ...parsed,
            }

            this.dismissSearchDropdown({ restoreQuery: false })
            window.clearTimeout(this.searchDebounceTimer)
            this.$refs.searchInput?.blur?.()

            this.placeMarker(parsed.lng, parsed.lat, true)
            this.flyToLocation(parsed.lng, parsed.lat)
        },
    }
}
