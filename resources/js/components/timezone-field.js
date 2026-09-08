import { mergeAlpineComponentData } from '../support/merge-alpine-component-data.js'
import {
    createTimezonePickerMixin,
    FFF_TIMEZONE_VIRTUAL_THRESHOLD,
} from '../support/timezone-picker-mixin.js'
import {
    resolveTimezonesFromRegistry,
    resetTimezoneRegistryCache,
} from '../core/timezone-registry.js'

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
    timezonePool = null,
    timezoneFilterKey = null,
    selectedTimezoneSeed = null,
    sortPreferredFirst = false,
    preferredTimezoneId = null,
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
    locale = null,
    virtualScrollThreshold = FFF_TIMEZONE_VIRTUAL_THRESHOLD,
}) {
    return mergeAlpineComponentData({
        state,
        statePath,
        timezones: Array.isArray(timezones) ? timezones : [],
        timezonePool,
        timezoneFilterKey,
        selectedTimezoneSeed,
        sortPreferredFirst,
        preferredTimezoneId,
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
        locale,
        virtualScrollThreshold,
        timezonesLoaded: ! timezonePool,
        timezonesLoading: false,

        get isLocked() {
            return this.disabled || this.readOnly
        },

        get isEmpty() {
            return ! this.state
        },

        init() {
            this.applyBrowserTimezoneDefault()

            const needsRegistryBeforeHandoff = Boolean(
                this.timezonePool
                && this.browserTimezoneDefault
                && (this.state || this.$el?.dataset?.fffDetectedTimezone),
            )

            if (needsRegistryBeforeHandoff) {
                void this.ensureTimezonesLoaded().then(() => {
                    this.syncTriggerSsrFromSelection?.()
                    this.initTimezonePicker()
                })
            } else {
                this.initTimezonePicker()
            }

            document.addEventListener('livewire:navigated', () => {
                if (! this.timezonePool) {
                    return
                }

                resetTimezoneRegistryCache()
                this.timezones = this.selectedTimezoneSeed ? [this.selectedTimezoneSeed] : []
                this.timezonesLoaded = false
            })
        },

        async ensureTimezonesLoaded() {
            if (! this.timezonePool || this.timezonesLoaded) {
                return
            }

            if (this.timezonesLoading) {
                while (this.timezonesLoading) {
                    await new Promise((resolve) => setTimeout(resolve, 16))
                }

                return
            }

            this.timezonesLoading = true

            try {
                resetTimezoneRegistryCache()

                this.timezones = await resolveTimezonesFromRegistry({
                    pool: this.timezonePool,
                    timezoneFilterKey: this.timezoneFilterKey,
                    preferredTimezoneId: this.preferredTimezoneId,
                    sortPreferredFirst: this.sortPreferredFirst,
                    locale: this.locale,
                })

                if (this.timezones.length === 0) {
                    resetTimezoneRegistryCache()

                    this.timezones = await resolveTimezonesFromRegistry({
                        pool: this.timezonePool,
                        timezoneFilterKey: this.timezoneFilterKey,
                        preferredTimezoneId: this.preferredTimezoneId,
                        sortPreferredFirst: this.sortPreferredFirst,
                        locale: this.locale,
                    })
                }

                this.timezonesLoaded = true
            } finally {
                this.timezonesLoading = false
            }
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
            const fromList = Array.isArray(this.allowedTimezoneIdentifiers)
                ? this.allowedTimezoneIdentifiers
                : this.timezones.map((timezone) => timezone.id)
            const allowed = new Set(fromList)
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
                if (allowed.size === 0 || allowed.has(candidate)) {
                    return candidate
                }
            }

            return null
        },

        async toggleMenu() {
            if (this.isLocked) {
                return
            }

            if (this.menuOpen) {
                this.closeTimezoneMenu()

                return
            }

            await this.ensureTimezonesLoaded()
            this.toggleTimezoneMenu()
        },

        closeMenu() {
            this.closeTimezoneMenu()
        },
    }, timezonePicker)
}
