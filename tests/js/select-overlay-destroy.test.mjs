import assert from 'node:assert/strict'
import { describe, it, beforeEach, afterEach } from 'node:test'

import {
    createSearchableSelectMenuMixin,
    emergencySelectOverlayCleanup,
} from '../../resources/js/core/searchable-select-menu.js'

function makeClassList(initial = []) {
    const store = new Set(initial)

    return {
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
    }
}

describe('select overlay destroy + emergency cleanup', () => {
    let previousDocument
    let previousWindow
    let menus

    beforeEach(() => {
        previousDocument = globalThis.document
        previousWindow = globalThis.window
        menus = []

        const bodyClasses = new Set()

        globalThis.document = {
            body: {
                classList: makeClassList(),
                style: {},
            },
            documentElement: {
                classList: makeClassList(),
                style: {},
                getAttribute() {
                    return null
                },
                setAttribute() {},
                removeAttribute() {},
            },
            querySelectorAll(selector) {
                if (String(selector).includes('fff-select-headless-menu')
                    || String(selector).includes('fff-teleported-menu')
                    || String(selector).includes('fff-overlay-sheet')) {
                    return menus.filter((menu) => {
                        const open = menu.classList.contains('is-open') || menu.classList.contains('is-closing')

                        return open
                    })
                }

                return []
            },
            querySelector(selector) {
                if (String(selector).includes('is-open')) {
                    return null
                }

                const all = this.querySelectorAll(selector)

                return all[0] ?? null
            },
            getElementById() {
                return null
            },
        }

        globalThis.window = {
            document: globalThis.document,
            addEventListener() {},
            removeEventListener() {},
            requestAnimationFrame(cb) {
                return setTimeout(cb, 0)
            },
            cancelAnimationFrame(id) {
                clearTimeout(id)
            },
            matchMedia: () => ({ matches: false, addListener() {}, removeListener() {}, addEventListener() {}, removeEventListener() {} }),
        }
    })

    afterEach(() => {
        globalThis.document = previousDocument
        globalThis.window = previousWindow
    })

    it('destroyTeleportedMenuLifecycle closes open state and unbinds listeners', () => {
        const menu = {
            classList: makeClassList(['is-open', 'fff-select-headless-menu']),
            style: {},
            dataset: {},
            __fffHasBeenPositioned: true,
        }
        menus.push(menu)

        let unbindCalls = 0
        let dropdownUnbindCalls = 0

        const mixin = createSearchableSelectMenuMixin({
            openKey: 'comboboxOpen',
            ownerIdPrefix: 'fff-test-select',
        })

        const host = Object.assign(Object.create(mixin), {
            comboboxOpen: true,
            menuReady: true,
            __fffOverlayManaged: false,
            __fffSheetScrollLocked: false,
            __fffMenuOverlayId: 'fff-test-select-menu',
            resolveMenuElement() {
                return menu
            },
            unbindMenuListeners() {
                unbindCalls += 1
            },
            __fffDropdownUnbind() {
                dropdownUnbindCalls += 1
            },
        })

        host.destroyTeleportedMenuLifecycle()

        assert.equal(host.comboboxOpen, false)
        assert.equal(unbindCalls, 1)
        assert.equal(dropdownUnbindCalls, 1)
        assert.equal(host.__fffDropdownUnbind, null)
        assert.equal(menu.classList.contains('is-open'), false)
    })

    it('emergencySelectOverlayCleanup force-hides open teleported menus', () => {
        const menu = {
            classList: makeClassList(['is-open', 'fff-select-headless-menu', 'fff-teleported-menu']),
            style: { transition: 'x', transform: 'y' },
            dataset: { fffOverlayId: 'overlay-1' },
            getAttribute(name) {
                return name === 'data-fff-overlay-id' ? 'overlay-1' : null
            },
        }
        menus.push(menu)

        emergencySelectOverlayCleanup(globalThis.document)

        assert.equal(menu.classList.contains('is-open'), false)
        assert.equal(menu.classList.contains('is-closing'), false)
    })

    it('emergency cleanup after remount mid-open leaves no open menu class', () => {
        const menu = {
            classList: makeClassList(['is-open', 'fff-select-headless-menu', 'fff-teleported-menu']),
            style: {},
            dataset: { fffOverlayId: 'overlay-remount' },
            getAttribute(name) {
                return name === 'data-fff-overlay-id' ? 'overlay-remount' : null
            },
        }
        menus.push(menu)

        const mixin = createSearchableSelectMenuMixin({
            openKey: 'comboboxOpen',
            ownerIdPrefix: 'fff-remount-select',
        })

        const host = Object.assign(Object.create(mixin), {
            comboboxOpen: true,
            menuReady: true,
            resolveMenuElement() {
                return menu
            },
            unbindMenuListeners() {},
        })

        host.destroyTeleportedMenuLifecycle()
        emergencySelectOverlayCleanup(globalThis.document)

        assert.equal(host.comboboxOpen, false)
        assert.equal(menu.classList.contains('is-open'), false)
    })
})
