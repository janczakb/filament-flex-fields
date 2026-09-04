/**
 * Vertical overflow fade state for scrollers (dropdown option lists).
 *
 * Visibility: none | top | bottom | both
 */

export const SCROLL_FADE_ATTR = 'data-scroll-fade'

/**
 * @param {{ scrollTop?: number, scrollHeight?: number, clientHeight?: number } | null | undefined} element
 * @param {number} offset
 * @returns {'none' | 'top' | 'bottom' | 'both'}
 */
export function resolveVerticalScrollFade(element, offset = 1) {
    if (! element) {
        return 'none'
    }

    const scrollStart = Math.max(0, Number(element.scrollTop) || 0)
    const scrollSize = Number(element.scrollHeight) || 0
    const clientSize = Number(element.clientHeight) || 0
    const hasOverflow = scrollSize - clientSize > offset

    if (! hasOverflow) {
        return 'none'
    }

    const canScrollBefore = scrollStart > offset
    const canScrollAfter = scrollStart + clientSize + offset < scrollSize

    if (canScrollBefore && canScrollAfter) {
        return 'both'
    }

    if (canScrollBefore) {
        return 'top'
    }

    if (canScrollAfter) {
        return 'bottom'
    }

    return 'none'
}

/**
 * @param {{ dataset?: DOMStringMap, removeAttribute?: Function, setAttribute?: Function } | null | undefined} element
 * @param {number} offset
 * @returns {'none' | 'top' | 'bottom' | 'both'}
 */
export function updateVerticalScrollFade(element, offset = 1) {
    const visibility = resolveVerticalScrollFade(element, offset)

    if (! element) {
        return visibility
    }

    if (visibility === 'none') {
        if (element.dataset) {
            delete element.dataset.scrollFade
        }

        element.removeAttribute?.(SCROLL_FADE_ATTR)

        return visibility
    }

    if (element.dataset) {
        element.dataset.scrollFade = visibility
    }

    element.setAttribute?.(SCROLL_FADE_ATTR, visibility)

    return visibility
}
