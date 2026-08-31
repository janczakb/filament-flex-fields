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
import { bindOverlaySheetDismiss } from './overlay-sheet-dismiss.js'
import { resolveTeleportedMenuHorizontalLeft } from './teleported-menu-position.js'
import {
    claimOverlayExclusive,
    closeOverlayPanel,
    emitOverlayOpenLatency,
    openOverlayPanel,
    releaseOverlayExclusive,
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

function revealTeleportedMenuPanel(menu, isOpen) {
    if (! menu) {
        return
    }

    cancelMenuCloseAnimation(menu)
    menu.classList.remove('is-closing')
    menu.classList.remove('is-open')
    void menu.offsetWidth

    requestAnimationFrame(() => {
        if (isOpen) {
            menu.classList.add('is-open')
        }
    })
}

function hideTeleportedMenuPanel(menu, onHidden) {
    if (! menu) {
        onHidden?.()

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
    if (! menu || menu.querySelector('[data-fff-overlay-handle]')) {
        return
    }

    const handle = document.createElement('div')

    handle.className = 'fff-overlay-sheet__handle'
    handle.dataset.fffOverlayHandle = 'true'
    handle.setAttribute('aria-hidden', 'true')
    menu.insertBefore(handle, menu.firstChild)
}

function applyOverlayPresentation(menu, mode) {
    if (! menu) {
        return
    }

    menu.classList.toggle('fff-teleported-menu--sheet', mode === 'sheet')
    menu.classList.toggle('fff-teleported-menu--panel', mode !== 'sheet')

    if (menu.dataset) {
        menu.dataset.fffOverlayPresentation = mode
    }

    if (mode === 'sheet') {
        ensureSheetChrome(menu)
    }
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
            return this.__fffMenuOverlayId ?? `${ownerIdPrefix}-menu`
        },

        resolveDropdownAlign() {
            return 'start'
        },

        resolveMenuTriggerRef() {
            if (typeof this.resolveMenuTriggerElement === 'function') {
                return this.resolveMenuTriggerElement()
            }

            return this.$refs[triggerRef] ?? null
        },

        scheduleMenuPosition({ onAnchored = null } = {}) {
            this[readyKey] = false

            const attempt = (pass = 0) => {
                this.$nextTick(() => {
                    requestAnimationFrame(() => {
                        const trigger = this.resolveMenuTriggerRef()
                        const menu = this.$refs[menuRef]

                        if ((! trigger || ! menu) && pass < 12) {
                            attempt(pass + 1)

                            return
                        }

                        if (! trigger || ! menu) {
                            onAnchored?.()

                            return
                        }

                        // Open animation only once — never on follow-up layout/scroll passes.
                        this.updateMenuPosition({ reveal: true })

                        requestAnimationFrame(() => {
                            this.updateMenuPosition({ reveal: false })

                            if (typeof this.measureVirtualListViewport === 'function') {
                                this.measureVirtualListViewport()
                            }

                            onAnchored?.()
                        })
                    })
                })
            }

            attempt()
        },

        updateMenuPosition({ reveal = false } = {}) {
            const trigger = this.resolveMenuTriggerRef()
            const menu = this.$refs[menuRef]
            const overlayId = this.resolveMenuOverlayId()

            if (! trigger || ! menu) {
                return
            }

            applyTeleportedMenuTheme(menu, { variant: menuThemeVariant })

            if (this.__fffOverlayManaged) {
                updateOverlayPanelPosition(overlayId)
                this[readyKey] = true

                if (reveal) {
                    revealTeleportedMenuPanel(menu, this[openKey])
                } else if (this[openKey]) {
                    menu.classList.remove('is-closing')
                    menu.classList.add('is-open')
                }

                return
            }

            const overlayMode = resolveOverlayMode(window)

            applyOverlayPresentation(menu, overlayMode)

            if (overlayMode === 'sheet') {
                menu.style.position = 'fixed'
                menu.style.zIndex = resolveTeleportedMenuZIndex()
                menu.style.insetInline = '0'
                menu.style.bottom = '0'
                menu.style.width = '100%'
                menu.style.removeProperty('top')
                menu.style.removeProperty('left')
                menu.style.marginTop = '0'
                menu.classList.remove('fff-teleported-menu--above', 'fff-teleported-menu--below')
                this[readyKey] = true

                if (reveal) {
                    revealTeleportedMenuPanel(menu, this[openKey])
                } else if (this[openKey]) {
                    menu.classList.remove('is-closing')
                    menu.classList.add('is-open')
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
            const menuWidth = shouldMatchTriggerWidth
                ? Math.min(Math.max(rect.width, minMenuWidth), window.innerWidth - (viewportPadding * 2))
                : Math.min(minMenuWidth, window.innerWidth - (viewportPadding * 2))

            let top = rect.bottom + gap
            let opensAbove = false
            const direction = typeof window.getComputedStyle === 'function'
                ? window.getComputedStyle(trigger).direction || 'ltr'
                : 'ltr'

            menu.style.position = 'fixed'
            if (shouldMatchTriggerWidth) {
                menu.style.width = `${Math.round(menuWidth)}px`
            } else {
                menu.style.removeProperty('width')
            }
            menu.style.zIndex = resolveTeleportedMenuZIndex()
            menu.style.top = `${Math.round(top)}px`
            menu.style.left = `${Math.round(rect.left)}px`
            menu.style.marginTop = '0'

            const menuRect = menu.getBoundingClientRect()
            let left = resolveTeleportedMenuHorizontalLeft({
                triggerRect: rect,
                menuWidth: menuRect.width,
                align,
                direction,
                viewportPadding,
                windowWidth: window.innerWidth,
            })

            if (menuRect.bottom > window.innerHeight - viewportPadding) {
                const aboveTop = rect.top - menuRect.height - gap

                if (aboveTop >= viewportPadding) {
                    top = aboveTop
                    opensAbove = true
                }
            }

            menu.style.top = `${Math.round(top)}px`
            menu.style.left = `${Math.round(left)}px`
            menu.style.right = 'auto'

            menu.dir = direction

            menu.classList.toggle('fff-teleported-menu--above', opensAbove)
            menu.classList.toggle('fff-teleported-menu--below', ! opensAbove)
            this[readyKey] = true

            if (reveal) {
                revealTeleportedMenuPanel(menu, this[openKey])
            } else if (this[openKey]) {
                menu.classList.remove('is-closing')
                menu.classList.add('is-open')
            }
        },

        applySelectMenuTheme(menu) {
            applyTeleportedMenuTheme(menu, { variant: menuThemeVariant })
        },

        closeTeleportedMenu() {
            if (! this[openKey]) {
                return
            }

            const menu = this.$refs[menuRef]

            hideTeleportedMenuPanel(menu, () => {
                this[openKey] = false
            })
        },

        bindMenuListeners() {
            if (this.__fffOverlayManaged) {
                return
            }

            this.unbindMenuListeners()

            this[scrollHandlerKey] = (event) => {
                const menu = this.$refs[menuRef]
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
            window.addEventListener('resize', this[resizeHandlerKey])
        },

        unbindMenuListeners() {
            if (! this[scrollHandlerKey]) {
                return
            }

            if (this.__fffMenuPositionRaf) {
                cancelAnimationFrame(this.__fffMenuPositionRaf)
                this.__fffMenuPositionRaf = 0
            }

            for (const parent of this[scrollParentsKey] ?? []) {
                parent.removeEventListener('scroll', this[scrollHandlerKey])
            }

            window.removeEventListener('scroll', this[scrollHandlerKey], true)
            window.removeEventListener('resize', this[resizeHandlerKey])

            this[scrollHandlerKey] = null
            this[resizeHandlerKey] = null
            this[scrollParentsKey] = []
        },

        bindSelectMenuLifecycle({ wireExclusive = true } = {}) {
            const overlayExclusiveId = this.resolveMenuOverlayId()

            const displaceThisMenu = () => {
                if (! this[openKey]) {
                    return
                }

                if (closeMethod && typeof this[closeMethod] === 'function') {
                    this[closeMethod]()

                    return
                }

                this[openKey] = false
            }

            if (wireExclusive) {
                wireExclusiveFlexDropdown(this, {
                    openKey,
                    closeMethod,
                    ownerIdPrefix,
                })
            }

            this.$watch(openKey, (open) => {
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
                            const menu = this.$refs[menuRef]

                            if (! trigger || ! menu) {
                                this.bindMenuListeners()

                                return
                            }

                            const overlayMode = resolveOverlayMode(window)

                            applyOverlayPresentation(menu, overlayMode)

                            if (overlayMode === 'sheet') {
                                const zIndex = parseInt(window.getComputedStyle(menu).zIndex, 10) || 50

                                createOverlayBackdrop(document, overlayExclusiveId, {
                                    zIndex: zIndex - 1,
                                    onDismiss: displaceThisMenu,
                                })

                                if (this.__fffSheetDismissCleanup) {
                                    this.__fffSheetDismissCleanup()
                                }

                                this.__fffSheetDismissCleanup = bindOverlaySheetDismiss({
                                    panel: menu,
                                    onDismiss: displaceThisMenu,
                                })
                            }

                            openOverlayPanel({
                                id: overlayExclusiveId,
                                panel: menu,
                                anchor: trigger,
                                mode: overlayMode,
                                exclusive: wireExclusive,
                                manageVisibility: false,
                                onDisplace: displaceThisMenu,
                                minWidth: minMenuWidth,
                                matchTriggerWidth: typeof this.resolveMatchTriggerWidth === 'function'
                                    ? this.resolveMatchTriggerWidth()
                                    : matchTriggerWidth,
                                align: this.resolveDropdownAlign?.() === 'end' ? 'end' : 'start',
                                gap: menuGap,
                            })
                            this.__fffOverlayManaged = true
                            this.__fffOverlayMode = overlayMode
                            this[readyKey] = true

                            if (typeof this.__fffMenuOpenStartedAt === 'number') {
                                const latency = (typeof performance !== 'undefined' ? performance.now() : Date.now())
                                    - this.__fffMenuOpenStartedAt

                                emitOverlayOpenLatency(overlayExclusiveId, latency)
                            }
                        },
                    })

                    return
                }

                const menu = this.$refs[menuRef]

                if (menu) {
                    cancelMenuCloseAnimation(menu)
                    menu.classList.remove('is-open', 'is-closing', 'fff-teleported-menu--sheet', 'fff-teleported-menu--panel')
                    delete menu.dataset.fffOverlayPresentation
                }

                removeOverlayBackdrop(document, overlayExclusiveId)

                if (this.__fffSheetDismissCleanup) {
                    this.__fffSheetDismissCleanup()
                    this.__fffSheetDismissCleanup = null
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
                this[readyKey] = false

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
