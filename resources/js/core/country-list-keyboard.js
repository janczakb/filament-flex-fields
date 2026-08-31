import { createOverlayMenuKeyboardMixin } from './overlay-menu-keyboard.js'

/**
 * Country picker keyboard — delegates to unified overlay menu keyboard contract.
 */
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
    const unified = createOverlayMenuKeyboardMixin({
        openKey,
        resultsKey,
        scrollRef,
        menuRef,
        searchRef,
        searchEnabledKey,
        itemHeight,
        selectMethod,
        optionIdPrefix,
        activeIndexKey: 'activeCountryIndex',
        onEscape: 'closeMenu',
        getItemValue: (item) => item?.code ?? item,
        isItemSelected: (component, item) => {
            const selectedCode = typeof component.getActiveCountryCode === 'function'
                ? component.getActiveCountryCode()
                : null

            return Boolean(selectedCode && item?.code === selectedCode)
        },
    })

    return {
        activeCountryIndex: -1,
        countryListKeyboardScope: optionIdPrefix,
        ...unified,

        initCountryListKeyboard() {
            this.initOverlayMenuKeyboard()

            this.$watch('countries', () => {
                if (this[openKey]) {
                    this.syncOverlayMenuActiveIndex()
                }
            })

            this.$watch('countrySearchDebounced', () => {
                if (this[openKey]) {
                    this.syncOverlayMenuActiveIndex()
                }
            })
        },

        resetCountryListKeyboard() {
            this.syncOverlayMenuActiveIndex()
        },

        countryOptionId(index) {
            return this.overlayMenuOptionId(index)
        },

        getCountryMenuFocusables() {
            return this.getOverlayMenuFocusables()
        },

        onCountryMenuKeydown(event) {
            this.onOverlayMenuTabTrap(event)
        },

        syncActiveCountryIndex() {
            this.syncOverlayMenuActiveIndex()
        },

        scrollActiveCountryIntoView() {
            this.scrollOverlayMenuActiveIntoView()
        },

        onCountryListKeydown(event) {
            this.onOverlayMenuKeydown(event)
        },

        onCountrySearchKeydown(event) {
            this.onOverlayMenuSearchKeydown(event)
        },
    }
}
