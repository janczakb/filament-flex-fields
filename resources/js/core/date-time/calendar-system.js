import { createCalendar, GregorianCalendar, parseDate, toCalendar } from '@internationalized/date'

const CALENDAR_CACHE = new Map()

export function normalizeCalendarIdentifier(identifier) {
    if (! identifier) {
        return null
    }

    const normalized = String(identifier).trim().toLowerCase()

    if (normalized === 'gregorian' || normalized === 'gregory') {
        return 'gregory'
    }

    if (normalized === 'islamic') {
        return 'islamic-civil'
    }

    return normalized
}

export function resolveCalendarIdentifier(locale, explicit) {
    const fromExplicit = normalizeCalendarIdentifier(explicit)

    if (fromExplicit) {
        return fromExplicit
    }

    if (typeof locale !== 'string' || locale.trim() === '') {
        return null
    }

    const match = locale.trim().replace(/_/g, '-').match(/-u-ca-([\w-]+)/i)

    return match ? normalizeCalendarIdentifier(match[1]) : null
}

export function getCalendar(calendarIdentifier) {
    const identifier = normalizeCalendarIdentifier(calendarIdentifier)

    if (! identifier || identifier === 'gregory') {
        return new GregorianCalendar()
    }

    if (! CALENDAR_CACHE.has(identifier)) {
        CALENDAR_CACHE.set(identifier, createCalendar(identifier))
    }

    return CALENDAR_CACHE.get(identifier)
}

export function toFieldCalendarDate(value, calendarIdentifier) {
    if (! value) {
        return null
    }

    const calendar = getCalendar(calendarIdentifier)

    if (! calendar || calendar.identifier === 'gregory') {
        return value
    }

    return toCalendar(value, calendar)
}

export function parseGregorianDateString(value) {
    if (! value) {
        return null
    }

    const datePart = String(value).split('T')[0].split(' ')[0]

    try {
        return parseDate(datePart)
    } catch {
        return null
    }
}

export function toGregorianDateString(dateValue) {
    if (! dateValue) {
        return null
    }

    const gregorian = toCalendar(dateValue, new GregorianCalendar())

    return `${String(gregorian.year).padStart(4, '0')}-${String(gregorian.month).padStart(2, '0')}-${String(gregorian.day).padStart(2, '0')}`
}

export function isSameCalendarMonth(left, right) {
    if (! left || ! right) {
        return false
    }

    return left.year === right.year && left.month === right.month
}
