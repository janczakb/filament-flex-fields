import assert from 'node:assert/strict'
import test from 'node:test'

import {
    clampHorizontalCenter,
    positionTopPopup,
} from '../../resources/js/core/video-field-popup-position.js'

test('clampHorizontalCenter keeps popup inside boundary', () => {
    const triggerRect = { left: 300, top: 200, width: 40, height: 40, right: 340, bottom: 240 }
    const popupRect = { left: 0, top: 0, width: 160, height: 28, right: 160, bottom: 28 }
    const boundaryRect = { left: 100, top: 0, width: 200, height: 400, right: 300, bottom: 400 }

    const result = clampHorizontalCenter(triggerRect, popupRect, boundaryRect, {
        sideOffset: 12,
        boundaryOffset: 8,
    })

    assert.equal(result.left, 132)
    assert.equal(result.top, 160)
})

test('clampHorizontalCenter centers tooltip on trigger when there is room', () => {
    const triggerRect = { left: 200, top: 300, width: 40, height: 40, right: 240, bottom: 340 }
    const popupRect = { left: 0, top: 0, width: 80, height: 24, right: 80, bottom: 24 }
    const boundaryRect = { left: 0, top: 0, width: 800, height: 600, right: 800, bottom: 600 }

    const result = clampHorizontalCenter(triggerRect, popupRect, boundaryRect)

    assert.equal(result.left, 180)
    assert.equal(result.top, 264)
})

test('positionTopPopup writes coordinates relative to offset parent', () => {
    const boundary = {
        getBoundingClientRect: () => ({
            left: 0,
            top: 0,
            width: 400,
            height: 300,
            right: 400,
            bottom: 300,
        }),
    }

    const offsetParent = {
        getBoundingClientRect: () => ({
            left: 20,
            top: 40,
            width: 360,
            height: 220,
            right: 380,
            bottom: 260,
        }),
    }

    const trigger = {
        getBoundingClientRect: () => ({
            left: 300,
            top: 220,
            width: 36,
            height: 36,
            right: 336,
            bottom: 256,
        }),
    }

    const popup = {
        offsetParent,
        style: {},
        getBoundingClientRect: () => ({
            left: 0,
            top: 0,
            width: 120,
            height: 28,
            right: 120,
            bottom: 28,
        }),
    }

    positionTopPopup(popup, trigger, boundary)

    assert.equal(popup.style.left, '238px')
    assert.equal(popup.style.top, '140px')
    assert.equal(popup.style.right, 'auto')
    assert.equal(popup.style.transform, 'none')
})
