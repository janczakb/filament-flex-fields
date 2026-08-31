import { normalizeSearchQuery } from './search-normalize.js'

export const DEFAULT_VIRTUALIZE_THRESHOLD = 100
export const DEFAULT_VIRTUAL_WINDOW_SIZE = 50

function defaultGetOptionLabel(option) {
    if (option == null) {
        return ''
    }

    if (typeof option === 'string' || typeof option === 'number') {
        return String(option)
    }

    if (typeof option.label === 'string' || typeof option.label === 'number') {
        return String(option.label)
    }

    if (typeof option.name === 'string' || typeof option.name === 'number') {
        return String(option.name)
    }

    return String(option.value ?? option.id ?? '')
}

function defaultGetOptionValue(option) {
    if (option == null) {
        return option
    }

    if (typeof option === 'string' || typeof option === 'number') {
        return option
    }

    if (Object.prototype.hasOwnProperty.call(option, 'value')) {
        return option.value
    }

    if (Object.prototype.hasOwnProperty.call(option, 'id')) {
        return option.id
    }

    return option
}

function defaultFilterFn(option, normalizedQuery, getOptionLabel) {
    if (normalizedQuery === '') {
        return true
    }

    const label = normalizeSearchQuery(getOptionLabel(option))

    return label.includes(normalizedQuery)
}

/**
 * Alpine-agnostic headless combobox state machine for SelectField v3 migration.
 */
export function createComboboxEngine({
    options = [],
    multiple = false,
    searchable = true,
    getOptionLabel = defaultGetOptionLabel,
    getOptionValue = defaultGetOptionValue,
    filterFn = defaultFilterFn,
    isOptionDisabled = () => false,
    onChange = null,
    virtualizeThreshold = DEFAULT_VIRTUALIZE_THRESHOLD,
    virtualWindowSize = DEFAULT_VIRTUAL_WINDOW_SIZE,
    initialSelectedValues = [],
    recentValues = [],
    suggestedValues = [],
    allowCreate = false,
    createOptionLabel = (query) => `Create "${query}"`,
} = {}) {
    let destroyed = false
    let open = false
    let query = ''
    let highlightedIndex = -1
    let virtualWindowStart = 0
    const recentValueSet = new Set(recentValues.map((value) => String(value)))
    const suggestedValueSet = new Set(suggestedValues.map((value) => String(value)))
    const selectedValues = new Set(initialSelectedValues)

    function assertActive() {
        if (destroyed) {
            throw new Error('combobox engine destroyed')
        }
    }

    function computeFilteredList() {
        const normalizedQuery = searchable ? normalizeSearchQuery(query) : ''

        if (! searchable || normalizedQuery === '') {
            return options.slice()
        }

        return options.filter((option) => filterFn(option, normalizedQuery, getOptionLabel))
    }

    function computeSelectableFilteredList() {
        return computeFilteredList().filter((option) => ! isOptionDisabled(option))
    }

    function clampHighlightedIndex(index, total) {
        if (total <= 0) {
            return -1
        }

        return Math.max(0, Math.min(index, total - 1))
    }

    function syncVirtualWindowToHighlight(total) {
        if (options.length < virtualizeThreshold || total <= 0) {
            virtualWindowStart = 0

            return
        }

        if (highlightedIndex < virtualWindowStart) {
            virtualWindowStart = highlightedIndex
        } else if (highlightedIndex >= virtualWindowStart + virtualWindowSize) {
            virtualWindowStart = Math.max(0, highlightedIndex - virtualWindowSize + 1)
        }

        const maxStart = Math.max(0, total - virtualWindowSize)

        virtualWindowStart = Math.min(virtualWindowStart, maxStart)
    }

    function notifyChange() {
        onChange?.(Array.from(selectedValues))
    }

    function resetBrowseState() {
        query = ''
        highlightedIndex = -1
        virtualWindowStart = 0
    }

    return {
        getSnapshot() {
            return {
                open,
                query,
                highlightedIndex,
                selectedValues,
            }
        },

        open() {
            assertActive()
            open = true

            const total = computeFilteredList().length

            highlightedIndex = clampHighlightedIndex(highlightedIndex, total)
            syncVirtualWindowToHighlight(total)
        },

        close() {
            assertActive()
            open = false
            resetBrowseState()
        },

        toggle() {
            assertActive()

            if (open) {
                this.close()
            } else {
                this.open()
            }
        },

        setQuery(nextQuery) {
            assertActive()
            query = String(nextQuery ?? '')
            virtualWindowStart = 0

            const total = computeFilteredList().length

            highlightedIndex = clampHighlightedIndex(highlightedIndex, total)
            syncVirtualWindowToHighlight(total)
        },

        moveHighlight(delta) {
            assertActive()

            const selectable = computeSelectableFilteredList()
            const total = selectable.length

            if (total <= 0) {
                highlightedIndex = -1
                virtualWindowStart = 0

                return
            }

            const filtered = computeFilteredList()
            const currentValue = highlightedIndex >= 0 && highlightedIndex < filtered.length
                ? getOptionValue(filtered[highlightedIndex])
                : null

            let selectableIndex = currentValue == null
                ? -1
                : selectable.findIndex((option) => String(getOptionValue(option)) === String(currentValue))

            if (selectableIndex < 0) {
                selectableIndex = delta >= 0 ? 0 : total - 1
            } else {
                selectableIndex = Math.max(0, Math.min(selectableIndex + delta, total - 1))
            }

            const nextValue = getOptionValue(selectable[selectableIndex])
            highlightedIndex = filtered.findIndex((option) => String(getOptionValue(option)) === String(nextValue))

            syncVirtualWindowToHighlight(filtered.length)
        },

        selectHighlighted() {
            assertActive()

            const filtered = computeFilteredList()

            if (highlightedIndex < 0 || highlightedIndex >= filtered.length) {
                return false
            }

            const option = filtered[highlightedIndex]

            if (isOptionDisabled(option)) {
                return false
            }

            const value = getOptionValue(option)

            this.selectValue(value)

            return true
        },

        selectValue(value) {
            assertActive()

            const filtered = computeFilteredList()
            const option = filtered.find((entry) => String(getOptionValue(entry)) === String(value))

            if (option && isOptionDisabled(option)) {
                return
            }

            if (multiple) {
                selectedValues.add(value)
                notifyChange()

                return
            }

            selectedValues.clear()
            selectedValues.add(value)
            notifyChange()
            this.close()
        },

        deselectValue(value) {
            assertActive()

            if (! selectedValues.delete(value)) {
                return
            }

            notifyChange()
        },

        setSelectedValues(nextValues) {
            assertActive()

            const incoming = Array.from(nextValues ?? [])
            const current = Array.from(selectedValues)

            if (
                incoming.length === current.length
                && incoming.every((value, index) => String(value) === String(current[index]))
            ) {
                return
            }

            selectedValues.clear()

            for (const value of incoming) {
                selectedValues.add(value)
            }

            notifyChange()
        },

        filteredOptions() {
            assertActive()

            const filtered = computeFilteredList()
            const total = filtered.length

            if (options.length < virtualizeThreshold) {
                return {
                    options: filtered,
                    meta: {
                        startIndex: 0,
                        endIndex: total,
                        total,
                    },
                }
            }

            const startIndex = virtualWindowStart
            const endIndex = Math.min(total, startIndex + virtualWindowSize)

            return {
                options: filtered.slice(startIndex, endIndex),
                meta: {
                    startIndex,
                    endIndex,
                    total,
                },
            }
        },

        smartSections() {
            assertActive()

            const filtered = computeFilteredList()
            const normalizedQuery = searchable ? normalizeSearchQuery(query) : ''
            const sections = []

            if (normalizedQuery === '' && recentValueSet.size > 0) {
                const recent = filtered.filter((option) => recentValueSet.has(String(getOptionValue(option))))

                if (recent.length > 0) {
                    sections.push({ type: 'recent', label: 'Recent', options: recent })
                }
            }

            if (normalizedQuery === '' && suggestedValueSet.size > 0) {
                const suggested = filtered.filter((option) => suggestedValueSet.has(String(getOptionValue(option))))

                if (suggested.length > 0) {
                    sections.push({ type: 'suggested', label: 'Suggested', options: suggested })
                }
            }

            if (allowCreate && normalizedQuery !== '') {
                const exists = filtered.some((option) => normalizeSearchQuery(getOptionLabel(option)) === normalizedQuery)

                if (! exists) {
                    sections.push({
                        type: 'create',
                        label: createOptionLabel(String(query).trim()),
                        value: String(query).trim(),
                    })
                }
            }

            sections.push({ type: 'options', label: null, options: filtered })

            return sections
        },

        createInlineOption(rawValue) {
            assertActive()

            const value = String(rawValue ?? '').trim()

            if (value === '') {
                return false
            }

            this.selectValue(value)

            return true
        },

        setOptions(nextOptions) {
            assertActive()
            options = Array.isArray(nextOptions) ? nextOptions.slice() : []

            const total = computeFilteredList().length

            highlightedIndex = clampHighlightedIndex(highlightedIndex, total)
            syncVirtualWindowToHighlight(total)
        },

        setVirtualWindowStart(startIndex) {
            assertActive()

            const total = computeFilteredList().length
            const maxStart = Math.max(0, total - virtualWindowSize)

            virtualWindowStart = Math.max(0, Math.min(startIndex, maxStart))
        },

        destroy() {
            destroyed = true
            open = false
            query = ''
            highlightedIndex = -1
            virtualWindowStart = 0
            selectedValues.clear()
        },
    }
}

