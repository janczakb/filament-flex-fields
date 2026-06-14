import {
    emptyMapCanonical,
    hasCoordinates,
    hasStreetAddress,
    isStreetLevelFeature,
    parseGeocodeFeature,
    reverseGeocodeMapbox,
    searchMapboxPlaces,
} from '../support/mapbox-geocoding.js'

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
    defaultCenter,
    defaultZoom,
    searchable,
    countries,
    streetAddressesOnly,
    labels,
    readOnly,
}) {
    return {
        state,
        accessToken,
        defaultCenter,
        defaultZoom,
        searchable,
        countries,
        streetAddressesOnly,
        labels,
        readOnly,
        selectionError: null,
        map: null,
        marker: null,
        mapReady: false,
        mapLoading: true,
        mapError: null,
        searchQuery: '',
        selectedLabel: '',
        searchResults: [],
        searchOpen: false,
        searchLoading: false,
        searchHasMinQuery: false,
        searchFocused: false,
        highlightedIndex: -1,
        summaryLabel: null,
        searchDebounceTimer: null,

        init() {
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
            this.searchHasMinQuery = this.searchQuery.trim().length >= 2
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

            if (! query || query.length < 2 || ! this.accessToken) {
                return
            }

            try {
                const features = await searchMapboxPlaces({
                    query,
                    accessToken: this.accessToken,
                    countries: this.countries,
                    limit: 1,
                    autocomplete: false,
                    streetAddressesOnly: this.streetAddressesOnly,
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
            try {
                const parsed = await reverseGeocodeMapbox({
                    lng,
                    lat,
                    accessToken: this.accessToken,
                    countries: this.countries,
                    streetAddressesOnly: this.streetAddressesOnly,
                })

                if (parsed) {
                    return {
                        ...parsed,
                        lat: parsed.lat ?? lat,
                        lng: parsed.lng ?? lng,
                    }
                }

                if (this.streetAddressesOnly) {
                    return {
                        lat,
                        lng,
                    }
                }

                return {
                    lat,
                    lng,
                    place_name: `${lat.toFixed(5)}, ${lng.toFixed(5)}`,
                }
            } catch (error) {
                console.error(error)

                return {
                    lat,
                    lng,
                    place_name: `${lat.toFixed(5)}, ${lng.toFixed(5)}`,
                }
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
            }, 280)
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

            if (event.key === 'ArrowDown') {
                event.preventDefault()

                if (this.searchResults.length === 0) {
                    return
                }

                this.highlightedIndex = Math.min(
                    this.highlightedIndex + 1,
                    this.searchResults.length - 1,
                )

                return
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault()
                this.highlightedIndex = Math.max(this.highlightedIndex - 1, 0)

                return
            }

            if (event.key === 'Enter') {
                event.preventDefault()

                if (this.highlightedIndex >= 0 && this.searchResults[this.highlightedIndex]) {
                    this.selectSearchResult(this.searchResults[this.highlightedIndex])
                }

                return
            }

            if (event.key === 'Escape') {
                event.preventDefault()
                this.searchOpen = false
                this.highlightedIndex = -1
                this.searchQuery = this.selectedLabel
                this.searchResults = []
                event.target?.blur?.()
            }
        },

        async performSearch() {
            const query = this.searchQuery.trim()

            if (! this.searchable || ! this.accessToken) {
                this.searchResults = []
                this.searchLoading = false

                return
            }

            if (query.length < 2) {
                this.searchResults = []
                this.searchLoading = false

                return
            }

            const requestId = ++this.searchRequestId
            this.searchLoading = true

            try {
                const features = await searchMapboxPlaces({
                    query,
                    accessToken: this.accessToken,
                    countries: this.countries,
                    streetAddressesOnly: this.streetAddressesOnly,
                })

                if (requestId !== this.searchRequestId) {
                    return
                }

                this.searchResults = features.map((feature) => ({
                    id: feature.id,
                    label: feature.place_name,
                    feature,
                }))
                this.highlightedIndex = this.searchResults.length > 0 ? 0 : -1
            } catch (error) {
                if (requestId !== this.searchRequestId) {
                    return
                }

                console.error(error)
                this.searchResults = []
                this.highlightedIndex = -1
            } finally {
                if (requestId === this.searchRequestId) {
                    this.searchLoading = false
                }
            }
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
