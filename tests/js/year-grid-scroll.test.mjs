import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    applyYearGridWheelScroll,
    handleYearGridWheelEvent,
    isYearGridScrollTarget,
    normalizeYearGridWheelDelta,
} from '../../resources/js/core/date-time/year-grid-scroll.js'

describe('year grid scroll helpers', () => {
    it('scrolls within a fixed-height container', () => {
        const container = {
            clientHeight: 100,
            scrollHeight: 500,
            scrollTop: 0,
        }

        assert.equal(applyYearGridWheelScroll(container, 80), true)
        assert.equal(container.scrollTop, 80)
    })

    it('prevents wheel default only after scrolling', () => {
        const container = {
            clientHeight: 100,
            scrollHeight: 500,
            scrollTop: 0,
        }

        let prevented = false

        handleYearGridWheelEvent({
            currentTarget: container,
            deltaY: 40,
            deltaMode: 0,
            preventDefault() {
                prevented = true
            },
            stopPropagation() {},
        })

        assert.equal(container.scrollTop, 40)
        assert.equal(prevented, true)
    })

    it('does not block wheel when the list is not scrollable', () => {
        const container = {
            clientHeight: 100,
            scrollHeight: 100,
            scrollTop: 0,
        }

        let prevented = false

        handleYearGridWheelEvent({
            currentTarget: container,
            deltaY: 40,
            deltaMode: 0,
            preventDefault() {
                prevented = true
            },
            stopPropagation() {},
        })

        assert.equal(prevented, false)
    })

    it('normalizes line-based wheel deltas', () => {
        assert.equal(normalizeYearGridWheelDelta({ deltaY: 2, deltaMode: 1 }, { clientHeight: 100 }), 64)
    })

    it('detects scroll events from the year grid', () => {
        const button = {
            closest(selector) {
                return selector.includes('year-scroll') ? {} : null
            },
        }

        assert.equal(isYearGridScrollTarget(button), true)
        assert.equal(isYearGridScrollTarget(null), false)
        assert.equal(isYearGridScrollTarget({}), false)
    })
})
