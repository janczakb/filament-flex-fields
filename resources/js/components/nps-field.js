export default function npsFieldFormComponent({
    state,
    optionKeys,
    disabledOptions,
    disabled,
    required,
    usesIndicator = false,
}) {
    return {
        state,
        optionKeys,
        disabledOptions,
        disabled,
        required,
        usesIndicator,
        indicatorStyle: '',
        indicatorAnimated: false,
        indicatorHydrated: false,
        indicatorShouldSnap: false,
        resizeObserver: null,

        normalize(value) {
            return value === null || value === undefined ? null : String(value)
        },

        hasSelection() {
            return this.normalize(this.state) !== null
        },

        isSelected(value) {
            if (! this.hasSelection()) {
                return false
            }

            return this.normalize(this.state) === this.normalize(value)
        },

        isOptionDisabled(value) {
            return this.disabledOptions[this.normalize(value)] ?? false
        },

        canSelect(value) {
            return ! this.disabled && ! this.isOptionDisabled(value)
        },

        select(value) {
            if (! this.canSelect(value)) {
                return
            }

            const hadSelection = this.hasSelection()
            const willDeselect = ! this.required && this.isSelected(value)

            if (this.usesIndicator) {
                // Snap when appearing from empty — animating from (0, 0) causes a visible jump.
                this.indicatorShouldSnap = ! hadSelection && ! willDeselect
            }

            if (willDeselect) {
                this.state = null
            } else {
                this.state = value
            }
        },

        applyIndicatorStyle(selected, snap = false) {
            const style =
                'width: ' + selected.offsetWidth + 'px;' +
                'height: ' + selected.offsetHeight + 'px;' +
                'transform: translate3d(' + selected.offsetLeft + 'px, ' + selected.offsetTop + 'px, 0);' +
                'opacity: 1;'

            if (snap && this.indicatorAnimated) {
                this.indicatorAnimated = false
                this.indicatorStyle = style
                this.indicatorHydrated = true

                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        this.indicatorAnimated = true
                    })
                })

                return
            }

            this.indicatorStyle = style
            this.indicatorHydrated = true
        },

        updateIndicator(snap = false) {
            if (! this.usesIndicator) {
                return
            }

            const track = this.$refs.track

            if (! track) {
                return
            }

            const selected = track.querySelector('[data-segment-selected=true]')

            if (! selected) {
                this.indicatorStyle = 'opacity: 0;'
                this.indicatorHydrated = false

                return
            }

            this.applyIndicatorStyle(selected, snap)
        },

        enableIndicatorAnimation() {
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    this.indicatorAnimated = true
                })
            })
        },

        init() {
            if (! this.usesIndicator) {
                return
            }

            this.$watch('state', () => {
                this.$nextTick(() => {
                    const snap = this.indicatorShouldSnap

                    this.indicatorShouldSnap = false
                    this.updateIndicator(snap)
                })
            })

            this.$nextTick(() => {
                this.updateIndicator()
                this.enableIndicatorAnimation()
            })

            if (typeof ResizeObserver === 'undefined' || ! this.$refs.track) {
                return
            }

            this.resizeObserver = new ResizeObserver(() => this.updateIndicator())
            this.resizeObserver.observe(this.$refs.track)
        },
    }
}
