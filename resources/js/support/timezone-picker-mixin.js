import { createSearchableSelectMenuMixin } from '../core/searchable-select-menu.js'
import { createOverlayMenuKeyboardMixin } from '../core/overlay-menu-keyboard.js'
import { normalizeSearchQuery } from '../core/search-normalize.js'
import {
    computeFffVirtualWindow,
    SIBLING_VIRTUALIZE_THRESHOLD,
} from '../core/fff-virtual-adapter.js'

export const FFF_TIMEZONE_VIRTUAL_THRESHOLD = SIBLING_VIRTUALIZE_THRESHOLD
export const FFF_TIMEZONE_ROW_HEIGHT = 40
export const FFF_TIMEZONE_OVERSCAN = 6

/**
 * @param {{
 *   triggerRef?: string,
 *   menuRef?: string,
 *   ownerIdPrefix?: string,
 * }} [options]
 */
export function createTimezonePickerMixin(options = {}) {
    const {
        triggerRef = 'timezoneTrigger',
        menuRef = 'timezoneMenu',
        ownerIdPrefix = 'fff-timezone-field',
    } = options

    const selectMenu = createSearchableSelectMenuMixin({
        triggerRef,
        menuRef,
        ownerIdPrefix,
        onMenuClose() {
            this.virtualScrollTop = 0
            this.timezoneSearch = ''
        },
    })

    const timezoneKeyboard = createOverlayMenuKeyboardMixin({
        openKey: 'menuOpen',
        resultsKey: 'filteredTimezones',
        scrollRef: null,
        menuRef,
        searchRef: 'timezoneSearch',
        searchEnabledKey: 'searchable',
        itemHeight: FFF_TIMEZONE_ROW_HEIGHT,
        selectMethod: 'selectTimezone',
        optionIdPrefix: `${ownerIdPrefix}-option`,
        onEscape: 'closeTimezoneMenu',
        getItemValue: (item) => item?.id ?? item,
        isItemSelected: (component, item) => component.resolveTimezoneValue?.() === item?.id,
    })

    return {
        displayReady: false,
        menuOpen: false,
        timezoneSearch: '',
        menuReady: false,
        menuScrollHandler: null,
        menuResizeHandler: null,
        virtualScrollTop: 0,
        virtualViewportHeight: 320,
        overlayMenuActiveIndex: -1,
        ...selectMenu,
        ...timezoneKeyboard,

        initTimezonePicker() {
            this.initOverlayMenuKeyboard()

            this.$nextTick(() => {
                this.finalizeTimezoneTriggerHandoff()
            })

            this.bindSelectMenuLifecycle()
        },

        /**
         * Keep SSR as the visible trigger when the blocking catalog boot already
         * painted the official label. Never flip displayReady in that case —
         * that swap was CET (Intl) → catalog city after x-load.
         *
         * @param {number} [attempt]
         */
        finalizeTimezoneTriggerHandoff(attempt = 0) {
            if (this.browserTimezoneDefault && ! this.isTimezoneLocked) {
                const detected = this.$el?.dataset?.fffDetectedTimezone

                if (! this.state && detected) {
                    this.state = detected
                }

                const ssr = this.$el?.querySelector?.('.fff-timezone-field__ssr-label')
                const official = this.selectedTimezone?.label
                const ssrText = ssr?.textContent?.trim() ?? ''
                const ssrIsPlaceholder = Boolean(ssr?.classList?.contains?.('is-placeholder'))

                if (official && ssrText === String(official).trim()) {
                    return
                }

                if (official && (ssrIsPlaceholder || ssrText === '')) {
                    this.syncTriggerSsrFromSelection?.()

                    return
                }

                if (official) {
                    return
                }

                if (attempt < 8) {
                    this.$nextTick(() => {
                        this.finalizeTimezoneTriggerHandoff(attempt + 1)
                    })

                    return
                }
            }

            this.displayReady = true
        },

        resolveTimezoneValue() {
            if (typeof this.getTimezoneValue === 'function') {
                return this.getTimezoneValue()
            }

            return this.state ?? this.defaultTimezone
        },

        assignTimezoneValue(id) {
            if (typeof this.setTimezoneValue === 'function') {
                this.setTimezoneValue(id)

                return
            }

            this.state = id
        },

        get isTimezoneLocked() {
            if (typeof this.isTimezonePickerLocked === 'function') {
                return this.isTimezonePickerLocked()
            }

            return this.disabled || this.readOnly
        },

        get usesVirtualScroll() {
            return this.filteredTimezones.length > (this.virtualScrollThreshold ?? FFF_TIMEZONE_VIRTUAL_THRESHOLD)
        },

        resolveTimezoneVirtualWindow() {
            return computeFffVirtualWindow({
                count: this.filteredTimezones.length,
                scrollTop: this.virtualScrollTop,
                viewportHeight: this.virtualViewportHeight || 320,
                estimateSize: () => FFF_TIMEZONE_ROW_HEIGHT,
                overscan: FFF_TIMEZONE_OVERSCAN,
            })
        },

        get visibleTimezones() {
            if (! this.usesVirtualScroll) {
                return this.filteredTimezones
            }

            const windowed = this.resolveTimezoneVirtualWindow()

            return this.filteredTimezones.slice(windowed.startIndex, windowed.endIndex)
        },

        get virtualSpacerTop() {
            if (! this.usesVirtualScroll) {
                return 0
            }

            return this.resolveTimezoneVirtualWindow().paddingTop
        },

        get virtualSpacerBottom() {
            if (! this.usesVirtualScroll) {
                return 0
            }

            return this.resolveTimezoneVirtualWindow().paddingBottom
        },

        get selectedTimezone() {
            const timezoneId = this.resolveTimezoneValue()

            if (! timezoneId) {
                return null
            }

            return this.timezones.find((timezone) => timezone.id === timezoneId)
                ?? (this.selectedTimezoneSeed?.id === timezoneId ? this.selectedTimezoneSeed : null)
                ?? this.timezones[0]
                ?? null
        },

        get isTimezoneEmpty() {
            return ! this.resolveTimezoneValue()
        },

        get filteredTimezones() {
            const timezones = this.timezones ?? []
            const query = normalizeSearchQuery(this.timezoneSearch)

            if (! query) {
                return timezones
            }

            return timezones.filter((timezone) => {
                return normalizeSearchQuery(timezone.label).includes(query)
                    || normalizeSearchQuery(timezone.id).includes(query)
                    || normalizeSearchQuery(timezone.region).includes(query)
                    || normalizeSearchQuery(timezone.offset).includes(query)
            })
        },

        onTimezoneListScroll(event) {
            this.virtualScrollTop = event.target.scrollTop
            this.virtualViewportHeight = event.target.clientHeight || this.virtualViewportHeight || 320
        },

        onOverlaySheetGeometry() {
            const menu = this.$refs?.[menuRef]
            const list = menu?.querySelector?.('.fff-timezone-field__list, [data-fff-overlay-scroll]')
                ?? this.$el?.querySelector?.('.fff-timezone-field__list')

            if (! list) {
                return
            }

            this.virtualViewportHeight = list.clientHeight || this.virtualViewportHeight || 320
        },

        selectTimezone(id) {
            if (this.isTimezoneLocked) {
                return
            }

            this.assignTimezoneValue(id)
            this.syncTriggerSsrFromSelection?.()
            this.closeMenu()
        },

        toggleTimezoneMenu() {
            if (this.isTimezoneLocked) {
                return
            }

            if (this.menuOpen) {
                this.closeTeleportedMenu()

                return
            }

            this.menuOpen = true

            if (this.searchable) {
                this.$nextTick(() => {
                    this.$refs.timezoneSearch?.focus()
                })
            }
        },

        closeTimezoneMenu() {
            this.closeTeleportedMenu()
        },
    }
}
