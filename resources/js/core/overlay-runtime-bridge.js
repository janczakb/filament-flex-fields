import { bootGlobalOverlayRuntime } from './overlay-runtime.js'

/**
 * Bridge so teleported searchable menus use createOverlayRuntime for exclusive
 * arbitration, positioning, and scroll/resize listeners. Glass theme + open
 * reveal animation stay in searchable-select-menu (manageVisibility: false).
 */
export function ensureOverlayRuntimeBridge() {
    if (typeof window === 'undefined') {
        return null
    }

    return bootGlobalOverlayRuntime()
}

/**
 * Claim exclusive overlay ownership (lock-only; no panel DOM).
 *
 * @param {string} id
 * @param {{ onDisplace?: (() => void) | null }} [options]
 */
export function claimOverlayExclusive(id, options = {}) {
    const runtime = ensureOverlayRuntimeBridge()

    runtime?.claimExclusive?.(id, {
        exclusive: true,
        onDisplace: options.onDisplace ?? null,
    })
}

/**
 * Release a lock-only exclusive claim.
 *
 * @param {string} id
 */
export function releaseOverlayExclusive(id) {
    const runtime = ensureOverlayRuntimeBridge()

    runtime?.releaseExclusive?.(id)
}

/**
 * Open a managed panel overlay (position + exclusive + listeners).
 * Pass manageVisibility: false when the host owns glass reveal/hide.
 *
 * @param {{
 *   id: string,
 *   panel: HTMLElement,
 *   anchor: HTMLElement,
 *   mode?: 'panel' | 'sheet',
 *   exclusive?: boolean,
 *   manageVisibility?: boolean,
 *   onDisplace?: (() => void) | null,
 *   minWidth?: number,
 *   matchTriggerWidth?: boolean,
 *   gap?: number,
 * }} options
 */
export function openOverlayPanel(options) {
    const runtime = ensureOverlayRuntimeBridge()

    runtime?.open?.(options)
}

/**
 * @param {string} id
 */
export function closeOverlayPanel(id) {
    const runtime = ensureOverlayRuntimeBridge()

    runtime?.close?.(id)
}

/**
 * @param {string} id
 */
export function updateOverlayPanelPosition(id) {
    const runtime = ensureOverlayRuntimeBridge()

    runtime?.updatePosition?.(id)
}

/**
 * @param {string} id
 * @param {number} openLatencyMs
 */
export function emitOverlayOpenLatency(id, openLatencyMs) {
    const runtime = ensureOverlayRuntimeBridge()

    runtime?.notifyOpenLatency?.(id, openLatencyMs)
}
