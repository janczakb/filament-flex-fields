import assert from 'node:assert/strict'
import { describe, it, before, after } from 'node:test'

import { createSearchableSelectMenuMixin } from '../../resources/js/core/searchable-select-menu.js'

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
        }
        globalThis.matchMedia = globalThis.window.matchMedia
    })

    after(() => {
        globalThis.document = previousDocument
        globalThis.window = previousWindow
        globalThis.matchMedia = previousMatchMedia
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
})
