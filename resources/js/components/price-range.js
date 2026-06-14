import { cssVarPx, resolveBarCount, resamplePeaks } from '../core/dynamic-bars.js'

export default function priceRangeFormComponent({
    state,
    initialState = null,
    minBound,
    maxBound,
    step,
    integer,
    decimalPlaces,
    prefix,
    histogram,
    disabled,
}) {
    return {
        state,
        initialState,
        minBound,
        maxBound,
        step,
        integer,
        decimalPlaces,
        prefix,
        histogram,
        disabled,
        displayHistogram: [],
        activeThumb: null,
        dragMin: null,
        dragMax: null,
        chartResizeObserver: null,

        init() {
            this.ensureState()
            this.$watch('state', () => this.ensureState())

            this.$nextTick(() => {
                this.setupChartObserver()
                this.updateChartBars()
            })
        },

        destroy() {
            this.chartResizeObserver?.disconnect()
        },

        setupChartObserver() {
            const chart = this.$refs.chart

            if (! chart || this.chartResizeObserver) {
                return
            }

            this.chartResizeObserver = new ResizeObserver(() => {
                this.updateChartBars()
            })

            this.chartResizeObserver.observe(chart)
        },

        updateChartBars() {
            const chart = this.$refs.chart

            if (! chart) {
                return
            }

            const width = chart.clientWidth

            if (! width) {
                return
            }

            const idealBarWidthPx = cssVarPx(chart, '--fff-price-range-bar-w', 4)
            const gapPx = cssVarPx(chart, '--fff-price-range-chart-gap', 2)
            const count = resolveBarCount(width, {
                idealBarWidthPx,
                gapPx,
                minBars: 8,
                maxBars: Math.max(this.histogram.length, 64),
            })

            this.displayHistogram = resamplePeaks(this.histogram, count)
        },

        ensureState(changed = null) {
            if (! this.state || typeof this.state !== 'object') {
                this.state = {
                    min: this.initialState?.min ?? this.minBound,
                    max: this.initialState?.max ?? this.maxBound,
                }
            }

            if (this.state.min === undefined || this.state.min === null) {
                this.state.min = this.initialState?.min ?? this.minBound
            }

            if (this.state.max === undefined || this.state.max === null) {
                this.state.max = this.initialState?.max ?? this.maxBound
            }

            this.state.min = this.normalize(this.state.min ?? this.minBound)
            this.state.max = this.normalize(this.state.max ?? this.maxBound)

            if (this.state.min > this.state.max) {
                if (changed === 'max') {
                    this.state.min = this.state.max
                } else {
                    this.state.max = this.state.min
                }
            }
        },

        normalize(value) {
            const numeric = Number(value)

            if (Number.isNaN(numeric)) {
                return this.minBound
            }

            const clamped = Math.min(this.maxBound, Math.max(this.minBound, numeric))
            const stepped = Math.round(clamped / this.step) * this.step

            if (this.integer) {
                return Math.round(stepped)
            }

            if (this.decimalPlaces === null) {
                return stepped
            }

            return Number(stepped.toFixed(this.decimalPlaces))
        },

        get minValue() {
            return this.dragMin ?? this.normalize(this.state?.min ?? this.minBound)
        },

        get maxValue() {
            return this.dragMax ?? this.normalize(this.state?.max ?? this.maxBound)
        },

        get rangeSpan() {
            return Math.max(this.maxBound - this.minBound, 1)
        },

        get minPercent() {
            return ((this.minValue - this.minBound) / this.rangeSpan) * 100
        },

        get maxPercent() {
            return ((this.maxValue - this.minBound) / this.rangeSpan) * 100
        },

        get fillStyle() {
            return `left: ${this.minPercent}%; width: ${Math.max(0, this.maxPercent - this.minPercent)}%`
        },

        isBarInRange(index) {
            const count = this.displayHistogram.length

            if (count === 0) {
                return false
            }

            const bucketSize = this.rangeSpan / count
            const bucketMin = this.minBound + (index * bucketSize)
            const bucketMax = bucketMin + bucketSize

            return bucketMax >= this.minValue && bucketMin <= this.maxValue
        },

        formatValue(value) {
            const current = this.normalize(value)
            const formatted = this.integer
                ? String(current)
                : (this.decimalPlaces === null
                    ? String(current)
                    : current.toFixed(this.decimalPlaces))

            return this.prefix ? `${this.prefix}${formatted}` : formatted
        },

        valueFromClientX(clientX) {
            const rect = this.$refs.track.getBoundingClientRect()
            const ratio = Math.min(1, Math.max(0, (clientX - rect.left) / rect.width))

            return this.normalize(this.minBound + (ratio * this.rangeSpan))
        },

        closestThumb(value) {
            const minDistance = Math.abs(value - this.minValue)
            const maxDistance = Math.abs(value - this.maxValue)

            return minDistance <= maxDistance ? 'min' : 'max'
        },

        setThumbValue(thumb, value) {
            if (thumb === 'min') {
                const next = Math.min(value, this.maxValue)
                this.dragMin = next
                this.state.min = next

                return
            }

            const next = Math.max(value, this.minValue)
            this.dragMax = next
            this.state.max = next
        },

        onTrackPointerDown(event) {
            if (this.disabled || event.button !== 0) {
                return
            }

            event.preventDefault()

            const value = this.valueFromClientX(event.clientX)
            this.activeThumb = this.closestThumb(value)
            this.setThumbValue(this.activeThumb, value)
            this.$refs.track.setPointerCapture(event.pointerId)
        },

        onThumbPointerDown(thumb, event) {
            if (this.disabled || event.button !== 0) {
                return
            }

            event.preventDefault()
            event.stopPropagation()

            this.activeThumb = thumb
            this.$refs.track.setPointerCapture(event.pointerId)
        },

        onTrackPointerMove(event) {
            if (! this.activeThumb || this.disabled) {
                return
            }

            this.setThumbValue(this.activeThumb, this.valueFromClientX(event.clientX))
        },

        onTrackPointerUp(event) {
            if (! this.activeThumb) {
                return
            }

            if (this.dragMin !== null) {
                this.state.min = this.dragMin
            }

            if (this.dragMax !== null) {
                this.state.max = this.dragMax
            }

            this.activeThumb = null
            this.dragMin = null
            this.dragMax = null

            if (this.$refs.track.hasPointerCapture(event.pointerId)) {
                this.$refs.track.releasePointerCapture(event.pointerId)
            }
        },

        onMinInput(event) {
            if (this.disabled) {
                return
            }

            this.state.min = this.normalize(event.target.value)
            this.ensureState('min')
            event.target.value = this.state.min
        },

        onMaxInput(event) {
            if (this.disabled) {
                return
            }

            this.state.max = this.normalize(event.target.value)
            this.ensureState('max')
            event.target.value = this.state.max
        },

        thumbStyle(percent) {
            return `left: ${percent}%;`
        },
    }
}
