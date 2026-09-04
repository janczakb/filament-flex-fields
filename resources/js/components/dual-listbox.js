import { normalizeSearchQuery } from '../core/search-normalize.js'

export const FFF_DUAL_LISTBOX_VIRTUAL_THRESHOLD = 100
export const FFF_DUAL_LISTBOX_ROW_HEIGHT = 44
export const FFF_DUAL_LISTBOX_OVERSCAN = 6
export const FFF_DUAL_LISTBOX_POINTER_DRAG_THRESHOLD = 12
export const FFF_DUAL_LISTBOX_TOUCH_PICKUP_MS = 450

function buildVirtualWindow(items, scrollTop, viewportHeight) {
    if (items.length <= FFF_DUAL_LISTBOX_VIRTUAL_THRESHOLD) {
        return {
            items,
            spacerTop: 0,
            spacerBottom: 0,
            useVirtual: false,
        }
    }

    const startIndex = Math.max(
        0,
        Math.floor(scrollTop / FFF_DUAL_LISTBOX_ROW_HEIGHT) - FFF_DUAL_LISTBOX_OVERSCAN,
    )
    const visibleCount = Math.ceil(viewportHeight / FFF_DUAL_LISTBOX_ROW_HEIGHT)
        + (FFF_DUAL_LISTBOX_OVERSCAN * 2)
    const endIndex = Math.min(items.length, startIndex + visibleCount)

    return {
        items: items.slice(startIndex, endIndex),
        spacerTop: startIndex * FFF_DUAL_LISTBOX_ROW_HEIGHT,
        spacerBottom: Math.max(0, (items.length - endIndex) * FFF_DUAL_LISTBOX_ROW_HEIGHT),
        useVirtual: true,
    }
}

