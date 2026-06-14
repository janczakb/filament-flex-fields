import {
    finalizeMonthSegmentValue,
    formatMonthSegmentValue,
    getMonthSegmentMaxLength,
    getMonthSegmentPlaceholder,
    isMonthDisplayTextual,
    parseMonthSegmentValue,
    processMonthSegmentInput,
} from './month-display.js'

function defaultContext(context = {}) {
    return {
        locale: context.locale ?? null,
        monthDisplay: context.monthDisplay ?? 'numeric',
        hourCycle: context.hourCycle ?? 24,
        mode: context.mode ?? null,
        forceLeadingZeros: context.forceLeadingZeros ?? true,
    }
}

export function getSegmentMaxLength(part, mode = null, context = {}) {
    const options = defaultContext({ ...context, mode: mode ?? context.mode })

    if (part === 'month') {
        return getMonthSegmentMaxLength(options.locale, options.monthDisplay)
    }

    return matchPart(part, {
        day: () => 2,
        year: () => 4,
        hour: () => 2,
        minute: () => 2,
        second: () => 2,
        dayPeriod: () => 2,
        default: () => 2,
    })
}

export function processSegmentInputValue(part, previousValue, newRawValue, hourCycle = 24, mode = null, context = {}) {
    const options = defaultContext({ ...context, hourCycle, mode: mode ?? context.mode })

    if (part === 'month' && isMonthDisplayTextual(options.monthDisplay)) {
        return processMonthSegmentInput(previousValue, newRawValue, options.locale, options.monthDisplay)
    }

    const maxLength = getSegmentMaxLength(part, options.mode, options)

    if (part === 'dayPeriod') {
        return String(newRawValue ?? '').replace(/[^aApPmM]/g, '').toUpperCase().slice(0, maxLength)
    }

    const previousDigits = String(previousValue ?? '').replace(/\D/g, '')
    let digits = String(newRawValue ?? '').replace(/\D/g, '')

    if (previousDigits.length === maxLength && digits.length > maxLength && digits.startsWith(previousDigits)) {
        digits = digits.slice(-maxLength)
    } else if (digits.length > maxLength) {
        digits = digits.slice(0, maxLength)
    }

    return digits
}

export function finalizeSegmentValue(part, value, hourCycle, mode = null, context = {}) {
    if (value === null || value === undefined || String(value).trim() === '') {
        return ''
    }

    const options = defaultContext({ ...context, hourCycle, mode: mode ?? context.mode })

    if (part === 'month' && isMonthDisplayTextual(options.monthDisplay)) {
        return finalizeMonthSegmentValue(value, options.locale, options.monthDisplay)
    }

    return clampSegmentValue(part, value, options.hourCycle, options.mode, options)
}

export function sanitizeSegmentRawInput(part, value, mode = null, context = {}) {
    const options = defaultContext({ ...context, mode: mode ?? context.mode })

    if (part === 'month' && isMonthDisplayTextual(options.monthDisplay)) {
        return String(value).trim().slice(0, getMonthSegmentMaxLength(options.locale, options.monthDisplay))
    }

    if (part === 'dayPeriod') {
        return String(value).replace(/[^aApPmM]/g, '').toUpperCase().slice(0, getSegmentMaxLength(part, options.mode, options))
    }

    return String(value).replace(/\D/g, '').slice(0, getSegmentMaxLength(part, options.mode, options))
}

export function clampSegmentValue(part, value, hourCycle, mode = null, context = {}) {
    const options = defaultContext({ ...context, hourCycle, mode: mode ?? context.mode })

    if (part === 'dayPeriod') {
        return normalizeDayPeriod(value)
    }

    if (part === 'month' && isMonthDisplayTextual(options.monthDisplay)) {
        return finalizeMonthSegmentValue(value, options.locale, options.monthDisplay)
    }

    const digits = sanitizeSegmentRawInput(part, value, options.mode, options)

    if (digits === '') {
        return ''
    }

    const numeric = Number.parseInt(digits, 10)

    if (Number.isNaN(numeric)) {
        return ''
    }

    return matchPart(part, {
        month: () => String(Math.min(12, Math.max(1, numeric))).padStart(2, '0'),
        day: () => String(Math.min(31, Math.max(1, numeric))).padStart(2, '0'),
        year: () => String(Math.min(9999, Math.max(1, numeric))).padStart(4, '0'),
        hour: () => {
            const max = options.mode === 'duration' ? 99 : (options.hourCycle === 12 ? 12 : 23)
            const min = options.hourCycle === 12 && options.mode !== 'duration' ? 1 : 0
            const clamped = Math.min(max, Math.max(min, numeric))

            return String(clamped).padStart(2, '0')
        },
        minute: () => String(Math.min(59, Math.max(0, numeric))).padStart(2, '0'),
        second: () => String(Math.min(59, Math.max(0, numeric))).padStart(2, '0'),
        default: () => digits,
    })
}

export function normalizeDayPeriod(value) {
    const normalized = String(value).trim().toUpperCase()

    if (normalized.startsWith('P')) {
        return 'PM'
    }

    if (normalized.startsWith('A')) {
        return 'AM'
    }

    return normalized === 'PM' ? 'PM' : 'AM'
}

