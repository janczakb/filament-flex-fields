const DRAG_THRESHOLD_PX = 8
const DISMISS_FRACTION = 0.3
const VELOCITY_THRESHOLD = 0.5
const PEEK_VIEWPORT_FRACTION = 0.5
const EXPANDED_VIEWPORT_FRACTION = 0.8
const PEEK_MAX_PX = 32 * 16
/** Floor so empty/loading states (icon + label) are never clipped under the search row. */
export const OVERLAY_SHEET_MIN_HEIGHT_PX = 280
const SNAP_EASE = 'height 0.25s cubic-bezier(0.32, 0.72, 0, 1), max-height 0.25s cubic-bezier(0.32, 0.72, 0, 1), transform 0.25s cubic-bezier(0.32, 0.72, 0, 1)'
const EXIT_EASE = 'transform 0.2s cubic-bezier(0.32, 0.72, 0, 1)'
const EXIT_MS = 200

/**
 * @param {Window} win
 * @returns {number}
 */
export function resolveOverlaySheetPeekHeight(win = globalThis.window) {
    const viewport = win?.innerHeight ?? 0

    return Math.min(Math.round(viewport * PEEK_VIEWPORT_FRACTION), PEEK_MAX_PX)
}

/**
 * Minimum sheet height (never larger than the peek cap on short viewports).
 *
 * @param {Window} [win]
 * @returns {number}
 */
export function resolveOverlaySheetMinHeight(win = globalThis.window) {
    return Math.min(OVERLAY_SHEET_MIN_HEIGHT_PX, resolveOverlaySheetPeekHeight(win))
}

/**
 * @param {Window} win
 * @returns {number}
 */
export function resolveOverlaySheetExpandedHeight(win = globalThis.window) {
    const viewport = win?.innerHeight ?? 0

    return Math.round(viewport * EXPANDED_VIEWPORT_FRACTION)
}

/**
 * Size the sheet to its content, capped at the peek max. Returns fitted height.
 *
 * @param {HTMLElement} panel
 * @param {Window} [win]
 * @returns {{ fitted: number, cap: number, canExpand: boolean }}
 */
/**
 * @param {HTMLElement} panel
 * @param {string} property
 * @param {string} value
 */
function setSheetStyle(panel, property, value) {
    panel.style.setProperty(property, value, 'important')
}

export function fitOverlaySheetToContent(panel, win = globalThis.window) {
    const cap = resolveOverlaySheetPeekHeight(win)
    const minHeight = resolveOverlaySheetMinHeight(win)

    if (! panel) {
        return { fitted: cap, cap, canExpand: false }
    }

    // Skip mid-enter measures — layout thrash fights the transform slide.
    if (panel.dataset?.fffSheetEntering === 'true') {
        const cached = Number(panel.dataset.fffSheetFittedHeight)

        return {
            fitted: Number.isFinite(cached) && cached > 0 ? cached : minHeight,
            cap,
            canExpand: panel.dataset.fffOverlaySnap === 'peek',
        }
    }

    // Height changes must be instant — CSS sheet transition is transform-only,
    // but drag snap may leave SNAP_EASE on style.transition; clear it for fits.
    const previousTransition = panel.style?.transition
    if (typeof panel.style?.setProperty === 'function') {
        panel.style.setProperty('transition', 'none', 'important')
    }

    setSheetStyle(panel, 'width', '100%')
    setSheetStyle(panel, 'min-width', '0')
    setSheetStyle(panel, 'max-width', 'none')
    setSheetStyle(panel, 'left', '0')
    setSheetStyle(panel, 'right', '0')
    setSheetStyle(panel, 'inset-inline', '0')
    setSheetStyle(panel, 'bottom', '0')
    setSheetStyle(panel, 'top', 'auto')
    setSheetStyle(panel, 'height', 'auto')
    setSheetStyle(panel, 'min-height', `${minHeight}px`)
    setSheetStyle(panel, 'max-height', `${cap}px`)
    panel.style.setProperty('--fff-overlay-sheet-max-height', `${cap}px`)
    panel.style.setProperty('--fff-overlay-sheet-min-height', `${minHeight}px`)
    panel.classList.add('fff-overlay-sheet--measuring')

    const measured = Math.ceil(Math.max(
        panel.scrollHeight || 0,
        panel.getBoundingClientRect().height || 0,
        measureOverlaySheetNaturalHeight(panel),
    ))

    panel.classList.remove('fff-overlay-sheet--measuring')

    const fitted = Math.min(Math.max(measured, minHeight), cap)

    setSheetStyle(panel, 'height', `${fitted}px`)
    setSheetStyle(panel, 'min-height', `${minHeight}px`)
    setSheetStyle(panel, 'max-height', `${fitted}px`)
    panel.style.setProperty('--fff-overlay-sheet-max-height', `${fitted}px`)
    panel.dataset.fffSheetFittedHeight = String(fitted)

    void panel.offsetHeight

    if (typeof panel.style?.removeProperty === 'function') {
        panel.style.removeProperty('transition')
    } else if (panel.style) {
        panel.style.transition = previousTransition || ''
    }

    const canExpand = measured > cap + 8 || overlaySheetCanExpand(panel, fitted)

    panel.dataset.fffOverlaySnap = canExpand ? 'peek' : 'content'

    return { fitted, cap, canExpand }
}

