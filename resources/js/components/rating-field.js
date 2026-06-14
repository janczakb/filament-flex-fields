export default function ratingFieldFormComponent({
    state,
    disabled,
    readOnly,
    canInteract,
    max,
}) {
    return {
        state,
        hover: null,
        disabled,
        readOnly,
        canInteract,
        max,

        numericState() {
            if (this.state === null || this.state === undefined || this.state === '') {
                return null
            }

            return Number(this.state)
        },

        displayValue() {
            if (! this.canInteract) {
                return this.numericState() ?? 0
            }

            if (this.hover !== null) {
                return this.hover
            }

            return this.numericState() ?? 0
        },

        fillFor(index) {
            return Math.max(0, Math.min(1, this.displayValue() - (index - 1)))
        },

        isSelected(index) {
            const current = this.numericState()

            return current !== null && current === index
        },

        select(index) {
            if (! this.canInteract) {
                return
            }

            this.state = index
            this.hover = null
        },

        preview(index) {
            if (! this.canInteract) {
                return
            }

            this.hover = index
        },

        clearPreview() {
            this.hover = null
        },

        init() {
            this.$nextTick(() => {
                this.$root.classList.add('is-hydrated')
            })
        },
    }
}
