let libPhoneNumberModule = null
let libPhoneNumberPromise = null

async function loadLibPhoneNumber() {
    if (libPhoneNumberModule) {
        return libPhoneNumberModule
    }

    if (! libPhoneNumberPromise) {
        libPhoneNumberPromise = import('libphonenumber-js').then((module) => {
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

export default function phoneFieldFormComponent({
    state,
    statePath,
    countries,
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
        countries,
        defaultCountry,
        disabled,
        readOnly,
        showInternationalPrefix,
        searchable,
        searchPlaceholder,
        countryLabel,
        countryOpen: false,
        countrySearch: '',
        inputValue: initialInputValue ?? '',
        countryMenuReady: false,
        countryMenuScrollHandler: null,
        countryMenuResizeHandler: null,
        phoneLibReady: false,
        resolvedDialPrefix: null,

        get isLocked() {
            return this.disabled || this.readOnly
        },

        init() {
            this.ensurePhoneState()
            this.syncInputFromState()
            this.preloadSelectedCountryFlag()

            this.$watch('state.country', () => {
                this.ensurePhoneState()
                this.syncInputFromState()
                this.preloadSelectedCountryFlag()
                this.refreshDialPrefix()

                if (this.state.national) {
                    this.syncCurrentInput()
                }
            })

            this.$watch('countryOpen', (open) => {
                if (open) {
                    this.scheduleCountryMenuPosition()
                    this.bindCountryMenuListeners()

                    return
                }

                this.countryMenuReady = false
                this.unbindCountryMenuListeners()
            })
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
                ?? this.countries[0]
                ?? {
                    code: this.defaultCountry,
                    name: this.defaultCountry,
                    dial_code: '',
                    flag_url: '',
                }
        },

        get filteredCountries() {
            const query = this.countrySearch.trim().toLowerCase()

            if (! query) {
                return this.countries
            }

            return this.countries.filter((country) => {
                return country.name.toLowerCase().includes(query)
                    || country.code.toLowerCase().includes(query)
                    || country.dial_code.includes(query.replace('+', ''))
            })
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

            await this.ensurePhoneLibLoaded()

            this.countryOpen = ! this.countryOpen

            if (this.countryOpen && this.searchable) {
                this.$nextTick(() => {
                    this.$refs.countrySearch?.focus()
                })
            }
        },

        closeCountryMenu() {
            this.countryOpen = false
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

        scheduleCountryMenuPosition() {
            this.countryMenuReady = false

            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    this.updateCountryMenuPosition()

                    requestAnimationFrame(() => {
                        this.updateCountryMenuPosition()
                    })
                })
            })
        },

        updateCountryMenuPosition() {
            const trigger = this.$refs.countryTrigger
            const menu = this.$refs.countryMenu

            if (! trigger || ! menu) {
                return
            }

            this.applyCountryMenuTheme(menu)

            const rect = trigger.getBoundingClientRect()
            const gap = 12
            const viewportPadding = 16
            const menuWidth = Math.min(288, window.innerWidth - (viewportPadding * 2))

            let top = rect.bottom + gap
            let left = rect.left

            menu.style.position = 'fixed'
            menu.style.width = `${Math.round(menuWidth)}px`
            menu.style.zIndex = '80'
            menu.style.top = `${Math.round(top)}px`
            menu.style.left = `${Math.round(left)}px`

            const menuRect = menu.getBoundingClientRect()

            if (menuRect.bottom > window.innerHeight - viewportPadding) {
                const aboveTop = rect.top - menuRect.height - gap

                if (aboveTop >= viewportPadding) {
                    top = aboveTop
                }
            }

            if (left + menuRect.width > window.innerWidth - viewportPadding) {
                left = window.innerWidth - menuRect.width - viewportPadding
            }

            if (left < viewportPadding) {
                left = viewportPadding
            }

            menu.style.top = `${Math.round(top)}px`
            menu.style.left = `${Math.round(left)}px`
            this.countryMenuReady = true
        },

        applyCountryMenuTheme(menu) {
            const isDark = document.documentElement.classList.contains('dark')
            const blur = 'blur(16px) saturate(180%)'

            if (isDark) {
                menu.style.setProperty('--fff-select-menu-bg', '#27272a3d')
                menu.style.setProperty('--fff-select-menu-border', 'rgb(255 255 255 / 0.12)')
                menu.style.setProperty('--fff-select-menu-shadow', '0 4px 6px -1px rgb(0 0 0 / 0.28), 0 12px 28px -6px rgb(0 0 0 / 0.5)')
                menu.style.setProperty('--fff-select-menu-hover', 'rgb(63 63 70 / 0.82)')
                menu.style.setProperty('--fff-select-menu-selected', 'rgb(82 82 91 / 0.88)')
                menu.style.setProperty('--fff-select-search-bg', 'rgb(39 39 42 / 0.55)')
                menu.style.setProperty('--fff-select-search-border', 'rgb(63 63 70)')
            } else {
                menu.style.setProperty('--fff-select-menu-bg', '#ffffffa3')
                menu.style.setProperty('--fff-select-menu-border', 'rgb(228 228 231 / 0.65)')
                menu.style.setProperty('--fff-select-menu-shadow', '0 4px 6px -1px rgb(0 0 0 / 0.06), 0 12px 28px -6px rgb(0 0 0 / 0.12)')
                menu.style.setProperty('--fff-select-menu-hover', 'rgb(244 244 245 / 0.72)')
                menu.style.setProperty('--fff-select-menu-selected', 'rgb(228 228 231 / 0.78)')
                menu.style.setProperty('--fff-select-search-bg', 'rgb(255 255 255 / 0.55)')
                menu.style.setProperty('--fff-select-search-border', 'rgb(228 228 231)')
            }

            menu.style.backgroundColor = isDark ? '#27272a3d' : '#ffffffa3'
            menu.style.setProperty('backdrop-filter', blur)
            menu.style.setProperty('-webkit-backdrop-filter', blur)
        },

        bindCountryMenuListeners() {
            if (this.countryMenuScrollHandler) {
                return
            }

            this.countryMenuScrollHandler = () => this.updateCountryMenuPosition()
            this.countryMenuResizeHandler = () => this.updateCountryMenuPosition()

            window.addEventListener('scroll', this.countryMenuScrollHandler, true)
            window.addEventListener('resize', this.countryMenuResizeHandler)
        },

        unbindCountryMenuListeners() {
            if (! this.countryMenuScrollHandler) {
                return
            }

            window.removeEventListener('scroll', this.countryMenuScrollHandler, true)
            window.removeEventListener('resize', this.countryMenuResizeHandler)

            this.countryMenuScrollHandler = null
            this.countryMenuResizeHandler = null
        },
    }
}