/**
 * Sum intrinsic heights of sheet chrome + list content (avoids flex:1/min-height:0 collapse).
 *
 * @param {HTMLElement} panel
 * @returns {number}
 */
function measureOverlaySheetNaturalHeight(panel) {
    if (! panel || typeof panel.querySelectorAll !== 'function') {
        return 0
    }

    const handle = panel.querySelector('[data-fff-overlay-handle], .fff-overlay-sheet__handle')
    const search = panel.querySelector('.fi-select-input-search-ctn, .fff-teleported-menu__search, [data-fff-overlay-search]')
    const list = panel.querySelector(
        '.fi-select-input-options-ctn, .fff-select-dropdown-scroller, [data-fff-overlay-scroll], .fff-phone-field__country-list, .fff-country-field__list, .fff-timezone-field__list, .fff-currency-field__list',
    )

    let total = 0

    for (const node of [handle, search, list]) {
        if (! node || typeof node.getBoundingClientRect !== 'function') {
            continue
        }

        const boxHeight = Math.ceil(node.getBoundingClientRect().height || node.offsetHeight || 0)
        const scrollHeight = Math.ceil(node.scrollHeight || 0)

        total += Math.max(boxHeight, scrollHeight)
    }

    total += readOverlaySheetVerticalPadding(panel)

    return total
}

/**
 * Include sheet chrome padding (esp. safe-area bottom) in content-fit height.
 *
 * @param {HTMLElement} panel
 * @returns {number}
 */
function readOverlaySheetVerticalPadding(panel) {
    if (! panel || typeof globalThis.getComputedStyle !== 'function') {
        return 0
    }

    try {
        const styles = globalThis.getComputedStyle(panel)

        return Math.ceil(
            (Number.parseFloat(styles.paddingTop) || 0)
            + (Number.parseFloat(styles.paddingBottom) || 0),
        )
    } catch {
        return 0
    }
}

/**
 * @param {HTMLElement} panel
 * @param {number} peekHeight
 * @returns {boolean}
 */
export function overlaySheetCanExpand(panel, peekHeight) {
    if (! panel) {
        return false
    }

    if ((panel.scrollHeight || 0) > peekHeight + 8) {
        return true
    }

    if (typeof panel.querySelectorAll !== 'function') {
        return false
    }

    const nodes = panel.querySelectorAll('*')

    for (const node of nodes) {
        if (node.scrollHeight > node.clientHeight + 1) {
            return true
        }
    }

    return false
}

/**
 * Decide snap after a drag gesture.
 *
 * @param {{
 *   deltaY: number,
 *   velocityY: number,
 *   startHeight: number,
 *   peekHeight: number,
 *   expandedHeight: number,
 *   canExpand: boolean,
 *   wasExpanded: boolean,
 * }} input
 * @returns {'dismiss' | 'peek' | 'expanded'}
 */
export function resolveOverlaySheetSnap(input) {
    const {
        deltaY,
        velocityY,
        startHeight,
        peekHeight,
        expandedHeight,
        canExpand,
        wasExpanded,
    } = input

    const liveHeight = Math.max(0, startHeight - deltaY)
    const dismissFloor = peekHeight * (1 - DISMISS_FRACTION)
    const flickDown = velocityY > VELOCITY_THRESHOLD
    const flickUp = velocityY < -VELOCITY_THRESHOLD

    if (liveHeight < dismissFloor || (flickDown && deltaY > DRAG_THRESHOLD_PX && ! wasExpanded)) {
        return 'dismiss'
    }

    if (wasExpanded && (liveHeight < (peekHeight + expandedHeight) / 2 || (flickDown && deltaY > DRAG_THRESHOLD_PX))) {
        if (liveHeight < dismissFloor * 0.85 && flickDown) {
            return 'dismiss'
        }

        return 'peek'
    }

    if (canExpand && (liveHeight > (peekHeight + expandedHeight) / 2 || flickUp)) {
        return 'expanded'
    }

    return 'peek'
}

