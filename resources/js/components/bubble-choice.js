import {
    MORPH_DESELECT_MS,
    MORPH_SELECT_MS,
    STROKE_WIDTH_MAX,
    circleStrokePathD,
    easeForMorph,
    morphPointsAt,
    pointsToPathD,
    pointsToPolygon,
    resolveMorphRadii,
    strokeWidthFactor,
} from './bubble-choice/shape-morph.js'

export const defaultLayoutOptions = {
    size: 160,
    minSize: 25,
    gutter: 8,
    provideProps: false,
    numCols: 6,
    fringeWidth: 100,
    yRadius: 200,
    xRadius: 200,
    cornerRadius: 100,
    compact: false,
    gravitation: 0,
}

export function mergeLayoutOptions(overrides = {}) {
    const options = Object.assign({}, defaultLayoutOptions, overrides || {})

    options.numCols = Math.min(
        Math.max(1, Math.floor(Number(options.numCols) || defaultLayoutOptions.numCols)),
        Number.isFinite(Number(overrides?._optionCount))
            ? Math.max(1, Number(overrides._optionCount))
            : Number.POSITIVE_INFINITY,
    )

    delete options._optionCount

    options.size = Number(options.size) || defaultLayoutOptions.size
    options.minSize = Number(options.minSize) || defaultLayoutOptions.minSize
    options.gutter = Number(options.gutter) || 0
    options.fringeWidth = Number(options.fringeWidth) || 0
    options.yRadius = Number(options.yRadius) || 0
    options.xRadius = Number(options.xRadius) || 0
    options.cornerRadius = Number(options.cornerRadius) || 0
    options.gravitation = Number(options.gravitation) || 0
    options.compact = Boolean(options.compact)
    options.provideProps = Boolean(options.provideProps)

    return options
}

export function interpolate(actualMin, actualMax, val, targetMin, targetMax) {
    return ((val - actualMin) / (actualMax - actualMin)) * (targetMax - targetMin) + targetMin
}

/**
 * @returns {Array<Array<{ option: object|null, isPad: boolean }>>}
 */
export function buildBubbleRows(optionsList, numCols) {
    const cappedCols = Math.min(Math.max(1, numCols), Math.max(1, optionsList.length))
    const rows = []
    let colsRemaining = 0
    let evenRow = true

    for (let i = 0; i < optionsList.length; i += 1) {
        if (colsRemaining === 0) {
            colsRemaining = evenRow ? cappedCols - 1 : cappedCols
            evenRow = ! evenRow
            rows.push([])
        }

        rows[rows.length - 1].push({
            option: optionsList[i],
            isPad: false,
        })
        colsRemaining -= 1
    }

    if (rows.length > 1) {
        if (rows[rows.length - 1].length % 2 === rows[rows.length - 2].length % 2) {
            rows[rows.length - 1].push({ option: null, isPad: true })
        }
    }

    return rows
}

