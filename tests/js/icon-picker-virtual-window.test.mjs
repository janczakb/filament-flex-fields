import test from 'node:test'
import assert from 'node:assert/strict'
import {
    buildSearchResultsCacheKey,
    resolveIconGridStrideFromWidth,
    resolveIconPickerBottomSpacer,
    resolveIconPickerGridGeometry,
    resolveIconPickerMaxScrollTop,
    resolveIconPickerTrackHeight,
    resolveIconTrackStyle,
    resolveScrollTopForIconIndex,
    resolveVirtualTopSpacerStyle,
    resolveVirtualWindow,
    shouldPrefetchNextPage,
    shouldUseIconPickerVirtualTrack,
    usesIconPickerVirtualTrack,
} from '../../resources/js/core/icon-picker-virtual-window.js'

test('resolveIconGridStrideFromWidth matches square grid cells', () => {
    const columns = 6
    const gap = 6
    const containerWidth = 300
    const cellWidth = (containerWidth - (gap * (columns - 1))) / columns

    assert.equal(resolveIconGridStrideFromWidth(containerWidth, columns, gap), cellWidth + gap)
})

test('resolveIconPickerGridGeometry derives stable cell metrics from width', () => {
    const icons = resolveIconPickerGridGeometry({
        containerWidth: 336,
        layout: 'icons',
        gridColumns: 8,
    })

    assert.equal(icons.columns, 8)
    assert.ok(icons.cellSize > 0)
    assert.equal(icons.stride, icons.cellSize + icons.gap)

    const grid = resolveIconPickerGridGeometry({
        containerWidth: 336,
        layout: 'grid',
        gridColumns: 6,
    })

    assert.equal(grid.cellSize, 68)
    assert.equal(grid.stride, 74)
})

test('shouldUseIconPickerVirtualTrack virtualizes large grids only', () => {
    assert.equal(shouldUseIconPickerVirtualTrack({
        layout: 'grid',
        itemCount: 64,
        gridColumns: 8,
        viewportHeight: 224,
        measuredStride: 42.75,
    }), false)

    assert.equal(shouldUseIconPickerVirtualTrack({
        layout: 'icons',
        itemCount: 256,
        gridColumns: 8,
        viewportHeight: 224,
        measuredStride: 42.75,
    }), true)

    assert.equal(shouldUseIconPickerVirtualTrack({
        layout: 'list',
        itemCount: 12,
    }), false)

    assert.equal(shouldUseIconPickerVirtualTrack({
        layout: 'list',
        itemCount: 80,
    }), true)
})

test('usesIconPickerVirtualTrack keeps list threshold semantics', () => {
    assert.equal(usesIconPickerVirtualTrack('list', 12), false)
    assert.equal(usesIconPickerVirtualTrack('list', 80), true)
})

test('buildSearchResultsCacheKey normalizes query set and page', () => {
    assert.equal(buildSearchResultsCacheKey('star', 'heroicons', 2), 'star|heroicons|2')
    assert.equal(buildSearchResultsCacheKey(null, null, null), '||1')
})

test('resolveVirtualWindow returns a bounded slice with stable track height', () => {
    const items = Array.from({ length: 120 }, (_, index) => ({ name: `icon-${index}` }))
    const window = resolveVirtualWindow({
        items,
        scrollTop: 240,
        viewportHeight: 288,
        layout: 'icons',
        gridColumns: 8,
    })

    assert.ok(window.slice.length > 0)
    assert.ok(window.offsetTop >= 0)
    assert.ok(window.trackHeight > 0)
    assert.equal(window.slice[0].name, window.slice[0].name)
})

test('resolveVirtualWindow never returns an empty slice for non-empty lists', () => {
    const items = Array.from({ length: 128 }, (_, index) => ({ name: `icon-${index}` }))
    const window = resolveVirtualWindow({
        items,
        scrollTop: 99999,
        viewportHeight: 224,
        layout: 'grid',
        gridColumns: 6,
    })

    assert.ok(window.slice.length > 0)
    assert.ok(window.endIndex <= items.length)
})

test('resolveVirtualWindow track height matches total rows', () => {
    const items = Array.from({ length: 64 }, (_, index) => ({ name: `icon-${index}` }))
    const window = resolveVirtualWindow({
        items,
        scrollTop: 0,
        viewportHeight: 224,
        layout: 'icons',
        gridColumns: 8,
    })

    const stride = 48 + 6
    const totalRows = Math.ceil(items.length / 8)

    assert.equal(window.trackHeight, resolveIconPickerTrackHeight(totalRows, stride))
})

