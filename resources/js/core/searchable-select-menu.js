/**
 * Shared glass theme tokens for teleported searchable dropdown menus.
 *
 * M2: exclusive lock, panel positioning, and scroll/resize listeners go through
 * createOverlayRuntime.open({ manageVisibility: false }). Glass theme + reveal
 * animation stay here. flex-dropdown-coordinator still closes sibling Alpine
 * dropdowns (emoji / color / date / etc.) that are not overlay claims.
 */
import { wireExclusiveFlexDropdown } from './flex-dropdown-coordinator.js'
import { createOverlayBackdrop, removeOverlayBackdrop } from './overlay-backdrop.js'
import { resolveOverlayMode } from './overlay-mode.js'
import { bindOverlaySheetDismiss, fitOverlaySheetToContent } from './overlay-sheet-dismiss.js'
import { lockOverlaySheetScroll, unlockOverlaySheetScroll } from './overlay-sheet-scroll-lock.js'
import {
    resolveTeleportedMenuHorizontalLeft,
    resolveTeleportedMenuVerticalPlacement,
} from './teleported-menu-position.js'
import {
    claimOverlayExclusive,
    closeOverlayPanel,
    emitOverlayOpenLatency,
    openOverlayPanel,
    releaseOverlayExclusive,
    setOverlayPanelMode,
    updateOverlayPanelPosition,
} from './overlay-runtime-bridge.js'
import {
    prefersReducedMotion,
    resolveIsDark,
    resolveTeleportedMenuZIndex,
} from './theme-utils.js'

const OVERFLOW_SCROLL_RE = /(auto|scroll|overlay)/

function isDomNode(value) {
    return value != null && typeof value === 'object' && typeof value.nodeType === 'number'
}

function isInternalMultiSelectChipScroll(event, trigger) {
    if (! trigger || ! isDomNode(event?.target) || ! trigger.contains(event.target)) {
        return false
    }

    return Boolean(event.target.closest('.fi-select-input-value-badges-ctn'))
}

export function shouldSkipMenuScrollReposition(event, menu, trigger) {
    if (
        menu
        && isDomNode(event?.target)
        && menu.contains(event.target)
    ) {
        return true
    }

    return isInternalMultiSelectChipScroll(event, trigger)
}

