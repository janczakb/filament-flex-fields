/**
 * Align the invisible noUi handle with the decorative thumb chrome.
 *
 * noUi places the handle midpoint on the raw track ratio point (0…1 of host width).
 * Our chrome insets the thumb by (padding + half thumb) on each end, so the touch
 * target must slide by that edge amount: +edge at min, 0 at mid, −edge at max.
 */
export function resolveFlexSliderHandleVisualOffset({
    position = 0,
    padding = 0,
    thumbSize = 0,
    direction = 1,
} = {}) {
    const safePosition = Number.isFinite(position) ? Math.min(1, Math.max(0, position)) : 0
    const safePadding = Number.isFinite(padding) ? padding : 0
    const safeThumbSize = Number.isFinite(thumbSize) && thumbSize > 0 ? thumbSize : 0
    const safeDirection = direction < 0 ? -1 : 1
    const edge = safePadding + (safeThumbSize / 2)

    return safeDirection * edge * (1 - (2 * safePosition))
}
