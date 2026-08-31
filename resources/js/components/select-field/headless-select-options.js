import { normalizeSearchQuery } from '../../core/search-normalize.js'

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

    return option
}

/**
 * @param {unknown} option
 */
export function isHeadlessOptionGroup(option) {
    return option != null
        && typeof option === 'object'
        && Array.isArray(option.options)
}

/**
 * @param {unknown} option
 */
export function headlessOptionIsDisabled(option) {
    if (option == null || typeof option !== 'object') {
        return false
    }

    return Boolean(option.disabled ?? option.isDisabled)
}

/**
 * @param {Record<string, unknown>} option
 */
export function headlessOptionValue(option) {
    return String(defaultGetOptionValue(option))
}

/**
 * @param {Record<string, unknown>} option
 * @param {'dropdown' | 'trigger'} context
 */
export function headlessOptionLabelHtml(option, context = 'dropdown') {
    if (context === 'trigger') {
        const trigger = option.triggerLabel ?? option.label ?? option.value ?? ''

        return String(trigger)
    }

    return String(option.label ?? option.name ?? option.value ?? '')
}

/**
 * @param {Array<unknown>} options
 * @returns {Array<Record<string, unknown>>}
 */
export function flattenHeadlessOptions(options) {
    const flat = []

    for (const option of options ?? []) {
        if (isHeadlessOptionGroup(option)) {
            flat.push(...flattenHeadlessOptions(option.options))

            continue
        }

        flat.push(option)
    }

    return flat
}

/**
 * @param {Array<unknown>} options
 * @param {string} normalizedQuery
 * @param {(option: Record<string, unknown>) => string} getOptionLabel
 */
function optionMatchesQuery(option, normalizedQuery, getOptionLabel) {
    if (normalizedQuery === '') {
        return true
    }

    const label = normalizeSearchQuery(getOptionLabel(option))

    return label.includes(normalizedQuery)
}

/**
 * Client-side filter that preserves option groups (empty groups are removed).
 *
 * @param {Array<unknown>} options
 * @param {string} query
 * @param {(option: Record<string, unknown>) => string} getOptionLabel
 */
export function filterHeadlessOptionTree(options, query, getOptionLabel = headlessOptionLabelHtml) {
    const normalizedQuery = normalizeSearchQuery(String(query ?? '').trim())

    if (normalizedQuery === '') {
        return Array.isArray(options) ? options.slice() : []
    }

    const filtered = []

    for (const option of options ?? []) {
        if (isHeadlessOptionGroup(option)) {
            const groupOptions = filterHeadlessOptionTree(option.options ?? [], query, getOptionLabel)

            if (groupOptions.length === 0) {
                continue
            }

            filtered.push({
                ...option,
                options: groupOptions,
            })

            continue
        }

        if (optionMatchesQuery(option, normalizedQuery, getOptionLabel)) {
            filtered.push(option)
        }
    }

    return filtered
}

export const HEADLESS_DROPDOWN_ROW_HEIGHTS = {
    option: 36,
    create: 36,
    section: 28,
    'group-header': 28,
    separator: 9,
}

/**
 * @param {Array<unknown>} options
 * @param {{
 *   multiple?: boolean,
 *   keepSelectedOptionsInDropdown?: boolean,
 *   isOptionSelected?: (value: string) => boolean,
 *   withSeparators?: boolean,
 * }} config
 */
export function buildHeadlessDropdownRows(options, {
    multiple = false,
    keepSelectedOptionsInDropdown = false,
    isOptionSelected = () => false,
    withSeparators = true,
} = {}) {
    const rows = []
    let groupIndex = 0

    for (const node of options ?? []) {
        if (isHeadlessOptionGroup(node)) {
            let groupOptions = Array.isArray(node.options) ? node.options.slice() : []

            if (multiple && ! keepSelectedOptionsInDropdown) {
                groupOptions = groupOptions.filter((option) => {
                    return ! isOptionSelected(headlessOptionValue(option))
                })
            }

            if (groupOptions.length === 0) {
                continue
            }

            if (withSeparators && groupIndex > 0) {
                rows.push({
                    type: 'separator',
                    key: `separator:${String(node.label ?? '')}:${groupIndex}`,
                })
            }

            groupIndex += 1

            rows.push({
                type: 'group',
                label: String(node.label ?? ''),
                options: groupOptions,
                key: `group:${String(node.label ?? '')}`,
            })

            continue
        }

        if (multiple && ! keepSelectedOptionsInDropdown && isOptionSelected(headlessOptionValue(node))) {
            continue
        }

        rows.push({
            type: 'option',
            option: node,
            key: headlessOptionValue(node),
        })
    }

    return rows
}

