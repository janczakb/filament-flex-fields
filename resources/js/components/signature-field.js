const COORD_PRECISION = 1

function roundCoord(value) {
    const factor = 10 ** COORD_PRECISION

    return Math.round(value * factor) / factor
}

function perpendicularDistance(point, start, end) {
    const dx = end.x - start.x
    const dy = end.y - start.y

    if (dx === 0 && dy === 0) {
        return Math.hypot(point.x - start.x, point.y - start.y)
    }

    const t = ((point.x - start.x) * dx + (point.y - start.y) * dy) / (dx * dx + dy * dy)
    const clamped = Math.max(0, Math.min(1, t))
    const projX = start.x + clamped * dx
    const projY = start.y + clamped * dy

    return Math.hypot(point.x - projX, point.y - projY)
}

function simplifyStroke(points, tolerance = 0.35) {
    if (points.length <= 2) {
        return points
    }

    let maxDistance = 0
    let index = 0

    for (let i = 1; i < points.length - 1; i++) {
        const distance = perpendicularDistance(points[i], points[0], points[points.length - 1])

        if (distance > maxDistance) {
            index = i
            maxDistance = distance
        }
    }

    if (maxDistance > tolerance) {
        const left = simplifyStroke(points.slice(0, index + 1), tolerance)
        const right = simplifyStroke(points.slice(index), tolerance)

        return [...left.slice(0, -1), ...right]
    }

    return [points[0], points[points.length - 1]]
}

function smoothPoints(points, windowRadius = 1) {
    if (points.length <= 2) {
        return points
    }

    return points.map((point, index) => {
        const start = Math.max(0, index - windowRadius)
        const end = Math.min(points.length - 1, index + windowRadius)
        let sumX = 0
        let sumY = 0
        let sumWidth = 0
        let count = 0

        for (let i = start; i <= end; i++) {
            sumX += points[i].x
            sumY += points[i].y
            sumWidth += points[i].width ?? 0
            count++
        }

        return {
            x: sumX / count,
            y: sumY / count,
            width: sumWidth / count,
        }
    })
}

function polishStrokePoints(points, smoothingEnabled) {
    if (! smoothingEnabled || points.length <= 8) {
        return points
    }

    if (points.length <= 28) {
        return smoothPoints(points, 1)
    }

    if (points.length <= 90) {
        return smoothPoints(points, 1)
    }

    return smoothPoints(simplifyStroke(points, 0.42), 1)
}

function pointsToQuadraticPath(points) {
    if (points.length === 0) {
        return ''
    }

    if (points.length === 1) {
        const point = points[0]

        return `M${roundCoord(point.x)},${roundCoord(point.y)}`
    }

    if (points.length === 2) {
        return `M${roundCoord(points[0].x)},${roundCoord(points[0].y)}L${roundCoord(points[1].x)},${roundCoord(points[1].y)}`
    }

    let path = `M${roundCoord(points[0].x)},${roundCoord(points[0].y)}`

    for (let i = 1; i < points.length - 1; i++) {
        const control = points[i]
        const midX = (points[i].x + points[i + 1].x) / 2
        const midY = (points[i].y + points[i + 1].y) / 2

        path += `Q${roundCoord(control.x)},${roundCoord(control.y)} ${roundCoord(midX)},${roundCoord(midY)}`
    }

    const last = points[points.length - 1]
    const prev = points[points.length - 2]

    path += `Q${roundCoord(prev.x)},${roundCoord(prev.y)} ${roundCoord(last.x)},${roundCoord(last.y)}`

    return path
}

function pointsToSmoothPath(points) {
    return pointsToQuadraticPath(points)
}

function escapeXml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('"', '&quot;')
        .replaceAll('<', '&lt;')
}

