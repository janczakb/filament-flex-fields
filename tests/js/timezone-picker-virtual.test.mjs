import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    createTimezonePickerMixin,
    FFF_TIMEZONE_ROW_HEIGHT,
    FFF_TIMEZONE_VIRTUAL_THRESHOLD,
} from '../../resources/js/support/timezone-picker-mixin.js'
import { SIBLING_VIRTUALIZE_THRESHOLD } from '../../resources/js/core/fff-virtual-adapter.js'

describe('timezone picker TanStack virt', () => {
    it('uses shared sibling virtualize threshold', () => {
        assert.equal(FFF_TIMEZONE_VIRTUAL_THRESHOLD, SIBLING_VIRTUALIZE_THRESHOLD)
    })

    it('windows long lists via fff-virtual-adapter', () => {
        const mixin = createTimezonePickerMixin()
        const timezones = Array.from({ length: 120 }, (_, index) => ({
            id: `Zone/${index}`,
            label: `Zone ${index}`,
            region: 'Test',
            offset: '+00:00',
        }))

        const host = Object.create(null)
        Object.defineProperties(host, Object.getOwnPropertyDescriptors(mixin))
        Object.assign(host, {
            timezones,
            timezoneSearch: '',
            virtualScrollTop: FFF_TIMEZONE_ROW_HEIGHT * 10,
            virtualViewportHeight: 320,
            virtualScrollThreshold: FFF_TIMEZONE_VIRTUAL_THRESHOLD,
            disabled: false,
            readOnly: false,
        })

        assert.equal(host.usesVirtualScroll, true)
        assert.ok(host.visibleTimezones.length < timezones.length)
        assert.ok(host.visibleTimezones.length > 0)
        assert.ok(host.virtualSpacerTop > 0)
        assert.ok(host.virtualSpacerBottom > 0)

        const windowed = host.resolveTimezoneVirtualWindow()
        assert.equal(windowed.totalSize, timezones.length * FFF_TIMEZONE_ROW_HEIGHT)
        assert.ok(windowed.startIndex >= 4)
    })
})
