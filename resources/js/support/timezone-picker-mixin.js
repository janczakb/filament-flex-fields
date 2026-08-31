import { createSearchableSelectMenuMixin } from '../core/searchable-select-menu.js'
import { createOverlayMenuKeyboardMixin } from '../core/overlay-menu-keyboard.js'
import { normalizeSearchQuery } from '../core/search-normalize.js'

export const FFF_TIMEZONE_VIRTUAL_THRESHOLD = 50
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
        overlayMenuActiveIndex: -1,
        ...selectMenu,
        ...timezoneKeyboard,

        initTimezonePicker() {
            this.initOverlayMenuKeyboard()

            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    this.displayReady = true
                })
            })

            this.bindSelectMenuLifecycle()
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

        get visibleTimezones() {
            if (! this.usesVirtualScroll) {
                return this.filteredTimezones
            }

            const startIndex = Math.max(0, Math.floor(this.virtualScrollTop / FFF_TIMEZONE_ROW_HEIGHT) - FFF_TIMEZONE_OVERSCAN)
            const viewportHeight = 320
            const visibleCount = Math.ceil(viewportHeight / FFF_TIMEZONE_ROW_HEIGHT) + (FFF_TIMEZONE_OVERSCAN * 2)
            const endIndex = Math.min(this.filteredTimezones.length, startIndex + visibleCount)

            return this.filteredTimezones.slice(startIndex, endIndex)
        },

        get virtualSpacerTop() {
            if (! this.usesVirtualScroll) {
                return 0
            }

            const startIndex = Math.max(0, Math.floor(this.virtualScrollTop / FFF_TIMEZONE_ROW_HEIGHT) - FFF_TIMEZONE_OVERSCAN)

            return startIndex * FFF_TIMEZONE_ROW_HEIGHT
        },

        get virtualSpacerBottom() {
            if (! this.usesVirtualScroll) {
                return 0
            }

            const startIndex = Math.max(0, Math.floor(this.virtualScrollTop / FFF_TIMEZONE_ROW_HEIGHT) - FFF_TIMEZONE_OVERSCAN)
            const viewportHeight = 320
            const visibleCount = Math.ceil(viewportHeight / FFF_TIMEZONE_ROW_HEIGHT) + (FFF_TIMEZONE_OVERSCAN * 2)
            const endIndex = Math.min(this.filteredTimezones.length, startIndex + visibleCount)

            return Math.max(0, (this.filteredTimezones.length - endIndex) * FFF_TIMEZONE_ROW_HEIGHT)
        },

        get selectedTimezone() {
            const timezoneId = this.resolveTimezoneValue()

            if (! timezoneId) {
                return null
            }

            return this.timezones.find((timezone) => timezone.id === timezoneId)
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
        },

        selectTimezone(id) {
            if (this.isTimezoneLocked) {
                return
            }

            this.assignTimezoneValue(id)
            this.closeMenu()
        },

        toggleTimezoneMenu() {
            if (this.isTimezoneLocked) {
                return
            }

            const willOpen = ! this.menuOpen

            this.menuOpen = willOpen

            if (this.menuOpen && this.searchable) {
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
