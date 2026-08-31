const DISMISS_THRESHOLD_PX = 72
const VELOCITY_THRESHOLD = 0.45

/**
 * Bottom-sheet drag-to-dismiss (pointer events).
 *
 * @param {{
 *   panel: HTMLElement,
 *   onDismiss: () => void,
 *   onCancel?: () => void,
 *   window?: Window,
 * }} options
 * @returns {() => void} cleanup
 */
export function bindOverlaySheetDismiss({ panel, onDismiss, onCancel, window: win = globalThis.window }) {
    if (! panel || typeof onDismiss !== 'function') {
        return () => {}
    }

    let startY = 0
    let lastY = 0
    let lastTs = 0
    let dragging = false
    let pointerId = null

    const resetTransform = () => {
        panel.style.transition = ''
        panel.style.transform = ''
    }

    const handlePointerDown = (event) => {
        if (event.pointerType === 'mouse' && event.button !== 0) {
            return
        }

        const target = event.target

        if (! (target instanceof Element)) {
            return
        }

        const onHandle = target.closest('[data-fff-overlay-handle], .fff-overlay-sheet__handle')

        if (! onHandle && ! target.closest('.fff-overlay-sheet__header')) {
            return
        }

        dragging = true
        pointerId = event.pointerId
        startY = event.clientY
        lastY = event.clientY
        lastTs = event.timeStamp
        panel.style.transition = 'none'
        panel.setPointerCapture?.(event.pointerId)
    }

    const handlePointerMove = (event) => {
        if (! dragging || event.pointerId !== pointerId) {
            return
        }

        const deltaY = Math.max(0, event.clientY - startY)

        lastY = event.clientY
        lastTs = event.timeStamp
        panel.style.transform = `translateY(${deltaY}px)`
    }

    const handlePointerUp = (event) => {
        if (! dragging || event.pointerId !== pointerId) {
            return
        }

        dragging = false
        pointerId = null
        panel.releasePointerCapture?.(event.pointerId)

        const deltaY = Math.max(0, event.clientY - startY)
        const velocity = deltaY / Math.max(1, event.timeStamp - lastTs)

        panel.style.transition = ''

        if (deltaY >= DISMISS_THRESHOLD_PX || velocity >= VELOCITY_THRESHOLD) {
            panel.classList.add('is-dismissing')
            onDismiss()
            win.setTimeout(() => {
                panel.classList.remove('is-dismissing')
                resetTransform()
            }, 200)

            return
        }

        resetTransform()
        onCancel?.()
    }

    panel.addEventListener('pointerdown', handlePointerDown)
    panel.addEventListener('pointermove', handlePointerMove)
    panel.addEventListener('pointerup', handlePointerUp)
    panel.addEventListener('pointercancel', handlePointerUp)

    return () => {
        panel.removeEventListener('pointerdown', handlePointerDown)
        panel.removeEventListener('pointermove', handlePointerMove)
        panel.removeEventListener('pointerup', handlePointerUp)
        panel.removeEventListener('pointercancel', handlePointerUp)
        resetTransform()
    }
}