/**
 * Flatten dropdown rows into uniformly sized virtual slots for windowed rendering.
 *
 * @param {Array<{ type: string, label?: string, value?: string, option?: Record<string, unknown>, options?: Array<Record<string, unknown>>, key: string }>} rows
 */
export function flattenHeadlessDropdownRowsForVirtualization(rows) {
    /** @type {Array<{ type: string, key: string, label?: string, value?: string, option?: Record<string, unknown>, height: number }>} */
    const flat = []

    for (const row of rows ?? []) {
        if (row.type === 'group') {
            flat.push({
                type: 'group-header',
                label: row.label,
                key: `${row.key}:header`,
                height: HEADLESS_DROPDOWN_ROW_HEIGHTS['group-header'],
            })

            for (const option of row.options ?? []) {
                flat.push({
                    type: 'option',
                    option,
                    key: headlessOptionValue(option),
                    height: HEADLESS_DROPDOWN_ROW_HEIGHTS.option,
                })
            }

            continue
        }

        if (row.type === 'separator') {
            flat.push({
                type: 'separator',
                key: row.key,
                height: HEADLESS_DROPDOWN_ROW_HEIGHTS.separator,
            })

            continue
        }

        const height = HEADLESS_DROPDOWN_ROW_HEIGHTS[row.type] ?? HEADLESS_DROPDOWN_ROW_HEIGHTS.option

        flat.push({
            ...row,
            height,
        })
    }

    return flat
}

/**
 * @param {Array<{ height?: number }>} flatRows
 * @param {number} startIndex
 * @param {number} windowSize
 */
export function windowHeadlessVirtualRows(flatRows, startIndex, windowSize) {
    const total = flatRows.length

    if (total === 0) {
        return {
            rows: [],
            meta: { startIndex: 0, endIndex: 0, total: 0, paddingTop: 0, paddingBottom: 0 },
        }
    }

    const maxStart = Math.max(0, total - windowSize)
    const clampedStart = Math.max(0, Math.min(startIndex, maxStart))
    const endIndex = Math.min(total, clampedStart + windowSize)
    const visibleRows = flatRows.slice(clampedStart, endIndex)

    let paddingTop = 0

    for (let index = 0; index < clampedStart; index += 1) {
        paddingTop += flatRows[index]?.height ?? HEADLESS_DROPDOWN_ROW_HEIGHTS.option
    }

    let paddingBottom = 0

    for (let index = endIndex; index < total; index += 1) {
        paddingBottom += flatRows[index]?.height ?? HEADLESS_DROPDOWN_ROW_HEIGHTS.option
    }

    return {
        rows: visibleRows,
        meta: {
            startIndex: clampedStart,
            endIndex,
            total,
            paddingTop,
            paddingBottom,
        },
    }
}

/**
 * @param {Array<{ type: string, option?: Record<string, unknown>, options?: Array<Record<string, unknown>> }>} rows
 * @returns {Array<Record<string, unknown>>}
 */
export function flattenHeadlessDropdownRowOptions(rows) {
    const flat = []

    for (const row of rows ?? []) {
        if (row.type === 'group') {
            flat.push(...(row.options ?? []))

            continue
        }

        if (row.option) {
            flat.push(row.option)
        }
    }

    return flat
}

/**
 * @param {Array<unknown>} options
 * @param {string} value
 */
export function findHeadlessOptionRecord(options, value) {
    const normalized = String(value)

    for (const option of options ?? []) {
        if (isHeadlessOptionGroup(option)) {
            const match = findHeadlessOptionRecord(option.options ?? [], normalized)

            if (match) {
                return match
            }

            continue
        }

        if (headlessOptionValue(option) === normalized) {
            return option
        }
    }

    return null
}
