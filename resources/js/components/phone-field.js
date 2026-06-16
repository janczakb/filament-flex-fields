import { createSearchableSelectMenuMixin } from '../core/searchable-select-menu.js'
import { createVirtualizedListMixin } from '../core/virtualized-list.js'
import { resolveCountriesFromRegistry, resetCountryRegistryCache } from '../core/country-registry.js'
import { createCountrySearchMixin } from '../core/country-search.js'
import { createCountryListKeyboardMixin } from '../core/country-list-keyboard.js'

let libPhoneNumberModule = null
let libPhoneNumberPromise = null

async function loadLibPhoneNumber() {
    if (libPhoneNumberModule) {
        return libPhoneNumberModule
    }

    if (! libPhoneNumberPromise) {
        libPhoneNumberPromise = import('libphonenumber-js/min').then((module) => {
            libPhoneNumberModule = module

            return module
        })
    }

    return libPhoneNumberPromise
}

export async function formatNationalDisplay(national, country) {
    const { parsePhoneNumberFromString, AsYouType } = await loadLibPhoneNumber()
    const digits = String(national ?? '').replace(/\D/g, '')

    if (digits === '') {
        return ''
    }

    const parsed = parsePhoneNumberFromString(digits, country)

    if (parsed) {
        return parsed.formatNational()
    }

    return new AsYouType(country).input(digits)
}

export async function formatPhoneInput(value, country) {
    const { AsYouType } = await loadLibPhoneNumber()
    const formatter = new AsYouType(country)

    return formatter.input(String(value ?? ''))
}

export async function syncPhoneState(rawInput, country) {
    const { AsYouType, parsePhoneNumberFromString } = await loadLibPhoneNumber()
    const digits = String(rawInput ?? '').replace(/\D/g, '')

    if (digits === '') {
        return {
            national: '',
            e164: '',
            formatted: '',
        }
    }

    const formatter = new AsYouType(country)
    const formatted = formatter.input(rawInput)
    const parsed = formatter.getNumber() ?? parsePhoneNumberFromString(rawInput, country)

    return {
        national: parsed?.nationalNumber ?? digits,
        e164: parsed?.number ?? '',
        formatted,
    }
}

const selectMenu = createSearchableSelectMenuMixin({
    openKey: 'countryOpen',
    readyKey: 'countryMenuReady',
    scrollHandlerKey: 'countryMenuScrollHandler',
    resizeHandlerKey: 'countryMenuResizeHandler',
    triggerRef: 'countryTrigger',
    menuRef: 'countryMenu',
    minMenuWidth: 288,
    matchTriggerWidth: false,
    menuGap: 12,
    closeMethod: 'closeCountryMenu',
    ownerIdPrefix: 'fff-phone-country',
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
    openKey: 'countryOpen',
    optionIdPrefix: 'fff-phone-field-option',
})

