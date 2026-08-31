function resolveListItems(component, itemsKey) {
    const value = component[itemsKey]

    if (typeof value === 'function') {
        return value.call(component)
    }

    return value ?? []
}

function resolveMenuFocusables(menu) {
    if (! menu) {
        return []
    }

    return [...menu.querySelectorAll(
        'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
    )].filter((element) => element.offsetParent !== null)
}

/**
 * Unified ARIA / keyboard contract for overlay pickers (phone, country, timezone, tags, geocoding…).
 *
 * @param {{
 *   openKey?: string,
 *   resultsKey: string,
 *   scrollRef?: string | null,
 *   menuRef: string,
 *   searchRef?: string | null,
 *   searchEnabledKey?: string | null,
 *   itemHeight?: number,
 *   selectMethod: string,
 *   getItemValue?: (item: unknown) => string | number | null,
 *   isItemSelected?: (component: object, item: unknown) => boolean,
 *   optionIdPrefix: string,
 *   activeIndexKey?: string,
 *   onEscape?: string | null,
 * }} config
 */
export function createOverlayMenuKeyboardMixin({
    openKey = 'menuOpen',
    resultsKey,
    scrollRef = null,
    menuRef,
    searchRef = null,
    searchEnabledKey = null,
    itemHeight = 40,
    selectMethod,
    getItemValue = (item) => (item && typeof item === 'object' && 'value' in item ? item.value : item),
    isItemSelected = null,
    optionIdPrefix,
    activeIndexKey = 'overlayMenuActiveIndex',
    onEscape = null,
} = {}) {
    return {
        overlayMenuPreviouslyFocused: null,

        initOverlayMenuKeyboard() {
            this.$watch(openKey, (open) => {
                if (open) {
                    this.syncOverlayMenuActiveIndex()
                    this.overlayMenuPreviouslyFocused = document.activeElement

                    this.$nextTick(() => {
                        if (searchEnabledKey && searchRef && this[searchEnabledKey] && this.$refs[searchRef]) {
                            this.$refs[searchRef].focus()

                            return
                        }

                        this.getOverlayMenuFocusables()[0]?.focus()
                    })

                    return
                }

                this[activeIndexKey] = -1

                if (typeof this.overlayMenuPreviouslyFocused?.focus === 'function') {
                    this.overlayMenuPreviouslyFocused.focus()
                }

                this.overlayMenuPreviouslyFocused = null
            })
        },

        overlayMenuOptionId(index) {
            return `${optionIdPrefix}-${index}`
        },

        getOverlayMenuFocusables() {
            return resolveMenuFocusables(this.$refs[menuRef])
        },

        syncOverlayMenuActiveIndex() {
            const items = resolveListItems(this, resultsKey)

            if (! items.length) {
                this[activeIndexKey] = -1

                return
            }

            if (typeof isItemSelected === 'function') {
                const selectedIndex = items.findIndex((item) => isItemSelected(this, item))

                if (selectedIndex >= 0) {
                    this[activeIndexKey] = selectedIndex

                    return
                }
            }

            if (this[activeIndexKey] < 0 || this[activeIndexKey] >= items.length) {
                this[activeIndexKey] = 0
            }
        },

        scrollOverlayMenuActiveIntoView() {
            const index = this[activeIndexKey]

            if (index < 0 || ! scrollRef) {
                return
            }

            const element = this.$refs[scrollRef]

            if (! element) {
                return
            }

            const targetTop = index * itemHeight
            const targetBottom = targetTop + itemHeight
            const viewTop = element.scrollTop
            const viewBottom = viewTop + element.clientHeight

            if (targetTop < viewTop) {
                element.scrollTop = targetTop

                if (typeof this.virtualListScrollTop === 'number') {
                    this.virtualListScrollTop = targetTop
                }
            } else if (targetBottom > viewBottom) {
                element.scrollTop = targetBottom - element.clientHeight

                if (typeof this.virtualListScrollTop === 'number') {
                    this.virtualListScrollTop = element.scrollTop
                }
            }
        },

        onOverlayMenuKeydown(event) {
            if (! this[openKey]) {
                return
            }

            if (event.key === 'Escape') {
                event.preventDefault()

                if (onEscape && typeof this[onEscape] === 'function') {
                    this[onEscape]()

                    return
                }

                if (typeof this.closeMenu === 'function') {
                    this.closeMenu()
                } else if (typeof this.closeTeleportedMenu === 'function') {
                    this.closeTeleportedMenu()
                } else {
                    this[openKey] = false
                }

                return
            }

            const items = resolveListItems(this, resultsKey)

            if (! items.length) {
                return
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault()
                this[activeIndexKey] = Math.min(this[activeIndexKey] + 1, items.length - 1)
                this.scrollOverlayMenuActiveIntoView()

                return
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault()
                this[activeIndexKey] = Math.max(this[activeIndexKey] - 1, 0)
                this.scrollOverlayMenuActiveIntoView()

                return
            }

            if (event.key === 'Home') {
                event.preventDefault()
                this[activeIndexKey] = 0
                this.scrollOverlayMenuActiveIntoView()

                return
            }

            if (event.key === 'End') {
                event.preventDefault()
                this[activeIndexKey] = items.length - 1
                this.scrollOverlayMenuActiveIntoView()

                return
            }

            if (event.key === 'Enter' && this[activeIndexKey] >= 0) {
                event.preventDefault()
                const item = items[this[activeIndexKey]]

                if (item != null && typeof this[selectMethod] === 'function') {
                    const value = getItemValue(item)

                    this[selectMethod](value ?? item)
                }
            }
        },

        onOverlayMenuSearchKeydown(event) {
            if (['ArrowDown', 'ArrowUp', 'Enter', 'Home', 'End', 'Escape'].includes(event.key)) {
                this.onOverlayMenuKeydown(event)
            }
        },

        onOverlayMenuTabTrap(event) {
            if (! this[openKey] || event.key !== 'Tab') {
                return
            }

            const focusables = this.getOverlayMenuFocusables()

            if (focusables.length === 0) {
                return
            }

            event.preventDefault()

            const currentIndex = focusables.indexOf(document.activeElement)
            const nextIndex = event.shiftKey
                ? (currentIndex <= 0 ? focusables.length - 1 : currentIndex - 1)
                : (currentIndex >= focusables.length - 1 ? 0 : currentIndex + 1)

            focusables[nextIndex]?.focus()
        },
    }
}
