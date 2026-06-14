import noUiSlider from 'nouislider'

export default function flexSliderFormComponent({
    arePipsStepped,
    autoFill,
    behavior,
    decimalPlaces,
    fillTrack,
    hasTooltips,
    hideThumbUntilInteraction,
    initialDisplayValue,
    initialFillSegments,
    initialLiveValues,
    initialNormalizedValues,
    initialValueRatios,
    isDisabled,
    isRtl,
    isVertical,
    maxDifference,
    maxValue,
    minDifference,
    minValue,
    nonLinearPoints,
    pipsDensity,
    pipsFilter,
    pipsFormatter,
    pipsMode,
    pipsValues,
    serverRenderedPips,
    prefix,
    rangePadding,
    showValue,
    state,
    step,
    suffix,
    tooltips,
    trackLabel,
    valuePosition,
}) {
    const normalizedInitialValues = [...(initialNormalizedValues ?? [])]
    const normalizedInitialRatios = [...(initialValueRatios ?? [])]

    return {
        state,
        slider: null,
        resizeObserver: null,
        syncChromeFrame: null,
        isHovered: false,
        isThumbHovered: false,
        isDragging: false,
        isSliderReady: false,
        valueRatios: [...normalizedInitialRatios],
        fillSegments: JSON.parse(JSON.stringify(initialFillSegments ?? [])),
        liveValues: [...(initialLiveValues ?? [])],
        liveDisplayValue: initialDisplayValue ?? '',
        hideThumbUntilInteraction,
        hasTooltips,
        prefix,
        suffix,
        decimalPlaces,
        showValue,
        trackLabel,
        valuePosition,
        isVertical,
        initialNormalizedValues: normalizedInitialValues,
        initialValueRatios: normalizedInitialRatios,
        initialFillSegments: JSON.parse(JSON.stringify(initialFillSegments ?? [])),

        init() {
            this.$nextTick(() => this.mountSlider())
        },

        mountSlider() {
            if (this.slider) {
                return
            }

            const track = this.$refs.track

            if (! track) {
                requestAnimationFrame(() => this.mountSlider())

                return
            }

            if (track.offsetWidth === 0 && ! this.isVertical) {
                requestAnimationFrame(() => this.mountSlider())

                return
            }

            const connect = this.resolveConnect()

            this.slider = noUiSlider.create(this.$refs.sliderHost, {
                behaviour: behavior,
                direction: isRtl ? 'rtl' : 'ltr',
                connect: connect === false ? false : connect,
                format: {
                    from: (value) => +value,
                    to: (value) => this.normalizeNumeric(value),
                },
                limit: maxDifference,
                margin: minDifference,
                orientation: isVertical ? 'vertical' : 'horizontal',
                padding: this.resolveSliderPadding(),
                pips: pipsMode && ! serverRenderedPips
                    ? {
                          density: pipsDensity ?? 10,
                          filter: pipsFilter,
                          format: pipsFormatter,
                          mode: pipsMode,
                          stepped: arePipsStepped,
                          values: pipsValues,
                      }
                    : null,
                range: {
                    min: minValue,
                    ...(nonLinearPoints ?? {}),
                    max: maxValue,
                },
                start: this.resolveStartValues(),
                step,
                tooltips: hasTooltips ? false : tooltips,
            })

            if (isDisabled) {
                this.slider.disable()
            }

            this.isSliderReady = true

            requestAnimationFrame(() => {
                this.scheduleSyncChrome()
            })

            this.slider.on('start', () => {
                this.isDragging = true
            })

            this.slider.on('slide', () => {
                this.scheduleSyncChrome()
            })

            this.slider.on('update', () => {
                this.updateLiveValues()

                if (! this.isDragging) {
                    this.scheduleSyncChrome()
                }
            })

            this.slider.on('end', () => {
                this.applyRangeEdgeSnap()
                this.syncChrome()
                this.isDragging = false
            })

            this.slider.on('change', (values) => {
                const normalized = this.snapValuesToRangeEdges(
                    this.normalizeValues(values),
                )

                this.state =
                    normalized.length > 1 ? normalized : normalized[0]

                if (! this.isDragging) {
                    this.syncChrome()
                }
            })

            this.$watch('state', () => {
                if (! this.slider || this.isDragging) {
                    return
                }

                if (this.isUninitializedEntangleState()) {
                    return
                }

                const current = this.getSliderValues()
                const next = this.normalizeValues(Alpine.raw(this.state))

                if (this.valuesEqual(current, next)) {
                    return
                }

                this.slider.set(this.toSliderSetValue(next), false)
                this.syncChrome()
            })

        },

        scheduleSyncChrome() {
            if (this.syncChromeFrame !== null) {
                return
            }

            this.syncChromeFrame = requestAnimationFrame(() => {
                this.syncChromeFrame = null
                this.syncChrome()
            })
        },

        resolveStartValues() {
            if (this.initialNormalizedValues.length > 1) {
                return [...this.initialNormalizedValues]
            }

            return this.initialNormalizedValues[0] ?? minValue
        },

        resolveConnect() {
            if (fillTrack !== null && fillTrack !== undefined) {
                return fillTrack
            }

            if (! autoFill) {
                return false
            }

            const handleCount = Math.max(
                this.initialNormalizedValues.length,
                1,
            )

            if (handleCount === 1) {
                return [true, false]
            }

            return Array.from({ length: handleCount + 1 }, (_, index) => {
                return index > 0 && index < handleCount
            })
        },

        normalizeValues(raw) {
            if (Array.isArray(raw)) {
                return raw.map((value) => this.normalizeNumeric(value))
            }

            if (raw === null || raw === undefined || raw === '') {
                return [...this.initialNormalizedValues]
            }

            return [this.normalizeNumeric(raw)]
        },

        normalizeNumeric(value) {
            return normalizeNumeric(value, minValue, decimalPlaces, step)
        },

        valuesEqual(left, right) {
            return (
                left.length === right.length &&
                left.every((value, index) => value === right[index])
            )
        },

        toSliderSetValue(values) {
            return values.length > 1 ? values : values[0]
        },

        isUninitializedEntangleState() {
            const raw = Alpine.raw(this.state)

            if (raw === null || raw === undefined || raw === '') {
                return true
            }

            const next = this.normalizeValues(raw)

            if (next.length !== this.initialNormalizedValues.length) {
                return false
            }

            const nextIsAllMin =
                next.length > 0 &&
                next.every((value) => value <= minValue + 0.0001)

            const initialIsNotAllMin = this.initialNormalizedValues.some(
                (value) => value > minValue + 0.0001,
            )

            return nextIsAllMin && initialIsNotAllMin
        },

        getSliderValues() {
            if (! this.slider) {
                return [...this.initialNormalizedValues]
            }

            return this.snapValuesToRangeEdges(
                this.normalizeValues(this.slider.get()),
            )
        },

        updateLiveValues(values = null) {
            const normalized = values ?? this.getSliderValues()

            this.liveValues = normalized.map((value, index) =>
                this.formatTooltipValue(value, index),
            )
            this.liveDisplayValue = this.formatSingle(
                normalized[0] ?? minValue,
            )
        },

        syncChrome() {
            const values = this.getSliderValues()

            this.applyHandleVisualOffset()
            this.resetVisualThumbInlineStyles()
            this.valueRatios = this.resolveValueRatios(values)
            this.rebuildFillSegments()
            this.updateLiveValues(values)
        },

        resolveValueRatios(values) {
            const positions = this.slider?.getPositions?.() ?? []

            return values.map((value, index) => {
                const position = positions[index]

                if (position !== undefined && position !== null && Number.isFinite(position)) {
                    return Math.min(1, Math.max(0, position / 100))
                }

                return this.valueToRatio(value)
            })
        },

        resetVisualThumbInlineStyles() {
            const track = this.$refs.track

            if (! track) {
                return
            }

            track.querySelectorAll('.fff-flex-slider__thumb--on-track').forEach((thumb) => {
                thumb.style.left = ''
                thumb.style.top = ''
                thumb.style.transform = ''
            })
        },

        applyHandleVisualOffset() {
            const host = this.$refs.sliderHost
            const track = this.$refs.track
            const shell = track?.querySelector('.fff-flex-slider__track-shell')

            if (! host || ! shell || ! this.slider) {
                return
            }

            const metrics = this.readTrackMetrics(shell)

            if (! metrics) {
                return
            }

            const positions = this.slider.getPositions?.() ?? []
            const handles = host.querySelectorAll('.noUi-handle')
            const direction = isRtl ? -1 : 1

            handles.forEach((handle, index) => {
                const position = (positions[index] ?? 0) / 100
                const offset = direction * (
                    (metrics.padding * (1 - (2 * position)))
                    - ((metrics.thumbSize / 2) * position)
                )

                handle.style.setProperty('--fff-flex-slider-handle-offset', `${offset}px`)
            })
        },

        readTrackMetrics(shell) {
            const root = this.$el

            if (! root || ! shell) {
                return null
            }

            const padding = this.readCssVarLength(root, '--fff-flex-slider-track-padding')
            const thumbSize = this.readCssVarLength(root, '--fff-flex-slider-thumb-w')
            const shellRect = shell.getBoundingClientRect()
            const axisSize = this.isVertical ? shellRect.height : shellRect.width
            const innerPadding = Number.isFinite(padding) ? padding : 0
            const innerThumbSize = Number.isFinite(thumbSize) && thumbSize > 0
                ? thumbSize
                : 0
            const usableSpan = axisSize - (2 * innerPadding) - innerThumbSize

            if (usableSpan <= 0) {
                return null
            }

            return {
                padding: innerPadding,
                thumbSize: innerThumbSize,
                usableSpan,
            }
        },

        readCssVarLength(element, variableName) {
            const probe = document.createElement('span')

            probe.style.position = 'absolute'
            probe.style.visibility = 'hidden'
            probe.style.pointerEvents = 'none'
            probe.style.width = `var(${variableName})`
            probe.style.height = '0'
            element.appendChild(probe)

            const length = probe.getBoundingClientRect().width

            probe.remove()

            return length
        },

        updateThumbHover(event) {
            if (! hasTooltips || this.isVertical) {
                return
            }

            if (this.isDragging) {
                this.isThumbHovered = true

                return
            }

            const track = this.$refs.track

            if (! track) {
                this.isThumbHovered = false

                return
            }

            const thumbs = track.querySelectorAll('.fff-flex-slider__thumb--on-track')

            if (thumbs.length === 0) {
                this.isThumbHovered = false

                return
            }

            this.isThumbHovered = Array.from(thumbs).some((thumb) => {
                const rect = thumb.getBoundingClientRect()

                return event.clientX >= rect.left - 6
                    && event.clientX <= rect.right + 6
                    && event.clientY >= rect.top - 10
                    && event.clientY <= rect.bottom + 10
            })
        },

        rebuildFillSegments() {
            const connect = this.resolveConnect()

            if (! connect) {
                this.fillSegments = []

                return
            }

            const flags = Array.isArray(connect) ? connect : [connect]
            const stops = [0, ...this.valueRatios, 1]
            const isSingleHandle = this.initialNormalizedValues.length <= 1
            const segments = []

            for (let index = 0; index < flags.length; index++) {
                if (! flags[index]) {
                    continue
                }

                segments.push({
                    type: this.resolveFillSegmentType(
                        stops[index],
                        isSingleHandle,
                    ),
                    startRatio: stops[index],
                    endRatio: stops[index + 1],
                })
            }

            this.fillSegments = segments
        },

        valueToRatio(value) {
            return valueToRatio(
                value,
                minValue,
                maxValue,
                decimalPlaces,
                step,
            )
        },

        formatRatio(ratio) {
            return String(Math.round(ratio * 1_000_000) / 1_000_000)
                .replace(/(\.\d*?[1-9])0+$/, '$1')
                .replace(/\.0+$/, '')
        },

        ratioForIndex(index) {
            return this.valueRatios[index]
                ?? this.initialValueRatios[index]
                ?? 0
        },

        valueRatioStyle(index) {
            return `--fff-flex-slider-value-ratio: ${this.formatRatio(this.ratioForIndex(index))};`
        },

        resolveFillSegmentType(startRatio, isSingleHandle) {
            if (isSingleHandle && startRatio <= 0.0001) {
                return 'from-min'
            }

            return 'between'
        },

        fillSegmentClass(index) {
            const segment =
                this.fillSegments[index] ?? this.initialFillSegments[index]

            if (! segment) {
                return 'fff-flex-slider__fill--from-min'
            }

            return segment.type === 'between'
                ? 'fff-flex-slider__fill--between'
                : 'fff-flex-slider__fill--from-min'
        },

        fillSegmentStyle(index) {
            const segment =
                this.fillSegments[index] ?? this.initialFillSegments[index]

            if (! segment) {
                return this.initialFillSegmentStyle(index) || 'opacity:1'
            }

            if (segment.type === 'between') {
                return `--fff-flex-slider-fill-start: ${this.formatRatio(segment.startRatio)}; --fff-flex-slider-fill-end: ${this.formatRatio(segment.endRatio)};`
            }

            return `--fff-flex-slider-value-ratio: ${this.formatRatio(segment.endRatio)};`
        },

        initialFillSegmentStyle(index) {
            const segment = this.initialFillSegments[index]

            if (! segment) {
                return ''
            }

            if (segment.type === 'between') {
                return `--fff-flex-slider-fill-start: ${this.formatRatio(segment.startRatio)}; --fff-flex-slider-fill-end: ${this.formatRatio(segment.endRatio)};`
            }

            return `--fff-flex-slider-value-ratio: ${this.formatRatio(segment.endRatio)};`
        },

        resolveSliderPadding() {
            if (rangePadding !== null && rangePadding !== undefined) {
                return rangePadding
            }

            return 0
        },

        snapValuesToRangeEdges(values) {
            if (
                ! this.slider
                || (rangePadding !== null && rangePadding !== undefined)
            ) {
                return values
            }

            const positions = this.slider.getPositions?.() ?? []

            return values.map((value, index) => {
                const position = positions[index]

                if (position === undefined || position === null) {
                    return value
                }

                if (position <= 0.5) {
                    return minValue
                }

                if (position >= 99.5) {
                    return maxValue
                }

                return value
            })
        },

        applyRangeEdgeSnap() {
            if (! this.slider) {
                return
            }

            const current = this.normalizeValues(this.slider.get())
            const snapped = this.snapValuesToRangeEdges(current)

            if (! this.valuesEqual(current, snapped)) {
                this.slider.set(this.toSliderSetValue(snapped), false)
            }
        },

        formatTooltipValue(value, index = 0) {
            const numeric = this.normalizeNumeric(value)

            if (Number.isNaN(numeric)) {
                return ''
            }

            if (hasTooltips && tooltips !== true && tooltips !== false) {
                let formatter = tooltips

                if (Array.isArray(tooltips)) {
                    formatter = tooltips[index] ?? tooltips[0]
                }

                if (
                    formatter
                    && typeof formatter === 'object'
                    && typeof formatter.to === 'function'
                ) {
                    return String(formatter.to(numeric))
                }
            }

            return this.formatSingle(value)
        },

        formatSingle(value) {
            const numeric = this.normalizeNumeric(value)

            if (Number.isNaN(numeric)) {
                return ''
            }

            let formatted

            if (decimalPlaces !== null) {
                formatted = numeric.toFixed(decimalPlaces)
            } else if (Number.isInteger(step)) {
                formatted = String(Math.round(numeric))
            } else if (step > 0) {
                const stepDecimals = String(step).includes('.')
                    ? String(step).split('.')[1].length
                    : 0

                formatted =
                    stepDecimals > 0
                        ? numeric.toFixed(stepDecimals).replace(/\.?0+$/, '')
                        : String(numeric)
            } else {
                formatted = String(numeric)
            }

            if (this.prefix) {
                formatted = `${this.prefix}${formatted}`
            }

            if (this.suffix) {
                formatted = `${formatted}${this.suffix}`
            }

            return formatted
        },

        get isRange() {
            return this.initialNormalizedValues.length > 1
        },

        destroy() {
            if (this.syncChromeFrame !== null) {
                cancelAnimationFrame(this.syncChromeFrame)
                this.syncChromeFrame = null
            }

            if (this.slider) {
                this.slider.destroy()
                this.slider = null
            }

            this.resizeObserver?.disconnect()
            this.resizeObserver = null
        },
    }
}

function normalizeNumeric(value, minValue, decimalPlaces, step) {
    const numeric = Number(value)

    if (Number.isNaN(numeric)) {
        return minValue
    }

    if (decimalPlaces !== null) {
        return Number(numeric.toFixed(decimalPlaces))
    }

    if (step !== null && step !== undefined && step > 0) {
        const stepped = Math.round(numeric / step) * step
        const stepDecimals = String(step).includes('.')
            ? String(step).split('.')[1].length
            : 0

        return stepDecimals > 0
            ? Number(stepped.toFixed(stepDecimals))
            : stepped
    }

    return numeric
}

function valueToRatio(value, minValue, maxValue, decimalPlaces, step) {
    const range = maxValue - minValue

    if (range <= 0) {
        return 0
    }

    const normalized = normalizeNumeric(
        value,
        minValue,
        decimalPlaces,
        step,
    )

    return Math.min(
        1,
        Math.max(0, (normalized - minValue) / range),
    )
}
