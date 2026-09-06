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
        if (Array.isArray(state) && state.length > 0) {
            return state
        }

        if (fallbackToInitial && Array.isArray(initialState) && initialState.length > 0) {
            return initialState
        }

        if (Array.isArray(state)) {
            return state
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
 * Whether Alpine may write combobox selection into the bound Livewire state.
 * `null` / `''` are valid empty single-select states and must still accept the first pick.
 *
 * @param {unknown} currentState
 */
export function canWriteHeadlessWireState(currentState) {
    return currentState !== undefined
}

/**
 * Map engine selected values → Livewire state shape.
 *
 * @param {string[]} values
 * @param {boolean} multiple
 * @returns {string[]|string|null}
 */
export function wireStateFromEngineValues(values, multiple) {
    const list = Array.isArray(values) ? values.map((value) => String(value)) : []

    if (multiple) {
        return list.slice()
    }

    return list.length > 0 ? list[0] : null
}

/**
 * Decide the next Livewire state after an engine selection change.
 *
 * @param {unknown} currentState
 * @param {string[]} values
 * @param {boolean} multiple
 * @returns {{ skip: true } | { skip: false, nextState: string[]|string|null }}
 */
export function commitHeadlessSelectionToWire(currentState, values, multiple) {
    if (! canWriteHeadlessWireState(currentState)) {
        return { skip: true }
    }

    const nextState = wireStateFromEngineValues(values, multiple)

    if (multiple) {
        const current = Array.isArray(currentState) ? currentState.map((value) => String(value)) : null

        if (current !== null && current.length === nextState.length && current.every((value, index) => value === nextState[index])) {
            return { skip: true }
        }

        return { skip: false, nextState }
    }

    if (String(currentState ?? '') === String(nextState ?? '')) {
        return { skip: true }
    }

    return { skip: false, nextState }
}

/**
 * SSR/default painted a value but Livewire still empty — push once on init.
 *
 * @param {unknown} state
 * @param {unknown} initialState
 * @param {boolean} multiple
 */
export function shouldHydrateInitialSelectionToWire(state, initialState, multiple) {
    return canWriteHeadlessWireState(state)
        && isHeadlessWireStateEmpty(state, multiple)
        && hasHeadlessInitialSelection(initialState, multiple)
}

/**
 * Keep selected values that still exist in the remote option list.
 *
 * @param {string[]} selectedValues
 * @param {Iterable<string>} allowedValues
 * @returns {string[]}
 */
export function pruneSelectedValuesToAllowed(selectedValues, allowedValues) {
    const allowed = new Set(Array.from(allowedValues, (value) => String(value)))

    return (Array.isArray(selectedValues) ? selectedValues : [])
        .map((value) => String(value))
        .filter((value) => allowed.has(value))
}

/**
 * Failed Livewire option fetches (`null` / throw) must not wipe painted options
 * or prune a valid selection to empty.
 *
 * @param {unknown} results
 */
export function shouldApplyRemoteOptionsFromFetchResult(results) {
    return results !== null
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
