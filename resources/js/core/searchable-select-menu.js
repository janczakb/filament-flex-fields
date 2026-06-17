/**
 * Shared glass theme tokens for teleported searchable dropdown menus.
 */
import { wireExclusiveFlexDropdown } from './flex-dropdown-coordinator.js'
import { resolveIsDark, resolveTeleportedMenuZIndex } from './theme-utils.js'

export function resolveScrollableParents(element) {
    const parents = []
    let node = element?.parentElement

    while (node && node !== document.documentElement) {
        const style = window.getComputedStyle(node)

        if (/(auto|scroll|overlay)/.test(`${style.overflow}${style.overflowY}${style.overflowX}`)) {
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
    const blur = 'blur(16px) saturate(180%)'
    const darkMenuBackground = variant === 'map'
        ? 'rgb(39 39 42 / 0.9)'
        : 'rgb(39 39 42 / 0.92)'

    menu.classList.add('fff-teleported-menu')

    if (isDark) {
        menu.style.setProperty('--fff-select-menu-bg', darkMenuBackground)
        menu.style.setProperty('--fff-select-menu-border', 'rgb(255 255 255 / 0.12)')
        menu.style.setProperty('--fff-select-menu-shadow', '0 4px 6px -1px rgb(0 0 0 / 0.28), 0 12px 28px -6px rgb(0 0 0 / 0.5)')
        menu.style.setProperty('--fff-select-menu-hover', 'rgb(63 63 70 / 0.82)')
        menu.style.setProperty('--fff-select-menu-selected', 'rgb(82 82 91 / 0.88)')
        menu.style.setProperty('--fff-select-search-bg', 'rgb(39 39 42 / 0.55)')
        menu.style.setProperty('--fff-select-search-border', 'rgb(63 63 70)')
        menu.style.setProperty('--fff-phone-field-menu-text', 'rgb(250 250 250)')
        menu.style.setProperty('--fff-phone-field-menu-muted', 'rgb(212 212 216)')
    } else {
        menu.style.setProperty('--fff-select-menu-bg', '#ffffffa3')
        menu.style.setProperty('--fff-select-menu-border', 'rgb(228 228 231 / 0.65)')
        menu.style.setProperty('--fff-select-menu-shadow', '0 2px 8px 0 #0000000f, 0 -6px 12px 0 #00000008, 0 14px 28px 0 #00000014')
        menu.style.setProperty('--fff-select-menu-hover', 'rgb(244 244 245 / 0.72)')
        menu.style.setProperty('--fff-select-menu-selected', 'rgb(228 228 231 / 0.78)')
        menu.style.setProperty('--fff-select-search-bg', 'rgb(255 255 255 / 0.55)')
        menu.style.setProperty('--fff-select-search-border', 'rgb(228 228 231)')
        menu.style.setProperty('--fff-phone-field-menu-text', 'rgb(24 24 27)')
        menu.style.setProperty('--fff-phone-field-menu-muted', 'rgb(113 113 122)')
    }

    menu.style.setProperty('background-color', isDark ? darkMenuBackground : '#ffffffa3', 'important')
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

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches

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
        scheduleMenuPosition() {
            this[readyKey] = false

            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    this.updateMenuPosition()

                    requestAnimationFrame(() => {
                        this.updateMenuPosition()

                        if (typeof this.measureVirtualListViewport === 'function') {
                            this.measureVirtualListViewport()
                        }
                    })
                })
            })
        },

        updateMenuPosition() {
            const trigger = this.$refs[triggerRef]
            const menu = this.$refs[menuRef]

            if (! trigger || ! menu) {
                return
            }

            applyTeleportedMenuTheme(menu, { variant: menuThemeVariant })

            const rect = trigger.getBoundingClientRect()
            const gap = menuGap
            const viewportPadding = 16
            const menuWidth = matchTriggerWidth
                ? Math.min(Math.max(rect.width, minMenuWidth), window.innerWidth - (viewportPadding * 2))
                : Math.min(minMenuWidth, window.innerWidth - (viewportPadding * 2))

            let top = rect.bottom + gap
            let left = rect.left
            let opensAbove = false

            menu.style.position = 'fixed'
            menu.style.width = `${Math.round(menuWidth)}px`
            menu.style.zIndex = resolveTeleportedMenuZIndex()
            menu.style.top = `${Math.round(top)}px`
            menu.style.left = `${Math.round(left)}px`
            menu.style.marginTop = '0'

            const menuRect = menu.getBoundingClientRect()

            if (menuRect.bottom > window.innerHeight - viewportPadding) {
                const aboveTop = rect.top - menuRect.height - gap

                if (aboveTop >= viewportPadding) {
                    top = aboveTop
                    opensAbove = true
                }
            }

            if (left + menuRect.width > window.innerWidth - viewportPadding) {
                left = window.innerWidth - menuRect.width - viewportPadding
            }

            if (left < viewportPadding) {
                left = viewportPadding
            }

            menu.style.top = `${Math.round(top)}px`
            menu.style.left = `${Math.round(left)}px`
            menu.classList.toggle('fff-teleported-menu--above', opensAbove)
            menu.classList.toggle('fff-teleported-menu--below', ! opensAbove)
            this[readyKey] = true
            revealTeleportedMenuPanel(menu, this[openKey])
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
            if (this[scrollHandlerKey]) {
                return
            }

            this[scrollHandlerKey] = () => this.updateMenuPosition()
            this[resizeHandlerKey] = () => this.updateMenuPosition()

            const trigger = this.$refs[triggerRef]
            const scrollParents = resolveScrollableParents(trigger)

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
            if (wireExclusive) {
                wireExclusiveFlexDropdown(this, {
                    openKey,
                    closeMethod,
                    ownerIdPrefix,
                })
            }

            this.$watch(openKey, (open) => {
                if (open) {
                    this.scheduleMenuPosition()
                    this.bindMenuListeners()

                    return
                }

                const menu = this.$refs[menuRef]

                if (menu) {
                    cancelMenuCloseAnimation(menu)
                    menu.classList.remove('is-open', 'is-closing')
                }

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
