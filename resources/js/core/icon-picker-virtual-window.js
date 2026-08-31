export const DEFAULT_VIRTUAL_OVERSCAN_ROWS = 3

export const ICON_PICKER_ROW_GAP = 6

/** List layout virtualizes at this count; grid/icons always virtualize. */
export const ICON_PICKER_VIRTUAL_SCROLL_THRESHOLD = 80

export const ICON_PICKER_LOAD_MORE_SKELETON_ROWS = 2

export const ICON_PICKER_MIN_SKELETON_MS = 220

export const ICON_PICKER_SKELETON_FADE_MS = 180

export const ICON_PICKER_SCROLL_PREFETCH_THRESHOLD_PX = 240

export const ICON_PICKER_SVG_PREFETCH_ROWS = 10

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

export function resolveIconPickerTrackHeight(totalRows, stride, gap = ICON_PICKER_ROW_GAP) {
    if (totalRows <= 0) {
        return 0
    }

    const rowHeight = Math.max(stride - gap, 0)

    return ((totalRows - 1) * stride) + rowHeight
}

export function resolveIconGridStrideFromWidth(containerWidth, columns, gap = ICON_PICKER_ROW_GAP) {
    const safeColumns = Math.max(1, Number(columns) || 1)

    if (containerWidth <= 0) {
        return null
    }

    const cellWidth = (containerWidth - (gap * (safeColumns - 1))) / safeColumns

    if (cellWidth <= 0) {
        return null
    }

    return cellWidth + gap
}

export function resolveIconGridCellSize(containerWidth, columns, gap = ICON_PICKER_ROW_GAP) {
    const stride = resolveIconGridStrideFromWidth(containerWidth, columns, gap)

    if (! stride || stride <= gap) {
        return null
    }

    return stride - gap
}

/**
 * Fixed-stride virtual window — single code path for list, grid, and icons layouts.
 */
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
        return createEmptyVirtualWindow()
    }

    const columns = resolveIconPickerColumns(layout, gridColumns)
    const stride = resolveIconPickerStride(layout, measuredStride)
    const totalRows = Math.ceil(total / columns)
    const trackHeight = resolveIconPickerTrackHeight(totalRows, stride)
    const viewport = Math.max(viewportHeight, stride)
    const visibleRows = Math.max(1, Math.ceil(viewport / stride))
    const mountedRows = visibleRows + (overscanRows * 2)
    const maxStartRow = Math.max(0, totalRows - mountedRows)
    const anchorRow = Math.max(0, Math.floor(scrollTop / stride))
    const startRow = Math.min(maxStartRow, Math.max(0, anchorRow - overscanRows))
    const endRow = Math.min(totalRows, startRow + mountedRows)
    const startIndex = startRow * columns
    const endIndex = Math.min(total, endRow * columns)
    const paddingTop = startRow * stride
    const paddingBottom = resolveIconPickerBottomSpacer({
        trackHeight,
        offsetTop: paddingTop,
        startRow,
        endRow,
        stride,
    })

    return {
        startIndex,
        endIndex,
        startRow,
        endRow,
        mountedRows,
        slice: items.slice(startIndex, endIndex),
        paddingTop,
        paddingBottom,
        offsetTop: paddingTop,
        trackHeight,
        stride,
        columns,
    }
}

export function createEmptyVirtualWindow() {
    return {
        startIndex: 0,
        endIndex: 0,
        startRow: 0,
        endRow: 0,
        mountedRows: 0,
        slice: [],
        paddingTop: 0,
        paddingBottom: 0,
        offsetTop: 0,
        trackHeight: 0,
        stride: 0,
        columns: 1,
    }
}

export function resolveIconPickerBottomSpacer({
    trackHeight,
    offsetTop,
    startRow,
    endRow,
    stride,
    gap = ICON_PICKER_ROW_GAP,
}) {
    const visibleRows = Math.max(0, endRow - startRow)

    if (visibleRows <= 0) {
        return Math.max(0, trackHeight - offsetTop)
    }

    const visibleHeight = resolveIconPickerTrackHeight(visibleRows, stride, gap)

    return Math.max(0, trackHeight - offsetTop - visibleHeight)
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
    const trackHeight = resolveIconPickerTrackHeight(totalRows, stride)
    const maxScrollTop = Math.max(0, trackHeight - viewportHeight)

    return Math.min(centeredRow * stride, maxScrollTop)
}

