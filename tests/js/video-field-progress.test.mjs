import assert from 'node:assert/strict'
import test from 'node:test'

import {
    formatPlaybackRateLabel,
    formatVideoTime,
    resolveCoarsePointer,
    resolveDefaultQualityKey,
    resolveProgressHoverRatio,
    resolveScrubDuration,
} from '../../resources/js/components/video-field.js'
import {
    VideoPreviewFrameCache,
    createVideoProgressSlider,
    getPercentFromPointerEvent,
    getSliderPreviewLeft,
    resolvePreviewCacheKey,
} from '../../resources/js/core/video-field-progress-slider.js'

test('resolveProgressHoverRatio clamps within track bounds', () => {
    assert.equal(resolveProgressHoverRatio(50, 0, 100), 0.5)
    assert.equal(resolveProgressHoverRatio(-10, 0, 100), 0)
    assert.equal(resolveProgressHoverRatio(150, 0, 100), 1)
    assert.equal(resolveProgressHoverRatio(50, 0, 0), null)
})

test('resolveScrubDuration prefers finite media duration', () => {
    assert.equal(resolveScrubDuration(10, 8), 10)
    assert.equal(resolveScrubDuration(Number.NaN, 8), 8)
    assert.equal(resolveScrubDuration(0, 0), 0)
})

test('getPercentFromPointerEvent clamps pointer position on track', () => {
    const rect = {
        left: 0,
        top: 0,
        right: 200,
        bottom: 20,
        width: 200,
        height: 20,
    }

    assert.equal(getPercentFromPointerEvent({ clientX: 100, clientY: 10 }, rect), 50)
    assert.equal(getPercentFromPointerEvent({ clientX: -20, clientY: 10 }, rect), 0)
    assert.equal(getPercentFromPointerEvent({ clientX: 240, clientY: 10 }, rect), 100)
})

test('getSliderPreviewLeft clamps preview within track width', () => {
    assert.equal(
        getSliderPreviewLeft(50, 176),
        'min(max(0px, calc(50.000% - 88px)), calc(100% - 176px))',
    )
})

test('resolvePreviewCacheKey buckets preview seeks', () => {
    assert.equal(resolvePreviewCacheKey(14.2), 14)
    assert.equal(resolvePreviewCacheKey(14.6), 14.5)
})

test('createVideoProgressSlider updates pointer state on move', () => {
    const element = {
        getBoundingClientRect: () => ({
            left: 0,
            top: 0,
            right: 100,
            bottom: 20,
            width: 100,
            height: 20,
        }),
        setPointerCapture: () => {},
        releasePointerCapture: () => {},
    }

    const states = []
    const slider = createVideoProgressSlider({
        getElement: () => element,
        onStateChange: (state) => states.push({ ...state }),
    })

    slider.handlePointerMove({ clientX: 25, clientY: 10, buttons: 0, pointerType: 'mouse' })

    assert.equal(states.at(-1)?.pointing, true)
    assert.equal(states.at(-1)?.pointerPercent, 25)
})

test('VideoPreviewFrameCache starts empty', () => {
    const cache = new VideoPreviewFrameCache(2)

    assert.equal(cache.get(1), null)
    cache.clear()
    assert.equal(cache.get(1), null)
})

test('formatVideoTime renders scrub preview labels', () => {
    assert.equal(formatVideoTime(14.2), '0:14')
    assert.equal(formatVideoTime(90), '1:30')
})

test('formatPlaybackRateLabel renders normalized speed labels', () => {
    assert.equal(formatPlaybackRateLabel(1), '1x')
    assert.equal(formatPlaybackRateLabel(1.5), '1.5x')
    assert.equal(formatPlaybackRateLabel(0.5), '0.5x')
})

test('resolveDefaultQualityKey prefers explicit default option', () => {
    assert.equal(resolveDefaultQualityKey([
        { key: '720', label: '720p' },
        { key: 'auto', label: 'Auto', default: true },
    ]), 'auto')
    assert.equal(resolveDefaultQualityKey([
        { key: '720', label: '720p' },
    ]), '720')
})

test('resolveCoarsePointer reads coarse pointer media query', () => {
    assert.equal(resolveCoarsePointer({ matches: true }), true)
    assert.equal(resolveCoarsePointer({ matches: false }), false)
    assert.equal(resolveCoarsePointer(null), false)
})
