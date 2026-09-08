import {
    buildHeadlessDropdownRows,
    flattenHeadlessDropdownRowsForVirtualization,
    headlessOptionValue,
} from './headless-select-options.js'
import { DEFAULT_VIRTUALIZE_THRESHOLD, DEFAULT_VIRTUAL_WINDOW_SIZE } from '../../core/combobox-engine.js'
import { computeFffVirtualWindow } from '../../core/fff-virtual-adapter.js'
import { updateVerticalScrollFade } from '../../core/vertical-scroll-fade.js'
import { bindOverlayScrollbar, syncOverlayScrollbar } from '../../core/overlay-scrollbar.js'

/**
 * Extra rows above/below the viewport. Fast Chrome flings outrun overscan:2
 * and briefly show empty spacer (panel background) at the leading edge.
 */
export const HEADLESS_SELECT_VIRTUAL_OVERSCAN = 10

function dropdownOptionsScroller(menu) {
    return menu?.querySelector?.('.fi-select-input-options-ctn')
        ?? menu?.querySelector?.('.fi-dropdown-list')
        ?? null
}

function syncDropdownScrollbarInset(menu) {
    if (! menu) {
        return
    }

    const list = dropdownOptionsScroller(menu)

    if (! list) {
        menu.classList.remove('fff-select-dropdown-panel--scrollable')

        return
    }

    menu.classList.toggle(
        'fff-select-dropdown-panel--scrollable',
        list.scrollHeight > list.clientHeight + 1,
    )
    const thumb = menu.querySelector('.fff-select-dropdown-scrollbar__thumb')
    bindOverlayScrollbar(list, thumb?.parentElement, thumb)
    syncOverlayScrollbar(list, thumb)
    updateVerticalScrollFade(list)
}