export function resolveScrollableParents(element) {
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

export function applyTeleportedMenuTheme(menu, { variant = 'default' } = {}) {
    if (! menu) {
        return
    }

    const isDark = resolveIsDark()
    const themeKey = `${isDark ? 'd' : 'l'}:${variant}`

    if (menu.__fffMenuThemeKey === themeKey) {
        return
    }

    menu.__fffMenuThemeKey = themeKey

    const blur = 'blur(16px) saturate(180%)'
    const lightMenuBackground = 'rgb(255 255 255 / 0.9)'
    const lightMenuShadow = '0 2px 8px 0 #0000000f, 0 -6px 12px 0 #00000008, 0 14px 28px 0 #00000014'
    const darkMenuBackground = variant === 'map'
        ? 'rgb(39 39 42 / 0.9)'
        : 'rgb(39 39 42 / 0.92)'
    const darkMenuShadow = '0 4px 6px -1px rgb(0 0 0 / 0.28), 0 12px 28px -6px rgb(0 0 0 / 0.5)'
    const menuBackground = isDark ? darkMenuBackground : lightMenuBackground
    const menuShadow = isDark ? darkMenuShadow : lightMenuShadow

    menu.classList.add('fff-teleported-menu')

    if (isDark) {
        menu.style.setProperty('--fff-select-menu-bg', darkMenuBackground)
        menu.style.setProperty('--fff-select-menu-border', 'rgb(255 255 255 / 0.12)')
        menu.style.setProperty('--fff-select-menu-shadow', darkMenuShadow)
        menu.style.setProperty('--fff-select-menu-hover', 'rgb(255 255 255 / 0.08)')
        menu.style.setProperty('--fff-select-menu-selected', 'transparent')
        menu.style.setProperty('--fff-select-search-bg', 'rgb(39 39 42 / 0.55)')
        menu.style.setProperty('--fff-select-search-border', 'rgb(63 63 70)')
        menu.style.setProperty('--fff-phone-field-menu-text', 'rgb(250 250 250)')
        menu.style.setProperty('--fff-phone-field-menu-muted', 'rgb(212 212 216)')
    } else {
        menu.style.setProperty('--fff-select-menu-bg', lightMenuBackground)
        menu.style.setProperty('--fff-select-menu-border', 'rgb(228 228 231 / 0.65)')
        menu.style.setProperty('--fff-select-menu-shadow', lightMenuShadow)
        menu.style.setProperty('--fff-select-menu-hover', '#ebebec')
        menu.style.setProperty('--fff-select-menu-selected', 'transparent')
        menu.style.setProperty('--fff-select-search-bg', 'rgb(255 255 255 / 0.55)')
        menu.style.setProperty('--fff-select-search-border', 'rgb(228 228 231)')
        menu.style.setProperty('--fff-phone-field-menu-text', 'rgb(24 24 27)')
        menu.style.setProperty('--fff-phone-field-menu-muted', 'rgb(113 113 122)')
    }

    menu.style.setProperty('background', menuBackground, 'important')
    menu.style.setProperty('background-color', menuBackground, 'important')
    menu.style.setProperty('box-shadow', menuShadow, 'important')
    menu.style.setProperty('backdrop-filter', blur, 'important')
    menu.style.setProperty('-webkit-backdrop-filter', blur, 'important')
    menu.style.color = isDark ? 'rgb(250 250 250)' : 'rgb(24 24 27)'
}

function cancelMenuCloseAnimation(menu) {
    if (! menu) {
        return
    }

    if (menu.__fffMenuCloseTimeout) {
        clearTimeout(menu.__fffMenuCloseTimeout)
        menu.__fffMenuCloseTimeout = null
    }

    if (menu.__fffMenuCloseListener) {
        menu.removeEventListener('transitionend', menu.__fffMenuCloseListener)
        menu.__fffMenuCloseListener = null
    }
}

/**
 * Resolve this instance's teleported menu panel.
 * Prefer an explicit DOM id / owner attribute so multiple SelectFields on one
 * page cannot steal each other's `$refs.headlessMenu` after x-teleport.
 *
 * @param {object} component
 * @param {string} menuRef
 * @returns {HTMLElement | null}
 */
export function resolveSearchableSelectMenuElement(component, menuRef = 'menuMenu') {
    const byRef = component?.$refs?.[menuRef] ?? null
    const expectedId = component?.menuDomId
        ? String(component.menuDomId)
        : null
    const owner = component?.componentKey ?? component?.statePath ?? null
    const ownerToken = owner != null && String(owner) !== ''
        ? String(owner)
        : null

    if (
        byRef
        && (! expectedId || byRef.id === expectedId)
        && (
            ! ownerToken
            || byRef.getAttribute?.('data-fff-select-menu-owner') === ownerToken
            || ! byRef.hasAttribute?.('data-fff-select-menu-owner')
        )
    ) {
        return byRef
    }

    const root = typeof document !== 'undefined' ? document : null

    if (expectedId && root?.getElementById) {
        const byId = root.getElementById(expectedId)

        if (byId) {
            return byId
        }
    }

    if (ownerToken && typeof root?.querySelector === 'function') {
        const escaped = typeof CSS !== 'undefined' && typeof CSS.escape === 'function'
            ? CSS.escape(ownerToken)
            : ownerToken.replace(/\\/g, '\\\\').replace(/"/g, '\\"')
        const byOwner = root.querySelector(
            `.fff-select-headless-menu[data-fff-select-menu-owner="${escaped}"]`,
        )

        if (byOwner) {
            return byOwner
        }
    }

    return byRef
}

/**
 * Hide any other SelectField glass menus that are still painted open.
 * Belt-and-suspenders when exclusive close is delayed by exit animation.
 *
 * @param {HTMLElement | null} ownMenu
 */
export function forceHideForeignSelectMenus(ownMenu = null) {
    if (typeof document === 'undefined' || typeof document.querySelectorAll !== 'function') {
        return
    }

    for (const menu of document.querySelectorAll('.fff-select-headless-menu.is-open, .fff-select-headless-menu.is-closing')) {
        if (ownMenu && menu === ownMenu) {
            continue
        }

        cancelMenuCloseAnimation(menu)
        menu.classList.remove('is-open', 'is-closing')
    }
}

function revealTeleportedMenuPanel(menu, isOpen) {
    if (! menu) {
        return
    }

    cancelMenuCloseAnimation(menu)
    menu.classList.remove('is-closing')

    // Optimistic open already painted `is-open` — stripping it for a reflow flash
    // leaves the panel on :not(.is-positioned)/opacity:0 for an extra frame (or
    // longer when Livewire starves rAF) and feels like the field is frozen.
    if (isOpen && typeof menu.classList?.contains === 'function' && menu.classList.contains('is-open')) {
        return
    }

    menu.classList.remove('is-open')
    void menu.offsetWidth

    requestAnimationFrame(() => {
        if (isOpen) {
            menu.classList.add('is-open')
        }
    })
}

const SHEET_EXIT_MS = 200

/**
 * @param {HTMLElement | null | undefined} menu
 * @returns {boolean}
 */
function menuIsSheet(menu) {
    return Boolean(
        menu?.classList?.contains('fff-teleported-menu--sheet')
        || menu?.classList?.contains('fff-overlay-sheet'),
    )
}

/**
 * Play bottom-sheet enter: closed frame → open.
 *
 * Height is applied with transitions disabled so the first open (content measure
 * 0 → fitted) does not eat the slide-up. Transform alone animates in.
 *
 * @param {HTMLElement} menu
 */
export function playSheetEnter(menu) {
    if (! menu) {
        return
    }

    cancelMenuCloseAnimation(menu)
    menu.classList.remove('is-open', 'is-closing')

    // Measure + park off-screen with transitions forced off until the open class
    // lands in the same frame — otherwise first-open `reveal` rAF can flip
    // `is-open` early and the slide stutters / skips.
    if (typeof menu.style?.setProperty === 'function') {
        menu.style.setProperty('transition', 'none', 'important')
    }

    // Height may already be fitted by openOverlayPanel; only measure when needed.
    if (! menu.dataset?.fffSheetFittedHeight) {
        fitOverlaySheetToContent(menu, window)
    }

    if (menu.dataset) {
        menu.dataset.fffSheetEntering = 'true'
    }

    if (typeof menu.style?.setProperty === 'function') {
        menu.style.setProperty('transform', 'translate3d(0, 100%, 0)', 'important')
    }

    void menu.offsetWidth

    const finishEntering = () => {
        if (! menu.dataset?.fffSheetEntering) {
            return
        }

        delete menu.dataset.fffSheetEntering
        menu.removeEventListener('transitionend', onEnterEnd)

        // Skeleton / Livewire options often land mid-enter — one instant refit
        // after the slide completes keeps height correct without fighting transform.
        if (menu.isConnected) {
            fitOverlaySheetToContent(menu, window)
        }
    }

    const onEnterEnd = (event) => {
        if (event.target !== menu || event.propertyName !== 'transform') {
            return
        }

        finishEntering()
    }

    menu.addEventListener('transitionend', onEnterEnd)
    // Prefer global timer (jsdom tests often stub globalThis, not window).
    const schedule = typeof globalThis.setTimeout === 'function'
        ? globalThis.setTimeout.bind(globalThis)
        : window.setTimeout.bind(window)

    schedule(finishEntering, 280)

    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            if (! menu.isConnected) {
                return
            }

            // Same frame: enable transform-only transition, drop parked transform, open.
            // Never leave SNAP_EASE (height/max-height) active during enter.
            if (typeof menu.style?.setProperty === 'function') {
                menu.style.setProperty(
                    'transition',
                    'transform 0.25s cubic-bezier(0.32, 0.72, 0, 1)',
                    'important',
                )
                menu.style.removeProperty('transform')
            } else if (typeof menu.style?.removeProperty === 'function') {
                menu.style.removeProperty('transition')
                menu.style.removeProperty('transform')
            }

            menu.classList.remove('is-closing')
            menu.classList.add('is-open')
        })
    })
}

