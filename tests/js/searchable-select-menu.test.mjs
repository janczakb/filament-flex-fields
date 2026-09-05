import assert from 'node:assert/strict'
import { describe, it, before, after } from 'node:test'

import { createSearchableSelectMenuMixin, shouldSkipMenuScrollReposition, playSheetEnter } from '../../resources/js/core/searchable-select-menu.js'
import { createOverlayRuntime } from '../../resources/js/core/overlay-runtime.js'

describe('searchable select menu scroll reposition', () => {
    let previousDocument
    let previousWindow
    let previousMatchMedia

    before(() => {
        previousDocument = globalThis.document
        previousWindow = globalThis.window
        previousMatchMedia = globalThis.matchMedia

        const htmlClasses = new Set()
        const htmlAttributes = new Map()
        const bodyClasses = new Set()
        const bodyChildren = []

        const makeClassList = (store) => ({
            add: (...names) => {
                for (const name of names) {
                    store.add(name)
                }
            },
            remove: (...names) => {
                for (const name of names) {
                    store.delete(name)
                }
            },
            contains: (name) => store.has(name),
            toggle(name, force) {
                if (force === true) {
                    store.add(name)
                } else if (force === false) {
                    store.delete(name)
                }
            },
        })

        globalThis.document = {
            documentElement: {
                classList: makeClassList(htmlClasses),
                getAttribute(name) {
                    return htmlAttributes.get(name) ?? null
                },
                setAttribute(name, value) {
                    htmlAttributes.set(name, String(value))
                },
                removeAttribute(name) {
                    htmlAttributes.delete(name)
                },
            },
            body: {
                classList: makeClassList(bodyClasses),
                appendChild(node) {
                    bodyChildren.push(node)
                    node.parentElement = this

                    return node
                },
                children: bodyChildren,
            },
            createElement(tag) {
                const classes = new Set()
                const dataset = {}
                const attributes = new Map()
                const listeners = []
                const children = []

                return {
                    tagName: String(tag).toUpperCase(),
                    className: '',
                    style: {},
                    dataset,
                    classList: makeClassList(classes),
                    parentElement: null,
                    firstChild: null,
                    children,
                    setAttribute(name, value) {
                        attributes.set(name, String(value))
                    },
                    getAttribute(name) {
                        return attributes.get(name) ?? null
                    },
                    appendChild(node) {
                        children.push(node)
                        this.firstChild = children[0] ?? null
                        node.parentElement = this

                        return node
                    },
                    querySelector() {
                        return null
                    },
                    addEventListener(type, handler, options) {
                        listeners.push({ type, handler, options })
                    },
                    remove() {
                        const index = bodyChildren.indexOf(this)

                        if (index >= 0) {
                            bodyChildren.splice(index, 1)
                        }

                        this.parentElement = null
                    },
                }
            },
            querySelector(selector) {
                const match = String(selector).match(/data-fff-overlay-backdrop="([^"]+)"/)

                if (! match) {
                    return null
                }

                return bodyChildren.find((node) => node.dataset?.fffOverlayBackdrop === match[1]) ?? null
            },
        }
        globalThis.window = {
            Alpine: { store: () => null },
            innerWidth: 1280,
            innerHeight: 800,
            matchMedia: () => ({ matches: false }),
            getComputedStyle: () => ({ direction: 'ltr', zIndex: '50' }),
            addEventListener() {},
            removeEventListener() {},
            requestAnimationFrame: (callback) => {
                callback()

                return 1
            },
            cancelAnimationFrame: () => {},
            setTimeout: (callback) => {
                callback()

                return 1
            },
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

    it('does not re-open sheet classes while exit animation is in progress', () => {
        const mixin = createSearchableSelectMenuMixin({
            openKey: 'comboboxOpen',
            menuRef: 'headlessMenu',
            ownerIdPrefix: 'fff-headless-select',
        })

        const menu = {
            classList: {
                classes: new Set(['is-closing', 'fff-teleported-menu--sheet']),
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
            style: {
                setProperty() {},
                removeProperty() {},
            },
            getBoundingClientRect: () => ({ top: 0, left: 0, bottom: 0, width: 320, height: 240 }),
            dir: 'ltr',
        }

        const trigger = {
            getBoundingClientRect: () => ({ top: 0, left: 0, bottom: 40, width: 320, height: 40 }),
        }

        const component = {
            ...mixin,
            comboboxOpen: true,
            __fffSheetClosing: true,
            menuDomId: 'menu-sheet',
            componentKey: 'data.select__sheet',
            $refs: { headlessMenu: menu, headlessTrigger: trigger },
            resolveMenuTriggerRef() {
                return trigger
            },
            resolveMenuElement() {
                return menu
            },
        }

        component.updateMenuPosition({ reveal: true, markReady: true })
        component.scheduleMenuPosition()

        assert.equal(menu.classList.contains('is-closing'), true)
        assert.equal(menu.classList.contains('is-open'), false)
    })

    it('clears sheet peek height when anchoring as a desktop panel', () => {
        const mixin = createSearchableSelectMenuMixin({
            openKey: 'comboboxOpen',
            menuRef: 'headlessMenu',
            ownerIdPrefix: 'fff-headless-select',
            minMenuWidth: 200,
            matchTriggerWidth: true,
        })

        const removed = []
        const menu = {
            classList: {
                classes: new Set(['fff-teleported-menu--sheet', 'is-open']),
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
                toggle(name, force) {
                    if (force) {
                        this.classes.add(name)
                    } else {
                        this.classes.delete(name)
                    }
                },
            },
            style: {
                height: '320px',
                maxHeight: '320px',
                removeProperty(name) {
                    removed.push(name)
                },
                setProperty() {},
            },
            dataset: {
                fffOverlayPresentation: 'sheet',
                fffSheetFittedHeight: '320',
                fffOverlaySnap: 'peek',
            },
            querySelectorAll: () => [],
            getBoundingClientRect: () => ({ top: 80, left: 0, bottom: 400, width: 320, height: 320 }),
            dir: 'ltr',
        }

        const trigger = {
            getBoundingClientRect: () => ({ top: 0, left: 10, bottom: 40, width: 280, height: 40 }),
        }

        const originalMatchMedia = globalThis.window?.matchMedia
        if (globalThis.window) {
            globalThis.window.matchMedia = () => ({ matches: false, addListener() {}, removeListener() {} })
            globalThis.window.innerWidth = 1200
            globalThis.window.innerHeight = 800
            globalThis.window.getComputedStyle = () => ({ direction: 'ltr' })
        }

        const component = {
            ...mixin,
            comboboxOpen: true,
            __fffOverlayManaged: false,
            __fffOverlayMode: 'sheet',
            menuDomId: 'menu-panel',
            componentKey: 'data.select__inline_field_label',
            $refs: { headlessMenu: menu, headlessTrigger: trigger },
            resolveMenuTriggerRef() {
                return trigger
            },
            resolveMenuElement() {
                return menu
            },
        }

        try {
            component.updateMenuPosition({ reveal: false, markReady: true })
        } finally {
            if (globalThis.window && originalMatchMedia) {
                globalThis.window.matchMedia = originalMatchMedia
            }
        }

        assert.equal(menu.classList.contains('fff-teleported-menu--sheet'), false)
        assert.equal(menu.classList.contains('fff-teleported-menu--panel'), true)
        assert.ok(removed.includes('height'))
        assert.ok(removed.includes('max-height'))
        assert.equal(menu.dataset.fffSheetFittedHeight, undefined)
    })

    it('copies trigger writing direction onto the mobile sheet menu', () => {
        const mixin = createSearchableSelectMenuMixin({
            openKey: 'comboboxOpen',
            menuRef: 'headlessMenu',
            ownerIdPrefix: 'fff-headless-select',
            minMenuWidth: 200,
            matchTriggerWidth: true,
        })

        const menu = {
            classList: {
                classes: new Set(),
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
                toggle(name, force) {
                    if (force) {
                        this.classes.add(name)
                    } else {
                        this.classes.delete(name)
                    }
                },
            },
            style: {
                removeProperty() {},
                setProperty() {},
            },
            dataset: {},
            firstChild: null,
            querySelectorAll: () => [],
            insertBefore() {},
            getBoundingClientRect: () => ({ top: 0, left: 0, bottom: 0, width: 320, height: 200 }),
            dir: 'ltr',
        }

        const trigger = {
            getBoundingClientRect: () => ({ top: 0, left: 10, bottom: 40, width: 280, height: 40 }),
        }

        const originalMatchMedia = globalThis.window?.matchMedia
        const originalInnerWidth = globalThis.window?.innerWidth
        const originalInnerHeight = globalThis.window?.innerHeight
        const originalGetComputedStyle = globalThis.window?.getComputedStyle
        const originalDocument = globalThis.document
        const originalHTMLElement = globalThis.HTMLElement

        globalThis.HTMLElement = class HTMLElement {}
        globalThis.document = {
            ...originalDocument,
            createElement(tag) {
                return {
                    tagName: String(tag).toUpperCase(),
                    className: '',
                    style: {},
                    dataset: {},
                    classList: {
                        add() {},
                        remove() {},
                        contains: () => false,
                    },
                    setAttribute() {},
                    appendChild() {},
                    addEventListener() {},
                    querySelector: () => null,
                }
            },
        }

        if (globalThis.window) {
            globalThis.window.matchMedia = (query) => ({
                matches: String(query).includes('pointer: coarse') || String(query).includes('max-width'),
                addListener() {},
                removeListener() {},
            })
            globalThis.window.innerWidth = 390
            globalThis.window.innerHeight = 800
            globalThis.window.getComputedStyle = () => ({ direction: 'rtl' })
        }

        const component = {
            ...mixin,
            comboboxOpen: true,
            __fffOverlayManaged: false,
            menuDomId: 'menu-sheet-rtl',
            componentKey: 'data.select__rtl_sheet',
            $refs: { headlessMenu: menu, headlessTrigger: trigger },
            resolveMenuTriggerRef() {
                return trigger
            },
            resolveMenuElement() {
                return menu
            },
        }

        try {
            component.updateMenuPosition({ reveal: false, markReady: true })
        } finally {
            globalThis.document = originalDocument
            if (originalHTMLElement) {
                globalThis.HTMLElement = originalHTMLElement
            } else {
                delete globalThis.HTMLElement
            }
            if (globalThis.window) {
                if (originalMatchMedia) {
                    globalThis.window.matchMedia = originalMatchMedia
                }
                if (originalInnerWidth != null) {
                    globalThis.window.innerWidth = originalInnerWidth
                }
                if (originalInnerHeight != null) {
                    globalThis.window.innerHeight = originalInnerHeight
                }
                if (originalGetComputedStyle) {
                    globalThis.window.getComputedStyle = originalGetComputedStyle
                } else {
                    delete globalThis.window.getComputedStyle
                }
            }
        }

        assert.equal(menu.dir, 'rtl')
        assert.equal(menu.classList.contains('fff-teleported-menu--sheet'), true)
    })

    it('playSheetEnter parks closed then opens on the next frames', async () => {
        const styles = {}
        const menu = {
            isConnected: true,
            classList: {
                classes: new Set(['is-open', 'fff-teleported-menu--sheet']),
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
            },
            style: {
                setProperty(name, value) {
                    styles[name] = value
                },
                removeProperty(name) {
                    delete styles[name]
                },
            },
            dataset: {
                fffSheetFittedHeight: '280',
            },
            scrollHeight: 280,
            offsetWidth: 390,
            offsetHeight: 280,
            querySelectorAll: () => [],
            querySelector: () => null,
            getBoundingClientRect: () => ({ height: 280, width: 390 }),
            addEventListener() {},
            removeEventListener() {},
        }

        const frames = []
        const originalRaf = globalThis.requestAnimationFrame
        const originalSetTimeout = globalThis.setTimeout
        globalThis.requestAnimationFrame = (cb) => {
            frames.push(cb)

            return frames.length
        }
        globalThis.setTimeout = () => 1

        try {
            playSheetEnter(menu)

            assert.equal(menu.classList.contains('is-open'), false)
            assert.equal(styles.transform, 'translate3d(0, 100%, 0)')
            assert.equal(styles.transition, 'none')
            assert.equal(menu.dataset.fffSheetEntering, 'true')

            // Flush both rAF callbacks used by playSheetEnter.
            while (frames.length > 0) {
                const cb = frames.shift()
                cb()
            }

            assert.equal(menu.classList.contains('is-open'), true)
            assert.equal(styles.transform, undefined)
            assert.match(String(styles.transition ?? ''), /transform/)
        } finally {
            globalThis.requestAnimationFrame = originalRaf
            globalThis.setTimeout = originalSetTimeout
        }
    })

    it('does not glass-reveal sheet menus during updateMenuPosition', () => {
        const mixin = createSearchableSelectMenuMixin({
            openKey: 'comboboxOpen',
            menuRef: 'headlessMenu',
            ownerIdPrefix: 'fff-headless-select',
            minMenuWidth: 200,
            matchTriggerWidth: true,
        })

        const menu = {
            classList: {
                classes: new Set(['fff-teleported-menu--sheet']),
                add(name) { this.classes.add(name) },
                remove(...names) {
                    for (const name of names) {
                        this.classes.delete(name)
                    }
                },
                contains(name) { return this.classes.has(name) },
                toggle(name, force) {
                    if (force) {
                        this.classes.add(name)
                    } else {
                        this.classes.delete(name)
                    }
                },
            },
            style: {
                removeProperty() {},
                setProperty() {},
            },
            dataset: {},
            firstChild: null,
            querySelectorAll: () => [],
            insertBefore() {},
            getBoundingClientRect: () => ({ top: 0, left: 0, bottom: 0, width: 320, height: 200 }),
        }

        const trigger = {
            getBoundingClientRect: () => ({ top: 0, left: 10, bottom: 40, width: 280, height: 40 }),
        }

        const originalMatchMedia = globalThis.window?.matchMedia
        const originalInnerWidth = globalThis.window?.innerWidth
        const originalInnerHeight = globalThis.window?.innerHeight
        const originalGetComputedStyle = globalThis.window?.getComputedStyle
        const originalDocument = globalThis.document
        const originalHTMLElement = globalThis.HTMLElement

        globalThis.HTMLElement = class HTMLElement {}
        globalThis.document = {
            ...originalDocument,
            createElement(tag) {
                return {
                    tagName: String(tag).toUpperCase(),
                    className: '',
                    style: {},
                    dataset: {},
                    classList: {
                        add() {},
                        remove() {},
                        contains: () => false,
                    },
                    setAttribute() {},
                    appendChild() {},
                    addEventListener() {},
                    querySelector: () => null,
                }
            },
        }

        if (globalThis.window) {
            globalThis.window.matchMedia = (query) => ({
                matches: String(query).includes('pointer: coarse') || String(query).includes('max-width'),
                addListener() {},
                removeListener() {},
            })
            globalThis.window.innerWidth = 390
            globalThis.window.innerHeight = 800
            globalThis.window.getComputedStyle = () => ({ direction: 'ltr' })
        }

        const component = {
            ...mixin,
            comboboxOpen: true,
            __fffOverlayManaged: false,
            menuDomId: 'menu-no-reveal',
            componentKey: 'data.select__no_reveal',
            $refs: { headlessMenu: menu, headlessTrigger: trigger },
            resolveMenuTriggerRef() {
                return trigger
            },
            resolveMenuElement() {
                return menu
            },
        }

        try {
            component.updateMenuPosition({ reveal: true, markReady: true })
            assert.equal(menu.classList.contains('is-open'), false)
        } finally {
            globalThis.document = originalDocument
            if (originalHTMLElement) {
                globalThis.HTMLElement = originalHTMLElement
            }
            if (globalThis.window) {
                globalThis.window.matchMedia = originalMatchMedia
                globalThis.window.innerWidth = originalInnerWidth
                globalThis.window.innerHeight = originalInnerHeight
                globalThis.window.getComputedStyle = originalGetComputedStyle
            }
        }
    })

    it('managed overlay flips sheet→panel on desktop restore while open', () => {
        const runtime = createOverlayRuntime({
            document: globalThis.document,
            window: globalThis.window,
        })
        globalThis.window.FffOverlayRuntime = runtime
        globalThis.window.getComputedStyle = () => ({ direction: 'ltr' })
        globalThis.window.matchMedia = () => ({ matches: false, addListener() {}, removeListener() {} })
        globalThis.window.innerWidth = 1200
        globalThis.window.innerHeight = 800
        globalThis.matchMedia = globalThis.window.matchMedia

        const mixin = createSearchableSelectMenuMixin({
            openKey: 'comboboxOpen',
            menuRef: 'headlessMenu',
            ownerIdPrefix: 'fff-headless-select',
            minMenuWidth: 200,
            matchTriggerWidth: true,
        })

        const removed = []
        const menu = {
            classList: {
                classes: new Set(['fff-teleported-menu--sheet', 'fff-overlay-sheet', 'is-open']),
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
                toggle(name, force) {
                    if (force) {
                        this.classes.add(name)
                    } else if (force === false) {
                        this.classes.delete(name)
                    }
                },
            },
            style: {
                left: '0',
                right: '0',
                bottom: '0',
                width: '100%',
                removeProperty(name) {
                    removed.push(name)
                    this[name] = ''
                },
                setProperty(name, value) {
                    this[name] = value
                },
            },
            dataset: {
                fffOverlayPresentation: 'sheet',
                fffOverlaySheet: 'true',
                fffOverlaySnap: 'peek',
            },
            querySelector: () => null,
            querySelectorAll: () => [],
            getBoundingClientRect: () => ({ top: 80, left: 0, bottom: 400, width: 390, height: 320 }),
            scrollHeight: 320,
            clientHeight: 320,
            offsetWidth: 390,
            dir: 'ltr',
            contains() {
                return false
            },
            setAttribute() {},
            removeAttribute() {},
            getAttribute() {
                return null
            },
            hidden: false,
        }

        const trigger = {
            getBoundingClientRect: () => ({ top: 120, left: 420, bottom: 160, width: 280, height: 40, right: 700 }),
            offsetWidth: 280,
        }

        runtime.open({
            id: 'fff-headless-select-members-menu',
            panel: menu,
            anchor: trigger,
            mode: 'sheet',
            exclusive: true,
            manageVisibility: false,
        })

        const component = {
            ...mixin,
            comboboxOpen: true,
            __fffOverlayManaged: true,
            __fffOverlayMode: 'sheet',
            __fffSheetScrollLocked: true,
            menuDomId: 'menu-managed-resize',
            componentKey: 'data.select__members',
            statePath: 'members',
            $refs: { headlessMenu: menu, headlessTrigger: trigger },
            resolveMenuOverlayId() {
                return 'fff-headless-select-members-menu'
            },
            resolveMenuTriggerRef() {
                return trigger
            },
            resolveMenuElement() {
                return menu
            },
        }

        globalThis.document.documentElement.setAttribute('data-fff-overlay-sheet-locks', '1')
        globalThis.document.documentElement.classList.add('fff-overlay-sheet-open')

        try {
            component.updateMenuPosition({ reveal: false, markReady: true })

            assert.equal(component.__fffOverlayMode, 'panel')
            assert.equal(menu.classList.contains('fff-teleported-menu--sheet'), false)
            assert.equal(menu.classList.contains('fff-teleported-menu--panel'), true)
            assert.equal(menu.classList.contains('fff-overlay-sheet'), false)
            assert.ok(removed.includes('left'))
            assert.notEqual(String(menu.style.left), '0')
            assert.match(String(menu.style.left), /px$/)
            assert.equal(component.__fffSheetScrollLocked, false)
            assert.equal(globalThis.document.documentElement.classList.contains('fff-overlay-sheet-open'), false)
        } finally {
            runtime.destroy()
            delete globalThis.window.FffOverlayRuntime
        }
    })

    it('managed overlay locks body scroll when flipping panel→sheet while open', () => {
        const runtime = createOverlayRuntime({
            document: globalThis.document,
            window: globalThis.window,
        })
        globalThis.window.FffOverlayRuntime = runtime
        globalThis.window.getComputedStyle = () => ({ direction: 'ltr', zIndex: '80' })
        globalThis.window.matchMedia = (query) => ({
            matches: String(query).includes('max-width') || String(query).includes('pointer: coarse'),
            addListener() {},
            removeListener() {},
        })
        globalThis.window.innerWidth = 390
        globalThis.window.innerHeight = 800
        globalThis.matchMedia = globalThis.window.matchMedia

        const mixin = createSearchableSelectMenuMixin({
            openKey: 'comboboxOpen',
            menuRef: 'headlessMenu',
            ownerIdPrefix: 'fff-headless-select',
            minMenuWidth: 200,
            matchTriggerWidth: true,
        })

        const menu = {
            classList: {
                classes: new Set(['fff-teleported-menu--panel', 'fff-overlay-panel', 'is-open']),
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
                toggle(name, force) {
                    if (force) {
                        this.classes.add(name)
                    } else if (force === false) {
                        this.classes.delete(name)
                    }
                },
            },
            style: {
                left: '420px',
                top: '160px',
                removeProperty(name) {
                    this[name] = ''
                },
                setProperty(name, value) {
                    this[name] = value
                },
            },
            dataset: {
                fffOverlayPresentation: 'panel',
            },
            querySelector: () => null,
            querySelectorAll: () => [],
            insertBefore(node) {
                return node
            },
            getBoundingClientRect: () => ({ top: 160, left: 420, bottom: 400, width: 280, height: 240 }),
            scrollHeight: 240,
            clientHeight: 240,
            offsetWidth: 280,
            dir: 'ltr',
            firstChild: null,
            contains() {
                return false
            },
            setAttribute() {},
            removeAttribute() {},
            getAttribute() {
                return null
            },
            addEventListener() {},
            removeEventListener() {},
            hidden: false,
        }

        const trigger = {
            getBoundingClientRect: () => ({ top: 120, left: 420, bottom: 160, width: 280, height: 40, right: 700 }),
            offsetWidth: 280,
        }

        runtime.open({
            id: 'fff-headless-select-members-sheet-menu',
            panel: menu,
            anchor: trigger,
            mode: 'panel',
            exclusive: true,
            manageVisibility: false,
        })

        const component = {
            ...mixin,
            comboboxOpen: true,
            __fffOverlayManaged: true,
            __fffOverlayMode: 'panel',
            __fffSheetScrollLocked: false,
            menuDomId: 'menu-managed-to-sheet',
            componentKey: 'data.select__members_sheet',
            statePath: 'members_sheet',
            $refs: { headlessMenu: menu, headlessTrigger: trigger },
            resolveMenuOverlayId() {
                return 'fff-headless-select-members-sheet-menu'
            },
            resolveMenuTriggerRef() {
                return trigger
            },
            resolveMenuElement() {
                return menu
            },
        }

        try {
            component.updateMenuPosition({ reveal: false, markReady: true })

            assert.equal(component.__fffOverlayMode, 'sheet')
            assert.equal(component.__fffSheetScrollLocked, true)
            assert.equal(globalThis.document.documentElement.classList.contains('fff-overlay-sheet-open'), true)
            assert.equal(globalThis.document.documentElement.getAttribute('data-fff-overlay-sheet-locks'), '1')
            assert.equal(menu.classList.contains('fff-teleported-menu--sheet'), true)
        } finally {
            if (component.__fffSheetScrollLocked) {
                component.syncOpenOverlaySheetEnvironment('panel', menu)
            }

            runtime.destroy()
            delete globalThis.window.FffOverlayRuntime
        }
    })

    it('managed overlay still binds window resize for panel↔sheet flips', () => {
        const mixin = createSearchableSelectMenuMixin({
            openKey: 'comboboxOpen',
            menuRef: 'headlessMenu',
            ownerIdPrefix: 'fff-headless-select',
            minMenuWidth: 200,
            matchTriggerWidth: true,
        })

        const listeners = []
        const originalAdd = globalThis.window.addEventListener.bind(globalThis.window)
        const originalRemove = globalThis.window.removeEventListener.bind(globalThis.window)
        const originalRaf = globalThis.requestAnimationFrame
        const rafCallbacks = []

        globalThis.window.addEventListener = (type, handler, options) => {
            listeners.push({ type, handler, options })
        }
        globalThis.window.removeEventListener = (type, handler) => {
            const idx = listeners.findIndex((entry) => entry.type === type && entry.handler === handler)
            if (idx >= 0) {
                listeners.splice(idx, 1)
            }
        }
        globalThis.requestAnimationFrame = (cb) => {
            rafCallbacks.push(cb)
            return rafCallbacks.length
        }

        let updateCalls = 0
        const component = {
            ...mixin,
            comboboxOpen: true,
            __fffOverlayManaged: true,
            __fffOverlayMode: 'panel',
            updateMenuPosition() {
                updateCalls += 1
            },
            resolveMenuTriggerRef() {
                return { offsetWidth: 200 }
            },
            resolveMenuElement() {
                return { classList: { contains() { return false } } }
            },
        }

        try {
            component.bindMenuListeners()

            assert.equal(listeners.filter((entry) => entry.type === 'resize').length, 1)
            assert.equal(listeners.filter((entry) => entry.type === 'scroll').length, 0)
            assert.equal(typeof component.menuResizeHandler, 'function')
            assert.equal(component.menuScrollHandler, undefined)

            component.menuResizeHandler()
            assert.equal(rafCallbacks.length, 1)
            rafCallbacks[0]()
            assert.equal(updateCalls, 1)

            component.unbindMenuListeners()
            assert.equal(listeners.filter((entry) => entry.type === 'resize').length, 0)
            assert.equal(component.menuResizeHandler, null)
        } finally {
            globalThis.window.addEventListener = originalAdd
            globalThis.window.removeEventListener = originalRemove
            globalThis.requestAnimationFrame = originalRaf
            component.unbindMenuListeners?.()
        }
    })
})
