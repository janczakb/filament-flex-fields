function resolveListItems(component, itemsKey) {
    const value = component[itemsKey]

    if (typeof value === 'function') {
        return value.call(component)
    }

    return value ?? []
}

export function createGeocodingListKeyboardMixin({
    openKey = 'searchOpen',
    resultsKey = 'searchResults',
    menuRef = 'searchDropdown',
    searchRef = 'searchInput',
    highlightedIndexKey = 'highlightedIndex',
    selectMethod = 'selectSearchResult',
    optionIdPrefix = 'fff-geocoding-option',
} = {}) {
    return {
        initGeocodingListKeyboard() {
            this.$watch(openKey, (open) => {
                if (! open) {
                    this[highlightedIndexKey] = -1
                }
            })
        },

        geocodingOptionId(index) {
            return `${optionIdPrefix}-${index}`
        },

        syncGeocodingHighlightedIndex() {
            const items = resolveListItems(this, resultsKey)

            if (items.length === 0) {
                this[highlightedIndexKey] = -1

                return
            }

            if (this[highlightedIndexKey] < 0 || this[highlightedIndexKey] >= items.length) {
                this[highlightedIndexKey] = 0
            }
        },

        onGeocodingSearchKeydown(event) {
            if (! this[openKey]) {
                return
            }

            const items = resolveListItems(this, resultsKey)

            if (items.length === 0) {
                return
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault()
                this[highlightedIndexKey] = Math.min(this[highlightedIndexKey] + 1, items.length - 1)
                this.scrollGeocodingOptionIntoView()

                return
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault()
                this[highlightedIndexKey] = Math.max(this[highlightedIndexKey] - 1, 0)
                this.scrollGeocodingOptionIntoView()

                return
            }

            if (event.key === 'Home') {
                event.preventDefault()
                this[highlightedIndexKey] = 0
                this.scrollGeocodingOptionIntoView()

                return
            }

            if (event.key === 'End') {
                event.preventDefault()
                this[highlightedIndexKey] = items.length - 1
                this.scrollGeocodingOptionIntoView()

                return
            }

            if (event.key === 'Enter') {
                event.preventDefault()
                const item = items[this[highlightedIndexKey]]

                if (item && typeof this[selectMethod] === 'function') {
                    this[selectMethod](item)
                }

                return
            }

            if (event.key === 'Escape') {
                event.preventDefault()
                this[openKey] = false
            }
        },

        scrollGeocodingOptionIntoView() {
            const menu = this.$refs[menuRef]
            const option = menu?.querySelector(`#${this.geocodingOptionId(this[highlightedIndexKey])}`)

            option?.scrollIntoView({ block: 'nearest' })
        },
    }
}
