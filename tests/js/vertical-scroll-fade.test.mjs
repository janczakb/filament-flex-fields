import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    SCROLL_FADE_ATTR,
    resolveVerticalScrollFade,
    updateVerticalScrollFade,
} from '../../resources/js/core/vertical-scroll-fade.js'

function makeScroller({ scrollTop = 0, scrollHeight, clientHeight, dataset = {} }) {
    const attributes = {}

    return {
        scrollTop,
        scrollHeight,
        clientHeight,
        dataset,
        setAttribute(name, value) {
            attributes[name] = value
        },
        removeAttribute(name) {
            delete attributes[name]
            delete dataset.scrollFade
        },
        attributes,
    }
}

describe('vertical-scroll-fade', () => {
    it('resolves none when content fits without overflow', () => {
        assert.equal(resolveVerticalScrollFade({
            scrollTop: 0,
            scrollHeight: 200,
            clientHeight: 200,
        }), 'none')
        assert.equal(resolveVerticalScrollFade(null), 'none')
    })

    it('resolves bottom at the start of an overflowing scroller', () => {
        assert.equal(resolveVerticalScrollFade({
            scrollTop: 0,
            scrollHeight: 400,
            clientHeight: 200,
        }), 'bottom')
    })

    it('resolves top at the end of an overflowing scroller', () => {
        assert.equal(resolveVerticalScrollFade({
            scrollTop: 200,
            scrollHeight: 400,
            clientHeight: 200,
        }), 'top')
    })

    it('resolves both when scrolled through the middle', () => {
        assert.equal(resolveVerticalScrollFade({
            scrollTop: 80,
            scrollHeight: 400,
            clientHeight: 200,
        }), 'both')
    })

    it('writes data-scroll-fade and clears it when overflow disappears', () => {
        const element = makeScroller({
            scrollTop: 80,
            scrollHeight: 400,
            clientHeight: 200,
        })

        assert.equal(updateVerticalScrollFade(element), 'both')
        assert.equal(element.dataset.scrollFade, 'both')
        assert.equal(element.attributes[SCROLL_FADE_ATTR], 'both')

        element.scrollHeight = 200
        element.scrollTop = 0

        assert.equal(updateVerticalScrollFade(element), 'none')
        assert.equal(element.dataset.scrollFade, undefined)
        assert.equal(element.attributes[SCROLL_FADE_ATTR], undefined)
    })
})
