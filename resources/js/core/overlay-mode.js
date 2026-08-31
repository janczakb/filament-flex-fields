/** Breakpoint (px) below which teleported pickers use bottom-sheet mode. */
export const OVERLAY_SHEET_BREAKPOINT = 768

/**
 * @param {Window | undefined} [win]
 * @returns {boolean}
 */
export function prefersOverlaySheet(win = typeof window !== 'undefined' ? window : undefined) {
    if (! win) {
        return false
    }

    const coarsePointer = win.matchMedia?.('(pointer: coarse)')?.matches === true
    const narrowViewport = win.innerWidth <= OVERLAY_SHEET_BREAKPOINT

    return coarsePointer || narrowViewport
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
