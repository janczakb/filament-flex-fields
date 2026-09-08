import { resolveOverlayMode } from '../../core/overlay-mode.js'
import {
    dropdownOptionsScroller,
    syncDropdownScrollbarInset,
} from './headless-combobox-scroll-virt.js'

/** Fixed desktop width for optionLayout('grid') theme-style pickers. */
export const GRID_DROPDOWN_WIDTH_PX = 400

export function createHeadlessComboboxMenuPositionMixin({ selectMenu } = {}) {
    return {
        resolveDropdownAlign() {
            return this.dropdownAlign === 'end' ? 'end' : 'start'
        },

        resolveMatchTriggerWidth() {
            // Grid pickers use a fixed desktop panel — matching the trigger causes a
            // full-width flash before applyGridDropdownWidth re-pins to 400px.
            if (this.isGridLayout) {
                return false
            }

            return this.matchTriggerWidth !== false
        },

        resolveMenuMinWidth() {
            if (this.isGridLayout) {
                return GRID_DROPDOWN_WIDTH_PX
            }

            return 288
        },

        afterOverlayPanelOpened(overlayMode) {
            if (overlayMode === 'sheet' || ! this.isGridLayout) {
                return
            }

            this.applyGridDropdownWidth()
        },

        resolveMenuTriggerElement() {
            // Match the visible field track (includes suffix chevron), not the
            // shrink-wrapped multi button/chips — otherwise the teleported menu
            // stays ~content-width (Entity mentions).
            const fieldTrack = this.$el?.closest?.('.fi-input-wrp.fff-select-field')
                ?? this.$el?.closest?.('.fi-input-wrp')
                ?? this.$el?.closest?.('.fff-select-field__shell')

            if (fieldTrack) {
                return fieldTrack
            }

            const button = this.$refs?.headlessTrigger

            if (! button) {
                return this.$refs?.headlessTriggerCtn ?? null
            }

            if (this.multiple) {
                return this.$refs?.headlessTriggerCtn
                    ?? button.closest('.fi-select-input-ctn:not(.fff-select-trigger-ssr)')
                    ?? button
            }

            return button
        },

        hasHeadlessMenuBeenPositioned() {
            return this.resolveMenuElement()?.__fffHasBeenPositioned === true
        },


        isSheetPresentation() {
            // While the exit slide runs, keep sheet chrome even if the viewport
            // already crossed back to desktop.
            if (this.__fffSheetClosing) {
                return true
            }

            if (! this.comboboxOpen) {
                return false
            }

            // Live breakpoint wins over stale open-time mode / leftover sheet classes.
            // Otherwise resize mobile→desktop keeps left:0 via applySheetDropdownWidth().
            return resolveOverlayMode(window) === 'sheet'
        },

        applySheetDropdownWidth() {
            const menu = this.resolveMenuElement()

            if (! menu) {
                return
            }

            menu.classList.remove('fi-width-none')
            menu.style.setProperty('width', '100%', 'important')
            menu.style.setProperty('min-width', '0', 'important')
            menu.style.setProperty('max-width', 'none', 'important')
            menu.style.left = '0'
            menu.style.right = '0'
            menu.style.insetInline = '0'
            menu.style.bottom = '0'
            menu.style.top = 'auto'
        },

        applyHeadlessDropdownWidthLayout() {
            if (this.isGridLayout) {
                this.applyGridDropdownWidth()

                return
            }

            if (this.useRichListDropdownLayout) {
                this.applyRichListDropdownWidth()

                return
            }

            this.applyPlainListDropdownWidth()
        },

        updateMenuPosition({ reveal = false } = {}) {
            const menu = this.resolveMenuElement()
            const trigger = this.resolveMenuTriggerElement()

            if (! menu || ! trigger) {
                return
            }

            // Never reflow width/anchor while the sheet exit animation is playing —
            // panel-width math would shrink the drawer mid-slide.
            if (this.__fffSheetClosing) {
                this.applySheetDropdownWidth()

                return
            }

            // Finalize width before anchoring, then re-anchor once sized (fixed pins otherwise overwritten).
            this.applyHeadlessDropdownWidthLayout()
            selectMenu.updateMenuPosition.call(this, { reveal })

            if (this.isSheetPresentation()) {
                return
            }

            if (this.isGridLayout) {
                this.applyGridDropdownWidth()

                return
            }

            if (this.canOptionLabelsWrap === false) {
                this.applyPlainListDropdownWidth()
            }
        },

        teardownHeadlessMenuPosition() {
            if (! this.hasHeadlessMenuBeenPositioned()) {
                this.menuReady = false
            }

            this.unbindDropdownScrollFadeObserver()
            this.unbindHeadlessMenuPositionObservers()
            this.unbindMenuListeners()

            const menu = this.resolveMenuElement()

            if (! menu) {
                return
            }

            menu.classList.remove('is-open', 'is-closing')

            if (! menu.__fffHasBeenPositioned) {
                menu.classList.remove('is-positioned')
            }
        },

        applyRichListDropdownWidth() {
            if (this.isSheetPresentation()) {
                this.applySheetDropdownWidth()

                return
            }

            if (this.canOptionLabelsWrap === false) {
                this.applyPlainListDropdownWidth()

                return
            }

            const menu = this.resolveMenuElement()
            const trigger = this.resolveMenuTriggerElement()

            if (! menu || ! trigger) {
                return
            }

            const buttonWidth = trigger.offsetWidth

            menu.style.width = `${buttonWidth}px`
            menu.style.minWidth = `${buttonWidth}px`
            menu.style.maxWidth = `min(${buttonWidth}px, calc(100vw - 2rem))`
            menu.style.overflowX = 'visible'
        },

        applyPlainListDropdownWidth() {
            if (this.isSheetPresentation()) {
                this.applySheetDropdownWidth()

                return
            }

            const menu = this.resolveMenuElement()
            const trigger = this.resolveMenuTriggerElement()

            if (! menu || ! trigger) {
                return
            }

            const buttonWidth = trigger.offsetWidth

            // wrapOptionLabels(false): dropdown matches trigger; long labels clamp in CSS.
            if (this.canOptionLabelsWrap === false) {
                const width = `${buttonWidth}px`

                menu.style.setProperty('width', width, 'important')
                menu.style.setProperty('min-width', width, 'important')
                menu.style.setProperty('max-width', width, 'important')
                menu.style.setProperty('overflow-x', 'hidden', 'important')

                return
            }

            const viewportCap = Math.max(buttonWidth, window.innerWidth - 32)
            const contentFloor = this.isUserSelectField ? 320 : buttonWidth

            const minWidth = Math.max(buttonWidth, contentFloor)

            menu.style.minWidth = `${minWidth}px`
            menu.style.maxWidth = `${viewportCap}px`

            if (! this.menuReady) {
                menu.style.width = `${minWidth}px`

                return
            }

            menu.style.width = 'max-content'

            const measuredWidth = Math.ceil(menu.scrollWidth)
            const targetWidth = Math.min(
                Math.max(buttonWidth, measuredWidth, contentFloor),
                viewportCap,
            )

            menu.style.width = `${targetWidth}px`
        },

        applyGridDropdownWidth() {
            if (this.isSheetPresentation()) {
                this.applySheetDropdownWidth()

                return
            }

            const menu = this.resolveMenuElement()

            if (! menu) {
                return
            }

            menu.classList.add('fi-width-none')
            menu.style.setProperty('width', `${GRID_DROPDOWN_WIDTH_PX}px`, 'important')
            menu.style.setProperty('max-width', `min(${GRID_DROPDOWN_WIDTH_PX}px, calc(100vw - 2rem))`, 'important')
            menu.style.setProperty('min-width', `${GRID_DROPDOWN_WIDTH_PX}px`, 'important')
        },

        scheduleMenuPositionAfterLayout() {
            if (! this.comboboxOpen || this.__fffSheetClosing) {
                return
            }

            const menu = this.resolveMenuElement()

            if (menu?.dataset?.fffSheetEntering === 'true') {
                return
            }

            if (this.__fffHeadlessMenuPositionRaf) {
                return
            }

            this.__fffHeadlessMenuPositionRaf = requestAnimationFrame(() => {
                this.__fffHeadlessMenuPositionRaf = requestAnimationFrame(() => {
                    this.__fffHeadlessMenuPositionRaf = 0

                    if (this.comboboxOpen && ! this.__fffSheetClosing) {
                        this.updateMenuPosition({ reveal: false })
                    }
                })
            })
        },

        bindHeadlessMenuPositionObservers() {
            this.unbindHeadlessMenuPositionObservers()

            if (typeof ResizeObserver === 'undefined') {
                return
            }

            const trigger = this.$refs.headlessTrigger
            const anchor = this.resolveMenuTriggerElement()

            if (! trigger && ! anchor) {
                return
            }

            this.menuTriggerResizeObserver = new ResizeObserver(() => {
                this.scheduleMenuPositionAfterLayout()
            })

            if (anchor) {
                this.menuTriggerResizeObserver.observe(anchor)
            }

            if (trigger && trigger !== anchor) {
                this.menuTriggerResizeObserver.observe(trigger)
            }
        },

        unbindHeadlessMenuPositionObservers() {
            if (this.__fffHeadlessMenuPositionRaf) {
                cancelAnimationFrame(this.__fffHeadlessMenuPositionRaf)
                this.__fffHeadlessMenuPositionRaf = 0
            }

            this.menuTriggerResizeObserver?.disconnect()
            this.menuTriggerResizeObserver = null
        },

        syncDropdownOverflowChrome() {
            syncDropdownScrollbarInset(this.resolveMenuElement())
        },

        bindDropdownScrollFadeObserver() {
            this.unbindDropdownScrollFadeObserver()
            syncDropdownScrollbarInset(this.resolveMenuElement())

            const list = this.$refs.headlessOptionsList ?? dropdownOptionsScroller(this.resolveMenuElement())

            if (! list || typeof ResizeObserver === 'undefined') {
                return
            }

            this.menuOptionsResizeObserver = new ResizeObserver(() => {
                syncDropdownScrollbarInset(this.resolveMenuElement())

                // Sheet peek ↔ expanded (and keyboard inset) change list clientHeight
                // without a scroll event — remount the TanStack window immediately.
                if (typeof this.shouldVirtualizeDropdown === 'function' && this.shouldVirtualizeDropdown()) {
                    const nextHeight = list.clientHeight || 0

                    if (nextHeight > 0 && nextHeight !== this._virtualViewportHeight) {
                        this._virtualViewportHeight = nextHeight
                        this._virtualWindowCache = null
                        this.virtualScrollTick = (this.virtualScrollTick ?? 0) + 1
                        this.$nextTick?.(() => this.bindVirtualRowMeasurements?.())
                    }
                }
            })
            this.menuOptionsResizeObserver.observe(list)

            if (list.firstElementChild) {
                this.menuOptionsResizeObserver.observe(list.firstElementChild)
            }
        },

        unbindDropdownScrollFadeObserver() {
            this.menuOptionsResizeObserver?.disconnect()
            this.menuOptionsResizeObserver = null
        }
    }
}