export function getBubbleSize(options, rows, row, col, scrollTop, scrollLeft) {
    const minProportion = options.minSize / options.size
    const yOffset = (options.size + options.gutter) * 0.866 * row
        - options.size
        + options.cornerRadius * (1.414 - 1) / 1.414
        - (options.yRadius - options.size)
    const xOffset = (options.size + options.gutter) * col
        + (options.numCols - rows[row].length) * (options.size + options.gutter) / 2
        - options.size
        + options.cornerRadius * (1.414 - 1) / 1.414
        - (options.xRadius - options.size)
    const dy = yOffset - scrollTop
    const dx = xOffset - scrollLeft
    const distance = Math.sqrt(dx * dx + dy * dy)
    const out = {
        bubbleSize: 1,
        translateX: 0,
        translateY: 0,
        distance,
    }

    let distanceFromEdge = 0
    let isInCornerRegion = false

    if (Math.abs(dx) <= options.xRadius && Math.abs(dy) <= options.yRadius) {
        if (Math.abs(dy) > options.yRadius - options.cornerRadius && Math.abs(dx) > options.xRadius - options.cornerRadius) {
            const distToInnerCorner = Math.sqrt(
                Math.pow(Math.abs(dy) - options.yRadius + options.cornerRadius, 2)
                + Math.pow(Math.abs(dx) - options.xRadius + options.cornerRadius, 2),
            )

            if (distToInnerCorner > options.cornerRadius) {
                distanceFromEdge = distToInnerCorner - options.cornerRadius
                isInCornerRegion = true
            }
        }
    } else if (
        Math.abs(dx) <= options.xRadius + options.fringeWidth
        && Math.abs(dy) <= options.yRadius + options.fringeWidth
    ) {
        if (Math.abs(dy) > options.yRadius - options.cornerRadius && Math.abs(dx) > options.xRadius - options.cornerRadius) {
            isInCornerRegion = true

            const distToInnerCorner = Math.sqrt(
                Math.pow(Math.abs(dy) - options.yRadius + options.cornerRadius, 2)
                + Math.pow(Math.abs(dx) - options.xRadius + options.cornerRadius, 2),
            )

            distanceFromEdge = distToInnerCorner - options.cornerRadius
        } else {
            distanceFromEdge = Math.max(Math.abs(dx) - options.xRadius, Math.abs(dy) - options.yRadius)
        }
    } else {
        isInCornerRegion = Math.abs(dy) > options.yRadius - options.cornerRadius
            && Math.abs(dx) > options.xRadius - options.cornerRadius

        if (isInCornerRegion) {
            const distToInnerCorner = Math.sqrt(
                Math.pow(Math.abs(dy) - options.yRadius + options.cornerRadius, 2)
                + Math.pow(Math.abs(dx) - options.xRadius + options.cornerRadius, 2),
            )

            distanceFromEdge = distToInnerCorner - options.cornerRadius
        } else {
            distanceFromEdge = Math.max(Math.abs(dx) - options.xRadius, Math.abs(dy) - options.yRadius)
        }
    }

    out.bubbleSize = interpolate(0, options.fringeWidth, Math.min(distanceFromEdge, options.fringeWidth), 1, minProportion)

    const translationMag = options.compact ? (options.size - options.minSize) / 2 : 0
    const interpolatedTranslationMag = interpolate(0, options.fringeWidth, distanceFromEdge, 0, translationMag)

    if (distanceFromEdge > 0 && distanceFromEdge <= options.fringeWidth) {
        out.translateX = interpolatedTranslationMag
        out.translateY = interpolatedTranslationMag
    } else if (distanceFromEdge - options.fringeWidth > 0) {
        const extra = Math.max(0, distanceFromEdge - options.fringeWidth - options.size / 2) * options.gravitation / 10
        out.translateX = translationMag + extra
        out.translateY = translationMag + extra
    }

    if (isInCornerRegion) {
        const cornerDx = Math.abs(dx) - options.xRadius + options.cornerRadius
        const cornerDy = Math.abs(dy) - options.yRadius + options.cornerRadius
        let theta = Math.atan(-cornerDy / cornerDx)

        if (dx > 0) {
            if (dy > 0) {
                theta *= -1
            }
        } else if (dy > 0) {
            theta += Math.PI
        } else {
            theta += Math.PI - 2 * theta
        }

        out.translateX *= -Math.cos(theta)
        out.translateY *= -Math.sin(theta)
    } else if (Math.abs(dx) > options.xRadius || Math.abs(dy) > options.yRadius) {
        if (Math.abs(dx) > options.xRadius) {
            out.translateX *= -Math.sign(dx)
            out.translateY = 0
        } else {
            out.translateY *= -Math.sign(dy)
            out.translateX = 0
        }
    }

    return out
}