export function resolveAdjacentSegmentIndex(parts, currentIndex, backwards = false) {
    if (! parts.length) {
        return null
    }

    const delta = backwards ? -1 : 1
    const next = currentIndex + delta

    if (next < 0 || next >= parts.length) {
        return null
    }

    return next
}

export function getNextSegmentIndex(parts, currentIndex, backwards = false) {
    const adjacent = resolveAdjacentSegmentIndex(parts, currentIndex, backwards)

    if (adjacent !== null) {
        return adjacent
    }

    if (! parts.length) {
        return 0
    }

    return backwards ? parts.length - 1 : 0
}

export function buildSegmentsFromValue(value, parts, locale, hourCycle, forceLeadingZeros, monthDisplay = 'numeric') {
    const segments = {}
    const context = { locale, monthDisplay, hourCycle, forceLeadingZeros }

    for (const part of parts) {
        segments[part] = ''
    }

    if (! value) {
        return segments
    }

    if ('month' in value) {
        segments.month = formatSegment('month', value.month, hourCycle, forceLeadingZeros, locale, monthDisplay)
        segments.day = formatSegment('day', value.day, hourCycle, forceLeadingZeros, locale, monthDisplay)
        segments.year = formatSegment('year', value.year, hourCycle, forceLeadingZeros, locale, monthDisplay)

        if ('hour' in value) {
            segments.hour = formatSegment('hour', value.hour, hourCycle, forceLeadingZeros, locale, monthDisplay)
            segments.minute = formatSegment('minute', value.minute, hourCycle, forceLeadingZeros, locale, monthDisplay)
            segments.second = formatSegment('second', value.second ?? 0, hourCycle, forceLeadingZeros, locale, monthDisplay)

            if (parts.includes('dayPeriod')) {
                segments.dayPeriod = value.hour >= 12 ? 'PM' : 'AM'
            }
        }

        return segments
    }

    if ('hour' in value) {
        segments.hour = formatSegment('hour', value.hour, hourCycle, forceLeadingZeros, locale, monthDisplay)
        segments.minute = formatSegment('minute', value.minute, hourCycle, forceLeadingZeros, locale, monthDisplay)
        segments.second = formatSegment('second', value.second ?? 0, hourCycle, forceLeadingZeros, locale, monthDisplay)

        if (parts.includes('dayPeriod')) {
            segments.dayPeriod = value.hour >= 12 ? 'PM' : 'AM'
        }

        return segments
    }

    return segments
}

export function formatSegment(part, value, hourCycle, forceLeadingZeros, locale = null, monthDisplay = 'numeric') {
    if (value === null || value === undefined || value === '') {
        return ''
    }

    if (part === 'dayPeriod') {
        return Number(value) >= 12 ? 'PM' : 'AM'
    }

    if (part === 'month') {
        return formatMonthSegmentValue(Number(value), locale, monthDisplay, forceLeadingZeros)
    }

    if (part === 'year') {
        return String(value).padStart(4, forceLeadingZeros ? '0' : '')
    }

    if (part === 'hour' && hourCycle === 12) {
        const hour = Number(value) % 12 || 12

        return String(hour).padStart(2, forceLeadingZeros ? '0' : '')
    }

    return String(value).padStart(part === 'year' ? 4 : 2, forceLeadingZeros ? '0' : '')
}

export function segmentsToCalendarDate(segments, mode = 'date', locale = null, monthDisplay = 'numeric') {
    const month = parseMonthSegmentValue(segments.month, locale, monthDisplay)
        ?? Number.parseInt(segments.month, 10)
    const day = mode === 'month' ? 1 : Number.parseInt(segments.day, 10)
    const year = Number.parseInt(segments.year, 10)

    if (mode === 'year') {
        if (Number.isNaN(year)) {
            return null
        }

        return { year, month: 1, day: 1 }
    }

    if (mode === 'month') {
        if (Number.isNaN(month)) {
            return null
        }

        const resolvedYear = Number.isNaN(year) ? new Date().getFullYear() : year

        return { year: resolvedYear, month, day: 1 }
    }

    if ([month, day, year].some(Number.isNaN)) {
        return null
    }

    return { year, month, day }
}

export function segmentsToTime(segments, hourCycle, showSeconds) {
    let hour = Number.parseInt(segments.hour, 10)
    const minute = Number.parseInt(segments.minute, 10)
    const second = showSeconds ? Number.parseInt(segments.second, 10) : 0

    if ([hour, minute].some(Number.isNaN)) {
        return null
    }

    if (hourCycle === 12) {
        const period = normalizeDayPeriod(segments.dayPeriod)

        if (period === 'PM' && hour < 12) {
            hour += 12
        }

        if (period === 'AM' && hour === 12) {
            hour = 0
        }
    }

    if (showSeconds && Number.isNaN(second)) {
        return null
    }

    return { hour, minute, second: showSeconds ? second : 0 }
}

function matchPart(part, handlers) {
    if (handlers[part]) {
        return handlers[part]()
    }

    return handlers.default()
}
