/**
 * Browser bridge for enterprise ObservabilityHooks when PHP cannot be reached
 * (overlay exclusive open, client-side select.search). Listeners:
 *
 *   window.addEventListener('fff:observability', (e) => {
 *     // e.detail.event — 'overlay.open' | 'select.search' | ...
 *   })
 *
 * PHP `ObservabilityHooks::emit` respects `enterprise.enabled`; this CustomEvent
 * always fires so client tooling can subscribe independently.
 *
 * @param {string} event
 * @param {Record<string, unknown>} [payload]
 * @param {{ window?: Window }} [context]
 */
export function emitObservabilityEvent(event, payload = {}, context = {}) {
    const targetWindow = context.window
        ?? (typeof window !== 'undefined' ? window : null)

    if (! targetWindow || typeof targetWindow.CustomEvent !== 'function') {
        return
    }

    targetWindow.dispatchEvent(new targetWindow.CustomEvent('fff:observability', {
        detail: {
            event,
            ...payload,
        },
    }))
}
