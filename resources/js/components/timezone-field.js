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

        toggleMenu() {
            this.toggleTimezoneMenu()
        },

        closeMenu() {
            this.closeTimezoneMenu()
        },
    }, timezonePicker)
}
