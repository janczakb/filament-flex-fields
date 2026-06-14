export function escapeRegex(value) {
    return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
}

export function slugify(value, separator = '-', maxLength = null) {
    if (value == null || value === '') {
        return ''
    }

    let slug = String(value)
        .normalize('NFKD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, separator)
        .replace(new RegExp(`${escapeRegex(separator)}+`, 'g'), separator)
        .replace(new RegExp(`^${escapeRegex(separator)}+|${escapeRegex(separator)}+$`, 'g'), '')

    if (maxLength != null && maxLength > 0 && slug.length > maxLength) {
        slug = slug.slice(0, maxLength).replace(new RegExp(`${escapeRegex(separator)}+$`), '')
    }

    return slug
}

export function isHomepageSlug(value, { allowHomepage = false } = {}) {
    return allowHomepage && String(value ?? '').trim() === '/'
}

export function toEditableSlug(value, { allowHomepage = false } = {}) {
    return isHomepageSlug(value, { allowHomepage }) ? '' : String(value ?? '')
}

export function normalizeEditableSlug(value, separator = '-') {
    if (value == null) {
        return ''
    }

    let slug = String(value).trim().toLowerCase().replace(/\//g, '')
    slug = slug.replace(new RegExp(`[^a-z0-9${escapeRegex(separator)}]+`, 'g'), separator)
    slug = slug.replace(new RegExp(`^${escapeRegex(separator)}+|${escapeRegex(separator)}+$`, 'g'), '')
    slug = slug.replace(new RegExp(`${escapeRegex(separator)}+`, 'g'), separator)

    return slug
}

export function fromEditableSlug(value, separator = '-', { allowHomepage = false } = {}) {
    const editable = normalizeEditableSlug(value, separator)

    if (allowHomepage && editable === '') {
        return '/'
    }

    return normalizeSlug(editable, separator, { allowHomepage })
}

export function normalizeSlug(value, separator = '-', { allowHomepage = false } = {}) {
    if (value == null) {
        return ''
    }

    const trimmed = String(value).trim()

    if (allowHomepage && trimmed === '/') {
        return '/'
    }

    let slug = trimmed.toLowerCase()
    slug = slug.replace(new RegExp(`[^a-z0-9${escapeRegex(separator)}]+`, 'g'), separator)
    slug = slug.replace(new RegExp(`^${escapeRegex(separator)}+|${escapeRegex(separator)}+$`, 'g'), '')
    slug = slug.replace(new RegExp(`${escapeRegex(separator)}+`, 'g'), separator)

    return slug
}

export function slugPatternMatches(value, pattern, { allowHomepage = false } = {}) {
    if (allowHomepage && value === '/') {
        return true
    }

    if (! pattern) {
        return true
    }

    return new RegExp(pattern).test(value)
}
