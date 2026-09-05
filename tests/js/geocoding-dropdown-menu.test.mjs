import assert from 'node:assert/strict'
import { describe, it, before, after } from 'node:test'
import { readFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

import { createGeocodingDropdownMenuMixin } from '../../resources/js/core/geocoding-dropdown-menu.js'

const root = join(dirname(fileURLToPath(import.meta.url)), '../..')

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
            addEventListener() {},
            removeEventListener() {},
        }
        globalThis.requestAnimationFrame = globalThis.window.requestAnimationFrame
        globalThis.cancelAnimationFrame = globalThis.window.cancelAnimationFrame
        globalThis.document = {
            documentElement: { classList },
            body: { classList },
            querySelector: () => null,
            addEventListener: () => {},
            removeEventListener: () => {},
            scrollingElement: null,
        }
    })

    after(() => {
        globalThis.window = previousWindow
        delete globalThis.requestAnimationFrame
        delete globalThis.cancelAnimationFrame
        delete globalThis.document
    })

    it('uses searchable-select overlay lifecycle for panel and sheet', () => {
        const source = readFileSync(join(root, 'resources/js/core/geocoding-dropdown-menu.js'), 'utf8')
        const address = readFileSync(join(root, 'resources/js/components/address-autocomplete.js'), 'utf8')
        const panel = readFileSync(
            join(root, 'resources/views/forms/components/partials/geocoding-search-dropdown-panel.blade.php'),
            'utf8',
        )

        assert.match(source, /createSearchableSelectMenuMixin/)
        assert.match(source, /bindSelectMenuLifecycle/)
        assert.match(source, /syncGeocodingDropdownWidth/)
        assert.match(source, /shouldShowGeocodingSheetSearch/)
        assert.match(source, /focusGeocodingSheetSearch/)
        assert.match(source, /pinGeocodingSheetWidth/)
        assert.match(source, /shouldDismissGeocodingOnBlur/)
        assert.match(source, /runAfterSheetEnter/)
        assert.doesNotMatch(source, /positionGeocodingDropdown/)
        assert.doesNotMatch(address, /createExclusiveDropdownMixin/)
        assert.match(address, /ownerIdPrefix:\s*'fff-address-autocomplete'/)
        assert.match(panel, /geocodingSheetSearchInput/)
        assert.match(panel, /shouldShowGeocodingSheetSearch\(\)/)
        assert.match(panel, /fi-select-input-search-ctn/)
        assert.match(panel, /searchOpen && searchHasMinQuery/)
        assert.match(panel, /fi-width-none/)
    })

    it('pins desktop width to the trigger after overlay anchoring', () => {
        const mixin = createGeocodingDropdownMenuMixin({
            openKey: 'searchOpen',
            readyKey: 'searchDropdownReady',
            triggerRef: 'searchWrap',
            menuRef: 'searchDropdown',
            ownerIdPrefix: 'fff-geocoding-test',
        })

        const setPropertyCalls = []
        const trigger = {
            getBoundingClientRect: () => ({
                top: 80,
                bottom: 120,
                left: 48,
                right: 448,
                width: 400,
                height: 40,
            }),
        }

        const menu = {
            style: {
                setProperty(name, value, priority) {
                    setPropertyCalls.push([name, value, priority])
                    this[name] = value
                },
                removeProperty() {},
            },
            classList: {
                contains: () => false,
                add() {},
                remove() {},
                toggle() {},
            },
            dataset: {},
        }

        let baseCalls = 0
        const component = {
            ...mixin,
            searchOpen: true,
            searchDropdownReady: false,
            __fffOverlayManaged: false,
            $refs: {
                searchWrap: trigger,
                searchDropdown: menu,
            },
            updateMenuPosition() {
                baseCalls += 1
            },
        }

        component.bindGeocodingDropdownMenu = mixin.bindGeocodingDropdownMenu
        // Re-bind width wrapper without full overlay lifecycle
        const baseUpdateMenuPosition = component.updateMenuPosition.bind(component)
        component.updateMenuPosition = (options = {}) => {
            component.syncGeocodingDropdownWidth()
            baseUpdateMenuPosition(options)
            component.syncGeocodingDropdownWidth()
        }

        component.updateMenuPosition({ reveal: false })

        assert.equal(baseCalls, 1)
        assert.equal(setPropertyCalls.some(([name, value]) => name === 'width' && value === '400px'), true)
        assert.equal(setPropertyCalls.some(([name, value]) => name === 'min-width' && value === '400px'), true)
    })

    it('skips desktop width pin in sheet mode', () => {
        globalThis.window.innerWidth = 390

        const mixin = createGeocodingDropdownMenuMixin({
            openKey: 'searchOpen',
            readyKey: 'searchDropdownReady',
            triggerRef: 'searchWrap',
            menuRef: 'searchDropdown',
        })

        const setPropertyCalls = []
        const component = {
            ...mixin,
            searchOpen: true,
            searchable: true,
            readOnly: false,
            __fffOverlayMode: 'sheet',
            $refs: {
                searchWrap: {
                    getBoundingClientRect: () => ({ width: 360, top: 0, bottom: 40, left: 0, right: 360, height: 40 }),
                },
                searchDropdown: {
                    style: {
                        setProperty(name, value, priority) {
                            setPropertyCalls.push([name, value, priority])
                        },
                    },
                    classList: {
                        contains: (name) => name === 'fff-teleported-menu--sheet',
                    },
                },
            },
        }

        assert.equal(component.shouldShowGeocodingSheetSearch(), true)
        assert.equal(component.shouldDismissGeocodingOnBlur(), false)
        component.syncGeocodingDropdownWidth()

        assert.equal(setPropertyCalls.some(([name, value]) => name === 'width' && value === '100%'), true)
        assert.equal(setPropertyCalls.some(([name, value]) => name === 'width' && value === '360px'), false)

        globalThis.window.innerWidth = 1280
    })
})
