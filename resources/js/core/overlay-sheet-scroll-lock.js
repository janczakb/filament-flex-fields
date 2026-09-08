const SHEET_OPEN_CLASS = 'fff-overlay-sheet-open'
const LOCK_ATTR = 'data-fff-overlay-sheet-locks'
const PREV_HTML_OVERFLOW = 'data-fff-prev-html-overflow'
const PREV_BODY_OVERFLOW = 'data-fff-prev-body-overflow'
const SCROLL_Y_ATTR = 'data-fff-overlay-scroll-y'
const LOCKED_SCROLL_Y = 'data-fff-overlay-locked-scroll-y'

/**
 * @param {Document} [doc]
 * @returns {number}
 */
function readLockCount(doc = globalThis.document) {
    const raw = doc?.documentElement?.getAttribute?.(LOCK_ATTR)

    return Number.parseInt(raw || '0', 10) || 0
}

/**
 * @param {Document} [doc]
 * @param {number} count
 */
function writeLockCount(doc, count) {
    if (! doc?.documentElement) {
        return
    }

    if (count <= 0) {
        doc.documentElement.removeAttribute(LOCK_ATTR)
        doc.documentElement.classList.remove(SHEET_OPEN_CLASS)

        return
    }

    doc.documentElement.setAttribute(LOCK_ATTR, String(count))
    doc.documentElement.classList.add(SHEET_OPEN_CLASS)
}

/**
 * @param {EventTarget | null | undefined} target
 * @param {HTMLElement | null | undefined} panel
 * @returns {boolean}
 */
function isSheetScrollTarget(target, panel) {
    if (! (target instanceof Element)) {
        return false
    }

    if (target.closest?.('input, textarea, [contenteditable="true"]')) {
        return true
    }

    const scrollRoot = target.closest?.(
        '[data-fff-overlay-scroll], .fff-select-dropdown-scroller, .fi-select-input-options-ctn, .fff-phone-field__country-list, .fff-country-field__list, .fff-timezone-field__list, .fff-currency-field__list',
    )

    if (! scrollRoot) {
        return false
    }

    return ! panel || panel.contains(scrollRoot)
}

/**
 * Remove leftover freezes from earlier experiments (position:fixed body, etc.).
 *
 * @param {Document} [doc]
 * @param {Window} [win]
 */
function scrubLegacyBodyFreeze(doc = globalThis.document, win = globalThis.window) {
    const body = doc?.body

    if (! body || typeof body.style?.removeProperty !== 'function') {
        return
    }

    const raw = body.getAttribute?.(SCROLL_Y_ATTR)

    if (raw == null) {
        return
    }

    const scrollY = Number.parseInt(raw, 10) || 0

    body.removeAttribute(SCROLL_Y_ATTR)
    body.style.removeProperty('position')
    body.style.removeProperty('top')
    body.style.removeProperty('left')
    body.style.removeProperty('right')
    body.style.removeProperty('width')
    body.style.removeProperty('height')
    body.style.removeProperty('touch-action')

    if (typeof win?.scrollTo === 'function') {
        win.scrollTo(0, scrollY)
    }
}

/**
 * Kill orphaned dimmers that Safari can leave as a half-screen tint after close.
 *
 * @param {Document} [doc]
 */
export function purgeOverlayBackdrops(doc = globalThis.document) {
    if (! doc?.querySelectorAll) {
        return
    }

    for (const node of doc.querySelectorAll('.fff-overlay-backdrop, [data-fff-overlay-backdrop]')) {
        if (typeof node.remove === 'function') {
            node.remove()
        }
    }
}

/**
 * @param {Document} doc
 * @param {Window} win
 */
