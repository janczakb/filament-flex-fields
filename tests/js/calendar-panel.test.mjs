import assert from 'node:assert/strict'
import { describe, it } from 'node:test'
import { parseDate } from '@internationalized/date'

import { addMonths, buildCalendarWeeks, buildScrollableYearRange, getWeekdayLabels, handleYearGridWheel, scrollYearCellIntoView, YEAR_GRID_VIEWPORT_HEIGHT_PX } from '../../resources/js/core/date-time/calendar-panel.js'
import { toFieldCalendarDate } from '../../resources/js/core/date-time/calendar-system.js'

function countOutside(weeks) {
    return weeks.flat().filter((cell) => cell.isOutsideMonth).length
}

function countInside(weeks) {
    return weeks.flat().filter((cell) => ! cell.isOutsideMonth).length
}

describe('buildCalendarWeeks', () => {
    it('marks only leading and trailing padding days as outside month', () => {
        const july = parseDate('2026-07-15')
        const weeks = buildCalendarWeeks(july, 0)

        assert.equal(countInside(weeks), 31)
        assert.ok(countOutside(weeks) <= 11)
    })

    it('keeps outside-month flags aligned after month navigation', () => {
        const july = parseDate('2026-07-15')
        const august = addMonths(july, 1)
        const weeks = buildCalendarWeeks(august, 0)

        assert.equal(countInside(weeks), 31)
        assert.ok(countOutside(weeks) <= 11)
    })

    it('preserves non-gregorian calendars when navigating months', () => {
        const indian = toFieldCalendarDate(parseDate('2026-07-15'), 'indian')
        const next = addMonths(indian, 1)

        assert.equal(next.calendar.identifier, 'indian')

        const weeks = buildCalendarWeeks(next, 0)

        assert.ok(countInside(weeks) > 27)
        assert.ok(countOutside(weeks) <= 11)
    })
})

describe('buildScrollableYearRange', () => {
    it('builds a centered span when no bounds are provided', () => {
        const years = buildScrollableYearRange({ centerYear: 2026, span: 2 })

        assert.deepEqual(years, [2024, 2025, 2026, 2027, 2028])
    })

    it('respects explicit min and max years', () => {
        const years = buildScrollableYearRange({
            centerYear: 2026,
            minYear: 2020,
            maxYear: 2025,
        })

        assert.deepEqual(years, [2020, 2021, 2022, 2023, 2024, 2025])
    })

    it('exposes a day-grid-matched viewport height constant', () => {
        assert.equal(YEAR_GRID_VIEWPORT_HEIGHT_PX, 236)
    })
})

describe('scrollYearCellIntoView', () => {
    it('scrolls within the container without using scrollIntoView', () => {
        const container = {
            clientHeight: 100,
            scrollHeight: 500,
            scrollTop: 0,
            getBoundingClientRect() {
                return { top: 0, height: 100 }
            },
            querySelector() {
                return {
                    getBoundingClientRect() {
                        return { top: 250, height: 32 }
                    },
                }
            },
        }

        scrollYearCellIntoView(container, 2026)

        assert.equal(container.scrollTop, 216)
    })
})

describe('handleYearGridWheel', () => {
    it('scrolls the year list and reports consumption', () => {
        const container = {
            clientHeight: 100,
            scrollHeight: 500,
            scrollTop: 0,
        }

        assert.equal(handleYearGridWheel(container, 40), true)
        assert.equal(container.scrollTop, 40)
    })

    it('does not consume wheel events when the list is not scrollable', () => {
        const container = {
            clientHeight: 100,
            scrollHeight: 100,
            scrollTop: 0,
        }

        assert.equal(handleYearGridWheel(container, 40), false)
    })
})

describe('RTL calendar layout', () => {
    it('reverses weekday labels and week rows for rtl direction', () => {
        const ltrLabels = getWeekdayLabels(0, 'en-US', 'ltr')
        const rtlLabels = getWeekdayLabels(0, 'en-US', 'rtl')
        const july = parseDate('2026-07-15')
        const ltrWeeks = buildCalendarWeeks(july, 0, 'ltr')
        const rtlWeeks = buildCalendarWeeks(july, 0, 'rtl')

        assert.deepEqual(rtlLabels, [...ltrLabels].reverse())
        assert.equal(rtlWeeks[0][0].date.day, ltrWeeks[0][6].date.day)
    })
})