export default function dualListboxFormComponent({
    state,
    options,
    searchable,
    reorderable,
    moveOnDoubleClick,
    showTransferButtons,
    disabled,
    maxItems,
    virtualThreshold = FFF_DUAL_LISTBOX_VIRTUAL_THRESHOLD,
}) {
    return {
        state,
        options,
        searchable,
        reorderable,
        moveOnDoubleClick,
        showTransferButtons,
        disabled,
        maxItems,
        virtualThreshold,
        availableQuery: '',
        selectedQuery: '',
        availableSelection: [],
        selectedSelection: [],
        availableScrollTop: 0,
        selectedScrollTop: 0,
        availableViewportHeight: 0,
        selectedViewportHeight: 0,
        draggingSelectedValue: null,
        dropTargetSelectedValue: null,
        draggingSelectedValues: [],
        touchHoldPointerId: null,
        touchHoldIdentifier: null,
        touchHoldPane: null,
        touchHoldValue: null,
        touchIgnoreClicksUntil: 0,
        pointerDragActive: false,
        pointerDragStartX: 0,
        pointerDragStartY: 0,
        touchPickupTimer: null,
        dropPane: null,
        dropAfter: false,
        ghostX: 0,
        ghostY: 0,
        html5DragEnabled: false,
        pointerListenerAbort: null,

        init() {
            this.ensureState()
            this.html5DragEnabled = typeof window !== 'undefined'
                && Boolean(window.matchMedia?.('(hover: hover) and (pointer: fine)')?.matches)
            this.bindPointerListeners()

            this.$watch('state', () => {
                this.ensureState()
            })
        },

        destroy() {
            this.clearTouchPickupTimer()
            this.pointerListenerAbort?.abort()
            this.pointerListenerAbort = null
        },

        bindPointerListeners() {
            if (typeof window === 'undefined' || typeof AbortController === 'undefined') {
                return
            }

            this.pointerListenerAbort?.abort()
            this.pointerListenerAbort = new AbortController()
            const signal = this.pointerListenerAbort.signal
            const options = { capture: true, passive: false, signal }

            window.addEventListener('pointermove', (event) => this.onPointerMove(event), options)
            window.addEventListener('pointerup', (event) => this.onPointerUp(event), options)
            window.addEventListener('pointercancel', (event) => this.onPointerUp(event), options)
            window.addEventListener('touchmove', (event) => this.onTouchMove(event), options)
            window.addEventListener('touchend', (event) => this.onTouchEnd(event), options)
        },

        ensureState() {
            if (! Array.isArray(this.state)) {
                this.state = []
            }
        },

        optionMap() {
            return Object.fromEntries(this.options.map((option) => [option.value, option]))
        },

        matchesQuery(option, query) {
            if (! query) {
                return true
            }

            const needle = normalizeSearchQuery(query)

            if (! needle) {
                return true
            }

            return (
                normalizeSearchQuery(option.label).includes(needle) ||
                normalizeSearchQuery(option.description).includes(needle)
            )
        },

        get availableItems() {
            const selected = new Set(this.state ?? [])

            return this.options.filter(
                (option) =>
                    ! selected.has(option.value) &&
                    this.matchesQuery(option, this.availableQuery),
            )
        },

        get selectedItems() {
            const map = this.optionMap()

            return (this.state ?? [])
                .map((value) => map[value])
                .filter(Boolean)
                .filter((option) => this.matchesQuery(option, this.selectedQuery))
        },

        get availableVirtualWindow() {
            return buildVirtualWindow(
                this.availableItems,
                this.availableScrollTop,
                this.availableViewportHeight || 256,
            )
        },

        get selectedVirtualWindow() {
            return buildVirtualWindow(
                this.selectedItems,
                this.selectedScrollTop,
                this.selectedViewportHeight || 256,
            )
        },

        onAvailableListScroll(event) {
            this.availableScrollTop = event.target.scrollTop
            this.availableViewportHeight = event.target.clientHeight
            this.cancelPendingTouchPickup()
        },

        onSelectedListScroll(event) {
            this.selectedScrollTop = event.target.scrollTop
            this.selectedViewportHeight = event.target.clientHeight
            this.cancelPendingTouchPickup()
        },

        measureAvailableList(event) {
            this.availableViewportHeight = event.target.clientHeight
        },

        measureSelectedList(event) {
            this.selectedViewportHeight = event.target.clientHeight
        },

        isAvailableSelected(value) {
            return this.availableSelection.includes(value)
        },

        isSelectedSelected(value) {
            return this.selectedSelection.includes(value)
        },

        isPointerDraggingValue(value) {
            return this.pointerDragActive && (this.draggingSelectedValues ?? []).includes(value)
        },

        isSelectedDropTarget(value) {
            return this.dropPane === 'selected' && this.dropTargetSelectedValue === value
        },

        get ghostCount() {
            return this.draggingSelectedValues?.length ?? 0
        },

        get ghostStack() {
            const map = this.optionMap()
            const values = [...(this.draggingSelectedValues ?? [])]
            const lead = this.touchHoldValue
            const ordered = lead !== null && values.includes(lead)
                ? [lead, ...values.filter((value) => value !== lead)]
                : values

            return ordered.slice(0, 3).map((value, depth) => ({
                value,
                label: map[value]?.label ?? '',
                depth,
            }))
        },

        get ghostStyle() {
            return {
                transform: `translate3d(${this.ghostX}px, ${this.ghostY}px, 0)`,
            }
        },

        ghostCardStyle(depth) {
            const layers = [
                { x: 0, y: 0, rotate: 0, z: 3 },
                { x: 10, y: 8, rotate: 5, z: 2 },
                { x: -8, y: 14, rotate: -6, z: 1 },
            ]
            const layer = layers[depth] ?? layers[0]

            return {
                transform: `translate(${layer.x}px, ${layer.y}px) rotate(${layer.rotate}deg)`,
                zIndex: layer.z,
            }
        },

        updateGhostPosition(clientX, clientY) {
            this.ghostX = (clientX ?? 0) - 40
            this.ghostY = (clientY ?? 0) - 28
        },

        syncDraggingFromSelection() {
            const selection = this.touchHoldPane === 'available'
                ? this.availableSelection
                : this.selectedSelection

            this.draggingSelectedValues = selection.length > 0
                ? [...selection]
                : (this.touchHoldValue === null ? [] : [this.touchHoldValue])
            this.draggingSelectedValue = this.touchHoldValue
        },

        isOptionDisabled(value) {
            return Boolean(this.optionMap()[value]?.disabled)
        },

        toggleAvailableSelection(value, event) {
            if (this.disabled || this.isOptionDisabled(value) || this.shouldIgnoreSelectionClick()) {
                return
            }

            this.selectedSelection = []
            this.availableSelection = this.resolveSelection(
                this.availableSelection,
                this.availableItems.map((item) => item.value),
                value,
                event,
            )
        },

        toggleSelectedSelection(value, event) {
            if (this.disabled || this.shouldIgnoreSelectionClick()) {
                return
            }

            this.availableSelection = []
            this.selectedSelection = this.resolveSelection(
                this.selectedSelection,
                (this.state ?? []).filter((item) => this.optionMap()[item]),
                value,
                event,
            )
        },

        resolveSelection(current, orderedValues, value, event) {
            if (event?.shiftKey) {
                const anchor = current.length > 0 ? current[current.length - 1] : value
                const start = orderedValues.indexOf(anchor)
                const end = orderedValues.indexOf(value)

                if (start === -1 || end === -1) {
                    return [value]
                }

                const [from, to] = start < end ? [start, end] : [end, start]

                return orderedValues.slice(from, to + 1)
            }

            if (event?.metaKey || event?.ctrlKey) {
                if (current.includes(value)) {
                    return current.filter((item) => item !== value)
                }

                return [...current, value]
            }

            return [value]
        },

        isCoarsePointerEvent(event) {
            return event?.pointerType === 'touch' || event?.pointerType === 'pen'
        },

        shouldIgnoreSelectionClick() {
            return Date.now() < this.touchIgnoreClicksUntil
        },

        onAvailablePointerDown(value, event) {
            this.onPanePointerDown('available', value, event)
        },

        onSelectedPointerDown(value, event) {
            this.onPanePointerDown('selected', value, event)
        },

        onPanePointerDown(pane, value, event) {
            if (this.disabled || ! this.isCoarsePointerEvent(event)) {
                return
            }

            if (pane === 'available' && this.isOptionDisabled(value)) {
                return
            }

            if (
                this.touchHoldPointerId !== null
                && event.pointerId !== this.touchHoldPointerId
                && this.touchHoldPane === pane
            ) {
                event.preventDefault()
                this.touchIgnoreClicksUntil = Number.POSITIVE_INFINITY
                this.addPaneSelection(pane, this.touchHoldValue)
                this.addPaneSelection(pane, value)

                if (this.pointerDragActive) {
                    this.syncDraggingFromSelection()
                }

                return
            }

            this.touchHoldPointerId = event.pointerId
            this.touchHoldIdentifier = null
            this.touchHoldPane = pane
            this.touchHoldValue = value
            this.pointerDragStartX = event.clientX ?? 0
            this.pointerDragStartY = event.clientY ?? 0
            this.dropPane = null
            this.dropAfter = false
            this.pointerDragActive = false
            this.scheduleTouchPickup()
        },

        onPointerMove(event) {
            if (this.disabled || event?.pointerId !== this.touchHoldPointerId) {
                return
            }

            const deltaX = (event.clientX ?? 0) - this.pointerDragStartX
            const deltaY = (event.clientY ?? 0) - this.pointerDragStartY

            if (! this.pointerDragActive) {
                if ((deltaX * deltaX) + (deltaY * deltaY) >= (FFF_DUAL_LISTBOX_POINTER_DRAG_THRESHOLD ** 2)) {
                    this.cancelPendingTouchPickup()
                }

                return
            }

            event.preventDefault()
            this.updateGhostPosition(event.clientX, event.clientY)
            this.updatePointerDropTarget(event.clientX, event.clientY)
        },

        onTouchMove(event) {
            if (this.disabled || this.touchHoldPointerId === null) {
                return
            }

            const touch = this.findHoldTouch(event.touches)

            if (! touch) {
                return
            }

            if (! this.pointerDragActive) {
                const deltaX = (touch.clientX ?? 0) - this.pointerDragStartX
                const deltaY = (touch.clientY ?? 0) - this.pointerDragStartY

                if ((deltaX * deltaX) + (deltaY * deltaY) >= (FFF_DUAL_LISTBOX_POINTER_DRAG_THRESHOLD ** 2)) {
                    this.cancelPendingTouchPickup()
                }

                return
            }

            event.preventDefault()
            this.updateGhostPosition(touch.clientX, touch.clientY)
            this.updatePointerDropTarget(touch.clientX, touch.clientY)
        },

        onTouchEnd(event) {
            if (this.touchHoldPointerId === null) {
                return
            }

            if (this.isHoldTouchStillDown(event.touches)) {
                return
            }

            this.clearTouchPickupTimer()

            if (this.pointerDragActive) {
                this.commitPointerDrop()
                this.touchIgnoreClicksUntil = Date.now() + 400
            }

            this.touchHoldPointerId = null
            this.touchHoldIdentifier = null
            this.touchHoldPane = null
            this.touchHoldValue = null
        },

        isHoldTouchStillDown(touchList) {
            if (! touchList || touchList.length === 0) {
                return false
            }

            if (this.touchHoldIdentifier !== null) {
                for (const touch of touchList) {
                    if (touch.identifier === this.touchHoldIdentifier) {
                        return true
                    }
                }

                return false
            }

            for (const touch of touchList) {
                const deltaX = (touch.clientX ?? 0) - this.pointerDragStartX
                const deltaY = (touch.clientY ?? 0) - this.pointerDragStartY

                if (((deltaX * deltaX) + (deltaY * deltaY)) < (80 ** 2)) {
                    return true
                }
            }

            return false
        },

        findHoldTouch(touchList) {
            if (! touchList || touchList.length === 0) {
                return null
            }

            if (this.touchHoldIdentifier === null) {
                let closest = touchList[0]
                let closestDistance = Number.POSITIVE_INFINITY

                for (const touch of touchList) {
                    const deltaX = (touch.clientX ?? 0) - this.pointerDragStartX
                    const deltaY = (touch.clientY ?? 0) - this.pointerDragStartY
                    const distance = (deltaX * deltaX) + (deltaY * deltaY)

                    if (distance < closestDistance) {
                        closestDistance = distance
                        closest = touch
                    }
                }

                this.touchHoldIdentifier = closest.identifier

                return closest
            }

            for (const touch of touchList) {
                if (touch.identifier === this.touchHoldIdentifier) {
                    return touch
                }
            }

            return null
        },

        beginPointerDrag() {
            this.clearTouchPickupTimer()
            this.pointerDragActive = true
            this.touchIgnoreClicksUntil = Number.POSITIVE_INFINITY
            this.addPaneSelection(this.touchHoldPane, this.touchHoldValue)
            this.syncDraggingFromSelection()
            this.updateGhostPosition(this.pointerDragStartX, this.pointerDragStartY)
        },

        scheduleTouchPickup() {
            this.clearTouchPickupTimer()

            if (typeof window === 'undefined') {
                return
            }

            this.touchPickupTimer = window.setTimeout(() => {
                this.touchPickupTimer = null

                if (this.touchHoldPointerId === null || this.pointerDragActive) {
                    return
                }

                this.beginPointerDrag()
            }, FFF_DUAL_LISTBOX_TOUCH_PICKUP_MS)
        },

        clearTouchPickupTimer() {
            if (this.touchPickupTimer !== null) {
                clearTimeout(this.touchPickupTimer)
                this.touchPickupTimer = null
            }
        },

        cancelPendingTouchPickup() {
            if (this.pointerDragActive) {
                return
            }

            this.clearTouchPickupTimer()
            this.touchHoldPointerId = null
            this.touchHoldIdentifier = null
            this.touchHoldPane = null
            this.touchHoldValue = null
        },

        updatePointerDropTarget(clientX, clientY) {
            const hit = this.resolvePointerDropHit(clientX, clientY)

            this.dropPane = hit.pane
            this.dropTargetSelectedValue = hit.value
            this.dropAfter = hit.after
        },

        resolvePointerDropHit(clientX, clientY) {
            if (typeof document === 'undefined' || typeof document.elementsFromPoint !== 'function') {
                return { pane: null, value: null, after: false }
            }

            const stack = document.elementsFromPoint(clientX, clientY)
            const dragging = new Set(this.draggingSelectedValues ?? [])

            for (const node of stack) {
                if (! node?.closest || node.closest('[data-fff-dual-listbox-ghost]')) {
                    continue
                }

                const item = node.closest('[data-fff-dual-listbox-item="true"]')
                const list = node.closest('[data-fff-dual-listbox-pane]')

                if (! list) {
                    continue
                }

                const pane = list.getAttribute('data-fff-dual-listbox-pane')
                const value = item?.getAttribute('data-fff-value') ?? null

                if (value !== null && dragging.has(value) && pane === this.touchHoldPane) {
                    continue
                }

                let after = false

                if (item) {
                    const rect = item.getBoundingClientRect()
                    after = clientY > rect.top + (rect.height / 2)
                } else {
                    after = pane === 'selected'
                }

                return { pane, value, after }
            }

            return { pane: null, value: null, after: false }
        },

        commitPointerDrop() {
            const fromPane = this.touchHoldPane
            const values = [...(this.draggingSelectedValues ?? [])]
            const toPane = this.dropPane
            const targetValue = this.dropTargetSelectedValue
            const after = this.dropAfter

            if (! this.pointerDragActive || values.length === 0 || ! toPane) {
                this.clearPointerDrag()

                return
            }

            if (fromPane === 'available' && toPane === 'selected') {
                this.moveToSelectedAt(values, targetValue, after)
            } else if (fromPane === 'selected' && toPane === 'available') {
                this.moveToAvailable(values)
            } else if (fromPane === 'selected' && toPane === 'selected' && this.canReorderSelected()) {
                this.insertSelectedBlock(values, targetValue, after)
            }

            this.clearPointerDrag()
        },

        moveToSelectedAt(values, targetValue = null, after = false) {
            if (this.disabled || values.length === 0) {
                return
            }

            const movable = values.filter((value) => {
                const option = this.optionMap()[value]

                return option && ! option.disabled && ! (this.state ?? []).includes(value)
            })

            if (movable.length === 0 || ! this.canAddCount(movable.length)) {
                return
            }

            const next = [...(this.state ?? [])]

            if (targetValue === null || ! next.includes(targetValue)) {
                this.state = [...next, ...movable]
            } else {
                let index = next.indexOf(targetValue)

                if (after) {
                    index += 1
                }

                next.splice(index, 0, ...movable)
                this.state = next
            }

            this.availableSelection = []
        },

        onPointerUp(event) {
            if (event?.pointerId !== this.touchHoldPointerId) {
                return
            }

            this.clearTouchPickupTimer()

            if (this.pointerDragActive) {
                this.commitPointerDrop()
            }

            this.touchHoldPointerId = null
            this.touchHoldIdentifier = null
            this.touchHoldPane = null
            this.touchHoldValue = null

            if (this.touchIgnoreClicksUntil === Number.POSITIVE_INFINITY) {
                this.touchIgnoreClicksUntil = Date.now() + 400
            }
        },

        addPaneSelection(pane, value) {
            if (value === null || value === undefined) {
                return
            }

            if (pane === 'available' && this.isOptionDisabled(value)) {
                return
            }

            if (pane === 'available') {
                this.selectedSelection = []

                if (! this.availableSelection.includes(value)) {
                    this.availableSelection = [...this.availableSelection, value]
                }

                return
            }

            this.availableSelection = []

            if (! this.selectedSelection.includes(value)) {
                this.selectedSelection = [...this.selectedSelection, value]
            }
        },

        canAddCount(count) {
            if (! this.maxItems) {
                return true
            }

            return (this.state?.length ?? 0) + count <= this.maxItems
        },

        moveToSelected(values) {
            if (this.disabled || values.length === 0) {
                return
            }

            const movable = values.filter((value) => {
                const option = this.optionMap()[value]

                return option && ! option.disabled && ! (this.state ?? []).includes(value)
            })

            if (movable.length === 0) {
                return
            }

            if (! this.canAddCount(movable.length)) {
                return
            }

            this.state = [...(this.state ?? []), ...movable]
            this.availableSelection = []
        },

        moveToAvailable(values) {
            if (this.disabled || values.length === 0) {
                return
            }

            const removable = new Set(values.filter((value) => ! this.isOptionDisabled(value)))

            if (removable.size === 0) {
                return
            }

            this.state = (this.state ?? []).filter((value) => ! removable.has(value))
            this.selectedSelection = []
        },

        moveSelectionToSelected() {
            this.moveToSelected(this.availableSelection)
        },

        moveSelectionToAvailable() {
            this.moveToAvailable(this.selectedSelection)
        },

        moveAllToSelected() {
            const values = this.availableItems
                .filter((item) => ! item.disabled)
                .map((item) => item.value)
            const allowed = this.maxItems
                ? values.slice(0, Math.max(0, this.maxItems - (this.state?.length ?? 0)))
                : values

            this.moveToSelected(allowed)
        },

        moveAllToAvailable() {
            this.moveToAvailable([...(this.state ?? [])])
        },

        swapLists() {
            if (this.disabled) {
                return
            }

            const selected = new Set(this.state ?? [])
            const lockedSelected = (this.state ?? []).filter((value) => this.isOptionDisabled(value))
            const selectable = this.options.filter((option) => ! option.disabled)
            let newSelected = selectable
                .map((option) => option.value)
                .filter((value) => ! selected.has(value))

            if (this.maxItems) {
                newSelected = newSelected.slice(0, Math.max(0, this.maxItems - lockedSelected.length))
            }

            this.state = [...lockedSelected, ...newSelected]
            this.availableSelection = []
            this.selectedSelection = []
        },

        canSwapLists() {
            if (this.disabled) {
                return false
            }

            const selectable = this.options.filter((option) => ! option.disabled)

            if (selectable.length === 0) {
                return false
            }

            const selected = new Set(this.state ?? [])
            const hasAvailable = selectable.some((option) => ! selected.has(option.value))
            const hasSelected = (this.state?.length ?? 0) > 0

            return hasAvailable || hasSelected
        },

        handleAvailableDoubleClick(value) {
            if (! this.moveOnDoubleClick || this.disabled) {
                return
            }

            this.moveToSelected([value])
        },

        handleSelectedDoubleClick(value) {
            if (! this.moveOnDoubleClick || this.disabled) {
                return
            }

            this.moveToAvailable([value])
        },

        moveSelectedUp(value) {
            if (! this.reorderable || this.disabled) {
                return
            }

            const index = (this.state ?? []).indexOf(value)

            if (index <= 0) {
                return
            }

            const next = [...this.state]
            ;[next[index - 1], next[index]] = [next[index], next[index - 1]]
            this.state = next
        },

        moveSelectedDown(value) {
            if (! this.reorderable || this.disabled) {
                return
            }

            const index = (this.state ?? []).indexOf(value)

            if (index === -1 || index >= (this.state?.length ?? 0) - 1) {
                return
            }

            const next = [...this.state]
            ;[next[index + 1], next[index]] = [next[index], next[index + 1]]
            this.state = next
        },

        canReorderSelected() {
            return this.reorderable && ! this.disabled
        },

        startSelectedDrag(value, event) {
            if (! this.canReorderSelected() || ! event?.dataTransfer) {
                return
            }

            this.draggingSelectedValue = value
            this.draggingSelectedValues = this.selectedSelection.includes(value) && this.selectedSelection.length > 1
                ? this.selectedSelection.filter((item) => (this.state ?? []).includes(item))
                : [value]
            event.dataTransfer.effectAllowed = 'move'
            event.dataTransfer.setData('text/plain', String(value))
        },

        markSelectedDropTarget(value) {
            if (! this.canReorderSelected() || this.draggingSelectedValue === null) {
                return
            }

            this.dropPane = 'selected'
            this.dropTargetSelectedValue = value
            this.dropAfter = false
        },

        dropSelectedAt(value, event) {
            event?.preventDefault()

            if (! this.canReorderSelected() || this.draggingSelectedValue === null) {
                this.clearSelectedDrag()

                return
            }

            this.reorderSelectedTo(this.draggingSelectedValue, value)
            this.clearSelectedDrag()
        },

        reorderSelectedTo(fromValue, toValue) {
            const moving = (this.draggingSelectedValues?.length
                ? this.draggingSelectedValues
                : [fromValue]
            ).filter((value) => (this.state ?? []).includes(value))

            if (moving.length === 0) {
                return
            }

            if (moving.length === 1) {
                this.reorderSingleSelectedTo(moving[0], toValue)

                return
            }

            this.reorderSelectedGroupTo(moving, toValue)
        },

        insertSelectedBlock(moving, targetValue, after = false) {
            const movingSet = new Set(moving)
            const next = [...(this.state ?? [])]
            const orderedMoving = next.filter((value) => movingSet.has(value))
            const remaining = next.filter((value) => ! movingSet.has(value))

            if (orderedMoving.length === 0) {
                return
            }

            if (targetValue === null || ! remaining.includes(targetValue)) {
                this.state = [...remaining, ...orderedMoving]

                return
            }

            let insertAt = remaining.indexOf(targetValue)

            if (after) {
                insertAt += 1
            }

            remaining.splice(insertAt, 0, ...orderedMoving)
            this.state = remaining
        },

        reorderSingleSelectedTo(fromValue, toValue) {
            if (fromValue === toValue) {
                return
            }

            const next = [...(this.state ?? [])]
            const fromIndex = next.indexOf(fromValue)
            const toIndex = next.indexOf(toValue)

            if (fromIndex === -1 || toIndex === -1) {
                return
            }

            next.splice(fromIndex, 1)
            next.splice(toIndex, 0, fromValue)
            this.state = next
        },

        reorderSelectedGroupTo(moving, toValue) {
            const movingSet = new Set(moving)

            if (movingSet.has(toValue)) {
                return
            }

            const next = [...(this.state ?? [])]
            const orderedMoving = next.filter((value) => movingSet.has(value))
            const remaining = next.filter((value) => ! movingSet.has(value))
            const originalFrom = next.findIndex((value) => movingSet.has(value))
            const originalTo = next.indexOf(toValue)
            let insertAt = remaining.indexOf(toValue)

            if (orderedMoving.length === 0 || originalTo === -1 || insertAt === -1) {
                return
            }

            if (originalFrom < originalTo) {
                insertAt += 1
            }

            remaining.splice(insertAt, 0, ...orderedMoving)
            this.state = remaining
        },

        clearSelectedDrag() {
            this.draggingSelectedValue = null
            this.dropTargetSelectedValue = null
            this.draggingSelectedValues = []
            this.dropPane = null
            this.dropAfter = false
        },

        clearPointerDrag() {
            this.pointerDragActive = false
            this.availableSelection = []
            this.selectedSelection = []
            this.clearSelectedDrag()
        },

        canMoveAllToSelected() {
            return this.availableItems.length > 0 && this.canAddCount(1)
        },
    }
}
