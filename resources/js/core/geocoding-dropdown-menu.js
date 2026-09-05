import { resolveOverlayMode } from './overlay-mode.js'
import { createSearchableSelectMenuMixin, runAfterSheetEnter } from './searchable-select-menu.js'

/**
 * Geocoding suggestions menu — same overlay stack as SelectField (panel + mobile sheet).
 */
export function createGeocodingDropdownMenuMixin({
    openKey = 'searchOpen',
    readyKey = 'searchDropdownReady',
    triggerRef = 'searchWrap',
    widthRef = null,
    menuRef = 'searchDropdown',
    closeMethod = 'closeSearchDropdown',
    menuThemeVariant = 'default',
    menuGap = 8,
    ownerIdPrefix = 'fff-geocoding',
    matchTriggerWidth = true,
    minMenuWidth = 288,
} = {}) {
    const selectMenu = createSearchableSelectMenuMixin({
        openKey,
        readyKey,
        triggerRef,
        menuRef,
        closeMethod,
        ownerIdPrefix,
        matchTriggerWidth,
        menuThemeVariant,
        menuGap,
        minMenuWidth,
    })

    return {
        ...selectMenu,

        resolveMenuTriggerElement() {
            return this.$refs[widthRef ?? triggerRef]
                ?? this.$refs[triggerRef]
                ?? null
        },

        isGeocodingSheetPresentation() {
            if (this.__fffOverlayMode === 'sheet') {
                return true
            }

            const menu = this.resolveMenuElement()

            if (menu?.classList?.contains?.('fff-teleported-menu--sheet')
                || menu?.classList?.contains?.('fff-overlay-sheet')) {
                return true
            }

            return resolveOverlayMode(typeof window !== 'undefined' ? window : undefined) === 'sheet'
        },

        shouldShowGeocodingSheetSearch() {
            return this.searchable !== false
                && ! this.readOnly
                && this.isGeocodingSheetPresentation()
        },

        shouldDismissGeocodingOnBlur() {
            // Sheet: backdrop / drag only. Panel: blur outside closes.
            return ! this.isGeocodingSheetPresentation()
        },

        focusGeocodingSheetSearch() {
            if (! this.shouldShowGeocodingSheetSearch()) {
                return
            }

            const input = this.$refs.geocodingSheetSearchInput

            if (! input || typeof input.focus !== 'function') {
                return
            }

            input.focus({ preventScroll: true })

            if (String(input.value ?? '').length > 0) {
                input.select?.()
            }
        },

        clearGeocodingSheetSearch() {
            this.searchQuery = ''
            this.searchResults = []
            this.highlightedIndex = -1
            this.searchLoading = false
            this.searchRefreshing = false
            this.updateSearchHasMinQuery?.()
            window.clearTimeout?.(this.searchDebounceTimer)
            this.$nextTick?.(() => this.focusGeocodingSheetSearch())
        },

        pinGeocodingSheetWidth() {
            const menu = this.resolveMenuElement()

            if (! menu?.style?.setProperty) {
                return
            }

            menu.style.setProperty('width', '100%', 'important')
            menu.style.setProperty('min-width', '0', 'important')
            menu.style.setProperty('max-width', 'none', 'important')
            menu.style.setProperty('left', '0', 'important')
            menu.style.setProperty('right', '0', 'important')
            menu.style.setProperty('inset-inline', '0', 'important')
        },

        syncGeocodingDropdownWidth() {
            if (this.isGeocodingSheetPresentation()) {
                this.pinGeocodingSheetWidth()

                return
            }

            const menu = this.resolveMenuElement()
            const trigger = this.resolveMenuTriggerElement()

            if (! menu || ! trigger) {
                return
            }

            const width = Math.max(Math.round(trigger.getBoundingClientRect().width), 0)

            if (width === 0) {
                return
            }

            const capped = Math.min(width, window.innerWidth - 32)

            menu.style.setProperty('width', `${capped}px`, 'important')
            menu.style.setProperty('min-width', `${capped}px`, 'important')
            menu.style.setProperty('max-width', `${capped}px`, 'important')
        },

        scheduleGeocodingSheetSearchFocus() {
            if (! this[openKey] || ! this.shouldShowGeocodingSheetSearch()) {
                return
            }

            const menu = this.resolveMenuElement()

            this.$nextTick?.(() => {
                runAfterSheetEnter(menu, () => {
                    this.focusGeocodingSheetSearch()
                })
            })
        },

        bindGeocodingDropdownMenu() {
            this.bindSelectMenuLifecycle()

            const baseUpdateMenuPosition = this.updateMenuPosition.bind(this)

            this.updateMenuPosition = (options = {}) => {
                // Sheet: pin 100vw before/after overlay work so trigger-width never
                // flashes mid enter (looks like the drawer shrinking).
                if (this.isGeocodingSheetPresentation()) {
                    this.pinGeocodingSheetWidth()
                    baseUpdateMenuPosition(options)
                    this.pinGeocodingSheetWidth()

                    return
                }

                this.syncGeocodingDropdownWidth()
                baseUpdateMenuPosition(options)
                this.syncGeocodingDropdownWidth()
            }

            this.$watch(openKey, (open) => {
                if (! open) {
                    return
                }

                this.scheduleGeocodingSheetSearchFocus()
            })
        },

        /** @deprecated Kept for callers that still tear down the legacy panel path. */
        teardownGeocodingDropdownMenu() {
            this.unbindMenuListeners?.()
            this[readyKey] = false

            const menu = this.resolveMenuElement()

            if (menu) {
                menu.classList.remove(
                    'is-open',
                    'is-closing',
                    'fff-teleported-menu--above',
                    'fff-teleported-menu--below',
                )
            }
        },
    }
}
