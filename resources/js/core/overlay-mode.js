/** Breakpoint (px) below which teleported pickers use bottom-sheet mode. */
export const OVERLAY_SHEET_BREAKPOINT = 768

/**
 * Sheet vs panel follows viewport width only.
 *
 * Coarse pointers must not lock sheet on wide screens — that prevents
 * mobile→desktop restore on resize (DevTools device mode, hybrid laptops).
 *
 * @param {Window | undefined} [win]
 * @returns {boolean}
 */
export function prefersOverlaySheet(win = typeof window !== 'undefined' ? window : undefined) {
    if (! win) {
        return false
    }

    return win.innerWidth <= OVERLAY_SHEET_BREAKPOINT
}

/**
 * @typedef {'panel' | 'sheet'} OverlayMode
 */

/**
 * @param {Window | undefined} [win]
 * @returns {OverlayMode}
 */
export function resolveOverlayMode(win = typeof window !== 'undefined' ? window : undefined) {
    return prefersOverlaySheet(win) ? 'sheet' : 'panel'
}
