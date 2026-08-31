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
