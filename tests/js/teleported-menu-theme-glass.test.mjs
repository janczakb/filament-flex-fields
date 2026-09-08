import assert from 'node:assert/strict'
import { describe, it, beforeEach } from 'node:test'
import { applyTeleportedMenuTheme } from '../../resources/js/core/searchable-select-menu.js'

describe('applyTeleportedMenuTheme desktop glass', () => {
    beforeEach(() => {
        globalThis.window = globalThis
        globalThis.document = {
            documentElement: { classList: { contains: () => false } },
            body: { classList: { contains: () => false } },
        }
        globalThis.matchMedia = () => ({ matches: false, addEventListener() {}, removeEventListener() {} })
    })

    it('applies backdrop blur on desktop panel menus', () => {
        const props = new Map()
        const menu = {
            classList: {
                contains: () => false,
                add() {},
            },
            dataset: { fffOverlayPresentation: 'panel' },
            style: {
                setProperty(name, value) {
                    props.set(name, value)
                },
            },
            closest: () => null,
        }

        applyTeleportedMenuTheme(menu)

        assert.equal(props.get('backdrop-filter'), 'blur(16px) saturate(180%)')
        assert.equal(props.get('-webkit-backdrop-filter'), 'blur(16px) saturate(180%)')
        assert.match(String(props.get('background') || ''), /rgb\(255 255 255 \/ 0\.9\)/)
    })

    it('keeps sheet menus opaque without blur', () => {
        const props = new Map()
        const menu = {
            classList: {
                contains: (name) => name === 'fff-overlay-sheet' || name === 'fff-teleported-menu--sheet',
                add() {},
            },
            dataset: { fffOverlayPresentation: 'sheet', fffOverlaySheet: 'true' },
            style: {
                setProperty(name, value) {
                    props.set(name, value)
                },
            },
            closest: () => null,
        }

        applyTeleportedMenuTheme(menu)

        assert.equal(props.get('backdrop-filter'), 'none')
        assert.equal(props.get('background'), '#ffffff')
    })
})
