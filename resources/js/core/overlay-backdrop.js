const BACKDROP_CLASS = 'fff-overlay-backdrop'

/** Sheet/panel sit above the dimmer; keep numeric so getComputedStyle math never yields NaN. */
export const OVERLAY_SHEET_PANEL_Z_INDEX = 60
export const OVERLAY_SHEET_BACKDROP_Z_INDEX = 50

/**
 * @param {Document} document
 * @param {string} overlayId
 * @param {{ onDismiss?: () => void, zIndex?: number, beforeElement?: Element | null }} [options]
 * @returns {HTMLElement}
 */
export function createOverlayBackdrop(document, overlayId, options = {}) {
    const existing = document.querySelector(`[data-fff-overlay-backdrop="${overlayId}"]`)
    const zIndex = Number.isFinite(options.zIndex)
        ? options.zIndex
        : OVERLAY_SHEET_BACKDROP_Z_INDEX

    if (existing && typeof existing === 'object') {
        existing.classList.remove('is-leaving')
        existing.style?.setProperty?.('z-index', String(zIndex), 'important')
        if (existing.style) {
            existing.style.zIndex = String(zIndex)
            existing.style.removeProperty('display')
        }
        ensureBackdropBehindPanel(existing, options.beforeElement)
        requestAnimationFrame(() => {
            existing.classList.add('is-visible')
        })

        return existing
    }

    const backdrop = document.createElement('div')
    backdrop.className = BACKDROP_CLASS
    backdrop.dataset.fffOverlayBackdrop = overlayId
    backdrop.setAttribute('aria-hidden', 'true')
    backdrop.style.setProperty?.('z-index', String(zIndex), 'important')
    backdrop.style.zIndex = String(zIndex)

    backdrop.addEventListener?.('click', () => {
        options.onDismiss?.()
    })

    const body = document.body
    const before = options.beforeElement

    if (body && before?.parentNode === body && typeof body.insertBefore === 'function') {
        body.insertBefore(backdrop, before)
    } else {
        body?.appendChild?.(backdrop)
    }

    ensureBackdropBehindPanel(backdrop, before)

    requestAnimationFrame(() => {
        backdrop.classList.add('is-visible')
    })

    return backdrop
}

/**
 * Keep the sheet after the backdrop in DOM order so equal/ambiguous stacking
 * cannot paint the dimmer on top of the drawer (first-open iOS Safari).
 *
 * @param {HTMLElement} backdrop
 * @param {Element | null | undefined} panel
 */
function ensureBackdropBehindPanel(backdrop, panel) {
    if (! backdrop || ! panel || panel === backdrop) {
        return
    }

    const parent = panel.parentNode

    if (! parent || backdrop.parentNode !== parent || typeof parent.insertBefore !== 'function') {
        return
    }

    let sibling = panel.previousSibling

    while (sibling) {
        if (sibling === backdrop) {
            return
        }

        sibling = sibling.previousSibling
    }

    parent.insertBefore(backdrop, panel)
}

/**
 * Remove immediately — delayed transitionend removal left a half-screen tint on iOS 26.
 *
 * @param {Document} document
 * @param {string} overlayId
 */
export function removeOverlayBackdrop(document, overlayId) {
    const nodes = document.querySelectorAll?.(
        overlayId
            ? `[data-fff-overlay-backdrop="${overlayId}"]`
            : '.fff-overlay-backdrop, [data-fff-overlay-backdrop]',
    )

    if (! nodes?.length) {
        return
    }

    for (const backdrop of nodes) {
        backdrop.classList.remove('is-visible', 'is-leaving')

        if (backdrop.style) {
            backdrop.style.display = 'none'
            backdrop.style.opacity = '0'
            backdrop.style.pointerEvents = 'none'
        }

        if (typeof backdrop.remove === 'function') {
            backdrop.remove()
        }
    }
}