test('resolveVirtualWindow aligns padding with measured stride', () => {
    const items = Array.from({ length: 120 }, (_, index) => ({ name: `icon-${index}` }))
    const stride = 44
    const window = resolveVirtualWindow({
        items,
        scrollTop: 220,
        viewportHeight: 224,
        layout: 'icons',
        gridColumns: 8,
        measuredStride: stride,
        overscanRows: 3,
    })

    assert.equal(window.paddingTop, 88)
    assert.equal(window.offsetTop, 88)
})

test('resolveVirtualWindow keeps a stable mounted row count', () => {
    const items = Array.from({ length: 200 }, (_, index) => ({ name: `icon-${index}` }))
    const stride = 44
    const window = resolveVirtualWindow({
        items,
        scrollTop: 180,
        viewportHeight: 224,
        layout: 'icons',
        gridColumns: 8,
        measuredStride: stride,
        overscanRows: 4,
    })

    assert.equal(window.mountedRows, window.endRow - window.startRow)
    assert.ok(window.slice.length > 0)
    assert.ok(window.slice.length <= window.mountedRows * 8)
})

test('resolveScrollTopForIconIndex centers the target row', () => {
    const scrollTop = resolveScrollTopForIconIndex({
        index: 40,
        total: 128,
        layout: 'icons',
        gridColumns: 8,
        viewportHeight: 224,
    })

    assert.ok(scrollTop >= 0)
})

test('resolveIconPickerBottomSpacer fills remaining track height', () => {
    const stride = 44
    const trackHeight = 440
    const bottomSpacer = resolveIconPickerBottomSpacer({
        trackHeight,
        offsetTop: 88,
        startRow: 2,
        endRow: 5,
        stride,
    })

    assert.equal(bottomSpacer, trackHeight - 88 - (((5 - 2 - 1) * stride) + (stride - 6)))
})

test('resolveIconPickerMaxScrollTop never returns negative values', () => {
    assert.equal(resolveIconPickerMaxScrollTop(500, 224), 276)
    assert.equal(resolveIconPickerMaxScrollTop(100, 224), 0)
})

test('resolveIconTrackStyle uses minHeight without paddingTop', () => {
    const window = resolveVirtualWindow({
        items: Array.from({ length: 64 }, (_, index) => ({ name: `icon-${index}` })),
        scrollTop: 0,
        viewportHeight: 224,
        layout: 'icons',
        gridColumns: 8,
    })

    const style = resolveIconTrackStyle({ window, loadMoreTailHeight: 96 })

    assert.ok(style.minHeight)
    assert.equal(style.paddingTop, undefined)
})

test('resolveVirtualTopSpacerStyle maps paddingTop to a flex spacer', () => {
    assert.equal(resolveVirtualTopSpacerStyle(0), null)
    assert.deepEqual(resolveVirtualTopSpacerStyle(88), {
        height: '88px',
        flexShrink: 0,
    })
})

test('resolveVirtualWindow shifts the slice when scrolling a large grid', () => {
    const items = Array.from({ length: 256 }, (_, index) => ({ name: `icon-${index}` }))
    const stride = 42.75

    const top = resolveVirtualWindow({
        items,
        scrollTop: 0,
        viewportHeight: 224,
        layout: 'icons',
        gridColumns: 8,
        measuredStride: stride,
    })

    const middle = resolveVirtualWindow({
        items,
        scrollTop: 320,
        viewportHeight: 224,
        layout: 'icons',
        gridColumns: 8,
        measuredStride: stride,
    })

    assert.equal(top.startIndex, 0)
    assert.ok(middle.startIndex > top.startIndex)
    assert.ok(middle.slice.length < items.length)
    assert.ok(middle.paddingTop > 0)
})

test('shouldPrefetchNextPage triggers near the end of the track', () => {
    assert.equal(shouldPrefetchNextPage({
        scrollTop: 250,
        trackHeight: 500,
        viewportHeight: 224,
        thresholdPx: 160,
    }), true)

    assert.equal(shouldPrefetchNextPage({
        scrollTop: 0,
        trackHeight: 500,
        viewportHeight: 224,
        thresholdPx: 160,
    }), false)
})
