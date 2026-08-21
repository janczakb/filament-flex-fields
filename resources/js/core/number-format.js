/**
 * Shared numeric display formatting.
 *
 * `formatDecimal()` deliberately keeps the historical, locale-agnostic output
 * when no locale is configured, so fields that never opt in are unchanged.
 */

export function normalizeLocale(locale) {
    if (typeof locale !== 'string' || locale.trim() === '') {
        return 'en-US'
    }

    return locale.trim().replace(/_/g, '-')
}

/**
 * @param {number} numeric
 * @param {{ locale?: string|null, decimalPlaces?: number|null }} [options]
 */
export function formatDecimal(numeric, { locale = null, decimalPlaces = null } = {}) {
    if (! Number.isFinite(numeric)) {
        return ''
    }

    const hasLocale = typeof locale === 'string' && locale.trim() !== ''

    if (! hasLocale) {
        return decimalPlaces === null ? String(numeric) : numeric.toFixed(decimalPlaces)
    }

    try {
        return new Intl.NumberFormat(normalizeLocale(locale), {
            minimumFractionDigits: decimalPlaces ?? 0,
            // Intl caps fraction digits at 20, which matches `String(number)`.
            maximumFractionDigits: decimalPlaces ?? 20,
        }).format(numeric)
    } catch {
        // An unsupported locale tag must never break the field.
        return decimalPlaces === null ? String(numeric) : numeric.toFixed(decimalPlaces)
    }
}