export function contrastTextForBackground(background, light = '#f8fafc', dark = '#0f172a') {
    if (! background || typeof background !== 'string') {
        return light
    }

    const value = background.trim()
    const hex = value.match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i)
    let rgb = null

    if (hex) {
        let body = hex[1]

        if (body.length === 3) {
            body = body.split('').map((char) => char + char).join('')
        }

        rgb = [
            Number.parseInt(body.slice(0, 2), 16),
            Number.parseInt(body.slice(2, 4), 16),
            Number.parseInt(body.slice(4, 6), 16),
        ]
    } else {
        const match = value.match(/^rgba?\(\s*([\d.]+)\s*[, ]\s*([\d.]+)\s*[, ]\s*([\d.]+)/i)

        if (match) {
            rgb = [Number.parseFloat(match[1]), Number.parseFloat(match[2]), Number.parseFloat(match[3])]
        }
    }

    if (! rgb) {
        return light
    }

    const [r, g, b] = rgb.map((channel) => {
        const channelValue = channel / 255

        return channelValue <= 0.03928
            ? channelValue / 12.92
            : ((channelValue + 0.055) / 1.055) ** 2.4
    })

    const luminance = 0.2126 * r + 0.7152 * g + 0.0722 * b

    return luminance > 0.45 ? dark : light
}

function verticalPadding(options) {
    return `calc(50% - ${options.yRadius + options.size / 2 - options.cornerRadius * (1.414 - 1) / 1.414}px)`
}

function horizontalPadding(options) {
    return `calc(50% - ${options.xRadius + options.size / 2 - options.cornerRadius * (1.414 - 1) / 1.414}px)`
}

function cornerOffsetPx(options) {
    return options.cornerRadius * (1.414 - 1) / 1.414
}

/**
 * Exact algebraic form of their scrollTo((scrollSize - clientSize) / 2)
 * given spacers/padding of calc(50% - (radius + size/2 - cornerOffset)).
 *
 * @returns {{ scrollLeft: number, scrollTop: number }}
 */
export function estimateCenterScroll(options, rows) {
    const offset = cornerOffsetPx(options)
    const contentWidth = options.size * options.numCols + options.gutter * Math.max(0, options.numCols - 1)
    let stackHeight = options.size

    for (let index = 1; index < rows.length; index += 1) {
        stackHeight += options.size + options.size * -0.134 + options.gutter * 0.866
    }

    return {
        scrollLeft: Math.max(0, contentWidth / 2 - (options.xRadius + options.size / 2 - offset)),
        scrollTop: Math.max(0, stackHeight / 2 - (options.yRadius + options.size / 2 - offset)),
    }
}

