/**
 * Legacy fixed-slot virtual helpers (pre-TanStack). Quarantined for historical unit tests only.
 * Production Select virt uses fff-virtual-adapter / @tanstack/virtual-core.
 */
import { HEADLESS_DROPDOWN_ROW_HEIGHTS } from '../../../../resources/js/components/select-field/headless-select-options.js'

/**
 * @param {Array<{ height?: number }>} flatRows
 * @param {number} [fallbackRowHeight]
 * @returns {number[]} prefixSums where prefixSums[i] is the offset of row i
 */
export function buildVirtualRowPrefixSums(flatRows, fallbackRowHeight = HEADLESS_DROPDOWN_ROW_HEIGHTS.option) {
    const prefixSums = new Array((flatRows?.length ?? 0) + 1)
    prefixSums[0] = 0

    for (let index = 0; index < (flatRows?.length ?? 0); index += 1) {
        prefixSums[index + 1] = prefixSums[index]
            + (flatRows[index]?.height ?? fallbackRowHeight)
    }

    return prefixSums
}

/**
 * Binary-search the first row whose bottom edge is past scrollTop
 * (largest i where prefixSums[i] <= scrollTop).
 *
 * @param {number[]} prefixSums
 * @param {number} scrollTop
 * @returns {number}
 */
export function findVirtualRowIndexAtScrollTop(prefixSums, scrollTop) {
    if (! Array.isArray(prefixSums) || prefixSums.length <= 1) {
        return 0
    }

    const offset = Math.max(0, Number(scrollTop) || 0)
    let low = 0
    let high = prefixSums.length - 2

    while (low < high) {
        const mid = (low + high + 1) >> 1

        if (prefixSums[mid] <= offset) {
            low = mid
        } else {
            high = mid - 1
        }
    }

    return low
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

