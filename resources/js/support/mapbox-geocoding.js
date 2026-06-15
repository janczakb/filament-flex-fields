import {
    geocodingCacheKey,
    readGeocodingCache,
    writeGeocodingCache,
} from '../core/geocoding-cache.js'

export class GeocodingApiError extends Error {
    constructor(message, { status = null, payload = null } = {}) {
        super(message)
        this.name = 'GeocodingApiError'
        this.status = status
        this.payload = payload
    }
}

function readCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
}

function buildDirectSearchUrl({ query, accessToken, countries, language, limit, autocomplete, types }) {
    const url = new URL(`https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query)}.json`)
    url.searchParams.set('access_token', accessToken)
    url.searchParams.set('limit', String(limit))
    url.searchParams.set('language', language)

    if (autocomplete) {
        url.searchParams.set('autocomplete', 'true')
    }

    if (types) {
        url.searchParams.set('types', types)
    }

    if (Array.isArray(countries) && countries.length > 0) {
        url.searchParams.set('country', countries.join(',').toLowerCase())
    }

    return url
}

function buildDirectReverseUrl({ lng, lat, accessToken, countries, language, types }) {
    const url = new URL(`https://api.mapbox.com/geocoding/v5/mapbox.places/${lng},${lat}.json`)
    url.searchParams.set('access_token', accessToken)
    url.searchParams.set('language', language)

    if (types) {
        url.searchParams.set('types', types)
    }

    if (Array.isArray(countries) && countries.length > 0) {
        url.searchParams.set('country', countries.join(',').toLowerCase())
    }

    return url
}

async function postGeocodeProxy(url, body) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': readCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify(body),
    })

    const payload = await response.json().catch(() => ({}))

    if (! response.ok) {
        const message = payload?.message
            ?? payload?.errors?.geocode?.[0]
            ?? `Geocoding request failed (${response.status}).`

        throw new GeocodingApiError(message, { status: response.status, payload })
    }

    if (payload?.error) {
        throw new GeocodingApiError(String(payload.error))
    }

    return payload
}

async function fetchDirect(url) {
    const response = await fetch(url)
    const payload = await response.json().catch(() => ({}))

    if (! response.ok) {
        throw new GeocodingApiError(payload?.message ?? `Geocoding request failed (${response.status}).`, {
            status: response.status,
            payload,
        })
    }

    return payload
}

export function parseGeocodeFeature(feature) {
    const center = feature?.center ?? feature?.geometry?.coordinates ?? []
    const lng = Number(center[0])
    const lat = Number(center[1])

    let street = ''
    let city = ''
    let region = ''
    let postcode = ''
    let country = ''
    let countryName = ''

    if (feature?.address) {
        street = [feature.address, feature.text].filter(Boolean).join(' ')
    } else if (feature?.place_type?.includes('address') && feature?.text) {
        street = feature.text
    } else if (feature?.text && feature?.place_type?.includes('poi')) {
        street = feature.text
    }

    for (const item of feature?.context ?? []) {
        const type = String(item?.id ?? '').split('.')[0] ?? ''

        if (type === 'postcode') {
            postcode = item.text ?? ''
        }

        if (type === 'place') {
            city = item.text ?? ''
        }

        if (type === 'locality' && ! city) {
            city = item.text ?? ''
        }

        if (type === 'region') {
            region = item.text ?? ''
        }

        if (type === 'country') {
            countryName = item.text ?? ''
            country = String(item.short_code ?? '').toUpperCase()
        }
    }

    if (! city && feature?.place_type?.includes('place')) {
        city = feature.text ?? ''
    }

    return {
        lat: Number.isFinite(lat) ? lat : null,
        lng: Number.isFinite(lng) ? lng : null,
        street: street || null,
        city: city || null,
        region: region || null,
        postcode: postcode || null,
        country: country || null,
        country_name: countryName || null,
        place_name: feature?.place_name ?? null,
    }
}

export function emptyMapCanonical() {
    return {
        lat: null,
        lng: null,
        street: null,
        city: null,
        region: null,
        postcode: null,
        country: null,
        country_name: null,
        place_name: null,
    }
}

export function emptyAddressCanonical() {
    return {
        street: null,
        city: null,
        region: null,
        postcode: null,
        country: null,
        country_name: null,
        place_name: null,
    }
}

export const STREET_ADDRESS_MAPBOX_TYPES = 'address'

export function hasStreetAddress(state) {
    const street = state?.street

    if (street === null || street === undefined) {
        return false
    }

    return String(street).trim() !== ''
}

export function isStreetLevelFeature(feature) {
    if (! feature) {
        return false
    }

    const placeTypes = Array.isArray(feature.place_type) ? feature.place_type : []

    if (! placeTypes.includes('address')) {
        return false
    }

    return hasStreetAddress(parseGeocodeFeature(feature))
}

