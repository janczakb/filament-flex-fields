import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import { concealOverlayScrollbar, revealOverlayScrollbar, syncOverlayScrollbar } from '../../resources/js/core/overlay-scrollbar.js'

describe('overlay-scrollbar', () => {
    it('hides the thumb when content fits', () => {
        const track = { dataset: {}, clientHeight: 200 }
        const thumb = { parentElement: track, style: {} }

        const state = syncOverlayScrollbar({
            scrollTop: 0,
            scrollHeight: 200,
            clientHeight: 200,
        }, thumb)

        assert.equal(state.visible, false)
        assert.equal(track.dataset.visible, 'false')
        assert.equal(track.dataset.active, 'false')
        assert.equal(thumb.style.height, '0px')
    })

    it('sizes the thumb from the visible ratio when overflowing', () => {
        const track = { dataset: {}, clientHeight: 200 }
        const thumb = { parentElement: track, style: {} }

        const state = syncOverlayScrollbar({
            scrollTop: 0,
            scrollHeight: 400,
            clientHeight: 200,
        }, thumb)

        assert.equal(state.visible, true)
        assert.equal(track.dataset.visible, 'true')
        assert.equal(track.dataset.active, undefined)
        assert.equal(state.thumbHeight, 100)
        assert.equal(state.thumbTop, 0)
        assert.equal(thumb.style.height, '100px')
        assert.equal(thumb.style.transform, 'translateY(0px)')
    })

    it('moves the thumb to the end at max scroll', () => {
        const track = { dataset: {}, clientHeight: 200 }
        const thumb = { parentElement: track, style: {} }

        const state = syncOverlayScrollbar({
            scrollTop: 200,
            scrollHeight: 400,
            clientHeight: 200,
        }, thumb)

        assert.equal(state.visible, true)
        assert.equal(state.thumbTop, 100)
        assert.equal(thumb.style.transform, 'translateY(100px)')
    })

    it('shows the thumb only while scrolling or held, then conceals', () => {
        const track = { dataset: { visible: 'true' } }

        revealOverlayScrollbar(track, { persist: true })
        assert.equal(track.dataset.active, 'true')

        concealOverlayScrollbar(track)
        assert.equal(track.dataset.active, 'false')
    })
})