function bindDocumentScrollGuards(doc, win) {
    if (doc.__fffOverlayScrollGuardsBound) {
        return
    }

    const onTouchMove = (event) => {
        if (readLockCount(doc) <= 0) {
            return
        }

        const panel = doc.querySelector?.('.fff-overlay-sheet.is-open, .fff-teleported-menu--sheet.is-open')

        if (isSheetScrollTarget(event.target, panel)) {
            return
        }

        event.preventDefault()
    }

    const onScroll = () => {
        if (readLockCount(doc) <= 0) {
            return
        }

        const locked = Number.parseInt(doc.documentElement?.getAttribute?.(LOCKED_SCROLL_Y) || '', 10)

        if (! Number.isFinite(locked)) {
            return
        }

        if (Math.abs((win.scrollY || 0) - locked) > 0.5) {
            win.scrollTo(0, locked)
        }
    }

    const onFocusIn = () => {
        if (readLockCount(doc) <= 0) {
            return
        }

        // iOS unlocks body scroll / pans on input focus — reassert lock.
        const html = doc.documentElement
        const body = doc.body

        html?.style?.setProperty?.('overflow', 'hidden')
        body?.style?.setProperty?.('overflow', 'hidden')
        onScroll()
    }

    doc.addEventListener('touchmove', onTouchMove, { passive: false, capture: true })
    win.addEventListener?.('scroll', onScroll, { passive: true, capture: true })
    doc.addEventListener('focusin', onFocusIn, true)

    doc.__fffOverlayScrollGuards = { onTouchMove, onScroll, onFocusIn }
    doc.__fffOverlayScrollGuardsBound = true
}

/**
 * Block page scroll while a sheet is open (overflow + touch guards — never position:fixed).
 *
 * @param {Document} [doc]
 * @param {Window} [win]
 */
export function lockOverlaySheetScroll(doc = globalThis.document, win = globalThis.window) {
    if (! doc?.documentElement) {
        return
    }

    scrubLegacyBodyFreeze(doc, win)
    bindDocumentScrollGuards(doc, win)

    const next = readLockCount(doc) + 1

    if (next === 1) {
        const html = doc.documentElement
        const body = doc.body
        const scrollY = Number(win?.scrollY || 0) || 0

        html.setAttribute(LOCKED_SCROLL_Y, String(scrollY))

        if (html && typeof html.style?.setProperty === 'function') {
            if (! html.hasAttribute(PREV_HTML_OVERFLOW)) {
                html.setAttribute(PREV_HTML_OVERFLOW, html.style.overflow || '')
            }

            html.style.setProperty('overflow', 'hidden')
        }

        if (body && typeof body.style?.setProperty === 'function') {
            if (! body.hasAttribute(PREV_BODY_OVERFLOW)) {
                body.setAttribute(PREV_BODY_OVERFLOW, body.style.overflow || '')
            }

            body.style.setProperty('overflow', 'hidden')
        }
    }

    writeLockCount(doc, next)
}

/**
 * @param {Document} [doc]
 * @param {Window} [win]
 */
export function unlockOverlaySheetScroll(doc = globalThis.document, win = globalThis.window) {
    if (! doc?.documentElement) {
        return
    }

    const next = Math.max(0, readLockCount(doc) - 1)

    writeLockCount(doc, next)

    if (next !== 0) {
        return
    }

    const html = doc.documentElement
    const body = doc.body
    const lockedY = Number.parseInt(html.getAttribute?.(LOCKED_SCROLL_Y) || '', 10)

    html.removeAttribute?.(LOCKED_SCROLL_Y)

    if (html && typeof html.style?.removeProperty === 'function') {
        const prev = html.getAttribute?.(PREV_HTML_OVERFLOW)

        html.removeAttribute?.(PREV_HTML_OVERFLOW)

        if (prev) {
            html.style.setProperty('overflow', prev)
        } else {
            html.style.removeProperty('overflow')
        }
    }

    if (body && typeof body.style?.removeProperty === 'function') {
        const prev = body.getAttribute?.(PREV_BODY_OVERFLOW)

        body.removeAttribute?.(PREV_BODY_OVERFLOW)

        if (prev) {
            body.style.setProperty('overflow', prev)
        } else {
            body.style.removeProperty('overflow')
        }
    }

    scrubLegacyBodyFreeze(doc, win)
    purgeOverlayBackdrops(doc)

    const active = doc.activeElement

    if (active && typeof active.blur === 'function' && active !== doc.body) {
        const tag = String(active.tagName || '').toLowerCase()

        if (tag === 'input' || tag === 'textarea' || active.isContentEditable) {
            active.blur()
        }
    }

    if (typeof win?.scrollTo === 'function') {
        win.scrollTo(0, Number.isFinite(lockedY) ? lockedY : (Number(win.scrollY || 0) || 0))
    }
}

/**
 * Keyboard inset via Apple's VisualViewport API (Safari has no env(keyboard-*)).
 *
 * @param {Window} [win]
 * @returns {number}
 */
