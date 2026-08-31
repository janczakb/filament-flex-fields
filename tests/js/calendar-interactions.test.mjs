import assert from 'node:assert/strict'
import { describe, it } from 'node:test'
import { parseDate } from '@internationalized/date'

import { createCalendarInteractionsBehavior } from '../../resources/js/core/date-time/calendar-interactions.js'

function createContext(overrides = {}) {
    const interactions = createCalendarInteractionsBehavior()

    return {
        mode: 'month',
        hasYearSegment: true,
        usesYearPickerOverlay: false,
        calendarViewMode: 'years',
        visibleMonth: parseDate('2026-06-01'),
        calendarPanel: {
            setCalendarYear(date, year) {
                return date.set({ year })
            },
            setCalendarMonth(date, month) {
                return date.set({ month })
            },
        },
        config: { closeOnSelect: true },
        setStateValue() {},
        bootstrapFromState() {},
        toConfigStoredValue(value) {
            return value
        },
        ...interactions,
        ...overrides,
    }
}

describe('month picker calendar navigation', () => {
    it('drills from years to months when the header is clicked', () => {
        const context = createContext()

        context.onCalendarHeaderClick()

        assert.equal(context.calendarViewMode, 'months')
    })

    it('drills from years to months when a year cell is selected', () => {
        const context = createContext()

        context.selectCalendarYear(2028)

        assert.equal(context.calendarViewMode, 'months')
        assert.equal(context.visibleMonth.year, 2028)
    })

    it('returns from months to years when the header is clicked again', () => {
        const context = createContext({ calendarViewMode: 'months' })

        context.onCalendarHeaderClick()

        assert.equal(context.calendarViewMode, 'years')
    })
})
