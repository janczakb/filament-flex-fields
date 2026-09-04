/**
 * Overlay scrollbar geometry for dropdown option lists.
 * Native OS scrollbars stay hidden; the thumb is painted identically everywhere
 * and fades out when the list is idle (overlay-style).
 */

const boundTracks = new WeakSet()
const idleTimers = new WeakMap()
const heldTracks = new WeakSet()

export const OVERLAY_SCROLLBAR_HIDE_DELAY_MS = 800

function clearIdleTimer(track) {
    const timer = idleTimers.get(track)

    if (timer) {
        clearTimeout(timer)
        idleTimers.delete(track)
    }
}

/**
 * @param {{ dataset?: DOMStringMap } | null | undefined} track
 */
export function concealOverlayScrollbar(track) {
    if (! track?.dataset) {
        return
    }

    clearIdleTimer(track)
    heldTracks.delete(track)
    track.dataset.active = 'false'
}

/**
 * @param {{ dataset?: DOMStringMap } | null | undefined} track
 * @param {{ persist?: boolean }} [options]
 */
export function revealOverlayScrollbar(track, options = {}) {
    if (! track?.dataset || track.dataset.visible !== 'true') {
        return
    }

    track.dataset.active = 'true'
    clearIdleTimer(track)

    if (options.persist) {
        heldTracks.add(track)

        return
    }

    heldTracks.delete(track)
    idleTimers.set(track, setTimeout(() => {
        if (heldTracks.has(track)) {
            return
        }

        track.dataset.active = 'false'
        idleTimers.delete(track)
    }, OVERLAY_SCROLLBAR_HIDE_DELAY_MS))
}

/**
 * @param {{ scrollTop?: number, scrollHeight?: number, clientHeight?: number } | null | undefined} scroller
 * @param {{ style?: CSSStyleDeclaration, parentElement?: { clientHeight?: number, dataset?: DOMStringMap } | null } | null | undefined} thumb
 * @param {number} minThumbPx
 * @returns {{ visible: boolean, thumbHeight?: number, thumbTop?: number }}
 */
export function syncOverlayScrollbar(scroller, thumb, minThumbPx = 24) {
    const track = thumb?.parentElement

    if (! scroller || ! thumb || ! track) {
        return { visible: false }
    }

    const scrollHeight = Number(scroller.scrollHeight) || 0
    const clientHeight = Number(scroller.clientHeight) || 0
    const visible = scrollHeight > clientHeight + 1

    if (track.dataset) {
        track.dataset.visible = visible ? 'true' : 'false'
    }

    if (! visible) {
        concealOverlayScrollbar(track)

        if (thumb.style) {
            thumb.style.height = '0px'
            thumb.style.transform = 'translateY(0px)'
        }

        return { visible: false }
    }

    const trackHeight = Number(track.clientHeight) || clientHeight
    const thumbHeight = Math.min(
        trackHeight,
        Math.max(minThumbPx, (clientHeight / scrollHeight) * trackHeight),
    )
    const maxThumbTop = Math.max(0, trackHeight - thumbHeight)
    const maxScroll = Math.max(1, scrollHeight - clientHeight)
    const thumbTop = (Number(scroller.scrollTop) || 0) / maxScroll * maxThumbTop

    if (thumb.style) {
        thumb.style.height = `${thumbHeight}px`
        thumb.style.transform = `translateY(${thumbTop}px)`
    }

    return { visible: true, thumbHeight, thumbTop }
}

/**
 * @param {{ addEventListener?: Function, scrollTop?: number, scrollHeight?: number, clientHeight?: number } | null | undefined} scroller
 * @param {{ addEventListener?: Function, hidden?: boolean, getBoundingClientRect?: Function, clientHeight?: number, setPointerCapture?: Function, dataset?: DOMStringMap } | null | undefined} track
 * @param {{ offsetHeight?: number, getBoundingClientRect?: Function, contains?: Function } | null | undefined} thumb
 */
export function bindOverlayScrollbar(scroller, track, thumb) {
    if (! scroller?.addEventListener || ! track?.addEventListener || ! thumb || boundTracks.has(track)) {
        return
    }

    boundTracks.add(track)

    const onScroll = () => {
        syncOverlayScrollbar(scroller, thumb)
        revealOverlayScrollbar(track)
    }

    scroller.addEventListener('scroll', onScroll, { passive: true })
    track.addEventListener('pointerenter', () => revealOverlayScrollbar(track, { persist: true }))
    track.addEventListener('pointerleave', () => revealOverlayScrollbar(track))

    const scrollToThumbTop = (thumbTop) => {
        const trackHeight = Number(track.clientHeight) || 0
        const thumbHeight = Number(thumb.offsetHeight) || 0
        const maxThumbTop = Math.max(0, trackHeight - thumbHeight)
        const clamped = Math.min(maxThumbTop, Math.max(0, thumbTop))
        const maxScroll = Math.max(1, (Number(scroller.scrollHeight) || 0) - (Number(scroller.clientHeight) || 0))

        scroller.scrollTop = maxThumbTop === 0 ? 0 : (clamped / maxThumbTop) * maxScroll
    }

    track.addEventListener('pointerdown', (event) => {
        if (track.dataset?.visible !== 'true') {
            return
        }

        event.preventDefault()
        revealOverlayScrollbar(track, { persist: true })
        track.setPointerCapture?.(event.pointerId)

        const rect = track.getBoundingClientRect?.() ?? { top: 0 }
        const thumbRect = thumb.getBoundingClientRect?.() ?? { top: rect.top }
        const grabbingThumb = event.target === thumb || Boolean(thumb.contains?.(event.target))
        const grabOffset = grabbingThumb
            ? event.clientY - thumbRect.top
            : (Number(thumb.offsetHeight) || 0) / 2

        const move = (clientY) => {
            scrollToThumbTop(clientY - rect.top - grabOffset)
        }

        move(event.clientY)

        const onMove = (moveEvent) => move(moveEvent.clientY)
        const onUp = () => {
            window.removeEventListener('pointermove', onMove)
            window.removeEventListener('pointerup', onUp)
            revealOverlayScrollbar(track)
        }

        window.addEventListener('pointermove', onMove)
        window.addEventListener('pointerup', onUp)
    })
}
