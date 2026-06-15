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

export function createCountryListKeyboardMixin({
    openKey = 'menuOpen',
    resultsKey = 'filteredCountries',
    scrollRef = 'countryListScroll',
    menuRef = 'countryMenu',
    searchRef = 'countrySearch',
    searchEnabledKey = 'searchable',
    itemHeight = 40,
    selectMethod = 'selectCountry',
    optionIdPrefix = 'fff-country-option',
} = {}) {
    return {
        activeCountryIndex: -1,
        countryListKeyboardScope: optionIdPrefix,
        countryMenuPreviouslyFocused: null,

        initCountryListKeyboard() {
            this.$watch(openKey, (open) => {
                if (open) {
                    this.syncActiveCountryIndex()
                    this.countryMenuPreviouslyFocused = document.activeElement

                    this.$nextTick(() => {
                        if (this[searchEnabledKey] && this.$refs[searchRef]) {
                            this.$refs[searchRef].focus()

                            return
                        }

                        const focusables = this.getCountryMenuFocusables()

                        focusables[0]?.focus()
                    })

                    return
                }

                this.activeCountryIndex = -1

                if (typeof this.countryMenuPreviouslyFocused?.focus === 'function') {
                    this.countryMenuPreviouslyFocused.focus()
                }

                this.countryMenuPreviouslyFocused = null
            })

            this.$watch('countries', () => {
                if (this[openKey]) {
                    this.syncActiveCountryIndex()
                }
            })

            this.$watch('countrySearchDebounced', () => {
                if (this[openKey]) {
                    this.syncActiveCountryIndex()
                }
            })
        },

        resetCountryListKeyboard() {
            this.syncActiveCountryIndex()
        },

        countryOptionId(index) {
            return `${this.countryListKeyboardScope}-${index}`
        },

        getCountryMenuFocusables() {
            return resolveMenuFocusables(this.$refs[menuRef])
        },

        onCountryMenuKeydown(event) {
            if (! this[openKey] || event.key !== 'Tab') {
                return
            }

            const focusables = this.getCountryMenuFocusables()

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

        syncActiveCountryIndex() {
            const items = resolveListItems(this, resultsKey)
            const selectedCode = typeof this.getActiveCountryCode === 'function'
                ? this.getActiveCountryCode()
                : null

            if (! items.length) {
                this.activeCountryIndex = -1

                return
            }

            if (selectedCode) {
                const selectedIndex = items.findIndex((country) => country.code === selectedCode)

                if (selectedIndex >= 0) {
                    this.activeCountryIndex = selectedIndex

                    return
                }
            }

            this.activeCountryIndex = 0
        },

        scrollActiveCountryIntoView() {
            if (this.activeCountryIndex < 0) {
                return
            }

            const element = this.$refs[scrollRef]

            if (! element) {
                return
            }

            const targetTop = this.activeCountryIndex * itemHeight
            const targetBottom = targetTop + itemHeight
            const viewTop = element.scrollTop
            const viewBottom = viewTop + element.clientHeight

            if (targetTop < viewTop) {
                element.scrollTop = targetTop
                this.virtualListScrollTop = targetTop
            } else if (targetBottom > viewBottom) {
                element.scrollTop = targetBottom - element.clientHeight
                this.virtualListScrollTop = element.scrollTop
            }
        },

        onCountryListKeydown(event) {
            if (! this[openKey]) {
                return
            }

            const items = resolveListItems(this, resultsKey)

            if (! items.length) {
                return
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault()
                this.activeCountryIndex = Math.min(this.activeCountryIndex + 1, items.length - 1)
                this.scrollActiveCountryIntoView()

                return
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault()
                this.activeCountryIndex = Math.max(this.activeCountryIndex - 1, 0)
                this.scrollActiveCountryIntoView()

                return
            }

            if (event.key === 'Home') {
                event.preventDefault()
                this.activeCountryIndex = 0
                this.scrollActiveCountryIntoView()

                return
            }

            if (event.key === 'End') {
                event.preventDefault()
                this.activeCountryIndex = items.length - 1
                this.scrollActiveCountryIntoView()

                return
            }

            if (event.key === 'Enter' && this.activeCountryIndex >= 0) {
                event.preventDefault()
                const country = items[this.activeCountryIndex]

                if (country && typeof this[selectMethod] === 'function') {
                    this[selectMethod](country.code)
                }
            }
        },

        onCountrySearchKeydown(event) {
            if (['ArrowDown', 'ArrowUp', 'Enter', 'Home', 'End'].includes(event.key)) {
                this.onCountryListKeydown(event)
            }
        },
    }
}
