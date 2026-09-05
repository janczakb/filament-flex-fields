import { emitObservabilityEvent } from './observability.js'
import { recordOverlayOpenLatency } from './overlay-telemetry-p95.js'
import { resolveTeleportedMenuHorizontalLeft, resolveTeleportedMenuVerticalPlacement } from './teleported-menu-position.js'
import { prefersReducedMotion, resolveTeleportedMenuZIndex } from './theme-utils.js'
import { fitOverlaySheetToContent } from './overlay-sheet-dismiss.js'
import { OVERLAY_SHEET_PANEL_Z_INDEX } from './overlay-backdrop.js'

const OVERFLOW_SCROLL_RE = /(auto|scroll|overlay)/
const VIEWPORT_PADDING = 16
const PANEL_GAP = 6

/**
 * @typedef {'panel' | 'sheet'} OverlayMode
 */

/**
 * @typedef {object} OverlayOpenOptions
 * @property {string} id
 * @property {HTMLElement} panel
 * @property {HTMLElement} anchor
 * @property {OverlayMode} [mode]
 * @property {boolean} [exclusive]
 */

/**
 * @typedef {object} OverlayTelemetryEvent
 * @property {'open' | 'close'} type
 * @property {string} id
 * @property {number} [durationMs]
 * @property {number} [openLatencyMs]
 */

/**
 * @typedef {(event: OverlayTelemetryEvent) => void} OverlayTelemetryCallback
 */

/**
 * Shared overlay controller for searchable menus and future pickers (M2).
 * `open()` owns exclusive arbitration, panel positioning, and scroll/resize
 * listeners. Pass `manageVisibility: false` when the host keeps glass reveal
 * animation (searchable teleported menus). `claimExclusive()` covers the
 * lock-only window before a panel is anchored.
 *
 * @param {{ document: Document, window: Window }} context
 */
