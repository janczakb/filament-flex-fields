export const TAP_THRESHOLD_MS = 250

export const DOUBLE_TAP_WINDOW_MS = 200

export function resolveGestureRegion(clientX, frameRect) {
    if (! frameRect?.width) {
        return 'center'
    }

    const relative = (clientX - frameRect.left) / frameRect.width

    if (relative < 1 / 3) {
        return 'left'
    }

    if (relative > 2 / 3) {
        return 'right'
    }

    return 'center'
}

export function createTapGestureTracker() {
    return {
        lastTapAt: 0,
        lastTapRegion: null,
        pointerDownAt: 0,
        pointerDownX: 0,
    }
}

export function beginTapGesture(tracker, event) {
    tracker.pointerDownAt = Date.now()
    tracker.pointerDownX = event.clientX
}

export function resolveTapGesture(tracker, event, frameRect) {
    const duration = Date.now() - tracker.pointerDownAt

    if (duration > TAP_THRESHOLD_MS) {
        return null
    }

    const region = resolveGestureRegion(event.clientX ?? tracker.pointerDownX, frameRect)
    const now = Date.now()
    const isDoubleTap = tracker.lastTapAt > 0
        && now - tracker.lastTapAt <= DOUBLE_TAP_WINDOW_MS
        && tracker.lastTapRegion === region

    tracker.lastTapAt = now
    tracker.lastTapRegion = region

    return {
        region,
        isDoubleTap,
    }
}
