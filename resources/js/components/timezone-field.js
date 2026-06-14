import { createSearchableSelectMenuMixin } from '../core/searchable-select-menu.js'

export const FFF_TIMEZONE_VIRTUAL_THRESHOLD = 50
export const FFF_TIMEZONE_ROW_HEIGHT = 40
export const FFF_TIMEZONE_OVERSCAN = 6

const selectMenu = createSearchableSelectMenuMixin({
    triggerRef: 'timezoneTrigger',
    menuRef: 'timezoneMenu',
    onMenuClose() {
        this.virtualScrollTop = 0
    },
})

export default function timezoneFieldFormComponent({
    state,
    statePath,
    timezones,
    defaultTimezone,
    disabled,
    readOnly,
    searchable,
    showOffset,
    searchPlaceholder,
    placeholder,
    browserTimezoneDefault,
    allowedTimezoneIdentifiers,
    initialState = null,
    virtualScrollThreshold = FFF_TIMEZONE_VIRTUAL_THRESHOLD,
}) {
    return {
        state,
        statePath,
        timezones,
        defaultTimezone,
        disabled,
        readOnly,
        searchable,
        showOffset,
        searchPlaceholder,
        placeholder,
        browserTimezoneDefault,
        allowedTimezoneIdentifiers,
        initialState,
        virtualScrollThreshold,
        displayReady: false,
        menuOpen: false,
        timezoneSearch: '',
        menuReady: false,
        menuScrollHandler: null,
        menuResizeHandler: null,
        virtualScrollTop: 0,
        ...selectMenu,

        get isLocked() {
            return this.disabled || this.readOnly
        },

        get usesVirtualScroll() {
            return this.filteredTimezones.length > this.virtualScrollThreshold
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

        init() {
            this.applyBrowserTimezoneDefault()

            this.$nextTick(() => {
                this.displayReady = true
            })

            this.bindSelectMenuLifecycle()
        },

        applyBrowserTimezoneDefault() {
            if (! this.browserTimezoneDefault || this.isLocked || this.state || this.initialState) {
                return
            }

            const detected = this.detectBrowserTimezone()

            if (detected) {
                this.state = detected
            }
        },

        detectBrowserTimezone() {
            const allowed = new Set(this.allowedTimezoneIdentifiers ?? this.timezones.map((timezone) => timezone.id))
            const candidates = []

            try {
                const resolved = Intl.DateTimeFormat().resolvedOptions().timeZone

                if (resolved) {
                    candidates.push(resolved)
                }
            } catch {
                // Ignore unsupported environments.
            }

            for (const candidate of candidates) {
                if (allowed.has(candidate)) {
                    return candidate
                }
            }

            return null
        },

        get selectedTimezone() {
            const timezoneId = this.state ?? this.defaultTimezone

            if (! timezoneId) {
                return null
            }

            return this.timezones.find((timezone) => timezone.id === timezoneId)
                ?? this.timezones[0]
                ?? null
        },

        get isEmpty() {
            return ! this.state
        },

        get filteredTimezones() {
            const query = this.timezoneSearch.trim().toLowerCase()

            if (! query) {
                return this.timezones
            }

            return this.timezones.filter((timezone) => {
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
            if (this.isLocked) {
                return
            }

            this.state = id
            this.closeMenu()
        },

        toggleMenu() {
            if (this.isLocked) {
                return
            }

            this.menuOpen = ! this.menuOpen

            if (this.menuOpen && this.searchable) {
                this.$nextTick(() => {
                    this.$refs.timezoneSearch?.focus()
                })
            }
        },

        closeMenu() {
            this.menuOpen = false
            this.timezoneSearch = ''
        },

    }
}
