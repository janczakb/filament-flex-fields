/**
 * DateTime OS — shared interval engine (ScheduleV2 parity on the client).
 *
 * Expands same-day and overnight slots into minute segments for overlap checks.
 */
import { normalizeTime, timeToMinutes } from '../time-utils.js'

const MINUTES_PER_DAY = 1440

export const SCHEDULE_DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']

/**
 * @param {Array<{from?: string, to?: string, overnight?: boolean}>} intervals
 * @returns {Array<{start: number, end: number}>}
 */
export function expandIntervals(intervals) {
    const segments = []

    for (const interval of intervals) {
        const from = timeToMinutes(normalizeTime(interval?.from ?? ''))
        const to = timeToMinutes(normalizeTime(interval?.to ?? ''))

        if (from === null || to === null) {
            continue
        }

        const overnight = Boolean(interval?.overnight) || from >= to

        if (overnight) {
            if (from < MINUTES_PER_DAY) {
                segments.push({ start: from, end: MINUTES_PER_DAY })
            }

            if (to > 0) {
                segments.push({ start: 0, end: to })
            }

            continue
        }

        if (from < to) {
            segments.push({ start: from, end: to })
        }
    }

    return segments
}

/**
 * @param {Array<{start: number, end: number}>} left
 * @param {Array<{start: number, end: number}>} right
 */
export function segmentsOverlap(left, right) {
    for (const a of left) {
        for (const b of right) {
            if (a.start < b.end && b.start < a.end) {
                return true
            }
        }
    }

    return false
}

/**
 * @param {Array<{from?: string, to?: string, overnight?: boolean}>} intervals
 */
export function validateNoOverlap(intervals) {
    const segments = expandIntervals(intervals)

    if (segments.length < 2) {
        return true
    }

    segments.sort((left, right) => left.start - right.start)

    for (let index = 1; index < segments.length; index += 1) {
        if (segments[index].start < segments[index - 1].end) {
            return false
        }
    }

    return true
}

/**
 * @param {Array<{from?: string, to?: string, overnight?: boolean}>} intervals
 */
export function slotsOverlap(intervals) {
    return ! validateNoOverlap(intervals)
}

/**
 * @param {{timezone?: string, days?: Record<string, {enabled?: boolean, slots?: Array<object>}>}} schedule
 */
export function copyDay(schedule, fromDay, toDay) {
    const sourceDay = String(fromDay ?? '').trim().toLowerCase()
    const targetDay = String(toDay ?? '').trim().toLowerCase()

    if (! SCHEDULE_DAYS.includes(sourceDay) || ! SCHEDULE_DAYS.includes(targetDay)) {
        return schedule
    }

    const days = schedule?.days && typeof schedule.days === 'object' ? schedule.days : {}
    const source = days[sourceDay]

    if (! source || typeof source !== 'object') {
        return schedule
    }

    const sourceSlots = Array.isArray(source.slots) ? source.slots : []

    return {
        ...schedule,
        days: {
            ...days,
            [targetDay]: {
                enabled: Boolean(source.enabled),
                slots: sourceSlots.map((slot) => ({ ...slot })),
            },
        },
    }
}

/**
 * Explicit overnight flag only — same rule as ScheduleValidator PHP.
 * (Inverted ranges without `overnight: true` are `from_before_to` errors.)
 */
export function slotIsOvernight(slot) {
    return Boolean(slot?.overnight)
}
