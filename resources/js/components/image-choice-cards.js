export default function imageChoiceCardsFormComponent({
    state,
    multiple,
    disabledOptions,
    disabled,
    rippleEnabled,
    maxSelections,
}) {
    return {
        state,
        multiple,
        disabledOptions,
        disabled,
        rippleEnabled,
        maxSelections,

        init() {
            this.clearDisabledSelection()
            this.$watch('disabledOptions', () => this.clearDisabledSelection())

            this.$nextTick(() => {
                this.$root.classList.add('is-hydrated')
            })
        },

        normalize(value) {
            return value === null || value === undefined ? null : String(value)
        },

        selectedValues() {
            if (! this.multiple) {
                return []
            }

            if (! Array.isArray(this.state)) {
                return []
            }

            return this.state.map((value) => this.normalize(value))
        },

        isSelected(value) {
            const key = this.normalize(value)

            if (this.multiple) {
                return this.selectedValues().includes(key)
            }

            return this.normalize(this.state) === key
        },

        isOptionDisabled(value) {
            return this.disabledOptions[this.normalize(value)] ?? false
        },

        isMaxReached() {
            return this.multiple
                && this.maxSelections !== null
                && this.selectedValues().length >= this.maxSelections
        },

        canSelect(value) {
            return ! this.disabled && ! this.isOptionDisabled(value)
        },

        canToggle(value) {
            if (! this.canSelect(value)) {
                return false
            }

            if (this.isSelected(value)) {
                return true
            }

            return ! this.isMaxReached()
        },

        select(value) {
            if (! this.canSelect(value)) {
                return
            }

            this.state = value
        },

        toggle(value) {
            if (! this.multiple || ! this.canToggle(value)) {
                return
            }

            const key = this.normalize(value)
            const current = this.selectedValues()

            if (current.includes(key)) {
                this.state = current.filter((item) => item !== key)

                return
            }

            this.state = [...current, key]
        },

        clearDisabledSelection() {
            if (this.multiple) {
                const next = this.selectedValues().filter((key) => ! this.isOptionDisabled(key))

                if (next.length !== this.selectedValues().length) {
                    this.state = next
                }

                return
            }

            if (this.state !== null && this.state !== undefined && this.state !== '' && this.isOptionDisabled(this.state)) {
                this.state = null
            }
        },

        ripple(event) {
            if (! this.rippleEnabled) {
                return
            }

            const card = event.currentTarget
            const circle = document.createElement('span')
            const diameter = Math.max(card.clientWidth, card.clientHeight)

            circle.className = 'fff-image-choice-cards__ripple'
            circle.style.width = `${diameter}px`
            circle.style.height = `${diameter}px`
            circle.style.left = `${event.offsetX - (diameter / 2)}px`
            circle.style.top = `${event.offsetY - (diameter / 2)}px`

            card.appendChild(circle)

            window.setTimeout(() => circle.remove(), 650)
        },
    }
}
