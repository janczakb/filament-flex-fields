/**
 * Shared glass theme tokens for teleported searchable dropdown menus.
 */
import { wireExclusiveFlexDropdown } from './flex-dropdown-coordinator.js'
import { resolveIsDark } from './theme-utils.js'

export function applyTeleportedMenuTheme(menu, { variant = 'default' } = {}) {
    if (! menu) {
        return
    }

    const isDark = resolveIsDark()
    const blur = 'blur(16px) saturate(180%)'
    const darkMenuBackground = variant === 'map'
        ? 'rgb(39 39 42 / 0.9)'
        : '#27272a3d'

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
        menu.style.setProperty('--fff-select-menu-shadow', '0 4px 6px -1px rgb(0 0 0 / 0.06), 0 12px 28px -6px rgb(0 0 0 / 0.12)')
        menu.style.setProperty('--fff-select-menu-hover', 'rgb(244 244 245 / 0.72)')
        menu.style.setProperty('--fff-select-menu-selected', 'rgb(228 228 231 / 0.78)')
        menu.style.setProperty('--fff-select-search-bg', 'rgb(255 255 255 / 0.55)')
        menu.style.setProperty('--fff-select-search-border', 'rgb(228 228 231)')
        menu.style.setProperty('--fff-phone-field-menu-text', 'rgb(24 24 27)')
        menu.style.setProperty('--fff-phone-field-menu-muted', 'rgb(113 113 122)')
    }

    menu.style.backgroundColor = isDark ? darkMenuBackground : '#ffffffa3'
    menu.style.setProperty('backdrop-filter', blur)
    menu.style.setProperty('-webkit-backdrop-filter', blur)
    menu.style.color = isDark ? 'rgb(250 250 250)' : 'rgb(24 24 27)'
}

/**
 * Floating searchable select menu used by country, timezone, and currency fields.
 */
export function createSearchableSelectMenuMixin({
    openKey = 'menuOpen',
    readyKey = 'menuReady',
    scrollHandlerKey = 'menuScrollHandler',
    resizeHandlerKey = 'menuResizeHandler',
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

            menu.style.position = 'fixed'
            menu.style.width = `${Math.round(menuWidth)}px`
            menu.style.zIndex = 'var(--fff-z-dropdown, 20)'
            menu.style.top = `${Math.round(top)}px`
            menu.style.left = `${Math.round(left)}px`
            menu.style.marginTop = '0'

            const menuRect = menu.getBoundingClientRect()

            if (menuRect.bottom > window.innerHeight - viewportPadding) {
                const aboveTop = rect.top - menuRect.height - gap

                if (aboveTop >= viewportPadding) {
                    top = aboveTop
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
            this[readyKey] = true
        },

        applySelectMenuTheme(menu) {
            applyTeleportedMenuTheme(menu, { variant: menuThemeVariant })
        },

        bindMenuListeners() {
            if (this[scrollHandlerKey]) {
                return
            }

            this[scrollHandlerKey] = () => this.updateMenuPosition()
            this[resizeHandlerKey] = () => this.updateMenuPosition()

            window.addEventListener('scroll', this[scrollHandlerKey], true)
            window.addEventListener('resize', this[resizeHandlerKey])
        },

        unbindMenuListeners() {
            if (! this[scrollHandlerKey]) {
                return
            }

            window.removeEventListener('scroll', this[scrollHandlerKey], true)
            window.removeEventListener('resize', this[resizeHandlerKey])

            this[scrollHandlerKey] = null
            this[resizeHandlerKey] = null
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
