import { resolveFlexSliderHandleVisualOffset } from '../../resources/js/support/flex-slider-handle-offset.js'
import { describe, it } from 'node:test'
import assert from 'node:assert/strict'

describe('resolveFlexSliderHandleVisualOffset', () => {
    const padding = 3
    const thumbSize = 20
    const edge = padding + thumbSize / 2

    it('shifts the handle by full edge inset at min and max', () => {
        assert.equal(
            resolveFlexSliderHandleVisualOffset({ position: 0, padding, thumbSize }),
            edge,
        )
        assert.equal(
            resolveFlexSliderHandleVisualOffset({ position: 1, padding, thumbSize }),
            -edge,
        )
    })

    it('is centered with no offset at mid track', () => {
        assert.equal(
            resolveFlexSliderHandleVisualOffset({ position: 0.5, padding, thumbSize }),
            0,
        )
    })

    it('interpolates linearly between edges', () => {
        assert.equal(
            resolveFlexSliderHandleVisualOffset({ position: 0.25, padding, thumbSize }),
            edge * 0.5,
        )
        assert.equal(
            resolveFlexSliderHandleVisualOffset({ position: 0.75, padding, thumbSize }),
            -edge * 0.5,
        )
    })

    it('mirrors for RTL direction', () => {
        assert.equal(
            resolveFlexSliderHandleVisualOffset({
                position: 0,
                padding,
                thumbSize,
                direction: -1,
            }),
            -edge,
        )
    })
})
