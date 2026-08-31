import assert from 'node:assert/strict'
import { describe, it, before, after } from 'node:test'

import { createGeocodingDropdownMenuMixin } from '../../resources/js/core/geocoding-dropdown-menu.js'

describe('geocoding dropdown menu', () => {
    let previousWindow

    before(() => {
        previousWindow = globalThis.window

        const classList = {
            contains: () => false,
            add() {},
            remove() {},
            toggle() {},
        }

        globalThis.window = {
            innerWidth: 1280,
            innerHeight: 800,
            matchMedia: () => ({ matches: false }),
            getComputedStyle: () => ({ direction: 'ltr' }),
            requestAnimationFrame: (callback) => {
                callback()

                return 1
            },
            cancelAnimationFrame: () => {},
        }
        globalThis.requestAnimationFrame = globalThis.window.requestAnimationFrame
        globalThis.cancelAnimationFrame = globalThis.window.cancelAnimationFrame
        globalThis.document = {
            documentElement: { classList },
            body: { classList },
            querySelector: () => null,
            addEventListener: () => {},
            removeEventListener: () => {},
        }
    })

    after(() => {
        globalThis.window = previousWindow
        delete globalThis.requestAnimationFrame
        delete globalThis.cancelAnimationFrame
        delete globalThis.document
    })

    it('anchors the menu to trigger width with fixed positioning', () => {
        const mixin = createGeocodingDropdownMenuMixin({
            openKey: 'searchOpen',
            readyKey: 'searchDropdownReady',
            triggerRef: 'searchWrap',
            menuRef: 'searchDropdown',
        })

        const trigger = {
            getBoundingClientRect: () => ({
                top: 80,
                bottom: 120,
                left: 48,
                right: 448,
                width: 400,
                height: 40,
            }),
            contains: () => false,
        }

        const setPropertyCalls = []
        const menu = {
            style: {
                setProperty(name, value, priority) {
                    setPropertyCalls.push([name, value, priority])
                    this[name] = value
                },
                removeProperty() {},
            },
            dir: 'ltr',
            classList: {
                _classes: new Set(),
                add(...names) {
                    names.forEach((name) => this._classes.add(name))
                },
                remove(...names) {
                    names.forEach((name) => this._classes.delete(name))
                },
                toggle(name, force) {
                    if (force) {
                        this._classes.add(name)
                    } else {
                        this._classes.delete(name)
                    }
                },
            },
            getBoundingClientRect: () => ({
                top: 128,
                bottom: 328,
                left: 48,
                right: 448,
                width: 400,
                height: 200,
            }),
            contains: () => false,
        }

        const component = {
            ...mixin,
            searchOpen: true,
            searchDropdownReady: false,
            $refs: {
                searchWrap: trigger,
                searchDropdown: menu,
            },
            $nextTick(callback) {
                callback()
            },
        }

        component.positionGeocodingDropdown()

        assert.equal(setPropertyCalls.some(([name, value]) => name === 'width' && value === '400px'), true)
        assert.equal(menu.style.left, '48px')
        assert.equal(menu.style.top, '128px')
        assert.equal(component.searchDropdownReady, true)
        assert.equal(menu.classList._classes.has('is-open'), true)
    })
})
