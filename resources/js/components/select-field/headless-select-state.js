/**
 * Normalize Filament/Alpine bound state into combobox engine selected values.
 *
 * @param {unknown} state
 * @param {boolean} multiple
 * @returns {string[]}
 */
export function normalizeInitialSelectedValues(state, multiple) {
    if (multiple) {
        if (! Array.isArray(state)) {
            return []
        }

        return state.map((value) => String(value))
    }

    if (state == null || state === '') {
        return []
    }

    return [String(state)]
}

/**
 * Resolve wire-bound state for engine hydration.
 *
 * @param {unknown} state
 * @param {unknown} initialState
 * @param {boolean} multiple
 * @param {{ fallbackToInitial?: boolean }} [options]
 * @returns {unknown}
 */
export function resolveHeadlessBoundState(state, initialState, multiple, { fallbackToInitial = true } = {}) {
    if (multiple) {
        if (Array.isArray(state)) {
            return state
        }

        if (fallbackToInitial && Array.isArray(initialState)) {
            return initialState
        }

        return []
    }

    if (state !== undefined && state !== null && state !== '') {
        return state
    }

    if (! fallbackToInitial) {
        return state ?? null
    }

    return initialState
}
