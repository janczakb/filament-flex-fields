import { createOverlayMenuKeyboardMixin } from './overlay-menu-keyboard.js'

/**
 * Address / geocoding dropdown keyboard — combobox contract (focus stays in search input).
 */
export function createGeocodingListKeyboardMixin({
    openKey = 'searchOpen',
    resultsKey = 'searchResults',
    menuRef = 'searchDropdown',
    searchRef = 'searchInput',
    highlightedIndexKey = 'highlightedIndex',
    selectMethod = 'selectSearchResult',
    optionIdPrefix = 'fff-geocoding-option',
    closeMethod = 'closeSearchDropdown',
} = {}) {
    const unified = createOverlayMenuKeyboardMixin({
        openKey,
        resultsKey,
        scrollRef: null,
        menuRef,
        searchRef,
        itemHeight: 40,
        selectMethod,
        optionIdPrefix,
        activeIndexKey: highlightedIndexKey,
        getItemValue: (item) => item,
        onEscape: closeMethod,
    })

    return {
        ...unified,

        initGeocodingListKeyboard() {
            // Combobox: never steal focus into the teleported list or restore it on close —
            // overlay-menu-keyboard focus management causes focus/blur infinite loops here.
            this.$watch(openKey, (open) => {
                if (open) {
                    this.syncOverlayMenuActiveIndex()

                    return
                }

                this[highlightedIndexKey] = -1
            })
        },

        geocodingOptionId(index) {
            if (typeof index === 'string') {
                return `${optionIdPrefix}-${index}`
            }

            return this.overlayMenuOptionId(index)
        },

        syncGeocodingHighlightedIndex() {
            this.syncOverlayMenuActiveIndex()
        },

        onGeocodingSearchKeydown(event) {
            this.onOverlayMenuSearchKeydown(event)
        },

        scrollGeocodingOptionIntoView() {
            const menu = this.$refs[menuRef]
            const option = menu?.querySelector(`#${this.geocodingOptionId(this[highlightedIndexKey])}`)

            option?.scrollIntoView({ block: 'nearest' })
        },

        scrollOverlayMenuActiveIntoView() {
            this.scrollGeocodingOptionIntoView()
        },
    }
}