export function createOverlayRuntime({ document, window }) {
    /** @type {Map<string, {
     *   id: string,
     *   panel: HTMLElement,
     *   anchor: HTMLElement,
     *   mode: OverlayMode,
     *   exclusive: boolean,
     *   manageVisibility: boolean,
     *   onDisplace: (() => void) | null,
     *   minWidth: number,
     *   matchTriggerWidth: boolean,
     *   align: 'start' | 'end',
     *   gap: number,
     *   openedAt: number,
     *   scrollHandler: ((event: Event) => void) | null,
     *   resizeHandler: (() => void) | null,
     *   scrollParents: Element[],
     * }>} */
    const overlays = new Map()

    /**
     * Lock-only exclusive owners (no panel DOM) until upgraded via `open()`.
     *
     * @type {Map<string, {
     *   id: string,
     *   exclusive: boolean,
     *   openedAt: number,
     *   onDisplace: (() => void) | null,
     * }>}
     */
    const exclusiveClaims = new Map()

    /** @type {Set<OverlayTelemetryCallback>} */
    const telemetryListeners = new Set()

    let destroyed = false
    let positionRaf = 0

    const requestFrame = typeof window.requestAnimationFrame === 'function'
        ? window.requestAnimationFrame.bind(window)
        : (callback) => setTimeout(callback, 0)

    const cancelFrame = typeof window.cancelAnimationFrame === 'function'
        ? window.cancelAnimationFrame.bind(window)
        : clearTimeout

    function emitTelemetry(event) {
        for (const listener of telemetryListeners) {
            listener(event)
        }

        if (event?.type === 'open') {
            emitObservabilityEvent('overlay.open', {
                id: event.id,
                openLatencyMs: event.openLatencyMs ?? null,
            }, { window })

            if (typeof event.openLatencyMs === 'number') {
                recordOverlayOpenLatency(event.openLatencyMs)
            }
        }
    }

    function resolveScrollableParents(element) {
        const parents = []
        let node = element?.parentElement

        while (node && node !== document.documentElement) {
            const style = window.getComputedStyle(node)

            if (OVERFLOW_SCROLL_RE.test(`${style.overflow}${style.overflowY}${style.overflowX}`)) {
                parents.push(node)
            }

            node = node.parentElement
        }

        return parents
    }

    function clearPanelMode(panel) {
        panel.classList.remove('fff-overlay-panel', 'fff-overlay-sheet', 'is-open', 'fff-overlay-reduced-motion')
        panel.removeAttribute('data-fff-overlay-mode')
        panel.removeAttribute('data-fff-overlay-id')
        panel.removeAttribute('data-fff-overlay-snap')
        panel.removeAttribute('data-fff-overlay-sheet')
        panel.style.removeProperty('position')
        panel.style.removeProperty('top')
        panel.style.removeProperty('left')
        panel.style.removeProperty('right')
        panel.style.removeProperty('bottom')
        panel.style.removeProperty('inset-inline')
        panel.style.removeProperty('width')
        panel.style.removeProperty('height')
        panel.style.removeProperty('max-height')
        panel.style.removeProperty('min-width')
        panel.style.removeProperty('max-width')
        panel.style.removeProperty('z-index')
        panel.style.removeProperty('margin-top')
        panel.style.removeProperty('transform')
        panel.style.removeProperty('transition')
        panel.hidden = true
        panel.setAttribute('aria-hidden', 'true')
    }

    function applyReducedMotion(panel) {
        const reducedMotion = prefersReducedMotion()

        panel.classList.toggle('fff-overlay-reduced-motion', reducedMotion)

        return reducedMotion
    }

    function positionPanel(entry) {
        const { panel, anchor } = entry
        const gap = entry.gap ?? PANEL_GAP
        const minWidth = entry.minWidth ?? 288
        const matchTriggerWidth = entry.matchTriggerWidth !== false
        const align = entry.align === 'end' ? 'end' : 'start'
        const rect = anchor.getBoundingClientRect()
        const menuWidth = matchTriggerWidth
            ? Math.min(Math.max(rect.width, minWidth), window.innerWidth - (VIEWPORT_PADDING * 2))
            : Math.min(minWidth, window.innerWidth - (VIEWPORT_PADDING * 2))

        const direction = typeof window.getComputedStyle === 'function'
            ? window.getComputedStyle(anchor).direction || 'ltr'
            : 'ltr'
        const forcedPlacement = entry.position === 'top' || entry.position === 'bottom'
            ? entry.position
            : null

        panel.style.position = 'fixed'
        const widthPx = `${Math.round(menuWidth)}px`
        panel.style.width = widthPx
        if (typeof panel.style.setProperty === 'function') {
            panel.style.setProperty('width', widthPx, 'important')
            panel.style.setProperty('max-width', 'none', 'important')
            panel.style.setProperty('--fff-select-menu-width', widthPx)
        }
        panel.style.zIndex = resolveTeleportedMenuZIndex()
        panel.style.top = `${Math.round(rect.bottom + gap)}px`
        panel.style.left = `${Math.round(rect.left)}px`
        panel.style.marginTop = '0'
        if (typeof panel.style.removeProperty === 'function') {
            panel.style.removeProperty('bottom')
            panel.style.removeProperty('height')
            panel.style.removeProperty('max-height')
            panel.style.removeProperty('min-height')
        }

        const panelRect = panel.getBoundingClientRect()
        let left = resolveTeleportedMenuHorizontalLeft({
            triggerRect: rect,
            menuWidth: panelRect.width,
            align,
            direction,
            viewportPadding: VIEWPORT_PADDING,
            windowWidth: window.innerWidth,
        })

        const { top, opensAbove } = resolveTeleportedMenuVerticalPlacement({
            triggerRect: rect,
            panelHeight: panelRect.height,
            gap,
            viewportPadding: VIEWPORT_PADDING,
            windowHeight: window.innerHeight,
            forcedPlacement,
        })

        panel.style.top = `${Math.round(top)}px`
        panel.style.left = `${Math.round(left)}px`
        panel.style.right = 'auto'

        if (typeof window.getComputedStyle === 'function') {
            panel.dir = direction
        }

        panel.classList.toggle('fff-teleported-menu--above', opensAbove)
        panel.classList.toggle('fff-teleported-menu--below', ! opensAbove)
    }

    function applySheetMode(entry) {
        const { panel, id, anchor } = entry

        panel.classList.add('fff-overlay-sheet', 'fff-teleported-menu--sheet')
        panel.classList.remove('fff-overlay-panel', 'fff-teleported-menu--panel')
        panel.dataset.fffOverlayMode = 'sheet'
        panel.dataset.fffOverlayId = id
        panel.dataset.fffOverlaySheet = 'true'
        panel.style.position = 'fixed'
        panel.style.zIndex = String(OVERLAY_SHEET_PANEL_Z_INDEX)
        if (typeof panel.style?.setProperty === 'function') {
            panel.style.setProperty('z-index', String(OVERLAY_SHEET_PANEL_Z_INDEX), 'important')
        }
        panel.style.removeProperty('margin-top')

        // Pin full viewport width before measure/fit — never inherit panel trigger width.
        if (typeof panel.style?.setProperty === 'function') {
            panel.style.setProperty('width', '100%', 'important')
            panel.style.setProperty('min-width', '0', 'important')
            panel.style.setProperty('max-width', 'none', 'important')
            panel.style.setProperty('left', '0', 'important')
            panel.style.setProperty('right', '0', 'important')
            panel.style.setProperty('inset-inline', '0', 'important')
            panel.style.setProperty('bottom', '0', 'important')
            panel.style.setProperty('top', 'auto', 'important')
        }

        if (anchor && typeof window.getComputedStyle === 'function') {
            panel.dir = window.getComputedStyle(anchor).direction || 'ltr'
        }

        fitOverlaySheetToContent(panel, window)
    }

    function applyPanelMode(entry) {
        const { panel, id } = entry

        panel.classList.add('fff-overlay-panel')
        panel.classList.remove('fff-overlay-sheet', 'fff-teleported-menu--sheet')
        panel.classList.add('fff-teleported-menu--panel')
        panel.dataset.fffOverlayMode = 'panel'
        panel.dataset.fffOverlayId = id
        panel.removeAttribute('data-fff-overlay-snap')
        panel.removeAttribute('data-fff-overlay-sheet')
        delete panel.dataset.fffSheetFittedHeight
        delete panel.dataset.fffOverlaySnap

        // Drop sheet peek height / bottom pin before measuring — otherwise the
        // desktop panel keeps a giant empty height (inlineFieldLabel Status).
        for (const property of [
            'height',
            'max-height',
            'min-height',
            'bottom',
            'inset-inline',
            'right',
            'left',
            'top',
            'width',
            'min-width',
            'max-width',
            'transform',
            'transition',
        ]) {
            panel.style.removeProperty(property)
        }

        panel.style.removeProperty('--fff-overlay-sheet-max-height')
        positionPanel(entry)
    }

    function revealPanel(panel) {
        applyReducedMotion(panel)
        panel.hidden = false
        panel.setAttribute('aria-hidden', 'false')
        panel.classList.add('is-open')

        if (! prefersReducedMotion()) {
            void panel.offsetWidth

            requestFrame(() => {
                panel.classList.add('is-open')
            })
        }
    }

    function hidePanel(panel) {
        panel.classList.remove('is-open')
        clearPanelMode(panel)
    }

    function unbindPositionListeners(entry) {
        if (entry.scrollHandler) {
            for (const parent of entry.scrollParents) {
                parent.removeEventListener('scroll', entry.scrollHandler)
            }

            window.removeEventListener('scroll', entry.scrollHandler, true)
            entry.scrollHandler = null
        }

        if (entry.resizeHandler) {
            window.removeEventListener('resize', entry.resizeHandler)
            entry.resizeHandler = null
        }

        entry.scrollParents = []
    }

    function schedulePositionUpdate(id) {
        if (positionRaf || destroyed) {
            return
        }

        positionRaf = requestFrame(() => {
            positionRaf = 0

            const entry = overlays.get(id)

            if (! entry || entry.mode !== 'panel') {
                return
            }

            positionPanel(entry)
        })
    }

    function bindPositionListeners(entry) {
        unbindPositionListeners(entry)

        if (entry.mode !== 'panel') {
            return
        }

        const scrollHandler = (event) => {
            if (
                entry.panel
                && event?.target instanceof Node
                && entry.panel.contains(event.target)
            ) {
                return
            }

            schedulePositionUpdate(entry.id)
        }

        const resizeHandler = () => {
            schedulePositionUpdate(entry.id)
        }

        entry.scrollHandler = scrollHandler
        entry.resizeHandler = resizeHandler
        entry.scrollParents = resolveScrollableParents(entry.anchor)

        for (const parent of entry.scrollParents) {
            parent.addEventListener('scroll', scrollHandler, { passive: true })
        }

        window.addEventListener('scroll', scrollHandler, true)
        window.addEventListener('resize', resizeHandler)
    }

    function closeInternal(id, { emitClose = true, displace = false } = {}) {
        const entry = overlays.get(id)

        if (! entry) {
            return
        }

        unbindPositionListeners(entry)

        const durationMs = Date.now() - entry.openedAt

        if (entry.manageVisibility !== false) {
            hidePanel(entry.panel)
        }

        overlays.delete(id)

        if (displace) {
            try {
                entry.onDisplace?.()
            } catch {
                // Host close handlers must not break exclusive arbitration.
            }
        }

        if (emitClose) {
            emitTelemetry({ type: 'close', id, durationMs })
        }
    }

    /**
     * Displace lock-only exclusive claims (does not touch managed panels).
     *
     * @param {string | null} [exceptId]
     * @param {{ emitClose?: boolean }} [options]
     */
    function displaceExclusiveClaims(exceptId = null, { emitClose = true } = {}) {
        for (const [claimId, claim] of [...exclusiveClaims.entries()]) {
            if (claimId === exceptId) {
                continue
            }

            exclusiveClaims.delete(claimId)

            const durationMs = Date.now() - claim.openedAt

            try {
                claim.onDisplace?.()
            } catch {
                // Host close handlers must not break exclusive arbitration.
            }

            if (emitClose) {
                emitTelemetry({ type: 'close', id: claimId, durationMs })
            }
        }
    }

    function closeManagedExclusive(exceptId = null) {
        for (const openId of [...overlays.keys()]) {
            if (openId === exceptId) {
                continue
            }

            const entry = overlays.get(openId)

            if (entry?.exclusive !== false) {
                closeInternal(openId, { displace: true })
            }
        }
    }

    /**
     * Exclusive lock without taking over panel/portal DOM.
     * Used while a menu is opening (before anchored `open()` upgrade).
     *
     * @param {string} id
     * @param {{ exclusive?: boolean, onDisplace?: (() => void) | null }} [options]
     */
    function claimExclusive(id, { exclusive = true, onDisplace = null } = {}) {
        if (destroyed || ! id) {
            return
        }

        if (exclusive) {
            closeManagedExclusive(id)
            displaceExclusiveClaims(id)
        }

        if (exclusiveClaims.has(id)) {
            exclusiveClaims.delete(id)
        }

        // Managed open for the same id upgrades to lock-only ownership.
        if (overlays.has(id)) {
            closeInternal(id, { emitClose: false })
        }

        exclusiveClaims.set(id, {
            id,
            exclusive,
            openedAt: Date.now(),
            onDisplace: typeof onDisplace === 'function' ? onDisplace : null,
        })

        emitTelemetry({ type: 'open', id })
    }

    /**
     * @param {string} id
     */
    function releaseExclusive(id) {
        const claim = exclusiveClaims.get(id)

        if (! claim) {
            return
        }

        exclusiveClaims.delete(id)

        emitTelemetry({
            type: 'close',
            id,
            durationMs: Date.now() - claim.openedAt,
        })
    }

    function open({
        id,
        panel,
        anchor,
        mode = 'panel',
        exclusive = true,
        manageVisibility = true,
        onDisplace = null,
        minWidth = 288,
        matchTriggerWidth = true,
        align = 'start',
        gap = PANEL_GAP,
        position = null,
    }) {
        if (destroyed || ! id || ! panel || ! anchor) {
            return
        }

        if (exclusive) {
            closeManagedExclusive(id)
            displaceExclusiveClaims(id)
        }

        if (overlays.has(id)) {
            closeInternal(id, { emitClose: false })
        }

        const upgradedFromClaim = exclusiveClaims.has(id)

        if (upgradedFromClaim) {
            exclusiveClaims.delete(id)
        }

        const entry = {
            id,
            panel,
            anchor,
            mode,
            exclusive,
            manageVisibility,
            onDisplace: typeof onDisplace === 'function' ? onDisplace : null,
            minWidth,
            matchTriggerWidth,
            align: align === 'end' ? 'end' : 'start',
            gap,
            position: position === 'top' || position === 'bottom' ? position : null,
            openedAt: Date.now(),
            scrollHandler: null,
            resizeHandler: null,
            scrollParents: [],
        }

        overlays.set(id, entry)

        if (mode === 'sheet') {
            applySheetMode(entry)
        } else {
            applyPanelMode(entry)
        }

        bindPositionListeners(entry)

        if (manageVisibility !== false) {
            revealPanel(panel)
        }

        if (! upgradedFromClaim) {
            emitTelemetry({ type: 'open', id })
        }
    }

    function close(id) {
        if (exclusiveClaims.has(id)) {
            releaseExclusive(id)
        }

        closeInternal(id)
    }

    function closeAll() {
        displaceExclusiveClaims(null)
        for (const id of [...overlays.keys()]) {
            closeInternal(id)
        }
    }

    function updatePosition(id) {
        const entry = overlays.get(id)

        if (! entry || entry.mode !== 'panel') {
            return
        }

        positionPanel(entry)
    }

    /**
     * Flip an open overlay between panel and sheet (breakpoint resize while open).
     * Keeps entry.mode in sync so updatePosition / sheet fits stay valid.
     *
     * @param {string} id
     * @param {OverlayMode} mode
     */
    function setMode(id, mode) {
        const entry = overlays.get(id)

        if (! entry || (mode !== 'panel' && mode !== 'sheet')) {
            return
        }

        if (entry.mode === mode) {
            if (mode === 'panel') {
                const panel = entry.panel
                const sheetChromeLeft = panel?.classList?.contains?.('fff-overlay-sheet')
                    || panel?.classList?.contains?.('fff-teleported-menu--sheet')

                // DOM can lag entry.mode after a failed/partial flip — re-assert panel.
                if (sheetChromeLeft) {
                    applyPanelMode(entry)
                } else {
                    positionPanel(entry)
                }
            } else {
                fitOverlaySheetToContent(entry.panel, window)
            }

            return
        }

        entry.mode = mode

        if (mode === 'sheet') {
            applySheetMode(entry)
        } else {
            applyPanelMode(entry)
        }
    }

    function isOpen(id) {
        return overlays.has(id) || exclusiveClaims.has(id)
    }

    function hasPanel(id) {
        return overlays.has(id)
    }

    function getOpenIds() {
        return [...new Set([...overlays.keys(), ...exclusiveClaims.keys()])]
    }

    function onTelemetry(callback) {
        telemetryListeners.add(callback)

        return () => {
            telemetryListeners.delete(callback)
        }
    }

    function notifyOpen(id) {
        if (! id) {
            return
        }

        emitTelemetry({ type: 'open', id })
    }

    function notifyClose(id, durationMs = 0) {
        if (! id) {
            return
        }

        emitTelemetry({ type: 'close', id, durationMs })
    }

    /**
     * Record measured open latency (anchor → visible) for SRE p95 tracking.
     *
     * @param {string} id
     * @param {number} openLatencyMs
     */
    function notifyOpenLatency(id, openLatencyMs) {
        if (! id || ! Number.isFinite(openLatencyMs)) {
            return
        }

        emitTelemetry({ type: 'open', id, openLatencyMs })
    }

    function destroy() {
        if (destroyed) {
            return
        }

        destroyed = true

        if (positionRaf) {
            cancelFrame(positionRaf)
            positionRaf = 0
        }

        closeAll()
        telemetryListeners.clear()
    }

    return {
        open,
        close,
        closeAll,
        updatePosition,
        setMode,
        isOpen,
        hasPanel,
        getOpenIds,
        claimExclusive,
        releaseExclusive,
        onTelemetry,
        notifyOpen,
        notifyClose,
        notifyOpenLatency,
        destroy,
    }
}

/** @type {ReturnType<typeof createOverlayRuntime> | null} */
let globalOverlayRuntime = null

/**
 * Attach a singleton overlay runtime on `window.FffOverlayRuntime` for teleported menus.
 *
 * @param {{ document?: Document, window?: Window }} [context]
 */
export function bootGlobalOverlayRuntime(context = {}) {
    if (typeof window === 'undefined') {
        return null
    }

    if (window.FffOverlayRuntime) {
        return window.FffOverlayRuntime
    }

    if (globalOverlayRuntime) {
        window.FffOverlayRuntime = globalOverlayRuntime

        return globalOverlayRuntime
    }

    const runtimeContext = {
        document: context.document ?? window.document,
        window: context.window ?? window,
    }

    globalOverlayRuntime = createOverlayRuntime(runtimeContext)
    window.FffOverlayRuntime = globalOverlayRuntime

    return globalOverlayRuntime
}
