const SHEET_OPEN_CLASS = 'fff-overlay-sheet-open'
const LOCK_ATTR = 'data-fff-overlay-sheet-locks'

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
 * Lock page scroll while a bottom sheet is open. Nested opens use a ref-count.
 *
 * @param {Document} [doc]
 */
export function lockOverlaySheetScroll(doc = globalThis.document) {
    if (! doc?.documentElement) {
        return
    }

    writeLockCount(doc, readLockCount(doc) + 1)
}

/**
 * Release one sheet scroll lock.
 *
 * @param {Document} [doc]
 */
export function unlockOverlaySheetScroll(doc = globalThis.document) {
    if (! doc?.documentElement) {
        return
    }

    writeLockCount(doc, Math.max(0, readLockCount(doc) - 1))
}
