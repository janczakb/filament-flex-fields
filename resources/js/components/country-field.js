import { createSearchableSelectMenuMixin } from '../core/searchable-select-menu.js'

const selectMenu = createSearchableSelectMenuMixin({
    triggerRef: 'countryTrigger',
    menuRef: 'countryMenu',
})

export default function countryFieldFormComponent({
    state,
    statePath,
    countries,
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
    allowedCountryCodes,
    initialState = null,
}) {
    return {
        state,
        statePath,
        countries,
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
        allowedCountryCodes,
        initialState,
        displayReady: false,
        menuOpen: false,
        countrySearch: '',
        menuReady: false,
        menuScrollHandler: null,
        menuResizeHandler: null,
        ...selectMenu,

        get isLocked() {
            return this.disabled || this.readOnly
        },

        init() {
            this.applyBrowserLocaleDefault()
            this.preloadSelectedCountryFlag()

            this.$nextTick(() => {
                this.displayReady = true
            })

            this.$watch('state', () => {
                this.preloadSelectedCountryFlag()
            })

            this.bindSelectMenuLifecycle()
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
            const allowed = new Set(this.allowedCountryCodes ?? this.countries.map((country) => country.code))
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
                ?? this.countries[0]
                ?? null
        },

        get isEmpty() {
            return ! this.state
        },

        get filteredCountries() {
            const query = this.countrySearch.trim().toLowerCase()

            if (! query) {
                return this.countries
            }

            return this.countries.filter((country) => {
                const dialCode = country.dial_code ?? ''

                return country.name.toLowerCase().includes(query)
                    || country.code.toLowerCase().includes(query)
                    || dialCode.includes(query.replace('+', ''))
            })
        },

        selectCountry(code) {
            if (this.isLocked) {
                return
            }

            this.state = code
            this.closeMenu()
        },

        toggleMenu() {
            if (this.isLocked) {
                return
            }

            this.menuOpen = ! this.menuOpen

            if (this.menuOpen && this.searchable) {
                this.$nextTick(() => {
                    this.$refs.countrySearch?.focus()
                })
            }
        },

        closeMenu() {
            this.menuOpen = false
            this.countrySearch = ''
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
