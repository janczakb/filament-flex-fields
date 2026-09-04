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

/**
 * @param {unknown} state
 * @param {boolean} multiple
 */
export function isHeadlessWireStateEmpty(state, multiple) {
    if (multiple) {
        return ! Array.isArray(state) || state.length === 0
    }

    return state == null || state === ''
}

/**
 * @param {unknown} initialState
 * @param {boolean} multiple
 */
export function hasHeadlessInitialSelection(initialState, multiple) {
    if (multiple) {
        return Array.isArray(initialState) && initialState.length > 0
    }

    return initialState != null && initialState !== ''
}

/**
 * Ignore a late empty Livewire entangle sync when SSR/default state still applies.
 *
 * @param {unknown} nextState
 * @param {unknown} initialState
 * @param {boolean} multiple
 * @param {boolean} userHasMutatedSelection
 */
export function shouldIgnoreEmptyHeadlessWireSync(
    nextState,
    initialState,
    multiple,
    userHasMutatedSelection,
) {
    if (userHasMutatedSelection) {
        return false
    }

    return isHeadlessWireStateEmpty(nextState, multiple)
        && hasHeadlessInitialSelection(initialState, multiple)
}
