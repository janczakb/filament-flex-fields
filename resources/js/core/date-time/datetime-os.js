/**
 * DateTime OS — shared client runtime for calendar, time, and schedule fields.
 * Mirrors PHP DateTimeOs + ScheduleV2 contracts for PHP↔JS parity.
 */
import {
    copyDay,
    expandIntervals,
    segmentsOverlap,
    slotIsOvernight,
    slotsOverlap,
    validateNoOverlap,
} from './interval-engine.js'
import {
    isValidTime,
    normalizeTime,
    timeToMinutes,
} from '../time-utils.js'

const LOCALE_FIRST_DAY_FALLBACKS = {
    de: 1,
    pl: 1,
    fr: 1,
    es: 1,
    it: 1,
    nl: 1,
    sv: 1,
    nb: 1,
    da: 1,
    fi: 1,
    pt: 1,
    ru: 1,
    uk: 1,
    cs: 1,
    sk: 1,
    hu: 1,
    ro: 1,
    tr: 1,
    ar: 6,
    he: 0,
    ja: 0,
    ko: 0,
    zh: 0,
}

export function firstDayOfWeekForLocale(locale = 'en') {
    const normalized = String(locale || 'en').replace('_', '-')

    try {
        const parts = new Intl.Locale(normalized)
        const weekInfo = parts.weekInfo ?? parts.getWeekInfo?.()

        if (weekInfo && Number.isInteger(weekInfo.firstDay)) {
            const isoDay = weekInfo.firstDay

            return isoDay === 7 ? 0 : Math.max(0, Math.min(6, isoDay))
        }
    } catch {
        // Intl.Locale unsupported — fall through.
    }

    const language = normalized.split('-')[0]?.toLowerCase() ?? 'en'

    return LOCALE_FIRST_DAY_FALLBACKS[language] ?? 0
}

export function isDateUnavailable(dateKey, unavailableDates = []) {
    if (! dateKey || ! Array.isArray(unavailableDates)) {
        return false
    }

    return unavailableDates.includes(dateKey)
}

export function isDateWithinBounds(dateKey, minValue = null, maxValue = null) {
    if (! dateKey) {
        return false
    }

    if (minValue && dateKey < minValue) {
        return false
    }

    if (maxValue && dateKey > maxValue) {
        return false
    }

    return true
}

export {
    copyDay,
    expandIntervals,
    isValidTime,
    normalizeTime,
    segmentsOverlap,
    slotIsOvernight,
    slotsOverlap,
    timeToMinutes,
    validateNoOverlap,
}
