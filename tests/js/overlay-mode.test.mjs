import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import { OVERLAY_SHEET_BREAKPOINT, prefersOverlaySheet, resolveOverlayMode } from '../../resources/js/core/overlay-mode.js'

describe('overlay-mode', () => {
    it('prefers sheet on coarse pointer', () => {
        const win = {
            innerWidth: 1280,
            matchMedia: () => ({ matches: true }),
        }

        assert.equal(prefersOverlaySheet(win), true)
        assert.equal(resolveOverlayMode(win), 'sheet')
    })

    it('prefers sheet on narrow viewport', () => {
        const win = {
            innerWidth: OVERLAY_SHEET_BREAKPOINT,
            matchMedia: () => ({ matches: false }),
        }

        assert.equal(prefersOverlaySheet(win), true)
        assert.equal(resolveOverlayMode(win), 'sheet')
    })

    it('uses panel on desktop fine pointer', () => {
        const win = {
            innerWidth: 1280,
            matchMedia: () => ({ matches: false }),
        }

        assert.equal(prefersOverlaySheet(win), false)
        assert.equal(resolveOverlayMode(win), 'panel')
    })
})
