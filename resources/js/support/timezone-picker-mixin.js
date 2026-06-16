import { createSearchableSelectMenuMixin } from '../core/searchable-select-menu.js'

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

    return {
        displayReady: false,
        menuOpen: false,
        timezoneSearch: '',
        menuReady: false,
        menuScrollHandler: null,
        menuResizeHandler: null,
        virtualScrollTop: 0,
        ...selectMenu,

        initTimezonePicker() {
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
            const query = this.timezoneSearch.trim().toLowerCase()

            if (! query) {
                return timezones
            }

            return timezones.filter((timezone) => {
                return timezone.label.toLowerCase().includes(query)
                    || timezone.id.toLowerCase().includes(query)
                    || String(timezone.region ?? '').toLowerCase().includes(query)
                    || timezone.offset.toLowerCase().includes(query)
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
