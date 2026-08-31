import { describe, expect, it } from 'vitest'

import { resolveVideoHotkeyAction } from '../../resources/js/core/video-field-hotkeys.js'
import {
    createTapGestureTracker,
    beginTapGesture,
    resolveTapGesture,
    resolveGestureRegion,
} from '../../resources/js/core/video-field-gestures.js'

describe('video-field-hotkeys', () => {
    it('maps common media keys to actions', () => {
        expect(resolveVideoHotkeyAction({ key: ' ', altKey: false, ctrlKey: false, metaKey: false, defaultPrevented: false, target: document.body })).toBe('togglePaused')
        expect(resolveVideoHotkeyAction({ key: 'f', altKey: false, ctrlKey: false, metaKey: false, defaultPrevented: false, target: document.body })).toBe('toggleFullscreen')
        expect(resolveVideoHotkeyAction({ key: 'ArrowUp', altKey: false, ctrlKey: false, metaKey: false, defaultPrevented: false, target: document.body })).toBe('volumeUp')
    })

    it('ignores editable targets', () => {
        const input = document.createElement('input')

        expect(resolveVideoHotkeyAction({ key: ' ', altKey: false, ctrlKey: false, metaKey: false, defaultPrevented: false, target: input })).toBeNull()
    })
})

describe('video-field-gestures', () => {
    it('resolves left center right regions', () => {
        const rect = { left: 0, width: 300 }

        expect(resolveGestureRegion(50, rect)).toBe('left')
        expect(resolveGestureRegion(150, rect)).toBe('center')
        expect(resolveGestureRegion(250, rect)).toBe('right')
    })

    it('detects double tap in the same region', () => {
        const tracker = createTapGestureTracker()
        const rect = { left: 0, width: 300 }

        beginTapGesture(tracker, { clientX: 150 })
        const first = resolveTapGesture(tracker, { clientX: 150 }, rect)

        beginTapGesture(tracker, { clientX: 150 })
        const second = resolveTapGesture(tracker, { clientX: 150 }, rect)

        expect(first?.isDoubleTap).toBe(false)
        expect(second?.isDoubleTap).toBe(true)
        expect(second?.region).toBe('center')
    })
})
