/**
 * @param {number} value
 * @param {number} boundaryStart
 * @param {number} boundaryEnd
 * @param {number} size
 */
function shiftCrossAxis(value, boundaryStart, boundaryEnd, size) {
    const max = boundaryEnd - size

    if (max < boundaryStart) {
        return boundaryStart
    }

    return Math.max(boundaryStart, Math.min(value, max))
}

/**
 * @param {HTMLElement} element
 */
export function getPopupPositionRect(element) {
    const rect = element.getBoundingClientRect()
    const width = element.offsetWidth || rect.width
    const height = element.offsetHeight || rect.height

    return {
        left: rect.left,
        top: rect.top,
        width,
        height,
    }
}

/**
 * @param {DOMRect} triggerRect
 * @param {DOMRect} popupRect
 * @param {DOMRect} boundaryRect
 * @param {{ sideOffset?: number, boundaryOffset?: number }} options
 */
export function clampHorizontalCenter(triggerRect, popupRect, boundaryRect, options = {}) {
    const sideOffset = options.sideOffset ?? 12
    const boundaryOffset = options.boundaryOffset ?? 8

    const centerX = triggerRect.left + (triggerRect.width / 2)
    const halfWidth = popupRect.width / 2

    const minCenter = boundaryRect.left + boundaryOffset + halfWidth
    const maxCenter = boundaryRect.right - boundaryOffset - halfWidth

    const clampedCenter = halfWidth > 0
        ? Math.max(minCenter, Math.min(centerX, maxCenter))
        : centerX

    return {
        left: clampedCenter - halfWidth,
        top: triggerRect.top - popupRect.height - sideOffset,
        centerX: clampedCenter,
    }
}

/**
 * @param {DOMRect} triggerRect
 * @param {DOMRect} boundaryRect
 * @param {{ side?: 'top' | 'bottom', sideOffset?: number, boundaryOffset?: number }} options
 */
export function getAvailableMenuHeight(triggerRect, boundaryRect, options = {}) {
    const side = options.side ?? 'top'
    const sideOffset = options.sideOffset ?? 8
    const boundaryOffset = options.boundaryOffset ?? 8

    if (side === 'top') {
        return Math.max(0, triggerRect.top - boundaryRect.top - boundaryOffset - sideOffset)
    }

    return Math.max(0, boundaryRect.bottom - triggerRect.bottom - boundaryOffset - sideOffset)
}

/**
 * Position a popup with `position: fixed` above its trigger, clamped inside a boundary.
 *
 * @param {HTMLElement} popupEl
 * @param {HTMLElement} triggerEl
 * @param {HTMLElement} boundaryEl
 * @param {{ side?: 'top' | 'bottom', align?: 'start' | 'center' | 'end', sideOffset?: number, alignOffset?: number, boundaryOffset?: number }} options
 */
export function positionFixedPopup(popupEl, triggerEl, boundaryEl, options = {}) {
    if (! popupEl || ! triggerEl || ! boundaryEl) {
        return
    }

    const side = options.side ?? 'top'
    const align = options.align ?? 'center'
    const sideOffset = options.sideOffset ?? 8
    const alignOffset = options.alignOffset ?? 0
    const boundaryOffset = options.boundaryOffset ?? 8

    const previousVisibility = popupEl.style.visibility
    const previousDisplay = popupEl.style.display
    const previousPointerEvents = popupEl.style.pointerEvents

    popupEl.style.visibility = 'hidden'
    popupEl.style.display = 'block'
    popupEl.style.pointerEvents = 'none'
    popupEl.style.position = 'fixed'
    popupEl.style.inset = 'auto'
    popupEl.style.margin = '0'
    popupEl.style.transform = 'none'

    const triggerRect = triggerEl.getBoundingClientRect()
    const popupRect = getPopupPositionRect(popupEl)
    const boundaryRect = boundaryEl.getBoundingClientRect()

    let top = 0
    let left = 0

    if (side === 'top') {
        top = triggerRect.top - popupRect.height - sideOffset
    } else {
        top = triggerRect.bottom + sideOffset
    }

    if (align === 'start') {
        left = triggerRect.left + alignOffset
    } else if (align === 'end') {
        left = triggerRect.right - popupRect.width + alignOffset
    } else {
        left = triggerRect.left + ((triggerRect.width - popupRect.width) / 2) + alignOffset
    }

    if (side === 'top' || side === 'bottom') {
        left = shiftCrossAxis(
            left,
            boundaryRect.left + boundaryOffset,
            boundaryRect.right - boundaryOffset,
            popupRect.width,
        )
    }

    if (side === 'top') {
        top = Math.max(boundaryRect.top + boundaryOffset, top)
    } else {
        top = shiftCrossAxis(
            top,
            boundaryRect.top + boundaryOffset,
            boundaryRect.bottom - boundaryOffset,
            popupRect.height,
        )
    }

    const availableHeight = getAvailableMenuHeight(triggerRect, boundaryRect, {
        side,
        sideOffset,
        boundaryOffset,
    })

    if (availableHeight > 0) {
        popupEl.style.setProperty('--fff-video-field-menu-max-height', `${Math.floor(availableHeight)}px`)
    }

    popupEl.style.left = `${left}px`
    popupEl.style.top = `${top}px`
    popupEl.style.right = 'auto'
    popupEl.style.bottom = 'auto'

    popupEl.style.visibility = previousVisibility
    popupEl.style.display = previousDisplay
    popupEl.style.pointerEvents = previousPointerEvents
}

/**
 * Position a popup element above its trigger, clamped inside a boundary.
 *
 * @param {HTMLElement} popupEl
 * @param {HTMLElement} triggerEl
 * @param {HTMLElement} boundaryEl
 * @param {{ sideOffset?: number, boundaryOffset?: number }} options
 */
export function positionTopPopup(popupEl, triggerEl, boundaryEl, options = {}) {
    positionFixedPopup(popupEl, triggerEl, boundaryEl, {
        side: 'top',
        align: 'center',
        sideOffset: options.sideOffset ?? 12,
        boundaryOffset: options.boundaryOffset ?? 8,
    })
}

/**
 * @param {HTMLElement} wrapperEl
 * @param {HTMLElement} boundaryEl
 * @param {{ sideOffset?: number, boundaryOffset?: number }} options
 */
export function positionControlTooltip(wrapperEl, boundaryEl, options = {}) {
    const tooltip = wrapperEl.querySelector('.fff-video-field__tooltip')
    const trigger = wrapperEl.querySelector('button, [role="button"]')

    if (! tooltip || ! trigger || ! boundaryEl) {
        return
    }

    positionTopPopup(tooltip, trigger, boundaryEl, options)
}