export function resolveIconPickerMaxScrollTop(trackHeight, viewportHeight, tailHeight = 0) {
    return Math.max(0, (trackHeight + tailHeight) - Math.max(viewportHeight, 0))
}

export function resolveLoadMoreSkeletonHeight({
    layout,
    measuredStride = null,
    rows = ICON_PICKER_LOAD_MORE_SKELETON_ROWS,
}) {
    const stride = resolveIconPickerStride(layout, measuredStride)

    return resolveIconPickerTrackHeight(rows, stride)
}

export function shouldPrefetchNextPage({
    scrollTop,
    trackHeight,
    viewportHeight,
    tailHeight = 0,
    thresholdPx = 120,
}) {
    const totalHeight = trackHeight + tailHeight

    if (totalHeight <= 0) {
        return false
    }

    return (scrollTop + viewportHeight + thresholdPx) >= totalHeight
}

export function buildSearchResultsCacheKey(query, set, page) {
    return `${query ?? ''}|${set ?? ''}|${page ?? 1}`
}

export function createVirtualWindowKey(window, itemCount, scrollTop = 0) {
    const scrollBucket = window.stride > 0
        ? Math.floor(scrollTop / window.stride)
        : 0

    return [
        scrollBucket,
        window.startRow,
        window.endRow,
        itemCount,
        window.stride ?? 0,
    ].join(':')
}

export function resolveIconPickerGridGeometry({
    containerWidth,
    layout,
    gridColumns = 6,
    gap = ICON_PICKER_ROW_GAP,
}) {
    const columns = resolveIconPickerColumns(layout, gridColumns)
    const cellWidth = resolveIconGridCellSize(containerWidth, columns, gap)
    const fallbackCellWidth = Math.max(resolveIconPickerRowHeight(layout) - gap, 0)
    const rawCellWidth = cellWidth ?? fallbackCellWidth
    const resolvedCellWidth = Math.floor(rawCellWidth)
    let cellHeight = resolvedCellWidth

    if (layout === 'grid') {
        cellHeight = resolveIconPickerRowHeight('grid')
    }

    const flooredCellHeight = Math.floor(cellHeight)
    const stride = flooredCellHeight + gap

    return {
        columns,
        gap,
        cellSize: flooredCellHeight,
        cellWidth: resolvedCellWidth,
        stride,
    }
}

export function shouldUseIconPickerVirtualTrack({
    layout,
    itemCount,
    gridColumns = 6,
    viewportHeight = 224,
    measuredStride = null,
    overscanRows = DEFAULT_VIRTUAL_OVERSCAN_ROWS,
}) {
    if (itemCount < 1) {
        return false
    }

    if (layout === 'list') {
        return itemCount >= ICON_PICKER_VIRTUAL_SCROLL_THRESHOLD
    }

    const columns = resolveIconPickerColumns(layout, gridColumns)
    const totalRows = Math.ceil(itemCount / columns)
    const stride = resolveIconPickerStride(layout, measuredStride)
    const viewport = Math.max(viewportHeight, stride)
    const visibleRows = Math.max(1, Math.ceil(viewport / stride))
    const mountedRows = visibleRows + (overscanRows * 2)

    return totalRows > mountedRows
}

export function usesIconPickerVirtualTrack(layout, itemCount) {
    return shouldUseIconPickerVirtualTrack({
        layout,
        itemCount,
    })
}

export function resolveVirtualTopSpacerStyle(paddingTop) {
    if (! paddingTop || paddingTop <= 0) {
        return null
    }

    return {
        height: `${paddingTop}px`,
        flexShrink: 0,
    }
}

export function resolveIconTrackStyle({
    window,
    loadMoreTailHeight = 0,
}) {
    if (! window || window.trackHeight <= 0) {
        return null
    }

    return {
        minHeight: `${window.trackHeight + loadMoreTailHeight}px`,
        boxSizing: 'border-box',
    }
}

export function resolveVirtualBottomSpacerStyle(paddingBottom) {
    if (! paddingBottom || paddingBottom <= 0) {
        return null
    }

    return {
        height: `${paddingBottom}px`,
        flexShrink: 0,
    }
}

export function resolveLoadMoreTailStyle(tailHeight) {
    if (! tailHeight || tailHeight <= 0) {
        return null
    }

    return {
        height: `${tailHeight}px`,
    }
}