/**
 * Run work after the mobile sheet enter slide finishes (or immediately for panels).
 * Livewire option fetches / focus must not contend with the enter animation frames.
 *
 * @param {HTMLElement | null | undefined} menu
 * @param {() => void} callback
 * @param {{ timeoutMs?: number }} [options]
 */
export function runAfterSheetEnter(menu, callback, { timeoutMs = 320 } = {}) {
    if (typeof callback !== 'function') {
        return
    }

    const isSheet = menuIsSheet(menu)
        || (typeof window !== 'undefined' && resolveOverlayMode(window) === 'sheet')

    if (! isSheet || ! menu) {
        callback()

        return
    }

    const startedAt = typeof performance !== 'undefined' ? performance.now() : Date.now()
    let settled = false

    const finish = () => {
        if (settled) {
            return
        }

        settled = true
        callback()
    }

    const tick = () => {
        if (settled) {
            return
        }

        const entering = menu.dataset?.fffSheetEntering === 'true'
        const open = typeof menu.classList?.contains === 'function'
            && menu.classList.contains('is-open')
        const elapsed = (typeof performance !== 'undefined' ? performance.now() : Date.now()) - startedAt

        if (! entering && open) {
            finish()

            return
        }

        if (elapsed >= timeoutMs) {
            finish()

            return
        }

        requestAnimationFrame(tick)
    }

    requestAnimationFrame(tick)
}

/**
 * Play bottom-sheet exit, then invoke onDone (typically sets openKey=false).
 *
 * @param {HTMLElement} menu
 * @param {() => void} onDone
 */
function playSheetExit(menu, onDone) {
    if (! menu) {
        onDone?.()

        return
    }

    cancelMenuCloseAnimation(menu)

    // Lock full-bleed geometry for the whole exit — reposition/width helpers must
    // not shrink the drawer while translateY animates.
    if (typeof menu.style?.setProperty === 'function') {
        menu.style.setProperty('width', '100%', 'important')
        menu.style.setProperty('min-width', '0', 'important')
        menu.style.setProperty('max-width', 'none', 'important')
        menu.style.setProperty('left', '0', 'important')
        menu.style.setProperty('right', '0', 'important')
        menu.style.setProperty('inset-inline', '0', 'important')
    }

    if (prefersReducedMotion()) {
        menu.classList.remove('is-open', 'is-closing')
        onDone?.()

        return
    }

    // Guarantee a from→to transform so the exit always animates.
    menu.classList.add('is-open')
    menu.classList.remove('is-closing')
    menu.style.transition = 'none'
    menu.style.transform = 'translateY(0)'
    void menu.offsetWidth
    menu.style.transition = ''
    menu.style.transform = ''

    menu.classList.remove('is-open')
    menu.classList.add('is-closing')

    let finished = false

    const complete = () => {
        if (finished) {
            return
        }

        finished = true
        cancelMenuCloseAnimation(menu)
        menu.classList.remove('is-closing')
        onDone?.()
    }

    const onTransitionEnd = (event) => {
        if (event.target !== menu) {
            return
        }

        if (event.propertyName !== 'transform') {
            return
        }

        complete()
    }

    menu.__fffMenuCloseListener = onTransitionEnd
    menu.addEventListener('transitionend', onTransitionEnd)
    menu.__fffMenuCloseTimeout = window.setTimeout(complete, SHEET_EXIT_MS + 40)
}

function hideTeleportedMenuPanel(menu, onHidden) {
    if (! menu) {
        onHidden?.()

        return
    }

    if (menuIsSheet(menu)) {
        playSheetExit(menu, onHidden)

        return
    }

    cancelMenuCloseAnimation(menu)
    menu.classList.remove('is-open')

    const reducedMotion = prefersReducedMotion()

    if (reducedMotion) {
        menu.classList.remove('is-closing')
        onHidden?.()

        return
    }

    menu.classList.add('is-closing')

    let finished = false

    const complete = () => {
        if (finished) {
            return
        }

        finished = true
        cancelMenuCloseAnimation(menu)
        menu.classList.remove('is-closing')
        onHidden?.()
    }

    const onTransitionEnd = (event) => {
        if (event.target !== menu) {
            return
        }

        if (event.propertyName !== 'opacity' && event.propertyName !== 'transform') {
            return
        }

        complete()
    }

    menu.__fffMenuCloseListener = onTransitionEnd
    menu.addEventListener('transitionend', onTransitionEnd)
    menu.__fffMenuCloseTimeout = window.setTimeout(complete, 220)
}

