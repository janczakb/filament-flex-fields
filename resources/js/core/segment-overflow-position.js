/**
 * Shared horizontal segment overflow positioning + scroll-shadow markers.
 * Used by Alpine (segment-scroll-shadow) and the blocking SSR IIFE bundle.
 */

export const SEGMENT_OVERFLOW_ATTR = 'data-fff-segment-overflow'

export const SEGMENT_OVERFLOW_SELECTOR = `[${SEGMENT_OVERFLOW_ATTR}]`

export const SEGMENT_SELECTED_SELECTOR = '[data-segment-selected="true"]'

export const SEGMENT_PREPARING_CLASS = 'fff-segment-scroll-shadow--preparing'

/**
 * @returns {{ canScrollBefore: boolean, canScrollAfter: boolean }}
 */
export function updateHorizontalSegmentScrollShadow(element, offset = 1) {
    if (! element) {
        return { canScrollBefore: false, canScrollAfter: false }
    }

    const scrollStart = Math.abs(element.scrollLeft)
    const scrollSize = element.scrollWidth
    const clientSize = element.clientWidth
    const hasOverflow = scrollSize - clientSize > offset

    if (! hasOverflow) {
        delete element.dataset.leftScroll
        delete element.dataset.rightScroll
        delete element.dataset.leftRightScroll

        return { canScrollBefore: false, canScrollAfter: false }
    }

    const canScrollBefore = scrollStart > offset
    const canScrollAfter = scrollStart + clientSize + offset < scrollSize

    if (canScrollBefore && canScrollAfter) {
        element.dataset.leftRightScroll = 'true'
        delete element.dataset.leftScroll
        delete element.dataset.rightScroll
    } else if (canScrollBefore) {
        element.dataset.leftScroll = 'true'
        delete element.dataset.rightScroll
        delete element.dataset.leftRightScroll
    } else if (canScrollAfter) {
        element.dataset.rightScroll = 'true'
        delete element.dataset.leftScroll
        delete element.dataset.leftRightScroll
    } else {
        delete element.dataset.leftScroll
        delete element.dataset.rightScroll
        delete element.dataset.leftRightScroll
    }

    return { canScrollBefore, canScrollAfter }
}

export function scrollSegmentItemIntoView(scrollElement, selectedElement, smooth = false, assignDirectly = false) {
    if (! scrollElement || ! selectedElement) {
        return
    }

    const targetLeft = selectedElement.offsetLeft - ((scrollElement.clientWidth - selectedElement.offsetWidth) / 2)
    const left = Math.max(0, targetLeft)

    if (assignDirectly) {
        scrollElement.scrollLeft = left

        return
    }

    scrollElement.scrollTo({
        left,
        behavior: smooth ? 'smooth' : 'auto',
    })
}

/**
 * Position overflow scroll + fade markers before Alpine hydrates (SSR / blocking script).
 *
 * @returns {boolean} Whether positioning ran on this call.
 */
export function positionSegmentOverflowScrollElement(scrollElement) {
    if (! scrollElement || scrollElement.dataset.ssrScrollPositioned === 'true') {
        return false
    }

    const selected = scrollElement.querySelector(SEGMENT_SELECTED_SELECTOR)

    if (selected) {
        scrollSegmentItemIntoView(scrollElement, selected, false, true)
    }

    scrollElement.dataset.ssrScrollPositioned = 'true'
    scrollElement.classList.remove(SEGMENT_PREPARING_CLASS)
    updateHorizontalSegmentScrollShadow(scrollElement)

    return true
}

/**
 * Position every unprepared overflow shell under root (modal, morph target, or document).
 *
 * @returns {number} How many shells were positioned on this call.
 */
export function bootSegmentOverflowElements(root) {
    const scope = root ?? (typeof globalThis.document !== 'undefined' ? globalThis.document : null)

    if (! scope?.querySelectorAll) {
        return 0
    }

    const pendingSelector = `${SEGMENT_OVERFLOW_SELECTOR}:not([data-ssr-scroll-positioned="true"])`
    const elements = []

    if (scope.matches?.(SEGMENT_OVERFLOW_SELECTOR) && scope.dataset.ssrScrollPositioned !== 'true') {
        elements.push(scope)
    }

    scope.querySelectorAll(pendingSelector).forEach((element) => {
        if (! elements.includes(element)) {
            elements.push(element)
        }
    })

    let positioned = 0

    for (const element of elements) {
        if (positionSegmentOverflowScrollElement(element)) {
            positioned++
        }
    }

    return positioned
}
