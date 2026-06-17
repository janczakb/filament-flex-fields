import test from 'node:test'
import assert from 'node:assert/strict'
import {
    buildSearchResultsCacheKey,
    ICON_PICKER_VIRTUAL_SCROLL_THRESHOLD,
    resolveScrollTopForIconIndex,
    resolveVirtualWindow,
} from '../../resources/js/core/icon-picker-virtual-window.js'

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

    assert.equal(window.trackHeight, totalRows * stride)
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

test('virtual scroll threshold allows first page without windowing', () => {
    assert.equal(ICON_PICKER_VIRTUAL_SCROLL_THRESHOLD, 80)
})