function strokesToSvg(strokes, { penColor, penWidth, backgroundColor, viewBoxWidth, viewBoxHeight }) {
    if (strokes.length === 0) {
        return null
    }

    const paths = strokes.map((stroke) => {
        const d = pointsToSmoothPath(stroke.points)

        if (! d) {
            return ''
        }

        const width = stroke.width ?? penWidth

        return `<path d="${d}" fill="none" stroke="${escapeXml(penColor)}" stroke-width="${roundCoord(width)}" stroke-linecap="round" stroke-linejoin="round"/>`
    }).filter(Boolean).join('')

    if (! paths) {
        return null
    }

    const background = backgroundColor
        ? `<rect width="100%" height="100%" fill="${escapeXml(backgroundColor)}"/>`
        : ''

    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${viewBoxWidth} ${viewBoxHeight}">${background}${paths}</svg>`
}

function sampleQuadratic(start, control, end, steps = 10) {
    const points = []

    for (let step = 1; step <= steps; step++) {
        const t = step / steps
        const inverse = 1 - t

        points.push({
            x: inverse * inverse * start.x + 2 * inverse * t * control.x + t * t * end.x,
            y: inverse * inverse * start.y + 2 * inverse * t * control.y + t * t * end.y,
        })
    }

    return points
}

function parsePathData(pathData) {
    const tokens = pathData.match(/[a-zA-Z]|-?\d*\.?\d+(?:e[-+]?\d+)?/g) ?? []
    const points = []
    let index = 0
    let command = ''
    let current = { x: 0, y: 0 }
    let start = { x: 0, y: 0 }
    let previousControl = null

    const readNumber = () => Number(tokens[index++])

    const pushPoint = (point) => {
        current = point
        points.push(point)
    }

    while (index < tokens.length) {
        const token = tokens[index]

        if (/^[a-zA-Z]$/.test(token)) {
            command = token
            index++
        }

        if (command === 'M') {
            const point = { x: readNumber(), y: readNumber() }
            start = point
            pushPoint(point)
            command = 'L'
            continue
        }

        if (command === 'L') {
            pushPoint({ x: readNumber(), y: readNumber() })
            continue
        }

        if (command === 'Q') {
            const control = { x: readNumber(), y: readNumber() }
            const end = { x: readNumber(), y: readNumber() }

            sampleQuadratic(current, control, end).forEach(pushPoint)
            previousControl = control
            continue
        }

        if (command === 'T') {
            const reflected = previousControl
                ? { x: 2 * current.x - previousControl.x, y: 2 * current.y - previousControl.y }
                : current
            const end = { x: readNumber(), y: readNumber() }

            sampleQuadratic(current, reflected, end).forEach(pushPoint)
            previousControl = reflected
            continue
        }

        index++
    }

    return points
}

function svgToStrokes(svg, penWidth) {
    if (! svg || typeof svg !== 'string') {
        return []
    }

    try {
        const parser = new DOMParser()
        const document = parser.parseFromString(svg, 'image/svg+xml')
        const pathElements = document.querySelectorAll('path')
        const strokes = []

        pathElements.forEach((element) => {
            const d = element.getAttribute('d')

            if (! d) {
                return
            }

            const points = parsePathData(d)

            if (points.length > 0) {
                strokes.push({
                    points,
                    width: Number(element.getAttribute('stroke-width')) || penWidth,
                })
            }
        })

        return strokes
    } catch {
        return []
    }
}

