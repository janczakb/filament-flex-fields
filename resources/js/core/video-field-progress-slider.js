/** @typedef {{ pointerPercent: number, pointing: boolean, dragging: boolean }} VideoProgressSliderState */

export function getPercentFromPointerEvent(event, rect, orientation = 'horizontal', isRTL = false) {
    if (! rect) {
        return 0
    }

    let ratio

    if (orientation === 'vertical') {
        ratio = 1 - ((event.clientY - rect.top) / rect.height)
    } else if (isRTL) {
        ratio = (rect.right - event.clientX) / rect.width
    } else {
        ratio = (event.clientX - rect.left) / rect.width
    }

    if (! Number.isFinite(ratio)) {
        return 0
    }

    return Math.max(0, Math.min(100, ratio * 100))
}

/**
 * Compute clamped preview positioning along the slider track.
 */
export function getSliderPreviewLeft(pointerPercent, previewWidth, overflow = 'clamp') {
    const halfWidth = previewWidth / 2
    const pointer = `${Number(pointerPercent).toFixed(3)}%`

    if (overflow === 'visible') {
        return `calc(${pointer} - ${halfWidth}px)`
    }

    return `min(max(0px, calc(${pointer} - ${halfWidth}px)), calc(100% - ${previewWidth}px))`
}

export function resolvePreviewCacheKey(time, step = 0.5) {
    if (! Number.isFinite(time) || time < 0) {
        return 0
    }

    return Math.round(time / step) * step
}

export class VideoPreviewFrameCache {
    constructor(maxEntries = 32) {
        this.maxEntries = maxEntries
        /** @type {Map<number, HTMLCanvasElement>} */
        this.entries = new Map()
    }

    get(key) {
        return this.entries.get(key) ?? null
    }

    set(key, sourceCanvas) {
        if (this.entries.has(key)) {
            this.entries.delete(key)
        }

        const cached = document.createElement('canvas')
        cached.width = sourceCanvas.width
        cached.height = sourceCanvas.height
        cached.getContext('2d')?.drawImage(sourceCanvas, 0, 0)
        this.entries.set(key, cached)

        while (this.entries.size > this.maxEntries) {
            const oldestKey = this.entries.keys().next().value
            this.entries.delete(oldestKey)
        }
    }

    clear() {
        this.entries.clear()
    }
}

/**
 * Pointer-driven progress slider controller for enterprise scrubbing UX.
 *
 * @param {{
 *   getElement: () => HTMLElement | null,
 *   isDisabled?: () => boolean,
 *   onStateChange?: (state: VideoProgressSliderState) => void,
 *   onValueChange?: (percent: number) => void,
 *   onValueCommit?: (percent: number) => void,
 *   onDragStart?: () => void,
 *   onDragEnd?: () => void,
 *   orientation?: 'horizontal' | 'vertical',
 * }} options
 */
export function createVideoProgressSlider(options) {
    let isDragging = false
    let capturedPointerId = null
    /** @type {DOMRect | null} */
    let cachedRect = null
    let pointerPercent = 0
    let pointing = false
    let dragging = false
    let committedOnRelease = false
    let lastDragPercent = 0
    const orientation = options.orientation ?? 'horizontal'

    function resolvePercent(event, rect) {
        return getPercentFromPointerEvent(event, rect, orientation)
    }

    /** @param {Partial<VideoProgressSliderState>} patch */
    function emit(patch) {
        if (patch.pointerPercent !== undefined) {
            pointerPercent = patch.pointerPercent
        }

        if (patch.pointing !== undefined) {
            pointing = patch.pointing
        }

        if (patch.dragging !== undefined) {
            dragging = patch.dragging
        }

        options.onStateChange?.({
            pointerPercent,
            pointing,
            dragging,
        })
    }

    function releaseCapture() {
        if (capturedPointerId === null) {
            return
        }

        const pointerId = capturedPointerId
        capturedPointerId = null

        try {
            options.getElement()?.releasePointerCapture(pointerId)
        } catch {
            // Ignore release errors when the element is already gone.
        }
    }

    function endDrag() {
        if (! isDragging) {
            emit({ pointing: false })

            return
        }

        if (! committedOnRelease) {
            options.onValueCommit?.(lastDragPercent)
        }

        isDragging = false
        emit({
            dragging: false,
            pointing: false,
        })
        options.onDragEnd?.()
        committedOnRelease = false
        cachedRect = null
    }

    return {
        getState() {
            return {
                pointerPercent,
                pointing,
                dragging,
            }
        },

        handlePointerDown(event) {
            if (options.isDisabled?.()) {
                return
            }

            event.stopPropagation()
            event.preventDefault()

            const element = options.getElement()

            if (! element) {
                return
            }

            cachedRect = element.getBoundingClientRect()
            committedOnRelease = false
            releaseCapture()
            capturedPointerId = event.pointerId
            element.setPointerCapture(event.pointerId)

            const percent = resolvePercent(event, cachedRect)
            isDragging = true
            lastDragPercent = percent
            emit({
                pointing: true,
                dragging: true,
                pointerPercent: percent,
            })
            options.onDragStart?.()
            options.onValueChange?.(percent)
        },

        handlePointerMove(event) {
            if (options.isDisabled?.()) {
                return
            }

            if (capturedPointerId !== null) {
                if (event.pointerType !== 'touch' && event.buttons === 0) {
                    endDrag()

                    return
                }

                const percent = resolvePercent(event, cachedRect)
                lastDragPercent = percent
                emit({ pointerPercent: percent })
                options.onValueChange?.(percent)

                return
            }

            const element = options.getElement()

            if (! element) {
                return
            }

            const percent = resolvePercent(event, element.getBoundingClientRect())
            emit({
                pointing: true,
                pointerPercent: percent,
            })
        },

        handlePointerUp(event) {
            if (options.isDisabled?.()) {
                return
            }

            event.stopPropagation()

            if (capturedPointerId === null) {
                return
            }

            const percent = resolvePercent(event, cachedRect)
            options.onValueChange?.(percent)
            options.onValueCommit?.(percent)
            committedOnRelease = true
        },

        handlePointerLeave() {
            if (capturedPointerId !== null) {
                return
            }

            emit({ pointing: false })
        },

        handleLostPointerCapture() {
            endDrag()
        },

        destroy() {
            releaseCapture()
            cachedRect = null
            isDragging = false
            capturedPointerId = null
        },
    }
}
