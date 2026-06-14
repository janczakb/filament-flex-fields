/** Bullet mask — matches CreditCardDisplay::MASK_CHAR in PHP. */
export const CARD_MASK_CHAR = '\u2022'

const BRANDS = {
    visa: /^4/,
    mastercard: /^(5[1-5]|2(2[2-9]\d{2}|[3-6]\d{3}|7[01]\d{2}|720))/,
}

export function detectCardBrand(number) {
    const digits = String(number ?? '').replace(/\D/g, '')

    if (digits === '') {
        return 'unknown'
    }

    if (BRANDS.visa.test(digits)) {
        return 'visa'
    }

    if (BRANDS.mastercard.test(digits)) {
        return 'mastercard'
    }

    return 'unknown'
}

export function formatCardNumberInput(value) {
    const digits = String(value ?? '').replace(/\D/g, '').slice(0, 19)
    const groups = digits.match(/.{1,4}/g) ?? []

    return groups.join(' ')
}

export function formatExpiryInput(value) {
    const digits = String(value ?? '').replace(/\D/g, '').slice(0, 4)

    if (digits.length <= 2) {
        return digits
    }

    return `${digits.slice(0, 2)}/${digits.slice(2)}`
}

export function displayCardNumber(number) {
    const digits = String(number ?? '').replace(/\D/g, '')
    const parts = []
    const mask = CARD_MASK_CHAR.repeat(4)

    for (let index = 0; index < 16; index += 4) {
        const chunk = digits.slice(index, index + 4)
        parts.push((chunk + mask).slice(0, 4))
    }

    return parts.join(' ')
}

export default function creditCardFormComponent({
    state,
    statePath,
    initialNumber = '',
    initialName = '',
    initialExpiry = '',
    initialCvv = '',
    flipOnCvvFocus,
    disabled,
}) {
    return {
        state,
        statePath,
        initialNumber,
        initialName,
        initialExpiry,
        initialCvv,
        cvv: '',
        flipOnCvvFocus,
        disabled,
        isFlipped: false,

        init() {
            if (! this.state || typeof this.state !== 'object' || Array.isArray(this.state)) {
                this.state = {
                    number: String(this.initialNumber ?? '').replace(/\D/g, '').slice(0, 19),
                    name: String(this.initialName ?? ''),
                    expiry: String(this.initialExpiry ?? ''),
                }
            }

            this.state.number = String(this.state.number ?? this.initialNumber ?? '').replace(/\D/g, '').slice(0, 19)
            this.state.name = String(this.state.name ?? this.initialName ?? '')
            this.state.expiry = String(this.state.expiry ?? this.initialExpiry ?? '')

            if ('cvv' in this.state) {
                this.cvv = String(this.state.cvv ?? this.initialCvv ?? '').replace(/\D/g, '').slice(0, 4)
                delete this.state.cvv
            } else {
                this.cvv = String(this.initialCvv ?? '').replace(/\D/g, '').slice(0, 4)
            }
        },

        syncCvvForValidation() {
            if (! this.statePath) {
                return
            }

            this.$wire.set(`${this.statePath}.cvv`, this.cvv)
        },

        get brand() {
            return detectCardBrand(this.resolvedNumber)
        },

        get resolvedNumber() {
            return String(this.state?.number ?? this.initialNumber ?? '').replace(/\D/g, '').slice(0, 19)
        },

        get resolvedName() {
            return String(this.state?.name ?? this.initialName ?? '')
        },

        get resolvedExpiry() {
            return String(this.state?.expiry ?? this.initialExpiry ?? '')
        },

        get resolvedCvv() {
            return String(this.cvv ?? this.initialCvv ?? '').replace(/\D/g, '').slice(0, 4)
        },

        get formattedNumberInput() {
            return formatCardNumberInput(this.resolvedNumber)
        },

        get displayNumber() {
            return displayCardNumber(this.resolvedNumber)
        },

        get displayName() {
            const name = this.resolvedName.trim()

            return name !== '' ? name.toUpperCase() : 'YOUR NAME'
        },

        get displayExpiry() {
            const expiry = this.resolvedExpiry.trim()

            return expiry !== '' ? expiry : 'MM/YY'
        },

        get displayCvv() {
            const cvv = this.resolvedCvv

            if (cvv === '') {
                return CARD_MASK_CHAR.repeat(3)
            }

            const length = cvv.startsWith('3') ? 4 : 3

            return cvv.padEnd(length, CARD_MASK_CHAR).slice(0, length)
        },

        onNumberInput(event) {
            const digits = event.target.value.replace(/\D/g, '').slice(0, 19)
            this.state.number = digits
            event.target.value = formatCardNumberInput(digits)
        },

        onNameInput(event) {
            this.state.name = event.target.value
        },

        onExpiryInput(event) {
            const formatted = formatExpiryInput(event.target.value)
            this.state.expiry = formatted
            event.target.value = formatted
        },

        onCvvInput(event) {
            const maxLength = this.brand === 'unknown' && String(this.resolvedNumber ?? '').startsWith('3') ? 4 : 3
            const digits = event.target.value.replace(/\D/g, '').slice(0, maxLength)
            this.cvv = digits
            event.target.value = digits
            this.syncCvvForValidation()
        },

        onCvvFocus() {
            if (this.flipOnCvvFocus) {
                this.isFlipped = true
            }
        },

        onCvvBlur() {
            if (this.flipOnCvvFocus) {
                this.isFlipped = false
            }
        },

        flipCard() {
            this.isFlipped = ! this.isFlipped
        },
    }
}
