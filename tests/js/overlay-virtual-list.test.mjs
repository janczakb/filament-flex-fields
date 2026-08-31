import assert from 'node:assert/strict'
import test from 'node:test'

import { createOverlayVirtualListMixin } from '../../resources/js/core/overlay-virtual-list.js'
import { createComboboxEngine } from '../../resources/js/core/combobox-engine.js'

test('createOverlayVirtualListMixin syncs engine window on scroll', () => {
    const mixin = createOverlayVirtualListMixin({ engineKey: '_overlayEngine', rowHeight: 40 })
    const options = Array.from({ length: 120 }, (_, index) => ({ value: index, label: `Row ${index}` }))

    const component = {
        _overlayEngine: createComboboxEngine({
            options,
            searchable: false,
            virtualizeThreshold: 100,
            virtualWindowSize: 20,
        }),
        overlayVirtualizeThreshold: 100,
        virtualScrollTick: 0,
        ...mixin,
    }

    component.onOverlayEngineScroll({ target: { scrollTop: 800 } })

    const meta = component.overlayEngineVirtualMeta().meta

    assert.ok(meta.startIndex >= 20)
})