function ensureSheetChrome(menu) {
    if (! menu) {
        return
    }

    const existing = [...menu.querySelectorAll('[data-fff-overlay-handle], .fff-overlay-sheet__handle')]

    if (existing.length > 0) {
        const [keep, ...extras] = existing

        for (const node of extras) {
            node.remove()
        }

        if (keep instanceof HTMLElement && ! keep.querySelector('.fff-overlay-sheet__handle-bar')) {
            const bar = document.createElement('span')

            bar.className = 'fff-overlay-sheet__handle-bar'
            keep.appendChild(bar)
        }

        return
    }

    const handle = document.createElement('div')
    const bar = document.createElement('span')

    handle.className = 'fff-overlay-sheet__handle'
    handle.dataset.fffOverlayHandle = 'true'
    handle.setAttribute('aria-hidden', 'true')
    bar.className = 'fff-overlay-sheet__handle-bar'
    handle.appendChild(bar)
    menu.insertBefore(handle, menu.firstChild)
}

function applyOverlayPresentation(menu, mode) {
    if (! menu) {
        return
    }

    menu.classList.toggle('fff-teleported-menu--sheet', mode === 'sheet')
    menu.classList.toggle('fff-teleported-menu--panel', mode !== 'sheet')
    menu.classList.toggle('fff-overlay-sheet', mode === 'sheet')
    menu.classList.toggle('fff-overlay-panel', mode !== 'sheet')

    if (menu.dataset) {
        menu.dataset.fffOverlayPresentation = mode

        if (mode === 'sheet') {
            menu.dataset.fffOverlaySheet = 'true'
        } else {
            delete menu.dataset.fffOverlaySheet
            delete menu.dataset.fffSheetFittedHeight
            delete menu.dataset.fffOverlaySnap
        }
    }

    if (mode === 'sheet') {
        ensureSheetChrome(menu)

        return
    }

    // Panel mode must not keep sheet peek height / bottom pin from a prior
    // sheet open (or a mode flip) — that leaves a giant empty desktop menu.
    clearOverlaySheetInlineStyles(menu)
    removeSheetChrome(menu)
}

/**
 * @param {HTMLElement | null | undefined} menu
 */
function clearOverlaySheetInlineStyles(menu) {
    if (! menu?.style || typeof menu.style.removeProperty !== 'function') {
        return
    }

    for (const property of [
        'height',
        'max-height',
        'min-height',
        'bottom',
        'left',
        'right',
        'inset-inline',
        'width',
        'min-width',
        'max-width',
        'top',
        'transform',
        'transition',
    ]) {
        menu.style.removeProperty(property)
    }

    menu.style.removeProperty('--fff-overlay-sheet-max-height')

    if (menu.dataset) {
        delete menu.dataset.fffSheetFittedHeight
        delete menu.dataset.fffOverlaySnap
    }
}

/**
 * @param {HTMLElement | null | undefined} menu
 */
function removeSheetChrome(menu) {
    if (! menu?.querySelectorAll) {
        return
    }

    menu.querySelectorAll('[data-fff-overlay-handle], .fff-overlay-sheet__handle').forEach((node) => {
        node.remove()
    })
}

/**
 * Floating searchable select menu used by country, timezone, and currency fields.
 */