export default function bubbleChoiceFormComponent({
    state,
    options,
    disabled,
    maxItems,
    selectedShape,
    bubbleColor = null,
    selectedBubbleColor = null,
    layout = {},
}) {
    return {
        state,
        options,
        disabled,
        maxItems,
        selectedShape,
        bubbleColor,
        selectedBubbleColor,
        layoutOptions: mergeLayoutOptions(layout),
        rows: [],
        scrollTop: 0,
        scrollLeft: 0,
        isLaidOut: false,
        isScrollSettled: false,
        _pinningScroll: false,
        morphProgress: {},
        _morphRaf: {},
        _morphTarget: {},
        _morphRadii: null,
        _circleStrokePathD: circleStrokePathD(),
        _scallopStrokePathD: null,

        init() {
            this.ensureState()
            this.rebuildRows()
            this.seedMorphProgress()

            const center = estimateCenterScroll(this.layoutOptions, this.rows)
            this.scrollLeft = center.scrollLeft
            this.scrollTop = center.scrollTop

            this.$watch('state', (next, prev) => {
                this.ensureState()
                this.syncMorphProgressFromState(next, prev)
            })

            // After x-for commits: pin DOM scroll, flush Alpine transforms, then reveal.
            // Do not sample scallop morph radii here — that blocked first paint.
            this.$nextTick(() => {
                this.pinDomScrollAndReveal()
            })
        },

        ensureState() {
            if (! Array.isArray(this.state)) {
                this.state = []
            }
        },

        rebuildRows() {
            const layoutOptions = mergeLayoutOptions({
                ...this.layoutOptions,
                ...layout,
                _optionCount: this.options.length,
                numCols: Math.min(
                    Number(layout.numCols ?? this.layoutOptions.numCols ?? defaultLayoutOptions.numCols),
                    Math.max(1, this.options.length),
                ),
            })

            this.layoutOptions = layoutOptions
            this.rows = buildBubbleRows(this.options, layoutOptions.numCols)
        },

        expectedCellCount() {
            return this.rows.reduce((total, row) => total + row.length, 0)
        },

        pinDomScrollAndReveal() {
            const scrollable = this.$refs.scrollable

            if (! scrollable) {
                this.isLaidOut = true
                this.isScrollSettled = true

                return
            }

            this._pinningScroll = true
            this.isScrollSettled = false

            const applyCenter = () => {
                const left = Math.max(0, (scrollable.scrollWidth - scrollable.clientWidth) / 2)
                const top = Math.max(0, (scrollable.scrollHeight - scrollable.clientHeight) / 2)

                scrollable.scrollLeft = left
                scrollable.scrollTop = top
                this.scrollLeft = left
                this.scrollTop = top
            }

            const cellsReady = () => {
                const expected = this.expectedCellCount()

                if (expected === 0) {
                    return true
                }

                return scrollable.querySelectorAll('.fff-bubble-choice__cell').length >= expected
            }

            // Nested x-for + spacer calc(50%) can grow scrollSize across several frames.
            // Stay invisible until size is stable and Alpine transforms match DOM scroll — then hard reveal once.
            let lastWidth = -1
            let lastHeight = -1
            let stableFrames = 0
            let frame = 0
            const neededStable = 1
            const maxFrames = 30

            const revealOnce = () => {
                applyCenter()
                this.isLaidOut = true
                this.isScrollSettled = true
                this._pinningScroll = false

                // Warm morph tables after paint — never during settle (that delayed reveal).
                requestAnimationFrame(() => {
                    this.cacheScallopStrokePathFromDom()
                    this.ensureMorphRadii()
                })
            }

            const tick = () => {
                applyCenter()

                const width = scrollable.scrollWidth
                const height = scrollable.scrollHeight
                const ready = cellsReady() && width > 0 && height > 0

                if (ready && width === lastWidth && height === lastHeight) {
                    stableFrames += 1
                } else {
                    stableFrames = 0
                    lastWidth = width
                    lastHeight = height
                }

                frame += 1

                if ((ready && stableFrames >= neededStable) || frame >= maxFrames) {
                    applyCenter()
                    revealOnce()

                    return
                }

                requestAnimationFrame(tick)
            }

            this.$nextTick(() => {
                applyCenter()
                requestAnimationFrame(tick)
            })
        },

        onScroll(event) {
            if (this._pinningScroll || ! event?.target?.className) {
                return
            }

            this.scrollTop = event.target.scrollTop
            this.scrollLeft = event.target.scrollLeft
        },

        spacerStyle() {
            return {
                height: verticalPadding(this.layoutOptions),
            }
        },

        rowContainerStyle() {
            const options = this.layoutOptions

            return {
                width: `${options.size * options.numCols + options.gutter * (options.numCols - 1)}px`,
                paddingLeft: horizontalPadding(options),
                paddingRight: horizontalPadding(options),
            }
        },

        rowStyle(rowIndex) {
            const options = this.layoutOptions

            return {
                marginTop: rowIndex > 0
                    ? `${options.size * -0.134 + options.gutter * 0.866}px`
                    : 0,
            }
        },

        cellStyle(rowIndex, colIndex) {
            const options = this.layoutOptions
            const { bubbleSize, translateX, translateY, distance } = getBubbleSize(
                options,
                this.rows,
                rowIndex,
                colIndex,
                this.scrollTop,
                this.scrollLeft,
            )

            return {
                width: `${options.size}px`,
                height: `${options.size}px`,
                marginRight: `${options.gutter / 2}px`,
                marginLeft: `${options.gutter / 2}px`,
                transform: `translateX(${translateX}px) translateY(${translateY}px) scale(${bubbleSize})`,
            }
        },

        runtimeBubbleSize(rowIndex, colIndex) {
            const { bubbleSize } = getBubbleSize(
                this.layoutOptions,
                this.rows,
                rowIndex,
                colIndex,
                this.scrollTop,
                this.scrollLeft,
            )

            return bubbleSize * this.layoutOptions.size
        },

        contentVisible(rowIndex, colIndex) {
            return this.runtimeBubbleSize(rowIndex, colIndex) > 50
        },

        isSelected(value) {
            return (this.state ?? []).includes(value)
        },

        canSelectMore() {
            if (! this.maxItems) {
                return true
            }

            return (this.state?.length ?? 0) < this.maxItems
        },

        seedMorphProgress() {
            const next = {}

            for (const value of this.state ?? []) {
                next[value] = 1
            }

            this.morphProgress = next
        },

        ensureMorphRadii() {
            const documentRef = typeof document !== 'undefined' ? document : null
            const scallopReady = Boolean(documentRef?.getElementById?.('fff-bubble-choice-geom-scallop'))

            if (
                this._morphRadii
                && (this._morphRadii.scallop !== this._morphRadii.circle || ! scallopReady)
            ) {
                return this._morphRadii
            }

            this._morphRadii = resolveMorphRadii(documentRef)
            this.cacheScallopStrokePathFromDom()

            if (! this._scallopStrokePathD) {
                this._scallopStrokePathD = pointsToPathD(morphPointsAt(1, this._morphRadii))
            }

            return this._morphRadii
        },

        cacheScallopStrokePathFromDom() {
            if (this._scallopStrokePathD || typeof document === 'undefined') {
                return
            }

            const d = document.getElementById('fff-bubble-choice-geom-scallop')?.getAttribute('d')

            if (d) {
                this._scallopStrokePathD = d
            }
        },

        syncMorphProgressFromState(nextState, prevState) {
            const nextSet = new Set(nextState ?? [])
            const prevSet = new Set(prevState ?? [])

            for (const value of nextSet) {
                if (! prevSet.has(value)) {
                    this.animateSelectionMorph(value, true)
                }
            }

            for (const value of prevSet) {
                if (! nextSet.has(value)) {
                    this.animateSelectionMorph(value, false)
                }
            }
        },

        animateSelectionMorph(value, selected) {
            if (this.selectedShape !== 'scallop') {
                this.morphProgress = {
                    ...this.morphProgress,
                    [value]: selected ? 1 : 0,
                }
                this._morphTarget = { ...this._morphTarget, [value]: selected ? 1 : 0 }

                return
            }

            this.ensureMorphRadii()

            if (this._morphRaf[value]) {
                cancelAnimationFrame(this._morphRaf[value])
                delete this._morphRaf[value]
            }

            const to = selected ? 1 : 0
            const from = Number.isFinite(this.morphProgress[value])
                ? this.morphProgress[value]
                : (selected ? 0 : 1)

            this._morphTarget = { ...this._morphTarget, [value]: to }

            if (from === to) {
                this.morphProgress = { ...this.morphProgress, [value]: to }

                return
            }

            const duration = selected ? MORPH_SELECT_MS : MORPH_DESELECT_MS
            const startedAt = performance.now()

            const tick = (now) => {
                const linear = Math.min(1, (now - startedAt) / duration)
                const eased = easeForMorph(linear, selected)
                const progress = from + (to - from) * eased

                this.morphProgress = { ...this.morphProgress, [value]: progress }

                if (linear < 1) {
                    this._morphRaf[value] = requestAnimationFrame(tick)

                    return
                }

                delete this._morphRaf[value]
                this.morphProgress = { ...this.morphProgress, [value]: to }

                if (to === 0) {
                    const { [value]: _removed, ...rest } = this.morphProgress
                    this.morphProgress = rest
                    const { [value]: _t, ...targets } = this._morphTarget
                    this._morphTarget = targets
                }
            }

            this._morphRaf[value] = requestAnimationFrame(tick)
        },

        selectionMorphProgress(value) {
            if (value == null) {
                return 0
            }

            if (this.selectedShape !== 'scallop') {
                return this.isSelected(value) ? 1 : 0
            }

            return Number.isFinite(this.morphProgress[value]) ? this.morphProgress[value] : 0
        },

        selectionMorphSelecting(value) {
            if (value == null) {
                return false
            }

            if (Object.prototype.hasOwnProperty.call(this._morphTarget, value)) {
                return this._morphTarget[value] === 1
            }

            return this.selectionMorphProgress(value) >= 1
        },

        selectionStrokeWidth(value) {
            return STROKE_WIDTH_MAX * strokeWidthFactor(
                this.selectionMorphProgress(value),
                this.selectionMorphSelecting(value),
            )
        },

        selectionMorphPoints(value) {
            return morphPointsAt(this.selectionMorphProgress(value), this.ensureMorphRadii())
        },

        isSelectionMorphing(value) {
            const progress = this.selectionMorphProgress(value)

            return progress > 0.001 && progress < 0.999
        },

        selectionStrokePath(value) {
            const progress = this.selectionMorphProgress(value)

            // One continuous path element — never swap DOM nodes.
            // Do not sample SVG geometry here (that blocked reload reveal).
            if (progress <= 0.001) {
                return this._circleStrokePathD
            }

            if (progress >= 0.999) {
                this.cacheScallopStrokePathFromDom()

                return this._scallopStrokePathD || this._circleStrokePathD
            }

            if (! this._morphRadii) {
                return this._circleStrokePathD
            }

            return pointsToPathD(morphPointsAt(progress, this._morphRadii))
        },

        selectionStrokeStyle(option) {
            if (! option) {
                return this.bubbleFaceStyle(option)
            }

            const progress = this.selectionMorphProgress(option.value)
            const scale = this.selectedShape === 'scallop'
                ? 1 + (0.02 * progress)
                : 1 + (0.06 * progress)

            return {
                ...this.bubbleFaceStyle(option),
                transform: `scale(${scale})`,
            }
        },

        bubbleClipPath(value) {
            if (this.selectedShape !== 'scallop' || value == null) {
                return null
            }

            const progress = this.selectionMorphProgress(value)

            // Always set an explicit clip so CSS never snaps to full scallop before JS morph starts.
            if (progress <= 0.001) {
                return 'var(--fff-bubble-choice-clip-circle)'
            }

            if (progress >= 0.999) {
                return 'var(--fff-bubble-choice-clip-scallop)'
            }

            return pointsToPolygon(this.selectionMorphPoints(value))
        },

        bubbleFaceStyle(option) {
            if (! option) {
                return {}
            }

            const idleColor = option.color || this.bubbleColor || null
            const activeColor = option.selectedColor || this.selectedBubbleColor || null
            const style = {}

            if (idleColor) {
                style['--fff-bubble-choice-color'] = idleColor
                style['--fff-bubble-choice-text'] = contrastTextForBackground(idleColor)
            }

            if (activeColor) {
                style['--fff-bubble-choice-selected-color'] = activeColor
                style['--fff-bubble-choice-selected-text-resolved'] = contrastTextForBackground(activeColor)
            }

            if (option.image) {
                style['--fff-bubble-choice-image'] = `url("${String(option.image).replaceAll('"', '\\"')}")`
            }

            if (option.image && this.imageMode(option) === 'background') {
                style['--fff-bubble-choice-text'] = '#ffffff'
                style['--fff-bubble-choice-selected-text-resolved'] = '#ffffff'
            }

            return style
        },

        bubbleButtonStyle(option) {
            const style = this.bubbleFaceStyle(option)

            if (! option) {
                return style
            }

            const clipPath = this.bubbleClipPath(option.value)

            if (clipPath) {
                style.clipPath = clipPath
            }

            return style
        },

        toggle(value) {
            if (this.disabled || value == null) {
                return
            }

            const option = this.options.find((item) => item.value === value)

            if (! option || option.disabled) {
                return
            }

            if (this.isSelected(value)) {
                this.state = (this.state ?? []).filter((item) => item !== value)

                return
            }

            if (! this.canSelectMore()) {
                return
            }

            this.state = [...(this.state ?? []), value]
        },

        imageMode(option) {
            return option?.imageMode === 'icon' ? 'icon' : 'background'
        },
    }
}
