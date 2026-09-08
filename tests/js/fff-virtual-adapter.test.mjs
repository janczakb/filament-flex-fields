import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    computeFffVirtualWindow,
    createFffVirtualGridWindow,
    createFffVirtualListMixin,
    FFF_USES_TANSTACK_VIRTUAL_CORE,
    SIBLING_VIRTUALIZE_THRESHOLD,
} from '../../resources/js/core/fff-virtual-adapter.js'
import { SELECT_VIRTUALIZE_THRESHOLD, ICON_VIRTUALIZE_THRESHOLD } from '../../resources/js/core/virtualization-policy.js'
import { Virtualizer } from '@tanstack/virtual-core'

describe('fff-virtual-adapter', () => {
    it('marks TanStack virtual-core as the sole SoT', () => {
        assert.equal(FFF_USES_TANSTACK_VIRTUAL_CORE, true)
        assert.equal(typeof Virtualizer, 'function')
    })

    it('computes a fixed-height list window with overscan', () => {
        const windowed = computeFffVirtualWindow({
            count: 200,
            scrollTop: 400,
            viewportHeight: 280,
            estimateSize: () => 40,
            overscan: 2,
        })

        assert.ok(windowed.startIndex >= 7)
        assert.ok(windowed.endIndex > windowed.startIndex)
        assert.equal(windowed.totalSize, 8000)
        assert.ok(windowed.paddingTop >= 0)
        assert.ok(windowed.paddingBottom >= 0)
    })

    it('supports mixed estimate sizes (measured-height contract)', () => {
        const heights = [36, 52, 36, 72, 36]
        const windowed = computeFffVirtualWindow({
            count: heights.length,
            scrollTop: 40,
            viewportHeight: 80,
            estimateSize: (index) => heights[index],
            overscan: 0,
        })

        assert.deepEqual(windowed.indexes, [1, 2])
        assert.equal(windowed.totalSize, heights.reduce((sum, h) => sum + h, 0))
    })

    it('computes a multi-lane grid window for icon picker (row-aligned)', () => {
        const windowed = createFffVirtualGridWindow({
            count: 120,
            scrollTop: 200,
            viewportHeight: 224,
            estimateSize: () => 74,
            overscan: 1,
            lanes: 6,
        })

        assert.equal(windowed.startIndex % 6, 0)
        assert.ok(windowed.endIndex > windowed.startIndex)
        assert.ok(windowed.totalSize > 0)
    })

    it('exports shared thresholds', () => {
        assert.equal(SELECT_VIRTUALIZE_THRESHOLD, 100)
        assert.equal(SIBLING_VIRTUALIZE_THRESHOLD, 50)
        assert.equal(ICON_VIRTUALIZE_THRESHOLD, 80)
    })

    it('createFffVirtualListMixin windows long sibling lists', () => {
        const mixin = createFffVirtualListMixin({
            itemsKey: 'filteredItems',
            itemHeight: 40,
            threshold: 50,
            buffer: 2,
        })
        const items = Array.from({ length: 120 }, (_, index) => ({ id: index }))
        const host = {
            ...mixin,
            filteredItems: items,
            virtualListScrollTop: 400,
            virtualListViewportHeight: 280,
            virtualListItemHeight: 40,
            virtualListThreshold: 50,
        }

        assert.equal(host.usesVirtualList(), true)
        const visible = host.virtualVisibleItems()
        assert.ok(visible.length > 0)
        assert.ok(visible[0].index >= 7)
    })
})