export function createSearchableSelectMenuMixin({
    openKey = 'menuOpen',
    readyKey = 'menuReady',
    scrollHandlerKey = 'menuScrollHandler',
    resizeHandlerKey = 'menuResizeHandler',
    scrollParentsKey = 'menuScrollParents',
    triggerRef = 'menuTrigger',
    menuRef = 'menuMenu',
    minMenuWidth = 288,
    matchTriggerWidth = true,
    menuGap = 6,
    closeMethod = 'closeMenu',
    ownerIdPrefix = 'fff-searchable-select',
    menuThemeVariant = 'default',
    onMenuClose = null,
} = {}) {
    return {
        resolveMenuOverlayId() {
            if (this.__fffMenuOverlayId) {
                return this.__fffMenuOverlayId
            }

            const raw = this.componentKey
                ?? this.statePath
                ?? this.menuDomId
                ?? this.$el?.id
                ?? this.$el?.getAttribute?.('wire:key')
                ?? null

            // Prefer a stable per-field id (componentKey/statePath) so multiple
            // SelectFields on one page do not share one overlay slot and steal
            // each other's teleported panel. Fall back to ownerIdPrefix-menu for
            // single-field hosts (phone/map) that already use a unique prefix.
            this.__fffMenuOverlayId = raw
                ? `${ownerIdPrefix}-${String(raw).replace(/[^a-zA-Z0-9_-]+/g, '-')}`
                : `${ownerIdPrefix}-menu`

            return this.__fffMenuOverlayId
        },

        resolveDropdownAlign() {
            return 'start'
        },

        resolveMenuElement() {
            return resolveSearchableSelectMenuElement(this, menuRef)
        },

        resolveMenuTriggerRef() {
            if (typeof this.resolveMenuTriggerElement === 'function') {
                return this.resolveMenuTriggerElement()
            }

            return this.$refs[triggerRef] ?? null
        },

        scheduleMenuPosition({ onAnchored = null } = {}) {
            // Exit animation owns transform classes — reposition/reveal would
            // strip `is-closing` and flash the sheet open again.
            if (this.__fffSheetClosing) {
                return
            }

            const trigger = this.resolveMenuTriggerRef()
            const menu = this.resolveMenuElement()
            const hasPositionedBefore = menu?.__fffHasBeenPositioned === true

            // Same-turn unlock only when the panel was already shown (Livewire
            // remorph / dependsOn). First open must wait for Alpine list paint +
            // final anchor — otherwise phone/country menus flash wrong size/top
            // (content-sized width, below→above flip) before settling.
            if (this[openKey] && trigger && menu) {
                this.updateMenuPosition({ reveal: false, markReady: hasPositionedBefore })

                if (hasPositionedBefore) {
                    menu.__fffHasBeenPositioned = true
                    this[readyKey] = true
                } else {
                    this[readyKey] = false
                }
            } else if (hasPositionedBefore) {
                this[readyKey] = true
            } else {
                this[readyKey] = false
            }

            const attempt = (pass = 0) => {
                this.$nextTick(() => {
                    requestAnimationFrame(() => {
                        const currentTrigger = this.resolveMenuTriggerRef()
                        const currentMenu = this.resolveMenuElement()

                        if ((! currentTrigger || ! currentMenu) && pass < 12) {
                            attempt(pass + 1)

                            return
                        }

                        if (! currentTrigger || ! currentMenu) {
                            onAnchored?.()

                            return
                        }

                        const sheetMode = resolveOverlayMode(window) === 'sheet'
                            || menuIsSheet(currentMenu)

                        const alreadyPaintedOpen = typeof currentMenu.classList?.contains === 'function'
                            && currentMenu.classList.contains('is-open')

                        // Skip reveal reflow when optimistic is-open is already on.
                        // Sheets never use glass reveal — playSheetEnter owns the slide.
                        const shouldReveal = ! sheetMode
                            && ! hasPositionedBefore
                            && pass === 0
                            && ! alreadyPaintedOpen

                        if (typeof this.measureVirtualListViewport === 'function') {
                            this.measureVirtualListViewport()
                        }

                        // Position while still hidden — first paint must already be final size/placement.
                        this.updateMenuPosition({
                            reveal: false,
                            markReady: false,
                        })

                        if (currentMenu) {
                            currentMenu.__fffHasBeenPositioned = true
                        }

                        const finishAnchor = () => {
                            if (typeof this.measureVirtualListViewport === 'function') {
                                this.measureVirtualListViewport()
                            }

                            this.updateMenuPosition({
                                reveal: shouldReveal,
                                markReady: false,
                            })

                            // Overlay open (onAnchored) may nudge geometry — reveal only after that.
                            onAnchored?.()
                            this[readyKey] = true

                            if (this[openKey] && ! this.__fffSheetClosing && currentMenu) {
                                // Sheet enter is owned by playSheetEnter() after overlay chrome is applied.
                                if (! sheetMode) {
                                    currentMenu.classList.remove('is-closing')
                                    currentMenu.classList.add('is-open')
                                }
                            }
                        }

                        if (shouldReveal) {
                            requestAnimationFrame(finishAnchor)

                            return
                        }

                        finishAnchor()
                    })
                })
            }

            attempt()
        },

        updateMenuPosition({ reveal = false, markReady = true } = {}) {
            if (this.__fffSheetClosing) {
                return
            }

            const trigger = this.resolveMenuTriggerRef()
            const menu = this.resolveMenuElement()

            if (! trigger || ! menu) {
                return
            }

            // While the enter slide runs, skip re-anchoring — skeleton/Livewire
            // height thrash would stutter the transform animation.
            if (menu.dataset?.fffSheetEntering === 'true') {
                return
            }

            applyTeleportedMenuTheme(menu, { variant: menuThemeVariant })

            if (this.__fffOverlayManaged) {
                const managedOverlayId = this.resolveMenuOverlayId()
                const runtime = typeof window !== 'undefined' ? window.FffOverlayRuntime : null
                const hasManagedPanel = typeof runtime?.hasPanel === 'function'
                    ? runtime.hasPanel(managedOverlayId)
                    : false

                if (hasManagedPanel) {
                    // Live breakpoint wins — stale open-time mode / leftover sheet
                    // classes must not leave the menu pinned left:0 after desktop restore.
                    const desiredMode = resolveOverlayMode(window)

                    this.__fffOverlayMode = desiredMode
                    applyOverlayPresentation(menu, desiredMode)
                    setOverlayPanelMode(managedOverlayId, desiredMode)
                    this.syncOpenOverlaySheetEnvironment(desiredMode, menu)

                    if (desiredMode === 'sheet') {
                        menu.style.position = 'fixed'
                        menu.style.zIndex = resolveTeleportedMenuZIndex()
                        menu.style.setProperty('left', '0', 'important')
                        menu.style.setProperty('right', '0', 'important')
                        menu.style.setProperty('inset-inline', '0', 'important')
                        menu.style.setProperty('bottom', '0', 'important')
                        menu.style.setProperty('width', '100%', 'important')
                        menu.style.setProperty('min-width', '0', 'important')
                        menu.style.setProperty('max-width', 'none', 'important')
                        menu.style.removeProperty('top')
                        menu.style.marginTop = '0'
                        menu.classList.remove('fff-teleported-menu--above', 'fff-teleported-menu--below')

                        if (
                            this[openKey]
                            && menu.classList?.contains?.('is-open')
                            && menu.dataset?.fffSheetEntering !== 'true'
                        ) {
                            fitOverlaySheetToContent(menu, window)
                        }
                    } else {
                        updateOverlayPanelPosition(managedOverlayId)
                    }

                    if (markReady) {
                        this[readyKey] = true
                    }

                    if (desiredMode !== 'sheet') {
                        if (reveal) {
                            revealTeleportedMenuPanel(menu, this[openKey])
                        } else if (this[openKey] && markReady) {
                            menu.classList.remove('is-closing')
                            menu.classList.add('is-open')
                        }
                    }

                    return
                }

                // Overlay entry gone (displaced/closed) but Alpine still flagged managed —
                // fall through to local anchoring so the panel cannot float detached.
                this.__fffOverlayManaged = false
            }

            const overlayMode = resolveOverlayMode(window)

            this.__fffOverlayMode = overlayMode
            applyOverlayPresentation(menu, overlayMode)
            this.syncOpenOverlaySheetEnvironment(overlayMode, menu)

            if (overlayMode === 'sheet') {
                menu.style.position = 'fixed'
                menu.style.zIndex = resolveTeleportedMenuZIndex()
                menu.style.setProperty('left', '0', 'important')
                menu.style.setProperty('right', '0', 'important')
                menu.style.setProperty('inset-inline', '0', 'important')
                menu.style.setProperty('bottom', '0', 'important')
                menu.style.setProperty('width', '100%', 'important')
                menu.style.setProperty('min-width', '0', 'important')
                menu.style.setProperty('max-width', 'none', 'important')
                menu.style.removeProperty('top')
                menu.style.marginTop = '0'
                menu.classList.remove('fff-teleported-menu--above', 'fff-teleported-menu--below')

                // Mirror trigger writing direction so sheet search/options use [dir=rtl] CSS.
                const sheetDirection = typeof window.getComputedStyle === 'function'
                    ? window.getComputedStyle(trigger).direction || 'ltr'
                    : 'ltr'
                menu.dir = sheetDirection

                if (markReady) {
                    this[readyKey] = true
                }

                // Never glass-reveal or flip is-open here — playSheetEnter owns enter.
                // After enter, refit when Livewire/skeleton content changes height.
                if (
                    this[openKey]
                    && menu.classList?.contains?.('is-open')
                    && menu.dataset?.fffSheetEntering !== 'true'
                ) {
                    fitOverlaySheetToContent(menu, window)
                }

                return
            }

            const rect = trigger.getBoundingClientRect()
            const gap = menuGap
            const viewportPadding = 16
            const align = this.resolveDropdownAlign?.() === 'end' ? 'end' : 'start'
            const shouldMatchTriggerWidth = typeof this.resolveMatchTriggerWidth === 'function'
                ? this.resolveMatchTriggerWidth()
                : matchTriggerWidth
            const resolvedMinWidth = typeof this.resolveMenuMinWidth === 'function'
                ? this.resolveMenuMinWidth()
                : minMenuWidth
            const menuWidth = shouldMatchTriggerWidth
                ? Math.min(Math.max(rect.width, resolvedMinWidth), window.innerWidth - (viewportPadding * 2))
                : Math.min(Math.max(resolvedMinWidth, 0), window.innerWidth - (viewportPadding * 2))

            const direction = typeof window.getComputedStyle === 'function'
                ? window.getComputedStyle(trigger).direction || 'ltr'
                : 'ltr'
            const forcedPlacement = this.position === 'top' || this.position === 'bottom'
                ? this.position
                : null

            menu.style.position = 'fixed'
            // Always pin width before measure — content-sized first paint (e.g. phone
            // country list) jumps when overlay later applies minMenuWidth.
            // Prefer setProperty(..., important) so a prior sheet `width: 100% !important`
            // cannot win; also assign `.width` for plain style mocks / older paths.
            const widthPx = `${Math.round(menuWidth)}px`
            menu.style.width = widthPx
            if (typeof menu.style.setProperty === 'function') {
                menu.style.setProperty('width', widthPx, 'important')
                menu.style.setProperty('max-width', 'none', 'important')
                menu.style.setProperty('--fff-select-menu-width', widthPx)
            }
            menu.style.zIndex = resolveTeleportedMenuZIndex()
            menu.style.top = `${Math.round(rect.bottom + gap)}px`
            menu.style.left = `${Math.round(rect.left)}px`
            menu.style.marginTop = '0'
            // Ensure prior sheet pins cannot stretch the panel to the viewport bottom.
            if (typeof menu.style.removeProperty === 'function') {
                menu.style.removeProperty('bottom')
                menu.style.removeProperty('height')
                menu.style.removeProperty('max-height')
                menu.style.removeProperty('min-height')
            }

            const menuRect = menu.getBoundingClientRect()
            let left = resolveTeleportedMenuHorizontalLeft({
                triggerRect: rect,
                menuWidth: menuRect.width || menuWidth,
                align,
                direction,
                viewportPadding,
                windowWidth: window.innerWidth,
            })

            const { top, opensAbove } = resolveTeleportedMenuVerticalPlacement({
                triggerRect: rect,
                panelHeight: menuRect.height,
                gap,
                viewportPadding,
                windowHeight: window.innerHeight,
                forcedPlacement,
            })

            menu.style.top = `${Math.round(top)}px`
            menu.style.left = `${Math.round(left)}px`
            menu.style.right = 'auto'

            menu.dir = direction

            menu.classList.toggle('fff-teleported-menu--above', opensAbove)
            menu.classList.toggle('fff-teleported-menu--below', ! opensAbove)

            if (markReady) {
                this[readyKey] = true
            }

            if (reveal) {
                revealTeleportedMenuPanel(menu, this[openKey])
            } else if (this[openKey] && markReady) {
                menu.classList.remove('is-closing')
                menu.classList.add('is-open')
            }
        },

        applySelectMenuTheme(menu) {
            applyTeleportedMenuTheme(menu, { variant: menuThemeVariant })
        },

        /**
         * Keep body scroll lock + sheet backdrop/dismiss in sync when the open
         * overlay flips panel↔sheet on resize (or when first anchored as sheet).
         *
         * @param {'panel' | 'sheet'} mode
         * @param {HTMLElement | null | undefined} [menu]
         * @param {{ bindDismiss?: boolean }} [options]
         */
        syncOpenOverlaySheetEnvironment(mode, menu = null, options = {}) {
            if (! this[openKey] || typeof document === 'undefined') {
                return
            }

            const bindDismiss = options.bindDismiss !== false
            const overlayId = this.resolveMenuOverlayId()
            const panel = menu ?? this.resolveMenuElement()

            if (mode === 'sheet') {
                if (! this.__fffSheetScrollLocked) {
                    lockOverlaySheetScroll(document)
                    this.__fffSheetScrollLocked = true
                }

                if (panel) {
                    const zIndex = Number.parseInt(window.getComputedStyle?.(panel)?.zIndex, 10) || 50

                    createOverlayBackdrop(document, overlayId, {
                        zIndex: zIndex - 1,
                        onDismiss: () => {
                            if (this.__fffOverlayMode === 'sheet' || menuIsSheet(this.resolveMenuElement())) {
                                this.closeTeleportedMenu()

                                return
                            }

                            if (typeof this.closeTeleportedMenuImmediate === 'function') {
                                this.closeTeleportedMenuImmediate()

                                return
                            }

                            this[openKey] = false
                        },
                    })

                    if (
                        bindDismiss
                        && ! this.__fffSheetDismissCleanup
                        && typeof panel.addEventListener === 'function'
                    ) {
                        this.__fffSheetDismissCleanup = bindOverlaySheetDismiss({
                            panel,
                            onDismiss: () => {
                                if (this.__fffSheetClosing || ! this[openKey]) {
                                    return
                                }

                                this.__fffSheetClosing = true
                                removeOverlayBackdrop(document, overlayId)
                                window.setTimeout(() => {
                                    this.__fffSheetClosing = false
                                    this[openKey] = false
                                }, 240)
                            },
                        })
                    }
                }

                return
            }

            removeOverlayBackdrop(document, overlayId)

            if (this.__fffSheetDismissCleanup) {
                this.__fffSheetDismissCleanup()
                this.__fffSheetDismissCleanup = null
            }

            if (this.__fffSheetScrollLocked) {
                unlockOverlaySheetScroll(document)
                this.__fffSheetScrollLocked = false
            }
        },

        closeTeleportedMenu() {
            if (! this[openKey] || this.__fffSheetClosing) {
                return
            }

            const menu = this.resolveMenuElement()
            const sheet = menuIsSheet(menu) || this.__fffOverlayMode === 'sheet'

            if (sheet) {
                this.__fffSheetClosing = true

                if (menu && ! menuIsSheet(menu)) {
                    applyOverlayPresentation(menu, 'sheet')
                }

                removeOverlayBackdrop(document, this.resolveMenuOverlayId())

                playSheetExit(menu, () => {
                    this.__fffSheetClosing = false
                    this[openKey] = false
                })

                return
            }

            hideTeleportedMenuPanel(menu, () => {
                this[openKey] = false
            })
        },

        /**
         * Exclusive displace must flip open state immediately. Delaying for the
         * glass exit animation leaves comboboxOpen=true and can keep another
         * field's panel painted while a sibling select is opening.
         */
        closeTeleportedMenuImmediate() {
            if (! this[openKey]) {
                return
            }

            const menu = this.resolveMenuElement()

            cancelMenuCloseAnimation(menu)

            if (menu) {
                menu.classList.remove(
                    'is-open',
                    'is-closing',
                    'fff-teleported-menu--sheet',
                    'fff-teleported-menu--panel',
                )
                clearOverlaySheetInlineStyles(menu)
                removeSheetChrome(menu)
            }

            this[openKey] = false
        },

        bindMenuListeners() {
            this.unbindMenuListeners()

            // Breakpoint flips (panel↔sheet) must run even when overlay-runtime owns
            // panel scroll reposition — runtime resize only calls positionPanel().
            this[resizeHandlerKey] = () => {
                if (this.__fffMenuPositionRaf) {
                    return
                }

                this.__fffMenuPositionRaf = requestAnimationFrame(() => {
                    this.__fffMenuPositionRaf = 0

                    if (this[openKey]) {
                        this.updateMenuPosition({ reveal: false })
                    }
                })
            }

            window.addEventListener('resize', this[resizeHandlerKey])

            if (this.__fffOverlayManaged) {
                return
            }

            this[scrollHandlerKey] = (event) => {
                const menu = this.resolveMenuElement()
                const trigger = this.resolveMenuTriggerRef()

                if (shouldSkipMenuScrollReposition(event, menu, trigger)) {
                    return
                }

                if (this.__fffMenuPositionRaf) {
                    return
                }

                this.__fffMenuPositionRaf = requestAnimationFrame(() => {
                    this.__fffMenuPositionRaf = 0

                    if (this[openKey]) {
                        this.updateMenuPosition({ reveal: false })
                    }
                })
            }

            const trigger = this.resolveMenuTriggerRef()
            const scrollParents = resolveScrollableParents(trigger)
            const scrollingElement = document.scrollingElement

            if (scrollingElement && ! scrollParents.includes(scrollingElement)) {
                scrollParents.push(scrollingElement)
            }

            this[scrollParentsKey] = scrollParents

            for (const parent of scrollParents) {
                parent.addEventListener('scroll', this[scrollHandlerKey], { passive: true })
            }

            window.addEventListener('scroll', this[scrollHandlerKey], true)
        },

        unbindMenuListeners() {
            if (this.__fffMenuPositionRaf) {
                cancelAnimationFrame(this.__fffMenuPositionRaf)
                this.__fffMenuPositionRaf = 0
            }

            if (this[scrollHandlerKey]) {
                for (const parent of this[scrollParentsKey] ?? []) {
                    parent.removeEventListener('scroll', this[scrollHandlerKey])
                }

                window.removeEventListener('scroll', this[scrollHandlerKey], true)
                this[scrollHandlerKey] = null
            }

            if (this[resizeHandlerKey]) {
                window.removeEventListener('resize', this[resizeHandlerKey])
                this[resizeHandlerKey] = null
            }

            this[scrollParentsKey] = []
        },

        bindSelectMenuLifecycle({ wireExclusive = true } = {}) {
            const displaceThisMenu = () => {
                if (! this[openKey]) {
                    return
                }

                // Exclusive displace must flip open state immediately so a sibling
                // picker can claim the overlay. Backdrop / handle dismiss use the
                // animated sheet exit instead.
                if (typeof this.closeTeleportedMenuImmediate === 'function') {
                    this.closeTeleportedMenuImmediate()

                    return
                }

                this[openKey] = false
            }

            if (wireExclusive) {
                // closeMethod null → controller sets openKey=false immediately
                // (avoids comboboxCloseMenu's glass-exit delay during exclusive open).
                wireExclusiveFlexDropdown(this, {
                    openKey,
                    closeMethod: null,
                    ownerIdPrefix,
                })
            }

            this.$watch(openKey, (open) => {
                const overlayExclusiveId = this.resolveMenuOverlayId()

                if (open) {
                    this.__fffMenuOpenStartedAt = typeof performance !== 'undefined' ? performance.now() : Date.now()

                    // Lock immediately so exclusive displace works before the
                    // teleported panel is measured/anchored; upgrade to open().
                    claimOverlayExclusive(overlayExclusiveId, {
                        onDisplace: displaceThisMenu,
                    })
                    this.scheduleMenuPosition({
                        onAnchored: () => {
                            const trigger = this.resolveMenuTriggerRef()
                            const menu = this.resolveMenuElement()
                            const anchoredOverlayId = this.resolveMenuOverlayId()

                            if (! trigger || ! menu) {
                                this.bindMenuListeners()

                                return
                            }

                            forceHideForeignSelectMenus(menu)

                            const overlayMode = resolveOverlayMode(window)

                            applyOverlayPresentation(menu, overlayMode)

                            openOverlayPanel({
                                id: anchoredOverlayId,
                                panel: menu,
                                anchor: trigger,
                                mode: overlayMode,
                                exclusive: wireExclusive,
                                manageVisibility: false,
                                onDisplace: displaceThisMenu,
                                minWidth: typeof this.resolveMenuMinWidth === 'function'
                                    ? this.resolveMenuMinWidth()
                                    : minMenuWidth,
                                matchTriggerWidth: typeof this.resolveMatchTriggerWidth === 'function'
                                    ? this.resolveMatchTriggerWidth()
                                    : matchTriggerWidth,
                                align: this.resolveDropdownAlign?.() === 'end' ? 'end' : 'start',
                                gap: menuGap,
                                position: this.position === 'top' || this.position === 'bottom'
                                    ? this.position
                                    : null,
                            })
                            this.__fffOverlayManaged = true
                            this.__fffOverlayMode = overlayMode
                            // Lock scroll + backdrop before enter; bind dismiss after so
                            // snap/layout work cannot delay the first slide frame.
                            this.syncOpenOverlaySheetEnvironment(overlayMode, menu, { bindDismiss: false })

                            if (typeof this.afterOverlayPanelOpened === 'function') {
                                this.afterOverlayPanelOpened(overlayMode, menu)
                            }

                            if (overlayMode === 'sheet') {
                                playSheetEnter(menu)
                                this.syncOpenOverlaySheetEnvironment('sheet', menu)
                            }
                            // readyKey is set by scheduleMenuPosition after onAnchored returns
                            // so first paint already includes overlay geometry.

                            if (typeof this.__fffMenuOpenStartedAt === 'number') {
                                const latency = (typeof performance !== 'undefined' ? performance.now() : Date.now())
                                    - this.__fffMenuOpenStartedAt

                                emitOverlayOpenLatency(anchoredOverlayId, latency)
                            }

                            // Managed overlays skip scroll listeners (runtime owns those),
                            // but still need resize for panel↔sheet breakpoint flips.
                            this.bindMenuListeners()
                        },
                    })

                    return
                }

                const menu = this.resolveMenuElement()

                if (menu) {
                    cancelMenuCloseAnimation(menu)
                    menu.classList.remove('is-open', 'is-closing', 'fff-teleported-menu--sheet', 'fff-teleported-menu--panel')
                    delete menu.dataset.fffOverlayPresentation
                    delete menu.dataset.fffSheetFittedHeight
                    delete menu.dataset.fffOverlaySnap
                    menu.style.transition = ''
                    menu.style.transform = ''
                    clearOverlaySheetInlineStyles(menu)
                    removeSheetChrome(menu)
                }

                this.__fffSheetClosing = false
                removeOverlayBackdrop(document, overlayExclusiveId)

                if (this.__fffSheetDismissCleanup) {
                    this.__fffSheetDismissCleanup()
                    this.__fffSheetDismissCleanup = null
                }

                if (this.__fffSheetScrollLocked) {
                    unlockOverlaySheetScroll(document)
                    this.__fffSheetScrollLocked = false
                }

                if (this.__fffOverlayManaged) {
                    closeOverlayPanel(overlayExclusiveId)
                    this.__fffOverlayManaged = false
                } else {
                    // Still lock-only (anchoring never completed).
                    releaseOverlayExclusive(overlayExclusiveId)
                }

                this.__fffOverlayMode = null
                this.__fffMenuOpenStartedAt = null

                if (! menu?.__fffHasBeenPositioned) {
                    this[readyKey] = false
                }

                if (typeof onMenuClose === 'function') {
                    onMenuClose.call(this)
                }

                if (typeof this.resetVirtualListScroll === 'function') {
                    this.resetVirtualListScroll()
                }

                this.unbindMenuListeners()
            })
        },
    }
}