export function filterStreetLevelFeatures(features) {
    return (features ?? []).filter(isStreetLevelFeature)
}

export function resolveMapboxSearchTypes({ types = null, streetAddressesOnly = false } = {}) {
    if (streetAddressesOnly) {
        return STREET_ADDRESS_MAPBOX_TYPES
    }

    if (Array.isArray(types) && types.length > 0) {
        return types.filter(Boolean).join(',')
    }

    if (typeof types === 'string' && types.trim() !== '') {
        return types.trim()
    }

    return null
}

export function mapboxSearchTypes(streetAddressesOnly) {
    return resolveMapboxSearchTypes({ streetAddressesOnly })
}

export function hasCoordinates(state) {
    const lat = state?.lat
    const lng = state?.lng

    if (lat === null || lat === undefined || lng === null || lng === undefined) {
        return false
    }

    return Number.isFinite(Number(lat)) && Number.isFinite(Number(lng))
}

/**
 * @param {{
 *   query: string,
 *   accessToken?: string|null,
 *   geocodeSearchUrl?: string|null,
 *   countries?: string[]|null,
 *   language?: string,
 *   limit?: number,
 *   autocomplete?: boolean,
 *   types?: string|null,
 *   streetAddressesOnly?: boolean,
 * }} options
 */
export async function searchMapboxPlaces({
    query,
    accessToken = null,
    geocodeSearchUrl = null,
    countries = null,
    language = 'en',
    limit = 6,
    autocomplete = true,
    types = null,
    streetAddressesOnly = false,
}) {
    const resolvedTypes = resolveMapboxSearchTypes({ types, streetAddressesOnly })
    const cacheKey = geocodingCacheKey('search', {
        query,
        geocodeSearchUrl,
        countries,
        language,
        limit,
        autocomplete,
        types: resolvedTypes,
        streetAddressesOnly,
    })

    const cached = readGeocodingCache(cacheKey)

    if (cached) {
        return cached
    }

    let features = []

    if (geocodeSearchUrl) {
        const payload = await postGeocodeProxy(geocodeSearchUrl, {
            query,
            language,
            limit,
            autocomplete,
            types: resolvedTypes,
            countries,
            street_addresses_only: streetAddressesOnly,
        })

        features = payload?.features ?? []
    } else {
        if (! accessToken) {
            throw new GeocodingApiError('Mapbox access token is missing.')
        }

        const payload = await fetchDirect(buildDirectSearchUrl({
            query,
            accessToken,
            countries,
            language,
            limit,
            autocomplete,
            types: resolvedTypes,
        }))

        features = payload?.features ?? []
    }

    const results = streetAddressesOnly ? filterStreetLevelFeatures(features) : features

    writeGeocodingCache(cacheKey, results)

    return results
}

/**
 * @param {{
 *   lng: number,
 *   lat: number,
 *   accessToken?: string|null,
 *   geocodeReverseUrl?: string|null,
 *   countries?: string[]|null,
 *   language?: string,
 *   types?: string|null,
 *   streetAddressesOnly?: boolean,
 * }} options
 */
export async function reverseGeocodeMapbox({
    lng,
    lat,
    accessToken = null,
    geocodeReverseUrl = null,
    countries = null,
    language = 'en',
    types = null,
    streetAddressesOnly = false,
}) {
    const resolvedTypes = resolveMapboxSearchTypes({ types, streetAddressesOnly })
    const cacheKey = geocodingCacheKey('reverse', {
        lng,
        lat,
        geocodeReverseUrl,
        countries,
        language,
        types: resolvedTypes,
        streetAddressesOnly,
    })

    const cached = readGeocodingCache(cacheKey)

    if (cached !== null) {
        return cached
    }

    let feature = null

    if (geocodeReverseUrl) {
        const payload = await postGeocodeProxy(geocodeReverseUrl, {
            lng,
            lat,
            language,
            types: resolvedTypes,
            countries,
            street_addresses_only: streetAddressesOnly,
        })

        feature = payload?.feature ?? null
    } else {
        if (! accessToken) {
            throw new GeocodingApiError('Mapbox access token is missing.')
        }

        const payload = await fetchDirect(buildDirectReverseUrl({
            lng,
            lat,
            accessToken,
            countries,
            language,
            types: resolvedTypes,
        }))

        const features = payload?.features ?? []

        if (streetAddressesOnly) {
            feature = filterStreetLevelFeatures(features)[0] ?? null
        } else {
            feature = features[0] ?? null
        }
    }

    const parsed = feature ? parseGeocodeFeature(feature) : null

    writeGeocodingCache(cacheKey, parsed)

    return parsed
}