export function resolveOverlayKeyboardInset(win = globalThis.window) {
    const vv = win?.visualViewport

    if (! vv || typeof vv.height !== 'number') {
        return 0
    }

    return Math.max(0, Math.round((win.innerHeight || 0) - vv.height - (vv.offsetTop || 0)))
}

/**
 * Dock sheet flush above the keyboard (visual viewport bottom). No fake paint.
 *
 * @param {HTMLElement | null | undefined} panel
 * @param {Window} [win]
 * @returns {number} keyboard inset px
 */
export function syncOverlaySheetToVisualViewport(panel, win = globalThis.window) {
    if (! panel || typeof panel.style?.setProperty !== 'function') {
        return 0
    }

    const inset = resolveOverlayKeyboardInset(win)
    const vv = win?.visualViewport

    panel.style.setProperty('bottom', inset > 0 ? `${inset}px` : '0', 'important')
    panel.style.setProperty('left', '0', 'important')
    panel.style.setProperty('right', '0', 'important')
    panel.style.setProperty('inset-inline', '0', 'important')
    panel.style.setProperty('margin-bottom', '0', 'important')
    panel.style.setProperty('top', 'auto', 'important')

    if (! vv || inset <= 0) {
        return 0
    }

    // Keep the open sheet inside the visible viewport above the keyboard.
    const fitted = Number.parseInt(panel.dataset?.fffSheetFittedHeight || '', 10)
    const minHeight = Number.parseInt(panel.dataset?.fffOverlaySheetMinHeight || '', 10)
        || Math.min(280, Math.round((win.innerHeight || 0) * 0.5))
    const maxVisible = Math.max(minHeight, Math.floor(vv.height - 4))
    const target = Math.min(
        Number.isFinite(fitted) && fitted > 0 ? Math.max(fitted, minHeight) : minHeight,
        maxVisible,
    )

    panel.style.setProperty('height', `${target}px`, 'important')
    panel.style.setProperty('max-height', `${target}px`, 'important')
    panel.style.setProperty('--fff-overlay-sheet-max-height', `${target}px`)

    return inset
}

/**
 * @param {HTMLElement | null | undefined} panel
 * @param {Window} [win]
 * @returns {() => void}
 */
export function bindOverlaySheetVisualViewport(panel, win = globalThis.window) {
    if (! panel || ! win?.visualViewport) {
        return () => {}
    }

    let raf = 0

    const schedule = () => {
        if (raf) {
            return
        }

        raf = win.requestAnimationFrame?.(() => {
            raf = 0

            if (! panel.isConnected) {
                return
            }

            if (! panel.classList?.contains?.('is-open') && ! panel.classList?.contains?.('fff-overlay-sheet')) {
                return
            }

            syncOverlaySheetToVisualViewport(panel, win)
        }) ?? 0

        if (! raf) {
            syncOverlaySheetToVisualViewport(panel, win)
        }
    }

    const vv = win.visualViewport

    vv.addEventListener('resize', schedule)
    vv.addEventListener('scroll', schedule)
    win.addEventListener('resize', schedule)
    panel.ownerDocument?.addEventListener?.('focusin', schedule, true)
    panel.ownerDocument?.addEventListener?.('focusout', schedule, true)

    schedule()

    return () => {
        if (raf && typeof win.cancelAnimationFrame === 'function') {
            win.cancelAnimationFrame(raf)
        }

        vv.removeEventListener('resize', schedule)
        vv.removeEventListener('scroll', schedule)
        win.removeEventListener('resize', schedule)
        panel.ownerDocument?.removeEventListener?.('focusin', schedule, true)
        panel.ownerDocument?.removeEventListener?.('focusout', schedule, true)
        panel.style?.removeProperty?.('bottom')
    }
}

/**
 * Sheet chrome flush — respects keyboard inset when VisualViewport reports one.
 *
 * @param {HTMLElement | null | undefined} panel
 * @param {Window} [win]
 * @returns {number}
 */
export function flushOverlaySheetToScreenBottom(panel, win = globalThis.window) {
    return syncOverlaySheetToVisualViewport(panel, win)
}

/**
 * @deprecated
 * @param {HTMLElement | null | undefined} panel
 * @param {Window} [win]
 */
export function pinOverlaySheetToVisualViewport(panel, win = globalThis.window) {
    return syncOverlaySheetToVisualViewport(panel, win)
}