export function createHeadlessComboboxScrollVirtMixin() {
    return {
        _virtualMeasuredHeights: null,
        _virtualRowObserver: null,
        _virtualMeasureFrame: null,

        shouldVirtualizeDropdown() {
            if (this.isGridLayout) {
                return false
            }

            return this.countVirtualizableDropdownRows() >= (this.virtualizeThreshold ?? DEFAULT_VIRTUALIZE_THRESHOLD)
        },

        shouldMeasureVirtualRowHeights() {
            return this.shouldVirtualizeDropdown()
        },

        countVirtualizableDropdownRows() {
            if (this.smartSuggestEnabled && this._engine && ! this.hasDynamicSearchResults) {
                return this.getEngineOptions().length
            }

            const rows = buildHeadlessDropdownRows(this.comboboxFilteredOptionTree(), {
                multiple: this.multiple,
                keepSelectedOptionsInDropdown: this.keepSelectedOptionsInDropdown,
                isOptionSelected: (value) => this.isOptionSelected(value),
                withSeparators: this.optionGroupSeparators !== false,
            })

            return flattenHeadlessDropdownRowsForVirtualization(rows).length
        },

        rebuildVirtualFlatRows() {
            const rows = this.buildHeadlessDropdownRowsForDisplay()

            this._virtualFlatRows = flattenHeadlessDropdownRowsForVirtualization(rows)
            this._virtualWindowCache = null

            return this._virtualFlatRows
        },

        resolveVirtualRowHeight(flatRow) {
            const key = flatRow?.key
            const measured = key != null
                ? this._virtualMeasuredHeights?.get?.(String(key))
                : null

            if (measured && measured > 0) {
                return measured
            }

            return flatRow?.height ?? (this.virtualRowHeight ?? 36)
        },

        resolveVirtualWindowForFlatRows(flatRows, scrollTop = this._virtualScrollTop ?? 0) {
            return computeFffVirtualWindow({
                count: flatRows.length,
                scrollTop,
                viewportHeight: this._virtualViewportHeight
                    ?? ((this.virtualWindowSize ?? DEFAULT_VIRTUAL_WINDOW_SIZE) * (this.virtualRowHeight ?? 36)),
                estimateSize: (index) => this.resolveVirtualRowHeight(flatRows[index]),
                overscan: HEADLESS_SELECT_VIRTUAL_OVERSCAN,
            })
        },

        bindVirtualRowMeasurements() {
            if (! this.shouldMeasureVirtualRowHeights() || typeof ResizeObserver === 'undefined') {
                return
            }

            const root = this.$refs?.headlessOptionsList
                ?? dropdownOptionsScroller(this.resolveMenuElement?.())

            if (! root) {
                return
            }

            if (! this._virtualMeasuredHeights) {
                this._virtualMeasuredHeights = new Map()
            }

            if (! this._virtualRowObserver) {
                this._virtualRowObserver = new ResizeObserver((entries) => {
                    let changed = false

                    for (const entry of entries) {
                        const node = entry.target
                        const key = node?.getAttribute?.('data-fff-virt-key')

                        if (! key) {
                            continue
                        }

                        const height = Math.ceil(entry.borderBoxSize?.[0]?.blockSize
                            ?? entry.contentRect?.height
                            ?? node.offsetHeight
                            ?? 0)

                        if (height <= 0) {
                            continue
                        }

                        const previous = this._virtualMeasuredHeights.get(key)

                        if (previous !== height) {
                            this._virtualMeasuredHeights.set(key, height)
                            changed = true
                        }
                    }

                    if (! changed) {
                        return
                    }

                    if (this._virtualMeasureFrame) {
                        return
                    }

                    this._virtualMeasureFrame = requestAnimationFrame(() => {
                        this._virtualMeasureFrame = null
                        this._virtualWindowCache = null
                        this.virtualScrollTick = (this.virtualScrollTick ?? 0) + 1
                    })
                })
            }

            const rows = root.querySelectorAll?.('[data-fff-virt-key]')

            if (! rows?.length) {
                return
            }

            rows.forEach((node) => {
                this._virtualRowObserver.observe(node)
            })
        },

        unbindVirtualRowMeasurements() {
            if (this._virtualMeasureFrame) {
                cancelAnimationFrame(this._virtualMeasureFrame)
                this._virtualMeasureFrame = null
            }

            this._virtualRowObserver?.disconnect?.()
            this._virtualRowObserver = null
        },

        buildHeadlessDropdownRowsForDisplay() {
            if (this.smartSuggestEnabled && this._engine && ! this.shouldVirtualizeDropdown()) {
                return this._engine.smartSections().flatMap((section) => {
                    /** @type {Array<{ type: string, key: string, label?: string, value?: string, option?: unknown }>} */
                    const rows = []

                    if (section.type === 'create') {
                        rows.push({
                            type: 'create',
                            key: `create-${section.value}`,
                            label: section.label,
                            value: section.value,
                        })

                        return rows
                    }

                    if (section.label && section.type !== 'options') {
                        rows.push({
                            type: 'section',
                            key: `section-${section.type}`,
                            label: section.label,
                        })
                    }

                    for (const option of section.options ?? []) {
                        const value = String(headlessOptionValue(option))

                        rows.push({
                            type: 'option',
                            option,
                            // Prefix by section so Alpine x-for keys stay unique when the
                            // same value appears in Recent/Suggested and the main list.
                            key: `${section.type}:${value}`,
                        })
                    }

                    return rows
                })
            }

            return buildHeadlessDropdownRows(this.comboboxFilteredOptionTree(), {
                multiple: this.multiple,
                keepSelectedOptionsInDropdown: this.keepSelectedOptionsInDropdown,
                isOptionSelected: (value) => this.isOptionSelected(value),
                withSeparators: this.optionGroupSeparators !== false,
            })
        },

        comboboxVirtualListStyle() {
            void this.virtualScrollTick

            if (! this.shouldVirtualizeDropdown()) {
                return {}
            }

            const flatRows = this._virtualFlatRows.length > 0
                ? this._virtualFlatRows
                : this.rebuildVirtualFlatRows()
            const windowed = this.resolveVirtualWindowForFlatRows(
                flatRows,
                this._virtualScrollTop ?? 0,
            )

            return {
                paddingTop: `${windowed.paddingTop}px`,
                paddingBottom: `${windowed.paddingBottom}px`,
            }
        },

        onHeadlessOptionsScroll(event) {
            syncDropdownScrollbarInset(this.resolveMenuElement())

            if (! this.shouldVirtualizeDropdown()) {
                if (this.hasPaginatedSearchResults) {
                    this.observeHeadlessLoadMore?.()
                }

                return
            }

            if (this._virtualFlatRows.length === 0) {
                this.rebuildVirtualFlatRows()
            }

            const scrollTop = event?.target?.scrollTop ?? 0
            this._virtualScrollTop = scrollTop
            this._virtualViewportHeight = event?.target?.clientHeight
                ?? this._virtualViewportHeight
                ?? 280

            const windowed = this.resolveVirtualWindowForFlatRows(this._virtualFlatRows, scrollTop)
            this.virtualRowWindowStart = windowed.startIndex
            this.virtualScrollTick = (this.virtualScrollTick ?? 0) + 1

            this.$nextTick?.(() => this.bindVirtualRowMeasurements())

            if (this.hasPaginatedSearchResults) {
                this.observeHeadlessLoadMore?.()
            }
        },

        comboboxFilteredDropdownRowsWithoutSections() {
            void this.comboboxQuery
            void this.virtualScrollTick
            void this.options

            let rows = this.buildHeadlessDropdownRowsForDisplay()

            if (this.isGridLayout) {
                rows = rows.flatMap((row) => {
                    if (row.type === 'group') {
                        return (row.options ?? []).map((option) => ({
                            type: 'option',
                            option,
                            key: headlessOptionValue(option),
                        }))
                    }

                    if (row.type === 'separator') {
                        return []
                    }

                    return [row]
                })
            }

            // Always flatten nested groups so the Blade loop renders one concrete
            // row type per iteration (no nested option x-for / group wrappers).
            const flatRows = flattenHeadlessDropdownRowsForVirtualization(rows)
            this._virtualFlatRows = flatRows

            if (this.shouldVirtualizeDropdown()) {
                const windowed = this.resolveVirtualWindowForFlatRows(
                    flatRows,
                    this._virtualScrollTop ?? 0,
                )

                this.$nextTick?.(() => this.bindVirtualRowMeasurements())

                return windowed.indexes.map((index) => flatRows[index]).filter(Boolean)
            }

            return flatRows
        },

        comboboxFilteredDropdownRows() {
            if (this.comboboxEntityMentionActive?.()) {
                const rows = this.comboboxFilteredDropdownRowsWithoutSections()

                return [
                    {
                        type: 'section',
                        key: 'entity-mentions',
                        label: `${this.entityMentionSectionLabel} (${this.mentionTrigger}${this.comboboxEntityMentionState().term})`,
                    },
                    ...rows,
                ]
            }

            return this.comboboxFilteredDropdownRowsWithoutSections()
        },
    }
}

export { dropdownOptionsScroller, syncDropdownScrollbarInset }