export default function phoneFieldFormComponent({
    state,
    statePath,
    countryPool,
    countryFilterKey,
    sortPreferredFirst,
    preferredCountryCode,
    selectedCountrySeed,
    defaultCountry,
    initialInputValue = '',
    disabled,
    readOnly,
    showInternationalPrefix,
    searchable,
    searchPlaceholder,
    countryLabel,
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
        showInternationalPrefix,
        searchable,
        searchPlaceholder,
        countryLabel,
        countries: [],
        countriesLoaded: false,
        countriesLoading: false,
        countryOpen: false,
        countrySearch: '',
        inputValue: initialInputValue ?? '',
        countryMenuReady: false,
        countryMenuScrollHandler: null,
        countryMenuResizeHandler: null,
        phoneLibReady: false,
        resolvedDialPrefix: null,
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
            this.ensurePhoneState()
            this.syncInputFromState()
            this.preloadSelectedCountryFlag()
            this.bindSelectMenuLifecycle()

            this.$watch('state.country', () => {
                this.ensurePhoneState()
                this.syncInputFromState()
                this.preloadSelectedCountryFlag()
                this.refreshDialPrefix()

                if (this.state.national) {
                    this.syncCurrentInput()
                }
            })

            document.addEventListener('livewire:navigated', () => {
                resetCountryRegistryCache()
                this.countries = []
                this.countriesLoaded = false
            })
        },

        getActiveCountryCode() {
            return this.state?.country ?? this.defaultCountry
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

        async ensurePhoneLibLoaded() {
            if (this.phoneLibReady) {
                return
            }

            await loadLibPhoneNumber()
            this.phoneLibReady = true
        },

        async syncCurrentInput() {
            await this.ensurePhoneLibLoaded()

            const synced = await syncPhoneState(this.inputValue, this.state.country)

            this.inputValue = synced.formatted
            this.state.national = synced.national
            this.state.e164 = synced.e164
        },

        get dialPrefix() {
            return this.resolvedDialPrefix ?? this.selectedCountry?.dial_code ?? ''
        },

        async refreshDialPrefix() {
            await this.ensurePhoneLibLoaded()

            const country = this.state?.country ?? this.defaultCountry

            try {
                const { getCountryCallingCode } = await loadLibPhoneNumber()

                this.resolvedDialPrefix = `+${getCountryCallingCode(country)}`
            } catch (error) {
                this.resolvedDialPrefix = this.selectedCountry?.dial_code ?? ''
            }
        },

        ensurePhoneState() {
            if (! this.state || typeof this.state !== 'object') {
                return
            }

            if (! this.state.country) {
                this.state.country = this.defaultCountry
            }

            if (this.state.national === undefined || this.state.national === null) {
                this.state.national = ''
            }

            if (this.state.e164 === undefined || this.state.e164 === null) {
                this.state.e164 = ''
            }
        },

        get selectedCountry() {
            const countryCode = this.state?.country ?? this.defaultCountry

            return this.countries.find((country) => country.code === countryCode)
                ?? (this.selectedCountrySeed?.code === countryCode ? this.selectedCountrySeed : null)
                ?? {
                    code: this.defaultCountry,
                    name: this.defaultCountry,
                    dial_code: '',
                    flag_url: '',
                }
        },

        async getDialPrefix() {
            await this.refreshDialPrefix()

            return this.dialPrefix
        },

        async syncInputFromState() {
            if (! this.state?.national) {
                if (! this.inputValue) {
                    this.inputValue = ''
                }

                return
            }

            await this.ensurePhoneLibLoaded()

            const country = this.state.country ?? this.defaultCountry

            this.inputValue = await formatNationalDisplay(this.state.national, country)
        },

        async onInput(event) {
            if (this.isLocked) {
                return
            }

            await this.ensurePhoneLibLoaded()

            const country = this.state.country ?? this.defaultCountry
            const synced = await syncPhoneState(event.target.value, country)

            this.inputValue = synced.formatted
            this.state.national = synced.national
            this.state.e164 = synced.e164
        },

        async onPhoneFocus() {
            await this.ensurePhoneLibLoaded()
        },

        async selectCountry(code) {
            if (this.isLocked) {
                return
            }

            await this.ensurePhoneLibLoaded()

            this.state.country = code
            this.closeCountryMenu()

            if (this.state.national) {
                const synced = await syncPhoneState(this.inputValue, code)

                this.inputValue = synced.formatted
                this.state.national = synced.national
                this.state.e164 = synced.e164
            }

            this.$nextTick(() => {
                this.$refs.phoneInput?.focus()
            })
        },

        async toggleCountryMenu() {
            if (this.isLocked) {
                return
            }

            const willOpen = ! this.countryOpen

            if (willOpen) {
                await Promise.all([
                    this.ensureCountriesLoaded(),
                    this.ensurePhoneLibLoaded(),
                ])
            }

            this.countryOpen = willOpen

            if (this.countryOpen && this.searchable) {
                this.$nextTick(() => {
                    this.$refs.countrySearch?.focus()
                })
            }
        },

        closeCountryMenu() {
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
