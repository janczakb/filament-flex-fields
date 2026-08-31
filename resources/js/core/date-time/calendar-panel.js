import {
    CalendarDate,
    CalendarDateTime,
    Time,
    getLocalTimeZone,
    isSameMonth,
} from '@internationalized/date'

import { normalizeIntlLocale, toCalendarDate } from './format-parse.js'

export function startOfVisibleMonth(date) {
    return date.set({ day: 1 })
}

export function buildCalendarWeeks(monthDate, firstDayOfWeek, direction = 'ltr') {
    const anchor = startOfVisibleMonth(monthDate)
    const year = anchor.year
    const month = anchor.month
    const firstOfMonth = anchor
    const daysInMonth = daysInCalendarMonth(year, month, anchor.calendar)
    const startOffset = (firstOfMonth.toDate(getLocalTimeZone()).getDay() - firstDayOfWeek + 7) % 7

    const weeks = []
    let currentWeek = []

    let cursor = firstOfMonth

    for (let index = 0; index < startOffset; index++) {
        cursor = cursor.subtract({ days: 1 })
        currentWeek.push(wrapCalendarCell(cursor, anchor))
    }

    for (let day = 1; day <= daysInMonth; day++) {
        currentWeek.push(wrapCalendarCell(firstOfMonth.set({ day }), anchor))

        if (currentWeek.length === 7) {
            weeks.push(currentWeek)
            currentWeek = []
        }
    }

    if (currentWeek.length > 0) {
        let trailingCursor = firstOfMonth.set({ day: daysInMonth })

        while (currentWeek.length < 7) {
            trailingCursor = trailingCursor.add({ days: 1 })
            currentWeek.push(wrapCalendarCell(trailingCursor, anchor))
        }

        weeks.push(currentWeek)
    }

    if (direction === 'rtl') {
        return weeks.map((week) => [...week].reverse())
    }

    return weeks
}

function wrapCalendarCell(date, anchor) {
    return {
        date,
        isOutsideMonth: ! isSameMonth(date, anchor),
    }
}

export function daysInCalendarMonth(year, month, calendar = null) {
    const anchor = calendar
        ? new CalendarDate(calendar, year, month, 1)
        : new CalendarDate(year, month, 1)

    return anchor.add({ months: 1 }).subtract({ days: 1 }).day
}

export function addMonths(date, count) {
    return startOfVisibleMonth(date).add({ months: count })
}

export const YEARS_PER_PAGE = 12
export const SCROLLABLE_YEAR_SPAN = 100
/** Matches the six-week day grid viewport (6 × 2.25rem rows + gaps). */
export const YEAR_GRID_VIEWPORT_HEIGHT_PX = 236

export function buildScrollableYearRange({
    centerYear,
    minYear = null,
    maxYear = null,
    span = SCROLLABLE_YEAR_SPAN,
}) {
    const start = minYear ?? (centerYear - span)
    const end = maxYear ?? (centerYear + span)

    if (start > end) {
        return [centerYear]
    }

    return Array.from({ length: end - start + 1 }, (_, index) => start + index)
}

export function getMonthLabel(date, locale) {
    const jsDate = date.toDate(getLocalTimeZone())

    return new Intl.DateTimeFormat(normalizeIntlLocale(locale), { month: 'long', year: 'numeric' }).format(jsDate)
}

export function getCalendarHeaderLabel(viewMode, date, locale) {
    if (! date) {
        return ''
    }

    const jsDate = date.toDate(getLocalTimeZone())

    if (viewMode === 'days') {
        return new Intl.DateTimeFormat(normalizeIntlLocale(locale), { month: 'long', year: 'numeric' }).format(jsDate)
    }

    if (viewMode === 'months') {
        return new Intl.DateTimeFormat(normalizeIntlLocale(locale), { year: 'numeric' }).format(jsDate)
    }

    return new Intl.DateTimeFormat(normalizeIntlLocale(locale), { year: 'numeric' }).format(jsDate)
}

export function getShortMonthLabels(locale) {
    const formatter = new Intl.DateTimeFormat(normalizeIntlLocale(locale), { month: 'short' })

    return Array.from({ length: 12 }, (_, index) => formatter.format(new Date(2024, index, 1)))
}

