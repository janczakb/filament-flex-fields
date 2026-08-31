import { applyTeleportedMenuTheme } from './searchable-select-menu.js'
import { resolveTeleportedMenuZIndex } from './theme-utils.js'

/**
 * Lightweight fixed-position geocoding dropdown — no overlay-runtime, no headless virtual list.
 */
export function createGeocodingDropdownMenuMixin({
    openKey = 'searchOpen',
    readyKey = 'searchDropdownReady',
    triggerRef = 'searchWrap',
    widthRef = null,
    menuRef = 'searchDropdown',
    closeMethod = 'closeSearchDropdown',
    menuThemeVariant = 'default',
    menuGap = 8,
    viewportPadding = 16,
} = {}) {
    return {
        bindGeocodingDropdownMenu() {
            this.$watch(openKey, (open) => {
                if (open) {
                    this.scheduleGeocodingDropdownPosition()

                    return
                }

                this.teardownGeocodingDropdownMenu()
            })
        },

        resolveGeocodingDropdownAnchor() {
            return this.$refs[widthRef ?? triggerRef] ?? this.$refs[triggerRef] ?? null
        },

        scheduleGeocodingDropdownPosition() {
            this[readyKey] = false

            const attempt = (pass = 0) => {
                this.$nextTick(() => {
                    requestAnimationFrame(() => {
                        const trigger = this.resolveGeocodingDropdownAnchor()
                        const menu = this.$refs[menuRef]

                        if ((! trigger || ! menu) && pass < 8) {
                            attempt(pass + 1)

                            return
                        }

                        if (! trigger || ! menu) {
                            return
                        }

                        this.positionGeocodingDropdown()
                        this.bindGeocodingDropdownListeners()
                    })
                })
            }

            attempt()
        },

        positionGeocodingDropdown() {
            const trigger = this.resolveGeocodingDropdownAnchor()
            const menu = this.$refs[menuRef]

            if (! trigger || ! menu || ! this[openKey]) {
                return
            }

            applyTeleportedMenuTheme(menu, { variant: menuThemeVariant })

            const rect = trigger.getBoundingClientRect()
            const width = Math.min(
                Math.max(Math.round(rect.width), 0),
                window.innerWidth - (viewportPadding * 2),
            )
            let top = Math.round(rect.bottom + menuGap)
            let left = Math.round(rect.left)
            let opensAbove = false

            menu.style.position = 'fixed'
            menu.style.setProperty('width', `${width}px`, 'important')
            menu.style.setProperty('min-width', `${width}px`, 'important')
            menu.style.setProperty('max-width', `${width}px`, 'important')
            menu.style.zIndex = String(resolveTeleportedMenuZIndex())
            menu.style.top = `${top}px`
            menu.style.left = `${left}px`
            menu.style.right = 'auto'
            menu.style.marginTop = '0'
            menu.style.removeProperty('max-height')

            if (typeof window.getComputedStyle === 'function') {
                menu.dir = window.getComputedStyle(trigger).direction || 'ltr'
            }

            const menuRect = menu.getBoundingClientRect()

            if (menuRect.bottom > window.innerHeight - viewportPadding) {
                const aboveTop = rect.top - menuRect.height - menuGap

                if (aboveTop >= viewportPadding) {
                    top = Math.round(aboveTop)
                    opensAbove = true
                }
            }

            if (left + menuRect.width > window.innerWidth - viewportPadding) {
                left = window.innerWidth - menuRect.width - viewportPadding
            }

            if (left < viewportPadding) {
                left = viewportPadding
            }

            menu.style.top = `${top}px`
            menu.style.left = `${left}px`

            menu.classList.remove('is-closing')
            menu.classList.toggle('fff-teleported-menu--above', opensAbove)
            menu.classList.toggle('fff-teleported-menu--below', ! opensAbove)
            menu.classList.add('is-open')

            this[readyKey] = true
        },

        bindGeocodingDropdownListeners() {
            this.unbindGeocodingDropdownListeners()

            this.__fffGeocodingRepositionHandler = () => {
                if (! this[openKey]) {
                    return
                }

                if (this.__fffGeocodingRepositionRaf) {
                    return
                }

                this.__fffGeocodingRepositionRaf = requestAnimationFrame(() => {
                    this.__fffGeocodingRepositionRaf = 0
                    this.positionGeocodingDropdown()
                })
            }

            this.__fffGeocodingOutsideHandler = (event) => {
                const trigger = this.resolveGeocodingDropdownAnchor()
                const menu = this.$refs[menuRef]
                const target = event?.target

                if (! (target instanceof Node)) {
                    return
                }

                if (menu?.contains(target) || trigger?.contains(target)) {
                    return
                }

                if (typeof this[closeMethod] === 'function') {
                    this[closeMethod]()
                } else {
                    this[openKey] = false
                }
            }

            window.addEventListener('resize', this.__fffGeocodingRepositionHandler, { passive: true })
            window.addEventListener('scroll', this.__fffGeocodingRepositionHandler, true)
            document.addEventListener('mousedown', this.__fffGeocodingOutsideHandler, true)
        },

        unbindGeocodingDropdownListeners() {
            if (this.__fffGeocodingRepositionRaf) {
                cancelAnimationFrame(this.__fffGeocodingRepositionRaf)
                this.__fffGeocodingRepositionRaf = 0
            }

            if (this.__fffGeocodingRepositionHandler) {
                window.removeEventListener('resize', this.__fffGeocodingRepositionHandler)
                window.removeEventListener('scroll', this.__fffGeocodingRepositionHandler, true)
                this.__fffGeocodingRepositionHandler = null
            }

            if (this.__fffGeocodingOutsideHandler) {
                document.removeEventListener('mousedown', this.__fffGeocodingOutsideHandler, true)
                this.__fffGeocodingOutsideHandler = null
            }
        },

        teardownGeocodingDropdownMenu() {
            const menu = this.$refs[menuRef]

            this.unbindGeocodingDropdownListeners()
            this[readyKey] = false

            if (menu) {
                menu.classList.remove('is-open', 'is-closing', 'fff-teleported-menu--above', 'fff-teleported-menu--below')
            }
        },
    }
}
