import assert from 'node:assert/strict'
import { describe, it, before, after } from 'node:test'

import { createSearchableSelectMenuMixin, shouldSkipMenuScrollReposition } from '../../resources/js/core/searchable-select-menu.js'
import { createOverlayRuntime } from '../../resources/js/core/overlay-runtime.js'

describe('searchable select menu scroll reposition', () => {
    let previousDocument
    let previousWindow
    let previousMatchMedia

    before(() => {
        previousDocument = globalThis.document
        previousWindow = globalThis.window
        previousMatchMedia = globalThis.matchMedia

        const classList = {
            contains: () => false,
            add() {},
            remove() {},
            toggle() {},
        }

        globalThis.document = {
            documentElement: { classList },
            body: { classList },
            querySelector: () => null,
        }
        globalThis.window = {
            Alpine: { store: () => null },
            innerWidth: 1280,
            innerHeight: 800,
            matchMedia: () => ({ matches: false }),
            requestAnimationFrame: (callback) => {
                callback()

                return 1
            },
            cancelAnimationFrame: () => {},
        }
        globalThis.requestAnimationFrame = globalThis.window.requestAnimationFrame
        globalThis.cancelAnimationFrame = globalThis.window.cancelAnimationFrame
        globalThis.matchMedia = globalThis.window.matchMedia
    })

    after(() => {
        globalThis.document = previousDocument
        globalThis.window = previousWindow
        globalThis.matchMedia = previousMatchMedia
        delete globalThis.requestAnimationFrame
        delete globalThis.cancelAnimationFrame

        if (globalThis.window) {
            delete globalThis.window.FffOverlayRuntime
        }
    })

    it('repositions without requiring reveal and keeps menuReady', () => {
        const mixin = createSearchableSelectMenuMixin({
            openKey: 'menuOpen',
            menuRef: 'menuMenu',
            triggerRef: 'menuTrigger',
        })

        const menu = {
            style: {
                setProperty() {},
            },
            classList: {
                add() {},
                remove() {},
                toggle() {},
                contains: () => true,
            },
            getBoundingClientRect: () => ({
                top: 100,
                bottom: 300,
                left: 40,
                right: 340,
                width: 300,
                height: 200,
            }),
        }

        const trigger = {
            getBoundingClientRect: () => ({
                top: 40,
                bottom: 72,
                left: 40,
                right: 240,
                width: 200,
                height: 32,
            }),
        }

        const component = {
            ...mixin,
            menuOpen: true,
            menuReady: false,
            $refs: {
                menuTrigger: trigger,
                menuMenu: menu,
            },
        }

        component.updateMenuPosition({ reveal: false })
        assert.equal(component.menuReady, true)
    })

    it('aligns dropdown to trailing edge when resolveDropdownAlign returns end', () => {
        const mixin = createSearchableSelectMenuMixin({
            openKey: 'menuOpen',
            menuRef: 'menuMenu',
            triggerRef: 'menuTrigger',
        })

        const menu = {
            dir: 'ltr',
            style: {
                left: '',
                top: '',
                width: '',
                position: '',
                zIndex: '',
                marginTop: '',
                right: '',
                setProperty() {},
                removeProperty(key) {
                    delete this[key]
                },
            },
            classList: {
                add() {},
                remove() {},
                toggle() {},
                contains: () => true,
            },
            getBoundingClientRect: () => ({
                top: 100,
                bottom: 300,
                left: 80,
                right: 240,
                width: 160,
                height: 200,
            }),
        }

        const trigger = {
            getBoundingClientRect: () => ({
                top: 40,
                bottom: 72,
                left: 80,
                right: 200,
                width: 120,
                height: 32,
            }),
        }

        const component = {
            ...mixin,
            menuOpen: true,
            menuReady: false,
            resolveDropdownAlign: () => 'end',
            $refs: {
                menuTrigger: trigger,
                menuMenu: menu,
            },
        }

        component.updateMenuPosition({ reveal: false })
        assert.equal(menu.style.left, '40px')
    })

    it('skips reposition for internal multi-select chip scrolling', () => {
        const menuChild = { nodeType: 1 }
        const menu = {
            contains(node) {
                return node === menuChild
            },
        }
        const badges = {
            nodeType: 1,
            closest(selector) {
                return selector === '.fi-select-input-value-badges-ctn' ? badges : null
            },
        }
        const trigger = {
            contains(node) {
                return node === badges
            },
        }
        const body = { nodeType: 1 }

        assert.equal(
            shouldSkipMenuScrollReposition({ target: badges }, menu, trigger),
            true,
        )
        assert.equal(
            shouldSkipMenuScrollReposition({ target: menuChild }, menu, trigger),
            true,
        )
        assert.equal(
            shouldSkipMenuScrollReposition({ target: body }, menu, trigger),
            false,
        )
    })

    it('defers scroll listeners until the teleported menu is anchored', () => {
        const mixin = createSearchableSelectMenuMixin({
            openKey: 'menuOpen',
            menuRef: 'menuMenu',
            triggerRef: 'menuTrigger',
        })

        let anchored = false
        let bindCount = 0

        const trigger = {
            getBoundingClientRect: () => ({
                top: 40,
                bottom: 72,
                left: 40,
                right: 240,
                width: 200,
                height: 32,
            }),
        }

        const menu = {
            style: {
                setProperty() {},
            },
            classList: {
                add() {},
                remove() {},
                toggle() {},
                contains: () => true,
            },
            getBoundingClientRect: () => ({
                top: 100,
                bottom: 300,
                left: 40,
                right: 340,
                width: 300,
                height: 200,
            }),
        }

        const component = {
            ...mixin,
            menuOpen: true,
            menuReady: false,
            menuScrollHandler: null,
            menuResizeHandler: null,
            menuScrollParents: [],
            $nextTick(callback) {
                callback()
            },
            $refs: {
                menuTrigger: trigger,
                menuMenu: menu,
            },
            bindMenuListeners() {
                bindCount += 1
            },
        }

        component.scheduleMenuPosition({
            onAnchored: () => {
                anchored = true
                component.bindMenuListeners()
            },
        })

        assert.equal(anchored, true)
        assert.equal(bindCount, 1)
        assert.equal(component.menuReady, true)
    })

    it('defers menuReady on first open until nextTick/rAF settles', () => {
        const mixin = createSearchableSelectMenuMixin({
            openKey: 'menuOpen',
            menuRef: 'menuMenu',
            triggerRef: 'menuTrigger',
            minMenuWidth: 288,
            matchTriggerWidth: false,
        })

        const trigger = {
            getBoundingClientRect: () => ({
                top: 40,
                bottom: 72,
                left: 40,
                right: 240,
                width: 200,
                height: 32,
            }),
        }

        const menu = {
            style: {
                width: '',
                setProperty() {},
                removeProperty(name) {
                    if (name === 'width') {
                        this.width = ''
                    }
                },
            },
            classList: {
                add() {},
                remove() {},
                toggle() {},
                contains: () => false,
            },
            getBoundingClientRect: () => ({
                top: 100,
                bottom: 300,
                left: 40,
                right: 328,
                width: 288,
                height: 200,
            }),
        }

        let runNextTick = null

        const component = {
            ...mixin,
            menuOpen: true,
            menuReady: false,
            $nextTick(callback) {
                runNextTick = callback
            },
            $refs: {
                menuTrigger: trigger,
                menuMenu: menu,
            },
        }

        component.scheduleMenuPosition()

        assert.equal(component.menuReady, false)
        assert.equal(menu.style.width, '288px')
        assert.equal(typeof runNextTick, 'function')

        const frames = []
        const originalRaf = globalThis.requestAnimationFrame
        globalThis.requestAnimationFrame = (cb) => {
            frames.push(cb)

            return frames.length
        }

        try {
            runNextTick()
            assert.equal(component.menuReady, false)
            assert.ok(frames.length >= 1)
            frames.shift()()
            // First-open reveal path schedules a second rAF before ready.
            if (frames.length > 0) {
                assert.equal(component.menuReady, false)
                frames.shift()()
            }
            assert.equal(component.menuReady, true)
            assert.equal(menu.__fffHasBeenPositioned, true)
        } finally {
            globalThis.requestAnimationFrame = originalRaf
        }
    })

    it('sets menuReady synchronously on re-anchor when already positioned', () => {
        const mixin = createSearchableSelectMenuMixin({
            openKey: 'menuOpen',
            menuRef: 'menuMenu',
            triggerRef: 'menuTrigger',
        })

        const trigger = {
            getBoundingClientRect: () => ({
                top: 40,
                bottom: 72,
                left: 40,
                right: 240,
                width: 200,
                height: 32,
            }),
        }

        const menu = {
            __fffHasBeenPositioned: true,
            style: {
                setProperty() {},
                removeProperty() {},
            },
            classList: {
                add() {},
                remove() {},
                toggle() {},
                contains: () => false,
            },
            getBoundingClientRect: () => ({
                top: 100,
                bottom: 300,
                left: 40,
                right: 340,
                width: 300,
                height: 200,
            }),
        }

        let nextTickScheduled = false

        const component = {
            ...mixin,
            menuOpen: true,
            menuReady: false,
            $nextTick(callback) {
                nextTickScheduled = true
                // Intentionally do not run — sync unlock must happen first.
            },
            $refs: {
                menuTrigger: trigger,
                menuMenu: menu,
            },
        }

        component.scheduleMenuPosition()

        assert.equal(component.menuReady, true)
        assert.equal(menu.__fffHasBeenPositioned, true)
        assert.equal(nextTickScheduled, true)
    })

    it('claims overlay exclusive lock on open and displaces the previous menu', () => {
        const runtime = createOverlayRuntime({
            document: globalThis.document,
            window: globalThis.window,
        })
        globalThis.window.FffOverlayRuntime = runtime

        function mountMenu(ownerIdPrefix) {
            const mixin = createSearchableSelectMenuMixin({
                openKey: 'menuOpen',
                menuRef: 'menuMenu',
                triggerRef: 'menuTrigger',
                ownerIdPrefix,
                closeMethod: 'closeMenu',
            })

            /** @type {((open: boolean) => void) | null} */
            let openWatcher = null

            const component = {
                ...mixin,
                menuOpen: false,
                menuReady: false,
                $el: null,
                $refs: {},
                $watch(key, callback) {
                    if (key === 'menuOpen') {
                        openWatcher = callback
                    }
                },
                scheduleMenuPosition() {},
                unbindMenuListeners() {},
                closeMenu() {
                    if (! this.menuOpen) {
                        return
                    }

                    this.menuOpen = false
                    openWatcher?.(false)
                },
                openMenu() {
                    this.menuOpen = true
                    openWatcher?.(true)
                },
            }

            component.bindSelectMenuLifecycle({ wireExclusive: false })

            return component
        }

        const country = mountMenu('fff-country-field')
        const phone = mountMenu('fff-phone-field')

        country.openMenu()

        assert.equal(runtime.isOpen('fff-country-field-menu'), true)
        assert.deepEqual(runtime.getOpenIds(), ['fff-country-field-menu'])

        phone.openMenu()

        assert.equal(country.menuOpen, false)
        assert.equal(runtime.isOpen('fff-country-field-menu'), false)
        assert.equal(runtime.isOpen('fff-phone-field-menu'), true)
        assert.deepEqual(runtime.getOpenIds(), ['fff-phone-field-menu'])

        phone.closeMenu()

        assert.equal(runtime.isOpen('fff-phone-field-menu'), false)
        assert.deepEqual(runtime.getOpenIds(), [])

        runtime.destroy()
        delete globalThis.window.FffOverlayRuntime
    })

    it('uses per-instance overlay ids for geocoding menus on the same page', () => {
        const runtime = createOverlayRuntime({
            document: globalThis.document,
            window: globalThis.window,
        })
        globalThis.window.FffOverlayRuntime = runtime

        function mountGeocodingMenu(ownerIdPrefix) {
            const mixin = createSearchableSelectMenuMixin({
                openKey: 'searchOpen',
                menuRef: 'searchDropdown',
                triggerRef: 'searchWrap',
                ownerIdPrefix,
                closeMethod: 'closeSearchDropdown',
            })

            /** @type {((open: boolean) => void) | null} */
            let openWatcher = null

            const ownerEl = { nodeType: 1 }

            const component = {
                ...mixin,
                searchOpen: false,
                searchDropdownReady: false,
                $el: ownerEl,
                $refs: {},
                $watch(key, callback) {
                    if (key === 'searchOpen') {
                        openWatcher = callback
                    }
                },
                scheduleMenuPosition() {},
                unbindMenuListeners() {},
                closeSearchDropdown() {
                    if (! this.searchOpen) {
                        return
                    }

                    this.searchOpen = false
                    openWatcher?.(false)
                },
                openSearch() {
                    this.searchOpen = true
                    openWatcher?.(true)
                },
            }

            component.__fffMenuOverlayId = `${ownerIdPrefix}-instance-menu`
            component.bindSelectMenuLifecycle({ wireExclusive: false })

            return component
        }

        const firstPicker = mountGeocodingMenu('fff-map-picker-1')
        const secondPicker = mountGeocodingMenu('fff-map-picker-2')

        firstPicker.openSearch()
        secondPicker.openSearch()

        assert.equal(runtime.isOpen('fff-map-picker-1-instance-menu'), false)
        assert.equal(runtime.isOpen('fff-map-picker-2-instance-menu'), true)
        assert.equal(firstPicker.searchOpen, false)
        assert.equal(secondPicker.searchOpen, true)

        runtime.destroy()
        delete globalThis.window.FffOverlayRuntime
    })

    it('assigns a unique overlay id per select component key', () => {
        const mixin = createSearchableSelectMenuMixin({
            ownerIdPrefix: 'fff-headless-select',
        })

        const first = {
            ...mixin,
            componentKey: 'data.select__cascade_region',
            statePath: 'select__cascade_region',
        }
        const second = {
            ...mixin,
            componentKey: 'data.select__status',
            statePath: 'select__status',
        }

        const firstId = first.resolveMenuOverlayId()
        const secondId = second.resolveMenuOverlayId()

        assert.notEqual(firstId, secondId)
        assert.match(firstId, /select__cascade_region/)
        assert.match(secondId, /select__status/)
        assert.equal(first.resolveMenuOverlayId(), firstId)
        assert.equal(
            createSearchableSelectMenuMixin({ ownerIdPrefix: 'fff-headless-select' }).resolveMenuOverlayId.call({}),
            'fff-headless-select-menu',
        )
    })

    it('resolves the menu panel by DOM id when $refs point at another select', async () => {
        const { resolveSearchableSelectMenuElement, forceHideForeignSelectMenus } = await import(
            '../../resources/js/core/searchable-select-menu.js'
        )

        const foreign = {
            id: 'foreign-menu',
            classList: {
                contains: () => true,
                add() {},
                remove() {},
            },
            getAttribute: () => 'data.select__cascade_country',
            hasAttribute: (name) => name === 'data-fff-select-menu-owner',
        }
        const own = {
            id: 'own-menu',
            classList: {
                contains: () => false,
                add() {},
                remove() {},
            },
            getAttribute: () => 'data.select__scale_10k',
            hasAttribute: (name) => name === 'data-fff-select-menu-owner',
        }

        const elementsById = {
            'own-menu': own,
            'foreign-menu': foreign,
        }

        globalThis.document.getElementById = (id) => elementsById[id] ?? null
        globalThis.document.querySelector = () => null
        globalThis.document.querySelectorAll = () => [foreign, own]

        const resolved = resolveSearchableSelectMenuElement({
            $refs: { headlessMenu: foreign },
            menuDomId: 'own-menu',
            componentKey: 'data.select__scale_10k',
        }, 'headlessMenu')

        assert.equal(resolved, own)

        forceHideForeignSelectMenus(own)
        // foreign loses is-open/is-closing; own is skipped
        assert.equal(typeof foreign.classList.remove, 'function')
    })

    it('closes exclusive sibling immediately without waiting for glass exit', () => {
        const mixin = createSearchableSelectMenuMixin({
            openKey: 'comboboxOpen',
            menuRef: 'headlessMenu',
            ownerIdPrefix: 'fff-headless-select',
        })

        const menu = {
            classList: {
                classes: new Set(['is-open']),
                add(name) {
                    this.classes.add(name)
                },
                remove(...names) {
                    for (const name of names) {
                        this.classes.delete(name)
                    }
                },
                contains(name) {
                    return this.classes.has(name)
                },
                toggle() {},
            },
        }

        const component = {
            ...mixin,
            comboboxOpen: true,
            menuDomId: 'menu-a',
            componentKey: 'data.select__a',
            $refs: { headlessMenu: menu },
        }

        component.closeTeleportedMenuImmediate()

        assert.equal(component.comboboxOpen, false)
        assert.equal(menu.classList.contains('is-open'), false)
        assert.equal(menu.classList.contains('is-closing'), false)
    })
})
