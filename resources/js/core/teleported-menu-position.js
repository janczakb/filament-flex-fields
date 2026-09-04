/**
 * Resolve fixed horizontal offset for a teleported menu anchored to a trigger.
 *
 * @param {{
 *   triggerRect: { left: number, right: number },
 *   menuWidth: number,
 *   align?: 'start' | 'end',
 *   direction?: string,
 *   viewportPadding?: number,
 *   windowWidth?: number,
 * }} options
 */
export function resolveTeleportedMenuHorizontalLeft({
    triggerRect,
    menuWidth,
    align = 'start',
    direction = 'ltr',
    viewportPadding = 16,
    windowWidth,
}) {
    const viewport = windowWidth ?? (typeof window !== 'undefined' ? window.innerWidth : 0)
    const isRtl = direction === 'rtl'
    let left

    if (align === 'end') {
        left = isRtl ? triggerRect.left : triggerRect.right - menuWidth
    } else {
        left = isRtl ? triggerRect.right - menuWidth : triggerRect.left
    }

    if (left + menuWidth > viewport - viewportPadding) {
        left = viewport - menuWidth - viewportPadding
    }

    if (left < viewportPadding) {
        left = viewportPadding
    }

    return left
}

/**
 * Resolve fixed vertical offset for a teleported menu anchored to a trigger.
 * Filament Select `position('top'|'bottom')` maps to forcedPlacement.
 *
 * @param {{
 *   triggerRect: { top: number, bottom: number },
 *   panelHeight: number,
 *   gap?: number,
 *   viewportPadding?: number,
 *   windowHeight?: number,
 *   forcedPlacement?: 'top' | 'bottom' | null,
 * }} options
 * @returns {{ top: number, opensAbove: boolean }}
 */
export function resolveTeleportedMenuVerticalPlacement({
    triggerRect,
    panelHeight,
    gap = 6,
    viewportPadding = 16,
    windowHeight,
    forcedPlacement = null,
} = {}) {
    const viewport = windowHeight ?? (typeof window !== 'undefined' ? window.innerHeight : 0)
    const belowTop = triggerRect.bottom + gap
    const aboveTop = triggerRect.top - panelHeight - gap

    if (forcedPlacement === 'top') {
        return { top: aboveTop, opensAbove: true }
    }

    if (forcedPlacement === 'bottom') {
        return { top: belowTop, opensAbove: false }
    }

    let top = belowTop
    let opensAbove = false

    if (belowTop + panelHeight > viewport - viewportPadding && aboveTop >= viewportPadding) {
        top = aboveTop
        opensAbove = true
    }

    return { top, opensAbove }
}