/**
 * Bottom-sheet drag: pull down to dismiss / collapse, pull up to expand when content overflows.
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
    let velocityY = 0
    let dragging = false
    let dragActive = false
    let pointerId = null
    let startHeight = 0

    const peekHeight = () => {
        const fitted = Number.parseInt(panel.dataset.fffSheetFittedHeight || '', 10)

        if (Number.isFinite(fitted) && fitted > 0) {
            return fitted
        }

        return resolveOverlaySheetPeekHeight(win)
    }
    const expandedHeight = () => resolveOverlaySheetExpandedHeight(win)

    const clearInlineMotion = () => {
        panel.style.transition = ''
        panel.style.transform = ''
    }

    const applySnapHeight = (snap, { animate = true } = {}) => {
        const height = snap === 'expanded' ? expandedHeight() : peekHeight()

        panel.dataset.fffOverlaySnap = snap === 'content' ? 'content' : snap
        setSheetStyle(panel, 'height', `${height}px`)
        setSheetStyle(panel, 'max-height', `${height}px`)
        panel.style.setProperty('--fff-overlay-sheet-max-height', `${height}px`)

        if (animate) {
            panel.style.transition = SNAP_EASE
            panel.style.transform = 'translate3d(0, 0, 0)'
        }
    }

    const ensureInitialSnap = () => {
        const snap = panel.dataset.fffOverlaySnap

        if (snap === 'content') {
            const fitted = peekHeight()

            setSheetStyle(panel, 'height', `${fitted}px`)
            setSheetStyle(panel, 'max-height', `${fitted}px`)
            panel.style.setProperty('--fff-overlay-sheet-max-height', `${fitted}px`)

            return
        }

        if (snap === 'expanded' || snap === 'peek') {
            if (! panel.style.height) {
                applySnapHeight(snap, { animate: false })
            }

            return
        }

        applySnapHeight('peek', { animate: false })
    }

    ensureInitialSnap()

    const handlePointerDown = (event) => {
        if (event.pointerType === 'mouse' && event.button !== 0) {
            return
        }

        const target = event.target

        if (! (target instanceof Element)) {
            return
        }

        if (target.closest('input, textarea, button, [role="button"], select, a, [data-fff-overlay-scroll]')) {
            return
        }

        const onHandle = target.closest('[data-fff-overlay-handle], .fff-overlay-sheet__handle')

        if (! onHandle && ! target.closest('.fff-overlay-sheet__header')) {
            return
        }

        dragging = true
        dragActive = false
        pointerId = event.pointerId
        startY = event.clientY
        lastY = event.clientY
        lastTs = event.timeStamp
        velocityY = 0
        startHeight = panel.getBoundingClientRect().height || peekHeight()
        panel.style.transition = 'none'
        panel.setPointerCapture?.(event.pointerId)
    }

    const handlePointerMove = (event) => {
        if (! dragging || event.pointerId !== pointerId) {
            return
        }

        const rawDelta = event.clientY - startY
        const now = event.timeStamp
        const dt = Math.max(1, now - lastTs)

        velocityY = (event.clientY - lastY) / dt
        lastY = event.clientY
        lastTs = now

        if (! dragActive) {
            if (Math.abs(rawDelta) < DRAG_THRESHOLD_PX) {
                return
            }

            dragActive = true
        }

        const wasExpanded = panel.dataset.fffOverlaySnap === 'expanded'
        const canExpand = overlaySheetCanExpand(panel, peekHeight())
        let deltaY = rawDelta

        if (! wasExpanded && ! canExpand) {
            deltaY = Math.max(0, rawDelta)
        } else if (! wasExpanded && canExpand) {
            const maxUp = expandedHeight() - startHeight
            deltaY = Math.min(Math.max(rawDelta, -maxUp), startHeight)
        } else {
            deltaY = Math.min(Math.max(rawDelta, -(expandedHeight() - startHeight)), startHeight)
        }

        const nextHeight = Math.max(0, startHeight - deltaY)

        setSheetStyle(panel, 'height', `${nextHeight}px`)
        setSheetStyle(panel, 'max-height', `${nextHeight}px`)
        panel.style.transform = 'translate3d(0, 0, 0)'
    }

    const handlePointerUp = (event) => {
        if (! dragging || event.pointerId !== pointerId) {
            return
        }

        dragging = false
        pointerId = null
        panel.releasePointerCapture?.(event.pointerId)

        if (! dragActive) {
            clearInlineMotion()

            return
        }

        dragActive = false

        const deltaY = event.clientY - startY
        const wasExpanded = panel.dataset.fffOverlaySnap === 'expanded'
        const peek = peekHeight()
        const expanded = expandedHeight()
        const canExpand = overlaySheetCanExpand(panel, peek)
        const snap = resolveOverlaySheetSnap({
            deltaY,
            velocityY,
            startHeight,
            peekHeight: peek,
            expandedHeight: expanded,
            canExpand,
            wasExpanded,
        })

        if (snap === 'dismiss') {
            panel.classList.add('is-dismissing')
            panel.style.transition = EXIT_EASE
            panel.style.transform = `translate3d(0, ${Math.max(peek, startHeight)}px, 0)`
            onDismiss()
            win.setTimeout(() => {
                panel.classList.remove('is-dismissing')
                clearInlineMotion()
                panel.style.height = ''
                panel.style.maxHeight = ''
            }, EXIT_MS)

            return
        }

        applySnapHeight(snap, { animate: true })
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
        clearInlineMotion()
        panel.style.height = ''
        panel.style.maxHeight = ''
    }
}
