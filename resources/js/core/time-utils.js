const TIME_PATTERN = /^(\d{1,2}):(\d{2})(?::(\d{2}))?$/

export function normalizeTime(value) {
    if (value == null) {
        return null
    }

    const time = String(value).trim()

    if (time === '') {
        return null
    }

    const match = time.match(TIME_PATTERN)

    if (! match) {
        return null
    }

    const hours = Number.parseInt(match[1], 10)
    const minutes = Number.parseInt(match[2], 10)

    if (hours < 0 || hours > 23 || minutes < 0 || minutes > 59) {
        return null
    }

    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`
}

export function timeToMinutes(time) {
    const normalized = normalizeTime(time)

    if (normalized === null) {
        return null
    }

    const [hours, minutes] = normalized.split(':')

    return (Number.parseInt(hours, 10) * 60) + Number.parseInt(minutes, 10)
}

export function isValidTime(value) {
    return normalizeTime(value) !== null
}

export function isTimeWithinRange(time, minValue = null, maxValue = null) {
    const minutes = timeToMinutes(time)

    if (minutes === null) {
        return false
    }

    const minMinutes = minValue ? timeToMinutes(minValue) : null
    const maxMinutes = maxValue ? timeToMinutes(maxValue) : null

    if (minMinutes !== null && minutes < minMinutes) {
        return false
    }

    if (maxMinutes !== null && minutes > maxMinutes) {
        return false
    }

    return true
}

export function formatHourFor12HourCycle(hour24) {
    const hour = Number.parseInt(hour24, 10)

    if (Number.isNaN(hour)) {
        return ''
    }

    const normalized = ((hour % 12) || 12)

    return String(normalized).padStart(2, '0')
}

export function dayPeriodFromHour24(hour24) {
    const hour = Number.parseInt(hour24, 10)

    if (Number.isNaN(hour)) {
        return ''
    }

    return hour >= 12 ? 'PM' : 'AM'
}

export function toHour24(hour12, dayPeriod) {
    const hour = Number.parseInt(hour12, 10)

    if (Number.isNaN(hour) || hour < 1 || hour > 12) {
        return null
    }

    const period = String(dayPeriod ?? '').trim().toUpperCase()

    if (period !== 'AM' && period !== 'PM') {
        return null
    }

    if (period === 'AM') {
        return hour === 12 ? 0 : hour
    }

    return hour === 12 ? 12 : hour + 12
}