/**
 * Relationship / async search adapter contract for headless SelectField.
 *
 * @param {{
 *   fetchResults: (search: string, signal?: AbortSignal) => Promise<unknown[]>,
 *   debounceMs?: number,
 *   minSearchLength?: number,
 *   warnLargeResultCount?: number,
 * }} config
 */
export function createRelationshipSearchAdapter({
    fetchResults,
    debounceMs = 1000,
    minSearchLength = 0,
    warnLargeResultCount = 5000,
} = {}) {
    if (typeof fetchResults !== 'function') {
        throw new Error('Relationship search adapter requires fetchResults(search, signal).')
    }

    /** @type {AbortController | null} */
    let inflight = null

    return {
        debounceMs,
        minSearchLength,
        warnLargeResultCount,

        async search(search, { onWarning = null } = {}) {
            inflight?.abort()
            inflight = new AbortController()

            const trimmed = String(search ?? '').trim()

            if (trimmed.length < minSearchLength) {
                return []
            }

            const results = await fetchResults(trimmed, inflight.signal)

            if (Array.isArray(results) && results.length >= warnLargeResultCount) {
                onWarning?.({
                    type: 'large_result_set',
                    count: results.length,
                    threshold: warnLargeResultCount,
                })
            }

            return Array.isArray(results) ? results : []
        },

        cancel() {
            inflight?.abort()
            inflight = null
        },
    }
}

/**
 * Async options loader stub for cascading / dependent selects.
 */
export function createCascadingAdapter({ parentGet, loadChildren }) {
    return async function loadCascadingOptions() {
        const parentValue = typeof parentGet === 'function' ? parentGet() : parentGet

        if (parentValue == null || parentValue === '') {
            return []
        }

        if (typeof loadChildren !== 'function') {
            return []
        }

        const children = await loadChildren(parentValue)

        return Array.isArray(children) ? children : []
    }
}
