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

export function mapboxSearchTypes(streetAddressesOnly) {
    return streetAddressesOnly ? STREET_ADDRESS_MAPBOX_TYPES : null
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
 *   accessToken: string,
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
    accessToken,
    countries = null,
    language = 'pl',
    limit = 6,
    autocomplete = true,
    types = null,
    streetAddressesOnly = false,
}) {
    const url = new URL(`https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query)}.json`)
    url.searchParams.set('access_token', accessToken)
    url.searchParams.set('limit', String(limit))
    url.searchParams.set('language', language)

    if (autocomplete) {
        url.searchParams.set('autocomplete', 'true')
    }

    const resolvedTypes = types ?? mapboxSearchTypes(streetAddressesOnly)

    if (resolvedTypes) {
        url.searchParams.set('types', resolvedTypes)
    }

    if (Array.isArray(countries) && countries.length > 0) {
        url.searchParams.set('country', countries.join(',').toLowerCase())
    }

    const response = await fetch(url)
    const payload = await response.json()
    const features = payload?.features ?? []

    if (streetAddressesOnly) {
        return filterStreetLevelFeatures(features)
    }

    return features
}

/**
 * @param {{
 *   lng: number,
 *   lat: number,
 *   accessToken: string,
 *   countries?: string[]|null,
 *   language?: string,
 *   types?: string|null,
 *   streetAddressesOnly?: boolean,
 * }} options
 */
export async function reverseGeocodeMapbox({
    lng,
    lat,
    accessToken,
    countries = null,
    language = 'pl',
    types = null,
    streetAddressesOnly = false,
}) {
    const url = new URL(`https://api.mapbox.com/geocoding/v5/mapbox.places/${lng},${lat}.json`)
    url.searchParams.set('access_token', accessToken)
    url.searchParams.set('language', language)

    const resolvedTypes = types ?? mapboxSearchTypes(streetAddressesOnly)

    if (resolvedTypes) {
        url.searchParams.set('types', resolvedTypes)
    }

    if (Array.isArray(countries) && countries.length > 0) {
        url.searchParams.set('country', countries.join(',').toLowerCase())
    }

    const response = await fetch(url)
    const payload = await response.json()
    const features = payload?.features ?? []

    if (streetAddressesOnly) {
        const feature = filterStreetLevelFeatures(features)[0]

        return feature ? parseGeocodeFeature(feature) : null
    }

    const feature = features[0]

    return feature ? parseGeocodeFeature(feature) : null
}
