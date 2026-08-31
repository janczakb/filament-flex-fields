import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import { resolveTeleportedMenuHorizontalLeft } from '../../resources/js/core/teleported-menu-position.js'

describe('resolveTeleportedMenuHorizontalLeft', () => {
    it('aligns the trailing edge in LTR when align is end', () => {
        const left = resolveTeleportedMenuHorizontalLeft({
            triggerRect: { left: 800, right: 920 },
            menuWidth: 160,
            align: 'end',
            direction: 'ltr',
            viewportPadding: 16,
            windowWidth: 1200,
        })

        assert.equal(left, 760)
    })

    it('aligns the leading edge in LTR when align is start', () => {
        const left = resolveTeleportedMenuHorizontalLeft({
            triggerRect: { left: 800, right: 920 },
            menuWidth: 160,
            align: 'start',
            direction: 'ltr',
            viewportPadding: 16,
            windowWidth: 1200,
        })

        assert.equal(left, 800)
    })

    it('clamps menus that overflow the viewport', () => {
        const left = resolveTeleportedMenuHorizontalLeft({
            triggerRect: { left: 1100, right: 1180 },
            menuWidth: 240,
            align: 'end',
            direction: 'ltr',
            viewportPadding: 16,
            windowWidth: 1200,
        })

        assert.equal(left, 940)
    })
})
