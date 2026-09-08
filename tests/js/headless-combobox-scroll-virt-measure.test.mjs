import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    createHeadlessComboboxScrollVirtMixin,
    HEADLESS_SELECT_VIRTUAL_OVERSCAN,
} from '../../resources/js/components/select-field/headless-combobox-scroll-virt.js'

describe('headless combobox scroll virt — measured heights', () => {
    it('prefers ResizeObserver cache over typed row estimates when virtualizing', () => {
        const mixin = createHeadlessComboboxScrollVirtMixin()
        const host = {
            ...mixin,
            virtualizeThreshold: 2,
            virtualRowHeight: 36,
            isGridLayout: false,
            _virtualMeasuredHeights: new Map([['opt:a', 72]]),
            countVirtualizableDropdownRows() {
                return 5
            },
        }

        assert.equal(host.shouldMeasureVirtualRowHeights(), true)
        assert.equal(host.resolveVirtualRowHeight({ key: 'opt:a', height: 36 }), 72)
        assert.equal(host.resolveVirtualRowHeight({ key: 'opt:b', height: 36 }), 36)

        const windowed = host.resolveVirtualWindowForFlatRows(
            [
                { key: 'opt:a', height: 36 },
                { key: 'opt:b', height: 36 },
                { key: 'opt:c', height: 36 },
                { key: 'opt:d', height: 36 },
                { key: 'opt:e', height: 36 },
            ],
            0,
        )

        assert.equal(windowed.totalSize, 72 + (36 * 4))
    })

    it('uses a larger overscan so fast flings do not flash empty spacers', () => {
        assert.ok(HEADLESS_SELECT_VIRTUAL_OVERSCAN >= 8)

        const mixin = createHeadlessComboboxScrollVirtMixin()
        const rows = Array.from({ length: 40 }, (_, index) => ({
            key: `opt:${index}`,
            height: 36,
        }))
        const host = {
            ...mixin,
            virtualRowHeight: 36,
            _virtualMeasuredHeights: null,
        }

        const atTop = host.resolveVirtualWindowForFlatRows(rows, 0)
        // Viewport defaults to windowSize * rowHeight (~10*36). With overscan 10,
        // the first window should reach well past a tiny 2-row buffer.
        assert.ok(atTop.endIndex - atTop.startIndex >= 15)
    })
})
