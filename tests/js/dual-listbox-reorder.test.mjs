import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import dualListboxFormComponent from '../../resources/js/components/dual-listbox.js'

function listbox(state = ['read', 'write', 'admin']) {
    const component = dualListboxFormComponent({
        state,
        options: [
            { value: 'read', label: 'Read', description: null, disabled: false },
            { value: 'write', label: 'Write', description: null, disabled: false },
            { value: 'admin', label: 'Admin', description: null, disabled: false },
        ],
        searchable: true,
        reorderable: true,
        moveOnDoubleClick: true,
        showTransferButtons: true,
        disabled: false,
        maxItems: null,
    })

    component.ensureState()

    return component
}

describe('dual listbox selected reorder', () => {
    it('moves a selected item up and down', () => {
        const component = listbox()

        component.moveSelectedDown('read')
        assert.deepEqual(component.state, ['write', 'read', 'admin'])

        component.moveSelectedUp('read')
        assert.deepEqual(component.state, ['read', 'write', 'admin'])
    })

    it('swaps two items when the first is dropped onto the second', () => {
        const component = listbox(['read', 'write'])

        component.reorderSelectedTo('read', 'write')
        assert.deepEqual(component.state, ['write', 'read'])
    })

    it('swaps two items when the second is dropped onto the first', () => {
        const component = listbox(['read', 'write'])

        component.reorderSelectedTo('write', 'read')
        assert.deepEqual(component.state, ['write', 'read'])
    })

    it('reorders by dropping onto another selected item', () => {
        const component = listbox()

        component.reorderSelectedTo('read', 'admin')
        assert.deepEqual(component.state, ['write', 'admin', 'read'])

        component.reorderSelectedTo('admin', 'write')
        assert.deepEqual(component.state, ['admin', 'write', 'read'])
    })

    it('ignores drag when reordering is disabled', () => {
        const component = listbox()
        component.reorderable = false

        assert.equal(component.canReorderSelected(), false)
        component.startSelectedDrag('read', { dataTransfer: { setData() {}, effectAllowed: '' } })
        assert.equal(component.draggingSelectedValue, null)
    })

    it('moves a multi-selected group as a block', () => {
        const component = listbox()
        component.draggingSelectedValues = ['read', 'write']
        component.reorderSelectedTo('read', 'admin')
        assert.deepEqual(component.state, ['admin', 'read', 'write'])
    })
})

