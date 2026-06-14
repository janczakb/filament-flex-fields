import {
    CalendarDate,
    CalendarDateTime,
    Time,
    parseDate,
    parseDateTime,
    parseTime,
    today,
    getLocalTimeZone,
    toCalendarDate,
} from '@internationalized/date'

import { getMonthSegmentPlaceholder, isMonthDisplayTextual } from './month-display.js'

const DATE_PARTS = ['month', 'day', 'year']
const TIME_PARTS = ['hour', 'minute', 'second', 'dayPeriod']

export function normalizeIntlLocale(locale) {
    if (typeof locale !== 'string' || locale.trim() === '') {
        return undefined
    }

    return locale.trim().replace(/_/g, '-')
}

export function resolveTimeZone(timeZone) {
    return timeZone || getLocalTimeZone()
}

export function getToday(timeZone) {
    return today(resolveTimeZone(timeZone))
}

export function parseStoredValue(value, mode, granularity, timeZone) {
    if (! value) {
        return null
    }

    const tz = resolveTimeZone(timeZone)

    try {
        if (mode === 'time' || mode === 'duration') {
            return parseTime(String(value))
        }

        if (mode === 'month') {
            const [year, month] = String(value).split('-').map(Number)

            if (! year || ! month) {
                return null
            }

            return new CalendarDate(year, month, 1)
        }

        if (mode === 'year') {
            const year = Number.parseInt(String(value), 10)

            if (Number.isNaN(year)) {
                return null
            }

            return new CalendarDate(year, 1, 1)
        }

        if (mode === 'date' || granularity === 'day') {
            const datePart = String(value).split('T')[0].split(' ')[0]

            return parseDate(datePart)
        }

        if (String(value).includes('T') || String(value).includes(' ')) {
            const normalized = String(value).replace(' ', 'T')

            return parseDateTime(normalized)
        }

        return parseDate(String(value))
    } catch {
        return null
    }
}

export function parseRangeStoredValue(value) {
    if (! value) {
        return { start: null, end: null }
    }

    if (typeof value === 'string') {
        const [start, end] = value.split(' - ')

        return {
            start: start?.trim() || null,
            end: end?.trim() || null,
        }
    }

    if (typeof value === 'object') {
        return {
            start: value.start ?? null,
            end: value.end ?? null,
        }
    }

    return { start: null, end: null }
}

export function toStoredValue(dateValue, mode, granularity, showSeconds, storageFormat) {
    if (! dateValue) {
        return null
    }

    if ((mode === 'time' || mode === 'duration') && dateValue instanceof Time) {
        return formatTimeValue(dateValue, showSeconds, storageFormat)
    }

    if (mode === 'month' && dateValue instanceof CalendarDate) {
        return `${String(dateValue.year).padStart(4, '0')}-${String(dateValue.month).padStart(2, '0')}`
    }

    if (mode === 'year' && dateValue instanceof CalendarDate) {
        return String(dateValue.year).padStart(4, '0')
    }

    if (dateValue instanceof CalendarDateTime) {
        return formatDateTimeValue(dateValue, granularity, showSeconds, storageFormat)
    }

    if (dateValue instanceof CalendarDate) {
        return formatDateValue(dateValue, storageFormat)
    }

    return null
}

export function formatDateValue(date, storageFormat = 'Y-m-d') {
    const year = String(date.year).padStart(4, '0')
    const month = String(date.month).padStart(2, '0')
    const day = String(date.day).padStart(2, '0')

    return storageFormat
        .replace('Y', year)
        .replace('m', month)
        .replace('d', day)
}

export function formatTimeValue(time, showSeconds, storageFormat = 'H:i') {
    const hour = String(time.hour).padStart(2, '0')
    const minute = String(time.minute).padStart(2, '0')
    const second = String(time.second).padStart(2, '0')

    let formatted = storageFormat
        .replace('H', hour)
        .replace('i', minute)
        .replace('s', second)

    if (! showSeconds) {
        formatted = formatted.replace(/:00$/, '')
    }

    return formatted
}

export function formatDateTimeValue(dateTime, granularity, showSeconds, storageFormat) {
    const date = formatDateValue(toCalendarDate(dateTime), 'Y-m-d')
    const time = formatTimeValue(
        new Time(dateTime.hour, dateTime.minute, showSeconds ? dateTime.second : 0),
        showSeconds,
        granularity === 'second' || showSeconds ? 'H:i:s' : 'H:i',
    )

    if (granularity === 'day') {
        return date
    }

    if (storageFormat.includes('T')) {
        return `${date}T${time}`
    }

    return `${date} ${time}`
}

export function compareCalendarDates(left, right) {
    if (! left || ! right) {
        return 0
    }

    if (left.year !== right.year) {
        return left.year - right.year
    }

    if (left.month !== right.month) {
        return left.month - right.month
    }

    return left.day - right.day
}

