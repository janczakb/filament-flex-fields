export const DEFAULT_VIRTUAL_OVERSCAN_ROWS = 3

export const ICON_PICKER_ROW_GAP = 6

/** Below this count, render all loaded icons without virtual windowing. */
export const ICON_PICKER_VIRTUAL_SCROLL_THRESHOLD = 80

export function resolveIconPickerColumns(layout, gridColumns = 6) {
    return layout === 'list' ? 1 : Math.max(1, Number(gridColumns) || 6)
}

export function resolveIconPickerRowHeight(layout) {
    if (layout === 'list') {
        return 44
    }

    if (layout === 'icons') {
        return 48
    }

    return 68
}

export function resolveIconPickerStride(layout, measuredStride = null) {
    if (measuredStride && measuredStride > 0) {
        return measuredStride
    }
    return resolveIconPickerRowHeight(layout) + ICON_PICKER_ROW_GAP
}

export function resolveVirtualWindow({
    items,
    scrollTop = 0,
    viewportHeight = 0,
    layout = 'grid',
    gridColumns = 6,
    overscanRows = DEFAULT_VIRTUAL_OVERSCAN_ROWS,
    measuredStride = null,
}) {
    const total = Array.isArray(items) ? items.length : 0

    if (total === 0) {
        return {
            startIndex: 0,
            endIndex: 0,
            slice: [],
            offsetTop: 0,
            trackHeight: 0,
        }
    }

    const columns = resolveIconPickerColumns(layout, gridColumns)
    const stride = resolveIconPickerStride(layout, measuredStride)
    const totalRows = Math.ceil(total / columns)
    const trackHeight = totalRows * stride
    const visibleRows = Math.max(1, Math.ceil(Math.max(viewportHeight, stride) / stride))
    const maxVisibleRows = visibleRows + (overscanRows * 2)
    const maxStartRow = Math.max(0, totalRows - maxVisibleRows)
    const startRow = Math.min(
        maxStartRow,
        Math.max(0, Math.floor(scrollTop / stride) - overscanRows),
    )
    const startIndex = startRow * columns
    const endIndex = Math.min(total, startIndex + (maxVisibleRows * columns))

    return {
        startIndex,
        endIndex,
        slice: items.slice(startIndex, endIndex),
        offsetTop: startRow * stride,
        trackHeight,
    }
}

export function resolveScrollTopForIconIndex({
    index,
    total,
    layout = 'grid',
    gridColumns = 6,
    viewportHeight = 0,
    measuredStride = null,
}) {
    const columns = resolveIconPickerColumns(layout, gridColumns)
    const stride = resolveIconPickerStride(layout, measuredStride)
    const totalRows = Math.ceil(Math.max(total, 1) / columns)
    const targetRow = Math.max(0, Math.floor(Math.max(index, 0) / columns))
    const visibleRows = Math.max(1, Math.ceil(Math.max(viewportHeight, stride) / stride))
    const centeredRow = Math.max(0, targetRow - Math.floor(visibleRows / 2))
    const maxScrollTop = Math.max(0, (totalRows * stride) - viewportHeight)

    return Math.min(centeredRow * stride, maxScrollTop)
}

export function buildSearchResultsCacheKey(query, set, page) {
    return `${query ?? ''}|${set ?? ''}|${page ?? 1}`
}
