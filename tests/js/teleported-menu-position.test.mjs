import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    resolveTeleportedMenuHorizontalLeft,
    resolveTeleportedMenuVerticalPlacement,
} from '../../resources/js/core/teleported-menu-position.js'

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

describe('resolveTeleportedMenuVerticalPlacement', () => {
    it('forces above when position is top', () => {
        const result = resolveTeleportedMenuVerticalPlacement({
            triggerRect: { top: 400, bottom: 440 },
            panelHeight: 200,
            gap: 6,
            windowHeight: 800,
            forcedPlacement: 'top',
        })

        assert.equal(result.opensAbove, true)
        assert.equal(result.top, 400 - 200 - 6)
    })

    it('forces below when position is bottom', () => {
        const result = resolveTeleportedMenuVerticalPlacement({
            triggerRect: { top: 700, bottom: 740 },
            panelHeight: 200,
            gap: 6,
            windowHeight: 800,
            forcedPlacement: 'bottom',
        })

        assert.equal(result.opensAbove, false)
        assert.equal(result.top, 740 + 6)
    })

    it('flips above when the panel would overflow the viewport', () => {
        const result = resolveTeleportedMenuVerticalPlacement({
            triggerRect: { top: 700, bottom: 740 },
            panelHeight: 200,
            gap: 6,
            viewportPadding: 16,
            windowHeight: 800,
            forcedPlacement: null,
        })

        assert.equal(result.opensAbove, true)
        assert.equal(result.top, 700 - 200 - 6)
    })
})
