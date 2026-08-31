import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    bootSegmentOverflowElements,
    positionSegmentOverflowScrollElement,
    scrollSegmentItemIntoView,
    updateHorizontalSegmentScrollShadow,
} from '../../resources/js/core/segment-overflow-position.js'

describe('segment-overflow-position', () => {
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

    it('skips repeat positioning when already marked by SSR script', () => {
        const scrollElement = {
            clientWidth: 200,
            scrollLeft: 10,
            classList: {
                removed: [],
                remove(className) {
                    this.removed.push(className)
                },
            },
            dataset: {
                ssrScrollPositioned: 'true',
            },
            querySelector() {
                throw new Error('querySelector should not run when SSR already positioned')
            },
        }

        assert.equal(positionSegmentOverflowScrollElement(scrollElement), false)
        assert.equal(scrollElement.scrollLeft, 10)
        assert.deepEqual(scrollElement.classList.removed, [])
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

    it('boots every unprepared overflow shell inside a morph or modal root', () => {
        const root = {
            matches() {
                return false
            },
            querySelectorAll(selector) {
                if (selector.includes('data-ssr-scroll-positioned')) {
                    return [scrollA, scrollB]
                }

                return []
            },
        }

        const scrollA = {
            dataset: {},
            clientWidth: 200,
            scrollLeft: 0,
            scrollWidth: 400,
            classList: { removed: [], remove(className) { this.removed.push(className) } },
            querySelector() {
                return null
            },
        }

        const scrollB = {
            dataset: {},
            clientWidth: 200,
            scrollLeft: 0,
            scrollWidth: 400,
            classList: { removed: [], remove(className) { this.removed.push(className) } },
            querySelector() {
                return { offsetLeft: 80, offsetWidth: 40 }
            },
            scrollTo() {
                throw new Error('scrollTo should not run for initial positioning')
            },
        }

        assert.equal(bootSegmentOverflowElements(root), 2)
        assert.equal(scrollA.dataset.ssrScrollPositioned, 'true')
        assert.equal(scrollB.dataset.ssrScrollPositioned, 'true')
        assert.equal(scrollB.scrollLeft, 0)
    })
})
