import assert from 'node:assert/strict'
import test from 'node:test'

import { createIconPickerComboboxBridge } from '../../resources/js/core/icon-picker-combobox-bridge.js'

test('createIconPickerComboboxBridge syncs highlight index with combobox engine', () => {
    const component = {
        loadedIconItems: [
            { name: 'heroicon-o-star', label: 'Star' },
            { name: 'heroicon-o-bolt', label: 'Bolt' },
        ],
        state: null,
        activeIconIndex: -1,
        selectIcon() {},
        $watch() {},
    }

    createIconPickerComboboxBridge(component)

    assert.equal(component.activeIconIndex, 0)
    assert.ok(component._iconEngine)

    component.iconComboboxMoveHighlight(1)

    assert.equal(component.activeIconIndex, 1)
})

test('createIconPickerComboboxBridge selects highlighted icon', () => {
    let selected = null

    const component = {
        loadedIconItems: [{ name: 'heroicon-o-star', label: 'Star' }],
        state: null,
        activeIconIndex: 0,
        selectIcon(name) {
            selected = name
        },
        $watch() {},
    }

    createIconPickerComboboxBridge(component)
    component.iconComboboxSelectHighlighted()

    assert.equal(selected, 'heroicon-o-star')
})
