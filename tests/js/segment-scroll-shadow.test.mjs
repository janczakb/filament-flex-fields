import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    positionSegmentOverflowScrollElement,
    scrollSegmentHorizontally,
    scrollSegmentItemIntoView,
    updateHorizontalSegmentScrollShadow,
} from '../../resources/js/components/segment-scroll-shadow.js'

describe('segment-scroll-shadow', () => {
    it('marks both scroll edges when content overflows on both sides', () => {
        const element = {
            scrollLeft: 40,
            scrollWidth: 400,
            clientWidth: 200,
            dataset: {},
        }

        const state = updateHorizontalSegmentScrollShadow(element)

        assert.equal(state.canScrollBefore, true)
        assert.equal(state.canScrollAfter, true)
        assert.equal(element.dataset.leftRightScroll, 'true')
    })

    it('marks only the trailing edge at scroll start', () => {
        const element = {
            scrollLeft: 0,
            scrollWidth: 400,
            clientWidth: 200,
            dataset: {},
        }

        const state = updateHorizontalSegmentScrollShadow(element)

        assert.equal(state.canScrollBefore, false)
        assert.equal(state.canScrollAfter, true)
        assert.equal(element.dataset.rightScroll, 'true')
        assert.equal(element.dataset.leftScroll, undefined)
    })

    it('clears scroll markers when content fits without overflow', () => {
        const element = {
            scrollLeft: 0,
            scrollWidth: 200,
            clientWidth: 200,
            dataset: {
                leftScroll: 'false',
                rightScroll: 'true',
            },
        }

        const state = updateHorizontalSegmentScrollShadow(element)

        assert.equal(state.canScrollBefore, false)
        assert.equal(state.canScrollAfter, false)
        assert.equal(element.dataset.leftScroll, undefined)
        assert.equal(element.dataset.rightScroll, undefined)
        assert.equal(element.dataset.leftRightScroll, undefined)
    })

    it('assigns scrollLeft directly for initial overflow positioning', () => {
        const scrollElement = {
            clientWidth: 200,
            scrollLeft: 0,
            classList: {
                removed: [],
                remove(className) {
                    this.removed.push(className)
                },
            },
            dataset: {},
            scrollTo() {
                throw new Error('scrollTo should not run for initial positioning')
            },
            querySelector() {
                return { offsetLeft: 120, offsetWidth: 40 }
            },
        }

        positionSegmentOverflowScrollElement(scrollElement)

        assert.equal(scrollElement.scrollLeft, 40)
        assert.equal(scrollElement.dataset.ssrScrollPositioned, 'true')
        assert.deepEqual(scrollElement.classList.removed, ['fff-segment-scroll-shadow--preparing'])
    })

    it('assigns scrollLeft directly for initial overflow positioning helper', () => {
        const scrollElement = {
            clientWidth: 200,
            scrollLeft: 0,
            scrollTo() {
                throw new Error('scrollTo should not run for initial positioning')
            },
        }

        scrollSegmentItemIntoView(scrollElement, { offsetLeft: 120, offsetWidth: 40 }, false, true)

        assert.equal(scrollElement.scrollLeft, 40)
    })

    it('scrolls horizontally by a viewport fraction', () => {
        global.getComputedStyle = () => ({ direction: 'ltr' })

        let scrolledTo = null

        scrollSegmentHorizontally({
            clientWidth: 200,
            scrollWidth: 600,
            scrollLeft: 0,
            scrollTo(options) {
                scrolledTo = options
            },
        }, 1)

        assert.equal(scrolledTo.left, 160)
        assert.equal(scrolledTo.behavior, 'smooth')

        delete global.getComputedStyle
    })
})
