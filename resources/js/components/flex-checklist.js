export default function flexChecklistFormComponent({
    state,
    initialSelected,
    disabledOptions,
    disabled,
    maxSelections,
}) {
    return {
        state,
        initialSelected,
        disabledOptions,
        disabled,
        maxSelections,

        normalize(value) {
            return String(value)
        },

        selectedValues() {
            if (Array.isArray(this.state)) {
                return this.state.map((value) => this.normalize(value))
            }

            return this.initialSelected.map((value) => this.normalize(value))
        },

        isSelected(value) {
            return this.selectedValues().includes(this.normalize(value))
        },

        isOptionDisabled(value) {
            return this.disabledOptions[this.normalize(value)] ?? false
        },

        isMaxReached() {
            return this.maxSelections !== null && this.selectedValues().length >= this.maxSelections
        },

        canToggle(value) {
            if (this.disabled || this.isOptionDisabled(value)) {
                return false
            }

            if (this.isSelected(value)) {
                return true
            }

            return ! this.isMaxReached()
        },

        toggle(value) {
            if (! this.canToggle(value)) {
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
    }
}
