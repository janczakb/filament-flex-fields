import { createSearchableSelectMenuMixin } from '../core/searchable-select-menu.js'
import { createVirtualizedListMixin } from '../core/virtualized-list.js'
import { resolveCountriesFromRegistry, resetCountryRegistryCache } from '../core/country-registry.js'
import { createCountrySearchMixin } from '../core/country-search.js'
import { createCountryListKeyboardMixin } from '../core/country-list-keyboard.js'

const selectMenu = createSearchableSelectMenuMixin({
    triggerRef: 'countryTrigger',
    menuRef: 'countryMenu',
    ownerIdPrefix: 'fff-country-field',
    onMenuClose() {
        this.countrySearch = ''
        this.countrySearchDebounced = ''
    },
})

const virtualList = createVirtualizedListMixin({
    itemsKey: 'filteredCountries',
    scrollRef: 'countryListScroll',
    itemHeight: 40,
})

const countrySearch = createCountrySearchMixin()
const countryKeyboard = createCountryListKeyboardMixin({
    openKey: 'menuOpen',
    optionIdPrefix: 'fff-country-field-option',
})

export default function countryFieldFormComponent({
    state,
    statePath,
    countryPool,
    countryFilterKey,
    sortPreferredFirst,
    preferredCountryCode,
    selectedCountrySeed,
    defaultCountry,
    disabled,
    readOnly,
    searchable,
    showCountryCode,
    showDialCode,
    searchPlaceholder,
    placeholder,
    browserLocaleDefault,
    languageCountryMap,
    initialState = null,
}) {
    return {
        state,
        statePath,
        countryPool,
        countryFilterKey,
        sortPreferredFirst,
        preferredCountryCode,
        selectedCountrySeed,
        defaultCountry,
        disabled,
        readOnly,
        searchable,
        showCountryCode,
        showDialCode,
        searchPlaceholder,
        placeholder,
        browserLocaleDefault,
        languageCountryMap,
        initialState,
        countries: [],
        countriesLoaded: false,
        countriesLoading: false,
        displayReady: false,
        menuOpen: false,
        countrySearch: '',
        menuReady: false,
        menuScrollHandler: null,
        menuResizeHandler: null,
        ...selectMenu,
        ...virtualList,
        ...countrySearch,
        ...countryKeyboard,

        get isLocked() {
            return this.disabled || this.readOnly
        },

        init() {
            this.initCountrySearch()
            this.initCountryListKeyboard()
            void this.bootstrapBrowserLocaleDefault()
            this.preloadSelectedCountryFlag()
            this.bindSelectMenuLifecycle()

            this.$nextTick(() => {
                this.displayReady = true
            })

            this.$watch('state', () => {
                this.preloadSelectedCountryFlag()
            })

            document.addEventListener('livewire:navigated', () => {
                resetCountryRegistryCache()
                this.countries = []
                this.countriesLoaded = false
            })
        },

        getActiveCountryCode() {
            return this.state ?? this.defaultCountry
        },

        async ensureCountriesLoaded() {
            if (this.countriesLoaded) {
                return
            }

            if (this.countriesLoading) {
                while (this.countriesLoading) {
                    await new Promise((resolve) => setTimeout(resolve, 16))
                }

                return
            }

            this.countriesLoading = true

            try {
                resetCountryRegistryCache()

                this.countries = await resolveCountriesFromRegistry({
                    pool: this.countryPool,
                    countryFilterKey: this.countryFilterKey,
                    preferredCountryCode: this.preferredCountryCode,
                    sortPreferredFirst: this.sortPreferredFirst,
                })

                if (this.countries.length === 0) {
                    resetCountryRegistryCache()

                    this.countries = await resolveCountriesFromRegistry({
                        pool: this.countryPool,
                        countryFilterKey: this.countryFilterKey,
                        preferredCountryCode: this.preferredCountryCode,
                        sortPreferredFirst: this.sortPreferredFirst,
                    })
                }

                this.countriesLoaded = true
            } finally {
                this.countriesLoading = false
            }
        },

        async bootstrapBrowserLocaleDefault() {
            if (! this.browserLocaleDefault || this.isLocked || this.state || this.initialState) {
                return
            }

            await this.ensureCountriesLoaded()
            this.applyBrowserLocaleDefault()
        },

        applyBrowserLocaleDefault() {
            if (! this.browserLocaleDefault || this.isLocked || this.state || this.initialState) {
                return
            }

            const detected = this.detectBrowserCountry()

            if (detected) {
                this.state = detected
            }
        },

        detectBrowserCountry() {
            const allowed = new Set(this.countries.map((country) => country.code))
            const languages = Array.isArray(navigator.languages) && navigator.languages.length > 0
                ? navigator.languages
                : [navigator.language].filter(Boolean)

            for (const locale of languages) {
                const matched = this.matchLocaleToCountry(String(locale), allowed)

                if (matched) {
                    return matched
                }
            }

            return null
        },

        matchLocaleToCountry(locale, allowed) {
            const regionMatch = locale.match(/[-_]([A-Za-z]{2})$/)

            if (regionMatch) {
                const region = regionMatch[1].toUpperCase()

                if (allowed.has(region)) {
                    return region
                }
            }

            const language = locale.slice(0, 2).toLowerCase()
            const mapped = this.languageCountryMap?.[language]

            if (mapped && allowed.has(mapped)) {
                return mapped
            }

            return null
        },

        get selectedCountry() {
            const countryCode = this.state ?? this.defaultCountry

            if (! countryCode) {
                return null
            }

            return this.countries.find((country) => country.code === countryCode)
                ?? (this.selectedCountrySeed?.code === countryCode ? this.selectedCountrySeed : null)
                ?? null
        },

        get isEmpty() {
            return ! this.state
        },

        selectCountry(code) {
            if (this.isLocked) {
                return
            }

            this.state = code
            this.closeMenu()
        },

        async toggleMenu() {
            if (this.isLocked) {
                return
            }

            const willOpen = ! this.menuOpen

            if (willOpen) {
                await this.ensureCountriesLoaded()
            }

            this.menuOpen = willOpen

            if (this.menuOpen && this.searchable) {
                this.$nextTick(() => {
                    this.$refs.countrySearch?.focus()
                })
            }
        },

        closeMenu() {
            this.closeTeleportedMenu()
        },

        preloadSelectedCountryFlag() {
            const url = this.selectedCountry?.flag_url

            if (! url) {
                return
            }

            const image = new Image()
            image.src = url
        },

    }
}
