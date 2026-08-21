/**
 * Shared search-query normalizer for client-side option filtering.
 *
 * Lowercases and strips combining diacritical marks so an unaccented query
 * still matches an accented label ("sao paulo" → "São Paulo", "dolar" →
 * "Dólar", "belem" → "Belém").
 */
export function normalizeSearchQuery(query) {
    return String(query ?? '')
        .trim()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
}
