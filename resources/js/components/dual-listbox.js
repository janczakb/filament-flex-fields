export const FFF_DUAL_LISTBOX_VIRTUAL_THRESHOLD = 100
export const FFF_DUAL_LISTBOX_ROW_HEIGHT = 44
export const FFF_DUAL_LISTBOX_OVERSCAN = 6

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

        init() {
            this.ensureState()

            this.$watch('state', () => {
                this.ensureState()
            })
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

            const needle = query.trim().toLowerCase()

            if (! needle) {
                return true
            }

            return (
                option.label.toLowerCase().includes(needle) ||
                (option.description ?? '').toLowerCase().includes(needle)
            )
        },

        get availableItems() {
            const selected = new Set(this.state ?? [])

            return this.options.filter(
                (option) =>
                    ! selected.has(option.value) &&
                    ! option.disabled &&
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
        },

        onSelectedListScroll(event) {
            this.selectedScrollTop = event.target.scrollTop
            this.selectedViewportHeight = event.target.clientHeight
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

        toggleAvailableSelection(value, event) {
            if (this.disabled) {
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
            if (this.disabled) {
                return
            }

            this.availableSelection = []
            this.selectedSelection = this.resolveSelection(
                this.selectedSelection,
                (this.state ?? []).filter((value) => this.optionMap()[value]),
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

            const removable = new Set(values)
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
            const values = this.availableItems.map((item) => item.value)
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
            const selectable = this.options.filter((option) => ! option.disabled)
            let newSelected = selectable
                .map((option) => option.value)
                .filter((value) => ! selected.has(value))

            if (this.maxItems) {
                newSelected = newSelected.slice(0, this.maxItems)
            }

            this.state = newSelected
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

        canMoveAllToSelected() {
            return this.availableItems.length > 0 && this.canAddCount(1)
        },
    }
}