export function compareDateTimeValues(left, right) {
    if (! left || ! right) {
        return 0
    }

    if (left instanceof CalendarDate && right instanceof CalendarDate) {
        return compareCalendarDates(left, right)
    }

    if (left instanceof CalendarDateTime && right instanceof CalendarDateTime) {
        const dateCompare = compareCalendarDates(toCalendarDate(left), toCalendarDate(right))

        if (dateCompare !== 0) {
            return dateCompare
        }

        if (left.hour !== right.hour) {
            return left.hour - right.hour
        }

        if (left.minute !== right.minute) {
            return left.minute - right.minute
        }

        return left.second - right.second
    }

    if (left instanceof Time && right instanceof Time) {
        if (left.hour !== right.hour) {
            return left.hour - right.hour
        }

        if (left.minute !== right.minute) {
            return left.minute - right.minute
        }

        return left.second - right.second
    }

    return 0
}

export function isDateInRange(date, start, end) {
    if (! date || ! start || ! end) {
        return false
    }

    return compareCalendarDates(date, start) >= 0 && compareCalendarDates(date, end) <= 0
}

export function getDateSegmentParts(locale) {
    try {
        const formatter = new Intl.DateTimeFormat(normalizeIntlLocale(locale))
        const parts = formatter.formatToParts(new Date(2024, 5, 15))
        const order = []

        for (const part of parts) {
            if (part.type === 'day') {
                order.push('day')
            } else if (part.type === 'month') {
                order.push('month')
            } else if (part.type === 'year') {
                order.push('year')
            }
        }

        if (order.length === 3) {
            return order
        }
    } catch {
        // Fallback below.
    }

    return [...DATE_PARTS]
}

export function getDateSegmentSeparator(locale, part, parts) {
    const index = parts.indexOf(part)

    if (index === -1 || index >= parts.length - 1) {
        return ''
    }

    try {
        const formatter = new Intl.DateTimeFormat(normalizeIntlLocale(locale))
        const formattedParts = formatter.formatToParts(new Date(2024, 5, 15))
        let seen = 0

        for (const formattedPart of formattedParts) {
            if (['day', 'month', 'year'].includes(formattedPart.type)) {
                seen++

                if (seen - 1 === index) {
                    continue
                }

                if (seen - 1 === index + 1) {
                    break
                }
            }

            if (formattedPart.type === 'literal' && seen - 1 === index) {
                const literal = String(formattedPart.value).trim()

                if (literal !== '') {
                    return literal[0]
                }
            }
        }
    } catch {
        // Fallback below.
    }

    return part === 'month' || part === 'day' ? '/' : ''
}

export function getSegmentParts(mode, granularity, hourCycle, showSeconds, locale = null, showYearSegment = true) {
    if (mode === 'time' || mode === 'duration' || mode === 'timeRange') {
        const parts = ['hour', 'minute']

        if (showSeconds || granularity === 'second') {
            parts.push('second')
        }

        if (hourCycle === 12 && mode === 'time') {
            parts.push('dayPeriod')
        }

        return parts
    }

    if (mode === 'month') {
        const parts = ['month']

        if (showYearSegment) {
            parts.push('year')
        }

        return parts
    }

    if (mode === 'year') {
        return ['year']
    }

    if (mode === 'date' || mode === 'dateRange' || granularity === 'day') {
        return getDateSegmentParts(locale)
    }

    const parts = [...getDateSegmentParts(locale), 'hour', 'minute']

    if (showSeconds || granularity === 'second') {
        parts.push('second')
    }

    if (hourCycle === 12) {
        parts.push('dayPeriod')
    }

    return parts
}

export function getSegmentPlaceholder(part, locale, monthDisplay = 'numeric') {
    if (part === 'month' && isMonthDisplayTextual(monthDisplay)) {
        return getMonthSegmentPlaceholder(locale, monthDisplay)
    }

    const placeholders = {
        month: 'mm',
        day: 'dd',
        year: 'yyyy',
        hour: 'hh',
        minute: 'mm',
        second: 'ss',
        dayPeriod: locale?.startsWith('pl') ? 'dd' : 'aa',
    }

    return placeholders[part] ?? part
}

export function getSegmentSeparatorAfter(part, parts, locale = null) {
    const index = parts.indexOf(part)

    if (index === -1 || index >= parts.length - 1) {
        return ''
    }

    if (['month', 'day', 'year'].includes(part)) {
        const nextPart = parts[index + 1]

        if (nextPart && ! ['month', 'day', 'year'].includes(nextPart)) {
            return ''
        }

        return getDateSegmentSeparator(locale, part, parts)
    }

    switch (part) {
        case 'hour':
            return ':'
        case 'minute':
            if (parts[index + 1] === 'second') {
                return ':'
            }

            if (parts[index + 1] === 'dayPeriod') {
                return ' '
            }

            return ''
        default:
            return ''
    }
}

export {
    CalendarDate,
    CalendarDateTime,
    Time,
    DATE_PARTS,
    TIME_PARTS,
    toCalendarDate,
    getLocalTimeZone,
}
