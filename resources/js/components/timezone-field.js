import { mergeAlpineComponentData } from '../support/merge-alpine-component-data.js'
import {
    createTimezonePickerMixin,
    FFF_TIMEZONE_VIRTUAL_THRESHOLD,
} from '../support/timezone-picker-mixin.js'

export { FFF_TIMEZONE_VIRTUAL_THRESHOLD }

const timezonePicker = createTimezonePickerMixin({
    triggerRef: 'timezoneTrigger',
    menuRef: 'timezoneMenu',
    ownerIdPrefix: 'fff-timezone-field',
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
    return mergeAlpineComponentData({
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

        get isLocked() {
            return this.disabled || this.readOnly
        },

        get isEmpty() {
            return ! this.state
        },

        init() {
            this.applyBrowserTimezoneDefault()
            this.initTimezonePicker()
        },

        /**
         * @returns {boolean} true when a browser timezone was written into state
         */
        applyBrowserTimezoneDefault() {
            if (! this.browserTimezoneDefault || this.isLocked || this.state || this.initialState) {
                return false
            }

            const detected = this.$el?.dataset?.fffDetectedTimezone || this.detectBrowserTimezone()

            if (! detected) {
                return false
            }

            this.state = detected

            return true
        },

        /**
         * Keep SSR trigger text aligned with the resolved selection. Used after
         * Alpine mounts (inline boot may have already painted a provisional label).
         */
        syncTriggerSsrFromSelection() {
            const selected = this.selectedTimezone

            if (! selected) {
                return
            }

            const root = this.$el

            if (! root || typeof root.querySelector !== 'function') {
                return
            }

            if (! root.dataset.fffDetectedTimezone && selected.id) {
                root.dataset.fffDetectedTimezone = String(selected.id)
            }

            const nextLabel = String(selected.label ?? '')
            const nextOffset = String(selected.offset ?? '')
            const label = root.querySelector('.fff-timezone-field__ssr-label')

            if (label && label.textContent !== nextLabel) {
                label.textContent = nextLabel
                label.classList.remove('is-placeholder')
                label.removeAttribute('data-fff-tz-ssr-provisional')
            } else if (label) {
                label.classList.remove('is-placeholder')
                label.removeAttribute('data-fff-tz-ssr-provisional')
            }

            const meta = root.querySelector('.fff-timezone-field__ssr-meta')

            if (meta && meta.textContent !== nextOffset) {
                meta.textContent = nextOffset
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

        toggleMenu() {
            this.toggleTimezoneMenu()
        },

        closeMenu() {
            this.closeTimezoneMenu()
        },
    }, timezonePicker)
}
