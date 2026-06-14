export default function flexVerificationCodeFormComponent({
    state,
    length,
    allowedCharacters,
    disabled,
    autoSubmitEnabled,
    autoSubmitMethod,
    autoSubmitUsesServerCallback,
}) {
    return {
        state,
        length,
        allowedCharacters,
        disabled,
        autoSubmitEnabled: Boolean(autoSubmitEnabled),
        autoSubmitMethod: autoSubmitMethod || null,
        autoSubmitUsesServerCallback: Boolean(autoSubmitUsesServerCallback),
        autoSubmitPending: false,
        chars: [],
        isUpdatingInputs: false,
        initialized: false,

        init() {
            if (this.initialized) {
                return
            }

            this.initialized = true
            this.syncCharsFromState(this.state, true)
        },

        syncCharsFromState(value, updateInputs = false) {
            const normalized = this.normalizeValue(value)
            const nextChars = Array.from({ length: this.length }, (_, index) => normalized[index] ?? '')
            const hasSameChars = this.chars.length === this.length
                && this.chars.every((character, index) => character === nextChars[index])

            if (! hasSameChars) {
                this.chars = nextChars
            }

            if (updateInputs) {
                this.writeInputValues(nextChars)
            }
        },

        writeInputValues(nextChars) {
            this.isUpdatingInputs = true

            for (let index = 0; index < this.length; index++) {
                const input = this.$refs[`input${index}`]

                if (! input) {
                    continue
                }

                const nextValue = nextChars[index] ?? ''

                if (input.value !== nextValue) {
                    input.value = nextValue
                }
            }

            requestAnimationFrame(() => {
                this.isUpdatingInputs = false
            })
        },

        normalizeValue(value) {
            const source = String(value ?? '')
            const pattern = this.allowedCharacters === 'numeric' ? /[0-9]/ : /[A-Za-z0-9]/
            const characters = []

            for (const character of source) {
                if (! pattern.test(character)) {
                    continue
                }

                characters.push(
                    this.allowedCharacters === 'numeric'
                        ? character
                        : character.toUpperCase(),
                )

                if (characters.length >= this.length) {
                    break
                }
            }

            return characters.join('')
        },

        isAllowedCharacter(character) {
            if (this.allowedCharacters === 'numeric') {
                return /^[0-9]$/.test(character)
            }

            return /^[A-Za-z0-9]$/.test(character)
        },

        formatCharacter(character) {
            if (! this.isAllowedCharacter(character)) {
                return null
            }

            return this.allowedCharacters === 'numeric'
                ? character
                : character.toUpperCase()
        },

        focusInput(index, shouldSelect = false) {
            const input = this.$refs[`input${index}`]

            if (! input || this.disabled) {
                return
            }

            input.focus()

            if (shouldSelect) {
                input.select()
            }
        },

        updateState() {
            const nextState = this.chars.join('')

            if (this.state === nextState) {
                this.maybeAutoSubmit()

                return
            }

            this.state = nextState
            this.maybeAutoSubmit()
        },

        maybeAutoSubmit() {
            if (! this.autoSubmitEnabled || this.autoSubmitUsesServerCallback) {
                return
            }

            if (! this.autoSubmitMethod || this.autoSubmitPending) {
                return
            }

            if (this.disabled) {
                return
            }

            const code = this.chars.join('')

            if (code.length !== this.length) {
                return
            }

            this.autoSubmitPending = true

            this.$wire.call(this.autoSubmitMethod, code).finally(() => {
                this.autoSubmitPending = false
            })
        },

        replaceCharacter(index, character) {
            if (index < 0 || index >= this.length) {
                return false
            }

            if (this.chars.length !== this.length) {
                this.chars = Array.from({ length: this.length }, (_, currentIndex) => this.chars[currentIndex] ?? '')
            }

            const nextCharacter = character ?? ''

            if ((this.chars[index] ?? '') === nextCharacter) {
                return false
            }

            this.chars = this.chars.map((current, currentIndex) => (
                currentIndex === index ? nextCharacter : current
            ))

            this.updateState()

            return true
        },

        handleInput(index, event) {
            if (this.disabled || this.isUpdatingInputs) {
                return
            }

            const raw = event.target.value
            const lastCharacter = raw.slice(-1)
            const formatted = this.formatCharacter(lastCharacter)

            if (formatted === null) {
                event.target.value = this.chars[index] ?? ''

                return
            }

            if (formatted === (this.chars[index] ?? '')) {
                event.target.value = formatted

                return
            }

            event.target.value = formatted
            this.replaceCharacter(index, formatted)

            if (index < this.length - 1) {
                this.focusInput(index + 1)
            }
        },

        handleKeydown(index, event) {
            if (this.disabled || this.isUpdatingInputs) {
                return
            }

            if (event.key === 'Backspace') {
                if (this.chars[index]) {
                    event.preventDefault()
                    this.replaceCharacter(index, '')
                    event.target.value = ''

                    return
                }

                if (index > 0) {
                    event.preventDefault()
                    this.replaceCharacter(index - 1, '')
                    this.$refs[`input${index - 1}`].value = ''
                    this.focusInput(index - 1)
                }

                return
            }

            if (event.key === 'ArrowLeft' && index > 0) {
                event.preventDefault()
                this.focusInput(index - 1)

                return
            }

            if (event.key === 'ArrowRight' && index < this.length - 1) {
                event.preventDefault()
                this.focusInput(index + 1)

                return
            }

            if (event.key === 'Home') {
                event.preventDefault()
                this.focusInput(0)

                return
            }

            if (event.key === 'End') {
                event.preventDefault()
                this.focusInput(this.length - 1)
            }
        },

        handleFocus(index, event) {
            if (this.disabled || this.isUpdatingInputs) {
                return
            }

            if (event?.target?.value) {
                event.target.select()
            }
        },

        handlePaste(event) {
            if (this.disabled || this.isUpdatingInputs) {
                return
            }

            event.preventDefault()

            const pasted = event.clipboardData?.getData('text') ?? ''
            const normalized = this.normalizeValue(pasted)

            if (normalized === '') {
                return
            }

            const nextChars = Array.from({ length: this.length }, (_, index) => normalized[index] ?? '')

            this.chars = nextChars
            this.updateState()
            this.writeInputValues(nextChars)

            const nextEmptyIndex = nextChars.findIndex((character) => character === '')

            this.$nextTick(() => {
                this.focusInput(nextEmptyIndex === -1 ? this.length - 1 : nextEmptyIndex)
            })
        },
    }
}