function drawStrokeOnContext(context, stroke, penWidth) {
    const points = stroke.points

    if (points.length === 0) {
        return
    }

    const prepared = points
    const lineWidth = stroke.width ?? penWidth

    context.lineWidth = lineWidth
    context.lineCap = 'round'
    context.lineJoin = 'round'
    context.beginPath()

    if (prepared.length === 1) {
        context.arc(prepared[0].x, prepared[0].y, lineWidth / 2, 0, Math.PI * 2)
        context.fill()

        return
    }

    if (prepared.length === 2) {
        context.moveTo(prepared[0].x, prepared[0].y)
        context.lineTo(prepared[1].x, prepared[1].y)
        context.stroke()

        return
    }

    context.moveTo(prepared[0].x, prepared[0].y)

    for (let i = 1; i < prepared.length - 1; i++) {
        const control = prepared[i]
        const midX = (prepared[i].x + prepared[i + 1].x) / 2
        const midY = (prepared[i].y + prepared[i + 1].y) / 2

        context.quadraticCurveTo(control.x, control.y, midX, midY)
    }

    const last = prepared[prepared.length - 1]
    const prev = prepared[prepared.length - 2]

    context.quadraticCurveTo(prev.x, prev.y, last.x, last.y)
    context.stroke()
}

export default function signatureFieldFormComponent({
    state,
    penColor,
    penWidth,
    backgroundColor,
    viewBoxWidth,
    viewBoxHeight,
    readOnly,
    fullscreenEnabled,
    undoable,
    smoothingEnabled,
    trackpadGlideEnabled,
    trackpadGlideKey,
    guidelinesEnabled,
    downloadFormat,
    downloadFilename,
    webpQuality,
    labels,
}) {
    return {
        state,
        penColor,
        penWidth,
        backgroundColor,
        viewBoxWidth,
        viewBoxHeight,
        readOnly,
        fullscreenEnabled,
        undoable,
        smoothingEnabled,
        trackpadGlideEnabled,
        trackpadGlideKey,
        guidelinesEnabled,
        downloadFormat,
        downloadFilename,
        webpQuality,
        labels,
        strokes: [],
        currentStroke: null,
        isDrawing: false,
        isFullscreen: false,
        activePointerId: null,
        glideIdleTimer: null,
        glideAnchor: null,
        glideArmed: false,
        glideEngaged: false,
        glideOutsideClickHandler: null,
        lastGlidePause: null,
        redrawFrame: null,
        resizeObserver: null,

        init() {
            this.hydrateFromState()
            this.setupTrackpadGlideEngagement()
            this.$watch('state', (value) => {
                if (value === this.exportSvg()) {
                    return
                }

                this.hydrateFromState()
                this.scheduleRedraw()
            })

            this.$nextTick(() => {
                this.setupCanvasObservers()
                this.resizeCanvas()
                this.scheduleRedraw()
            })
        },

        destroy() {
            this.resizeObserver?.disconnect()
            window.cancelAnimationFrame(this.redrawFrame)
            this.clearGlideIdleTimer()

            if (this.glideOutsideClickHandler) {
                document.removeEventListener('pointerdown', this.glideOutsideClickHandler, true)
                this.glideOutsideClickHandler = null
            }
        },

        hydrateFromState() {
            this.strokes = svgToStrokes(this.state, this.penWidth)
        },

        setupCanvasObservers() {
            const canvases = [this.$refs.canvas, this.$refs.fullscreenCanvas].filter(Boolean)

            if (canvases.length === 0) {
                return
            }

            this.resizeObserver = new ResizeObserver(() => {
                this.resizeCanvas()
                this.scheduleRedraw()
            })

            canvases.forEach((canvas) => this.resizeObserver.observe(canvas))
        },

        get activeCanvas() {
            return this.isFullscreen ? this.$refs.fullscreenCanvas : this.$refs.canvas
        },

        get hasSignature() {
            return this.strokes.length > 0
        },

        get canUndo() {
            return this.undoable && this.strokes.length > 0 && ! this.readOnly
        },

        get canClear() {
            return this.hasSignature && ! this.readOnly
        },

        get canDownload() {
            return this.downloadFormat !== null && this.hasSignature
        },

        get aspectRatio() {
            return `${this.viewBoxWidth} / ${this.viewBoxHeight}`
        },

        get showGlidePill() {
            return this.trackpadGlideEnabled && ! this.readOnly
        },

        get trackpadGlideKeyLabel() {
            return String(this.trackpadGlideKey || 'd').toUpperCase()
        },

        get glidePillText() {
            const template = this.glideArmed
                ? this.labels.trackpad_pill_active
                : this.labels.trackpad_pill_paused

            return String(template || '').replace(':key', this.trackpadGlideKeyLabel)
        },

        isTypingTarget(element) {
            if (! element || ! (element instanceof HTMLElement)) {
                return false
            }

            const tag = element.tagName

            if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
                return true
            }

            return element.isContentEditable
        },

        isTypingTargetOutsideSignature(element) {
            return this.isTypingTarget(element) && ! this.$refs.root?.contains(element)
        },

        matchesGlideKey(event) {
            const key = String(this.trackpadGlideKey || 'd').toLowerCase()

            return event.key.toLowerCase() === key
                || event.code === `Key${key.toUpperCase()}`
        },

        setupTrackpadGlideEngagement() {
            if (! this.trackpadGlideEnabled || this.readOnly) {
                return
            }

            this.glideOutsideClickHandler = (event) => {
                if (! this.$refs.root?.contains(event.target) && ! this.glideArmed) {
                    this.glideEngaged = false
                }
            }

            document.addEventListener('pointerdown', this.glideOutsideClickHandler, true)
        },

        engageGlide() {
            if (! this.showGlidePill) {
                return
            }

            this.glideEngaged = true
            this.$refs.root?.focus({ preventScroll: true })
        },

        handleGlideKeydown(event) {
            if (! this.showGlidePill) {
                return
            }

            if (event.metaKey || event.ctrlKey || event.altKey || event.repeat) {
                return
            }

            if (! this.matchesGlideKey(event)) {
                return
            }

            if (! this.glideArmed && ! this.glideEngaged) {
                return
            }

            if (this.isTypingTargetOutsideSignature(event.target)) {
                return
            }

            event.preventDefault()
            event.stopImmediatePropagation()
            this.toggleGlideArmed()
        },

        toggleGlideFromPill() {
            this.engageGlide()
            this.toggleGlideArmed()
        },

        toggleGlideArmed() {
            if (this.glideArmed) {
                this.disarmTrackpadGlide()

                return
            }

            this.glideArmed = true
        },

        disarmTrackpadGlide() {
            this.glideArmed = false
            this.glideAnchor = null
            this.lastGlidePause = null
            this.finishGlideStroke()
        },

        normalizePoint(event, canvas) {
            const rect = canvas.getBoundingClientRect()

            if (rect.width === 0 || rect.height === 0) {
                return null
            }

            const scale = Math.min(rect.width / this.viewBoxWidth, rect.height / this.viewBoxHeight)
            const offsetX = (rect.width - this.viewBoxWidth * scale) / 2
            const offsetY = (rect.height - this.viewBoxHeight * scale) / 2
            const x = (event.clientX - rect.left - offsetX) / scale
            const y = (event.clientY - rect.top - offsetY) / scale
            const pressure = Number.isFinite(event.pressure) && event.pressure > 0 ? event.pressure : 0.5
            const width = this.penWidth * (0.35 + pressure * 0.65)

            return {
                x: Math.max(0, Math.min(this.viewBoxWidth, x)),
                y: Math.max(0, Math.min(this.viewBoxHeight, y)),
                width,
            }
        },

        resizeCanvas() {
            ;[this.$refs.canvas, this.$refs.fullscreenCanvas].forEach((canvas) => {
                if (! canvas) {
                    return
                }

                const rect = canvas.getBoundingClientRect()
                const ratio = window.devicePixelRatio || 1
                const width = Math.max(1, Math.floor(rect.width * ratio))
                const height = Math.max(1, Math.floor(rect.height * ratio))

                if (canvas.width !== width || canvas.height !== height) {
                    canvas.width = width
                    canvas.height = height
                }
            })
        },

        scheduleRedraw() {
            window.cancelAnimationFrame(this.redrawFrame)
            this.redrawFrame = window.requestAnimationFrame(() => this.redraw())
        },

        redraw() {
            this.redrawCanvas(this.$refs.canvas)
            this.redrawCanvas(this.$refs.fullscreenCanvas)
        },

        redrawCanvas(canvas) {
            if (! canvas) {
                return
            }

            const context = canvas.getContext('2d')

            if (! context) {
                return
            }

            const width = canvas.width
            const height = canvas.height
            const scale = Math.min(width / this.viewBoxWidth, height / this.viewBoxHeight)
            const offsetX = (width - this.viewBoxWidth * scale) / 2
            const offsetY = (height - this.viewBoxHeight * scale) / 2

            context.setTransform(1, 0, 0, 1, 0, 0)
            context.clearRect(0, 0, width, height)

            if (this.backgroundColor && ! this.guidelinesEnabled) {
                context.fillStyle = this.backgroundColor
                context.fillRect(0, 0, width, height)
            }

            context.setTransform(scale, 0, 0, scale, offsetX, offsetY)
            context.strokeStyle = this.penColor
            context.fillStyle = this.penColor

            this.strokes.forEach((stroke) => {
                drawStrokeOnContext(context, stroke, this.penWidth)
            })

            if (this.currentStroke) {
                drawStrokeOnContext(context, this.currentStroke, this.penWidth)
            }
        },

        renderToExportCanvas(pixelRatio = 2) {
            const canvas = document.createElement('canvas')
            canvas.width = Math.max(1, Math.round(this.viewBoxWidth * pixelRatio))
            canvas.height = Math.max(1, Math.round(this.viewBoxHeight * pixelRatio))

            const context = canvas.getContext('2d')

            if (! context) {
                return null
            }

            if (this.backgroundColor) {
                context.fillStyle = this.backgroundColor
                context.fillRect(0, 0, canvas.width, canvas.height)
            } else {
                context.clearRect(0, 0, canvas.width, canvas.height)
            }

            context.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0)
            context.strokeStyle = this.penColor
            context.fillStyle = this.penColor

            this.strokes.forEach((stroke) => {
                drawStrokeOnContext(context, stroke, this.penWidth)
            })

            return canvas
        },

        isGlidePointerId(pointerId) {
            return pointerId === 'glide'
        },

        shouldUseTrackpadGlide(event) {
            return this.trackpadGlideEnabled
                && this.glideArmed
                && ! this.readOnly
                && event.pointerType === 'mouse'
                && event.buttons === 0
        },

        clearGlideIdleTimer() {
            if (this.glideIdleTimer !== null) {
                window.clearTimeout(this.glideIdleTimer)
                this.glideIdleTimer = null
            }
        },

        resetGlideIdleTimer() {
            this.clearGlideIdleTimer()

            this.glideIdleTimer = window.setTimeout(() => {
                this.finishGlideStroke(true)
            }, 320)
        },

        appendPointToCurrentStroke(point, options = {}) {
            if (! this.currentStroke) {
                return
            }

            const minDistance = options.minDistance ?? 0.15
            const bridge = options.bridge ?? false
            const last = this.currentStroke.points[this.currentStroke.points.length - 1]
            const distance = Math.hypot(point.x - last.x, point.y - last.y)

            if (distance < minDistance) {
                return
            }

            if (bridge && distance > 2.5) {
                const steps = Math.max(2, Math.min(16, Math.ceil(distance / 2.5)))

                for (let step = 1; step <= steps; step++) {
                    const progress = step / steps

                    this.currentStroke.points.push({
                        x: last.x + (point.x - last.x) * progress,
                        y: last.y + (point.y - last.y) * progress,
                        width: last.width + ((point.width ?? last.width) - last.width) * progress,
                    })
                }
            } else {
                this.currentStroke.points.push(point)
            }

            this.scheduleRedraw()
        },

        canResumeGlideStroke(point) {
            if (! this.lastGlidePause) {
                return false
            }

            if (Date.now() - this.lastGlidePause.at > 220) {
                return false
            }

            if (this.strokes.length === 0) {
                return false
            }

            return Math.hypot(point.x - this.lastGlidePause.point.x, point.y - this.lastGlidePause.point.y) < 40
        },

        beginGlideStroke(point) {
            if (this.canResumeGlideStroke(point)) {
                const resumedStroke = this.strokes.pop()

                if (resumedStroke) {
                    this.activePointerId = 'glide'
                    this.isDrawing = true
                    this.currentStroke = resumedStroke
                    this.lastGlidePause = null
                    this.resetGlideIdleTimer()
                    this.appendPointToCurrentStroke(point, { bridge: true, minDistance: 0.05 })
                    this.scheduleRedraw()

                    return
                }
            }

            this.lastGlidePause = null
            this.activePointerId = 'glide'
            this.isDrawing = true
            this.currentStroke = {
                points: [point],
                width: point.width,
            }
            this.resetGlideIdleTimer()
            this.scheduleRedraw()
        },

        handleTrackpadGlideMove(event, canvas) {
            if (! this.glideArmed) {
                this.glideAnchor = null

                return
            }

            const point = this.normalizePoint(event, canvas)

            if (! point) {
                return
            }

            event.preventDefault()

            if (! this.isDrawing) {
                if (! this.glideAnchor) {
                    this.glideAnchor = { x: event.clientX, y: event.clientY }

                    return
                }

                const travel = Math.hypot(
                    event.clientX - this.glideAnchor.x,
                    event.clientY - this.glideAnchor.y,
                )

                if (travel < 4) {
                    return
                }

                this.glideAnchor = null
                this.beginGlideStroke(point)

                return
            }

            if (! this.isGlidePointerId(this.activePointerId)) {
                return
            }

            this.resetGlideIdleTimer()
            this.appendPointToCurrentStroke(point, { bridge: true, minDistance: 0.05 })
        },

        finishGlideStroke(recordPause = false) {
            if (! this.isGlidePointerId(this.activePointerId)) {
                return
            }

            const lastPoint = this.currentStroke?.points?.[this.currentStroke.points.length - 1] ?? null

            this.clearGlideIdleTimer()
            this.glideAnchor = null
            this.finishStroke({ pointerId: 'glide' })

            if (recordPause && lastPoint) {
                this.lastGlidePause = {
                    at: Date.now(),
                    point: { ...lastPoint },
                }
            } else {
                this.lastGlidePause = null
            }
        },

        onPointerDown(event) {
            if (this.readOnly) {
                return
            }

            this.engageGlide()

            if (this.isGlidePointerId(this.activePointerId)) {
                this.finishGlideStroke()
            }

            if (this.activePointerId !== null) {
                return
            }

            const canvas = this.activeCanvas

            if (! canvas) {
                return
            }

            const point = this.normalizePoint(event, canvas)

            if (! point) {
                return
            }

            event.preventDefault()
            this.glideAnchor = null
            canvas.setPointerCapture(event.pointerId)
            this.activePointerId = event.pointerId
            this.isDrawing = true
            this.currentStroke = {
                points: [point],
                width: point.width,
            }
            this.scheduleRedraw()
        },

        onPointerMove(event) {
            const canvas = this.activeCanvas

            if (! canvas) {
                return
            }

            if (this.shouldUseTrackpadGlide(event)) {
                this.handleTrackpadGlideMove(event, canvas)

                return
            }

            if (! this.isDrawing || event.pointerId !== this.activePointerId || ! this.currentStroke) {
                return
            }

            const point = this.normalizePoint(event, canvas)

            if (! point) {
                return
            }

            event.preventDefault()
            this.appendPointToCurrentStroke(point)
        },

        onPointerUp(event) {
            if (this.isGlidePointerId(this.activePointerId)) {
                return
            }

            if (event.pointerId !== this.activePointerId) {
                return
            }

            this.finishStroke(event)
        },

        onPointerLeave(event) {
            if (this.isGlidePointerId(this.activePointerId)) {
                this.finishGlideStroke(false)

                return
            }

            if (this.trackpadGlideEnabled && ! this.isDrawing) {
                this.glideAnchor = null
            }

            if (event.pointerId !== this.activePointerId) {
                return
            }

            this.finishStroke(event)
        },

        onPointerCancel(event) {
            if (this.isGlidePointerId(this.activePointerId)) {
                this.finishGlideStroke(false)

                return
            }

            if (event.pointerId !== this.activePointerId) {
                return
            }

            this.finishStroke(event, true)
        },

        finishStroke(event, cancelled = false) {
            const canvas = this.activeCanvas
            const pointerId = event?.pointerId

            if (canvas && pointerId !== undefined && ! this.isGlidePointerId(pointerId) && canvas.hasPointerCapture?.(pointerId)) {
                canvas.releasePointerCapture(pointerId)
            }

            this.clearGlideIdleTimer()
            this.glideAnchor = null
            this.activePointerId = null
            this.isDrawing = false

            if (! cancelled && this.currentStroke && this.currentStroke.points.length > 0) {
                this.currentStroke.points = polishStrokePoints(this.currentStroke.points, this.smoothingEnabled)
                this.strokes.push(this.currentStroke)
                this.commitState()
            }

            this.currentStroke = null
            this.scheduleRedraw()
        },

        undo() {
            if (! this.canUndo) {
                return
            }

            this.strokes.pop()
            this.commitState()
            this.scheduleRedraw()
        },

        clear() {
            if (! this.canClear) {
                return
            }

            this.strokes = []
            this.currentStroke = null
            this.commitState()
            this.scheduleRedraw()
        },

        exportSvg() {
            return strokesToSvg(this.strokes, {
                penColor: this.penColor,
                penWidth: this.penWidth,
                backgroundColor: this.backgroundColor,
                viewBoxWidth: this.viewBoxWidth,
                viewBoxHeight: this.viewBoxHeight,
            })
        },

        commitState() {
            this.state = this.exportSvg()
        },

        triggerDownload(blob, filename) {
            const url = URL.createObjectURL(blob)
            const anchor = document.createElement('a')

            anchor.href = url
            anchor.download = filename
            anchor.rel = 'noopener'
            anchor.click()

            window.setTimeout(() => URL.revokeObjectURL(url), 0)
        },

        downloadSignature() {
            if (! this.canDownload) {
                return
            }

            const baseName = this.downloadFilename || 'signature'

            if (this.downloadFormat === 'svg') {
                const svg = this.exportSvg()

                if (! svg) {
                    return
                }

                this.triggerDownload(new Blob([svg], { type: 'image/svg+xml;charset=utf-8' }), `${baseName}.svg`)

                return
            }

            if (this.downloadFormat === 'webp') {
                const canvas = this.renderToExportCanvas(2)

                if (! canvas) {
                    return
                }

                canvas.toBlob((blob) => {
                    if (blob) {
                        this.triggerDownload(blob, `${baseName}.webp`)
                    }
                }, 'image/webp', this.webpQuality)
            }
        },

        openFullscreen() {
            if (! this.fullscreenEnabled || this.readOnly) {
                return
            }

            this.isFullscreen = true
            document.documentElement.classList.add('fff-signature-field--modal-open')

            this.$nextTick(() => {
                this.resizeCanvas()
                this.scheduleRedraw()
            })
        },

        closeFullscreen() {
            this.isFullscreen = false
            document.documentElement.classList.remove('fff-signature-field--modal-open')

            this.$nextTick(() => {
                this.resizeCanvas()
                this.scheduleRedraw()
            })
        },

        confirmFullscreen() {
            this.commitState()
            this.closeFullscreen()
        },
    }
}
