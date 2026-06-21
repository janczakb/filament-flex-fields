import {
    CalendarDate,
    CalendarDateTime,
    Time,
    getLocalTimeZone,
    normalizeIntlLocale,
    toCalendarDate,
} from './format-parse.js'

export function buildCalendarWeeks(monthDate, firstDayOfWeek) {
    const year = monthDate.year
    const month = monthDate.month
    const firstOfMonth = new CalendarDate(year, month, 1)
    const daysInMonth = daysInCalendarMonth(year, month)
    const startOffset = (firstOfMonth.toDate(getLocalTimeZone()).getDay() - firstDayOfWeek + 7) % 7

    const weeks = []
    let currentWeek = []

    for (let i = 0; i < startOffset; i++) {
        currentWeek.push(null)
    }

    for (let day = 1; day <= daysInMonth; day++) {
        currentWeek.push(new CalendarDate(year, month, day))

        if (currentWeek.length === 7) {
            weeks.push(currentWeek)
            currentWeek = []
        }
    }

    if (currentWeek.length > 0) {
        while (currentWeek.length < 7) {
            currentWeek.push(null)
        }

        weeks.push(currentWeek)
    }

    return weeks
}

export function daysInCalendarMonth(year, month) {
    return new CalendarDate(year, month + 1 > 12 ? 1 : month + 1, 1)
        .subtract({ days: 1 }).day
}

export function addMonths(date, count) {
    let month = date.month + count
    let year = date.year

    while (month > 12) {
        month -= 12
        year += 1
    }

    while (month < 1) {
        month += 12
        year -= 1
    }

    const maxDay = daysInCalendarMonth(year, month)

    return new CalendarDate(year, month, Math.min(date.day, maxDay))
}

export const YEARS_PER_PAGE = 12

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

    const years = buildYearRange(date.year)

    return `${years[0]} – ${years[years.length - 1]}`
}

export function getShortMonthLabels(locale) {
    const formatter = new Intl.DateTimeFormat(normalizeIntlLocale(locale), { month: 'short' })

    return Array.from({ length: 12 }, (_, index) => formatter.format(new Date(2024, index, 1)))
}

export function buildYearRange(centerYear, count = YEARS_PER_PAGE) {
    const half = Math.floor(count / 2)

    return Array.from({ length: count }, (_, index) => centerYear - half + index)
}

export function setCalendarMonth(date, month) {
    const maxDay = daysInCalendarMonth(date.year, month)

    return new CalendarDate(date.year, month, Math.min(date.day, maxDay))
}

export function setCalendarYear(date, year) {
    const maxDay = daysInCalendarMonth(year, date.month)

    return new CalendarDate(year, date.month, Math.min(date.day, maxDay))
}

export function shiftCalendarYear(date, count) {
    return setCalendarYear(date, date.year + count)
}

export function getWeekdayLabels(firstDayOfWeek, locale) {
    const formatter = new Intl.DateTimeFormat(normalizeIntlLocale(locale), { weekday: 'short' })
    const labels = []

    for (let i = 0; i < 7; i++) {
        const day = (firstDayOfWeek + i) % 7
        const date = new Date(2024, 0, 7 + day)

        labels.push(formatter.format(date))
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