describe('dual listbox touch multi-select', () => {
    it('adds items with a second touch pointer while the first is held', () => {
        const component = listbox([])
        component.options = [
            { value: 'read', label: 'Read', description: null, disabled: false },
            { value: 'write', label: 'Write', description: null, disabled: false },
            { value: 'admin', label: 'Admin', description: null, disabled: false },
        ]

        component.onPanePointerDown('available', 'read', {
            pointerType: 'touch',
            pointerId: 1,
            clientX: 40,
            clientY: 80,
            preventDefault() {},
        })
        component.onPanePointerDown('available', 'write', {
            pointerType: 'touch',
            pointerId: 2,
            preventDefault() {},
        })
        component.onPanePointerDown('available', 'admin', {
            pointerType: 'touch',
            pointerId: 3,
            preventDefault() {},
        })

        assert.deepEqual(component.availableSelection, ['read', 'write', 'admin'])
        assert.equal(component.shouldIgnoreSelectionClick(), true)

        component.beginPointerDrag()

        assert.equal(component.pointerDragActive, true)
        assert.equal(component.ghostCount, 3)
        assert.equal(component.ghostStack[0].label, 'Read')
        assert.equal(component.ghostStack.length, 3)

        component.toggleAvailableSelection('read', {})
        assert.deepEqual(component.availableSelection, ['read', 'write', 'admin'])
    })

    it('ignores mouse pointers for the hold gesture', () => {
        const component = listbox()

        component.onPanePointerDown('selected', 'read', { pointerType: 'mouse', pointerId: 1 })
        component.onPanePointerDown('selected', 'write', {
            pointerType: 'mouse',
            pointerId: 2,
            preventDefault() {},
        })

        assert.deepEqual(component.selectedSelection, [])
        assert.equal(component.touchHoldPointerId, null)
    })

    it('inserts a group after a chosen selected item', () => {
        const component = listbox(['keep-a', 'keep-b'])
        component.options = [
            { value: 'keep-a', label: 'A', description: null, disabled: false },
            { value: 'keep-b', label: 'B', description: null, disabled: false },
            { value: 'read', label: 'Read', description: null, disabled: false },
            { value: 'write', label: 'Write', description: null, disabled: false },
        ]

        component.moveToSelectedAt(['read', 'write'], 'keep-a', true)
        assert.deepEqual(component.state, ['keep-a', 'read', 'write', 'keep-b'])
    })

    it('drops a selected group back to available', () => {
        const component = listbox(['read', 'write', 'admin'])
        component.selectedSelection = ['write', 'admin']
        component.moveToAvailable(component.selectedSelection)
        assert.deepEqual(component.state, ['read'])
    })

    it('commits a pointer drop from available onto selected after a row', () => {
        const component = listbox(['keep-a', 'keep-b'])
        component.options.push(
            { value: 'extra', label: 'Extra', description: null, disabled: false },
        )
        component.pointerDragActive = true
        component.touchHoldPane = 'available'
        component.draggingSelectedValues = ['extra']
        component.dropPane = 'selected'
        component.dropTargetSelectedValue = 'keep-a'
        component.dropAfter = true
        component.commitPointerDrop()
        assert.deepEqual(component.state, ['keep-a', 'extra', 'keep-b'])
    })

    it('commits a pointer drop from selected onto available', () => {
        const component = listbox(['read', 'write', 'admin'])
        component.pointerDragActive = true
        component.touchHoldPane = 'selected'
        component.draggingSelectedValues = ['write', 'admin']
        component.dropPane = 'available'
        component.commitPointerDrop()
        assert.deepEqual(component.state, ['read'])
        assert.deepEqual(component.selectedSelection, [])
    })

    it('does not start a drag when the finger scrolls', () => {
        const component = listbox([])
        component.onPanePointerDown('available', 'read', {
            pointerType: 'touch',
            pointerId: 1,
            clientX: 40,
            clientY: 80,
            preventDefault() {},
        })
        component.onPointerMove({
            pointerId: 1,
            clientX: 40,
            clientY: 140,
            preventDefault() {},
        })

        assert.equal(component.pointerDragActive, false)
        assert.equal(component.touchHoldPointerId, null)
    })

    it('clears the highlighted selection after a successful drop', () => {
        const component = listbox(['keep-a'])
        component.options.push(
            { value: 'extra', label: 'Extra', description: null, disabled: false },
        )
        component.availableSelection = ['extra']
        component.pointerDragActive = true
        component.touchHoldPane = 'available'
        component.draggingSelectedValues = ['extra']
        component.dropPane = 'selected'
        component.dropTargetSelectedValue = 'keep-a'
        component.dropAfter = true
        component.commitPointerDrop()

        assert.deepEqual(component.state, ['keep-a', 'extra'])
        assert.deepEqual(component.availableSelection, [])
        assert.deepEqual(component.selectedSelection, [])
    })

    it('tracks the original hold touch while a second finger is down', () => {
        const component = listbox([])
        component.pointerDragActive = true
        component.touchHoldPointerId = 1
        component.touchHoldIdentifier = 0
        component.pointerDragStartX = 10
        component.pointerDragStartY = 20

        assert.equal(
            component.isHoldTouchStillDown([
                { identifier: 1, clientX: 200, clientY: 300 },
                { identifier: 0, clientX: 12, clientY: 22 },
            ]),
            true,
        )
        assert.equal(
            component.isHoldTouchStillDown([
                { identifier: 1, clientX: 200, clientY: 300 },
            ]),
            false,
        )
    })

    it('keeps disabled available options visible and unmovable', () => {
        const component = listbox([])
        component.options = [
            { value: 'read', label: 'Read', description: null, disabled: false },
            { value: 'admin', label: 'Admin', description: null, disabled: true },
        ]

        assert.deepEqual(component.availableItems.map((item) => item.value), ['read', 'admin'])
        component.moveToSelected(['admin'])
        assert.deepEqual(component.state, [])
    })

    it('does not remove disabled selected options', () => {
        const component = listbox(['read', 'admin'])
        component.options = [
            { value: 'read', label: 'Read', description: null, disabled: false },
            { value: 'write', label: 'Write', description: null, disabled: false },
            { value: 'admin', label: 'Admin', description: null, disabled: true },
        ]

        component.moveToAvailable(['admin', 'read'])
        assert.deepEqual(component.state, ['admin'])
    })

    it('reorders disabled selected options without transferring them', () => {
        const component = listbox(['read', 'write', 'admin'])
        component.options = [
            { value: 'read', label: 'Read', description: null, disabled: false },
            { value: 'write', label: 'Write', description: null, disabled: false },
            { value: 'admin', label: 'Admin', description: null, disabled: true },
        ]

        component.reorderSelectedTo('admin', 'read')
        assert.deepEqual(component.state, ['admin', 'read', 'write'])
        component.moveToAvailable(['admin'])
        assert.deepEqual(component.state, ['admin', 'read', 'write'])
    })
})
