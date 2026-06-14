export default function segmentControlFormComponent({
    state,
    optionKeys,
    disabledOptions,
    separators,
    disabled,
}) {
    return {
        state,
        optionKeys,
        disabledOptions,
        separators,
        disabled,
        indicatorStyle: '',
        indicatorAnimated: false,
        resizeObserver: null,

        normalize(value) {
            return value === null || value === undefined ? null : String(value)
        },

        isSelected(value) {
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

            this.state = value
            this.$nextTick(() => this.updateIndicator())
        },

        selectedIndex() {
            const current = this.normalize(this.state)

            return this.optionKeys.findIndex((key) => this.normalize(key) === current)
        },

        showSeparator(separatorIndex) {
            if (! this.separators) {
                return false
            }

            const selectedIndex = this.selectedIndex()

            if (selectedIndex === -1) {
                return true
            }

            return separatorIndex !== selectedIndex - 1 && separatorIndex !== selectedIndex
        },

        separatorClass(separatorIndex) {
            return this.showSeparator(separatorIndex) ? '' : 'is-hidden'
        },

        updateIndicator() {
            const track = this.$refs.track

            if (! track) {
                return
            }

            const selected = track.querySelector('[data-segment-selected=true]')

            if (! selected) {
                this.indicatorStyle = 'opacity: 0;'

                return
            }

            this.indicatorStyle =
                'width: ' + selected.offsetWidth + 'px;' +
                'height: ' + selected.offsetHeight + 'px;' +
                'transform: translate3d(' + selected.offsetLeft + 'px, ' + selected.offsetTop + 'px, 0);' +
                'opacity: 1;'
        },

        enableIndicatorAnimation() {
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    this.indicatorAnimated = true
                })
            })
        },

        init() {
            this.$watch('state', () => this.$nextTick(() => this.updateIndicator()))
            this.$nextTick(() => {
                this.updateIndicator()
                this.enableIndicatorAnimation()
            })

            if (typeof ResizeObserver === 'undefined') {
                return
            }

            this.resizeObserver = new ResizeObserver(() => this.updateIndicator())
            this.resizeObserver.observe(this.$refs.track)
        },
    }
}
