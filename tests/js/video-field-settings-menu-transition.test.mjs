import { describe, expect, it } from 'vitest'

import {
    getSettingsSubmenuTransitionAttrs,
    isSettingsRootInactive,
    measureSettingsPanel,
    SETTINGS_MENU_MAX_WIDTH_PX,
    SETTINGS_MENU_MIN_WIDTH_PX,
    SETTINGS_MENU_POPOVER_TRANSITION_MS,
    SETTINGS_MENU_TRANSITION_MS,
    waitForSettingsMenuPopoverAnimation,
} from '../../resources/js/core/video-field-settings-menu-transition.js'

describe('video-field-settings-menu-transition', () => {
    it('marks the root view inactive only while a submenu is fully active', () => {
        expect(isSettingsRootInactive(null, 'hidden')).toBe(false)
        expect(isSettingsRootInactive('quality', 'hidden')).toBe(false)
        expect(isSettingsRootInactive('quality', 'entering')).toBe(false)
        expect(isSettingsRootInactive('quality', 'active')).toBe(true)
        expect(isSettingsRootInactive('quality', 'exiting')).toBe(false)
    })

    it('builds submenu transition attrs for each phase without hidden', () => {
        expect(getSettingsSubmenuTransitionAttrs('hidden', 'forward')).toEqual({
            'data-open': null,
            hidden: '',
            'data-direction': 'forward',
            'data-starting-style': null,
            'data-ending-style': null,
        })

        expect(getSettingsSubmenuTransitionAttrs('entering', 'forward')).toEqual({
            'data-open': '',
            hidden: null,
            'data-direction': 'forward',
            'data-starting-style': '',
            'data-ending-style': null,
        })

        expect(getSettingsSubmenuTransitionAttrs('active', 'forward')).toEqual({
            'data-open': '',
            hidden: null,
            'data-direction': 'forward',
            'data-starting-style': null,
            'data-ending-style': null,
        })

        expect(getSettingsSubmenuTransitionAttrs('exiting', 'back')).toEqual({
            'data-open': '',
            hidden: null,
            'data-direction': 'back',
            'data-starting-style': null,
            'data-ending-style': '',
        })
    })

    it('measures panels even when a hidden attribute is present', () => {
        const panel = document.createElement('div')
        panel.setAttribute('hidden', '')
        panel.innerHTML = '<button type="button">Quality</button><button type="button">Speed</button>'
        document.body.appendChild(panel)

        const size = measureSettingsPanel(panel, SETTINGS_MENU_MIN_WIDTH_PX)

        expect(size.width).toBeGreaterThanOrEqual(SETTINGS_MENU_MIN_WIDTH_PX)
        expect(size.height).toBeGreaterThan(0)
        expect(panel.hasAttribute('hidden')).toBe(true)

        panel.remove()
    })

    it('does not inflate width from a stretched parent viewport', () => {
        const menu = document.createElement('div')
        menu.style.width = '1200px'
        const viewport = document.createElement('div')
        viewport.style.width = '100%'
        menu.appendChild(viewport)

        const panel = document.createElement('div')
        panel.className = 'fff-video-field__settings-panel'
        panel.innerHTML = '<div class="fff-video-field__settings-group"><button type="button">Quality</button><button type="button">Speed</button></div>'
        viewport.appendChild(panel)
        document.body.appendChild(menu)

        const size = measureSettingsPanel(panel, SETTINGS_MENU_MIN_WIDTH_PX)

        expect(size.width).toBeLessThanOrEqual(SETTINGS_MENU_MAX_WIDTH_PX)
        expect(size.width).toBeGreaterThanOrEqual(SETTINGS_MENU_MIN_WIDTH_PX)

        menu.remove()
    })

    it('does not inflate height from a stretched parent viewport', () => {
        const menu = document.createElement('div')
        menu.className = 'fff-video-field__settings-menu'
        menu.style.setProperty('--fff-video-field-menu-height', '240px')

        const viewport = document.createElement('div')
        viewport.className = 'fff-video-field__settings-viewport'
        viewport.style.height = '100%'
        menu.appendChild(viewport)

        const panel = document.createElement('div')
        panel.className = 'fff-video-field__settings-panel'
        panel.innerHTML = '<div class="fff-video-field__settings-group"><button type="button">Quality</button><button type="button">Speed</button></div>'
        viewport.appendChild(panel)
        document.body.appendChild(menu)

        const size = measureSettingsPanel(panel, SETTINGS_MENU_MIN_WIDTH_PX)

        expect(size.height).toBeLessThan(120)
        expect(size.height).toBeGreaterThan(0)

        menu.remove()
    })

    it('uses a 250ms transition duration', () => {
        expect(SETTINGS_MENU_TRANSITION_MS).toBe(250)
    })

    it('falls back to popover duration when the menu has no animations', async () => {
        const startedAt = Date.now()

        await waitForSettingsMenuPopoverAnimation(null, SETTINGS_MENU_POPOVER_TRANSITION_MS)

        expect(Date.now() - startedAt).toBeGreaterThanOrEqual(SETTINGS_MENU_POPOVER_TRANSITION_MS - 25)
    })
})