export function buildYearRange(centerYear, count = YEARS_PER_PAGE) {
    const half = Math.floor(count / 2)

    return Array.from({ length: count }, (_, index) => centerYear - half + index)
}

export function scrollYearCellIntoView(container, year) {
    if (! container || year == null) {
        return
    }

    const cell = container.querySelector(`[data-year="${year}"]`)

    if (! cell) {
        return
    }

    const containerRect = container.getBoundingClientRect()
    const cellRect = cell.getBoundingClientRect()
    const target = container.scrollTop
        + (cellRect.top - containerRect.top)
        - ((container.clientHeight - cellRect.height) / 2)
    const maxScroll = Math.max(0, container.scrollHeight - container.clientHeight)

    container.scrollTop = Math.max(0, Math.min(target, maxScroll))
}

export function handleYearGridWheel(container, deltaY) {
    if (! container || container.scrollHeight <= container.clientHeight + 1) {
        return false
    }

    const maxScroll = Math.max(0, container.scrollHeight - container.clientHeight)
    const next = Math.max(0, Math.min(container.scrollTop + deltaY, maxScroll))

    container.scrollTop = next

    return true
}

export function setCalendarMonth(date, month) {
    const maxDay = daysInCalendarMonth(date.year, month, date.calendar)

    return date.set({ month, day: Math.min(date.day, maxDay) })
}

export function setCalendarYear(date, year) {
    const maxDay = daysInCalendarMonth(year, date.month, date.calendar)

    return date.set({ year, day: Math.min(date.day, maxDay) })
}

export function shiftCalendarYear(date, count) {
    return startOfVisibleMonth(date).add({ years: count })
}

export function getWeekdayLabels(firstDayOfWeek, locale, direction = 'ltr') {
    const formatter = new Intl.DateTimeFormat(normalizeIntlLocale(locale), { weekday: 'short' })
    const labels = []

    for (let i = 0; i < 7; i++) {
        const day = (firstDayOfWeek + i) % 7
        const date = new Date(2024, 0, 7 + day)

        labels.push(formatter.format(date))
    }

    if (direction === 'rtl') {
        labels.reverse()
    }

    return labels
}

export function getRangeCellState(date, rangeStart, rangeEnd, hoveredDate) {
    if (! date) {
        return {}
    }

    const effectiveEnd = hoveredDate && rangeStart && ! rangeEnd ? hoveredDate : rangeEnd

    if (! rangeStart) {
        return {}
    }

    const isStart = sameCalendarDate(date, rangeStart)
    const isEnd = effectiveEnd ? sameCalendarDate(date, effectiveEnd) : false
    const inRange = effectiveEnd
        ? isDateBetween(date, rangeStart, effectiveEnd)
        : isStart

    const previousDate = date.subtract({ days: 1 })
    const nextDate = date.add({ days: 1 })
    const prevInSelection = effectiveEnd
        ? isDateBetween(previousDate, rangeStart, effectiveEnd)
        : false
    const nextInSelection = effectiveEnd
        ? isDateBetween(nextDate, rangeStart, effectiveEnd)
        : false

    return {
        'is-range-start': isStart,
        'is-range-end': isEnd,
        'is-in-range': inRange && ! isStart && ! isEnd,
        'is-range-single': isStart && isEnd,
        'is-range-row-start': inRange && ! prevInSelection,
        'is-range-row-end': inRange && ! nextInSelection,
    }
}

export function sameCalendarDate(left, right) {
    if (! left || ! right) {
        return false
    }

    return left.year === right.year && left.month === right.month && left.day === right.day
}

export function isDateBetween(date, start, end) {
    const compareStart = compareCalendarDates(date, start)
    const compareEnd = compareCalendarDates(date, end)

    return compareStart >= 0 && compareEnd <= 0
}

export function compareCalendarDates(left, right) {
    if (left.year !== right.year) {
        return left.year - right.year
    }

    if (left.month !== right.month) {
        return left.month - right.month
    }

    return left.day - right.day
}
