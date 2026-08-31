import {
    copyDay,
    expandIntervals,
    segmentsOverlap,
    slotIsOvernight,
    slotsOverlap as engineSlotsOverlap,
    validateNoOverlap,
} from '../core/date-time/interval-engine.js'
import {
    isValidTime as coreIsValidTime,
    normalizeTime as coreNormalizeTime,
    timeToMinutes as coreTimeToMinutes,
} from '../core/time-utils.js'

export const ALL_DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']

export const WEEKDAYS = ['mon', 'tue', 'wed', 'thu', 'fri']

export const SLOT_TYPE_SLOT = 'slot'

export const SLOT_TYPE_BREAK = 'break'

export { copyDay, validateNoOverlap, slotIsOvernight }

export function normalizeTime(value) {
    return coreNormalizeTime(value)
}

export function timeToMinutes(time) {
    const minutes = coreTimeToMinutes(time)

    return minutes === null ? NaN : minutes
}

export function isValidTimeFormat(time) {
    return coreIsValidTime(time)
}

export function isValidSlot(slot) {
    const from = normalizeTime(slot?.from)
    const to = normalizeTime(slot?.to)

    if (! from || ! to) {
        return false
    }

    if (slotIsOvernight(slot)) {
        return true
    }

    return timeToMinutes(from) < timeToMinutes(to)
}

/**
 * @param {Array<{from: string, to: string, overnight?: boolean}>} slots
 */
export function slotsOverlap(slots) {
    return engineSlotsOverlap(slots)
}

/**
 * @param {Array<{from: string, to: string, overnight?: boolean}>} slots
 */
export function validateDaySlots(slots, {
    minSlots = 1,
    maxSlots = 10,
    requireSlots = true,
} = {}) {
    const normalized = slots
        .map((slot) => ({
            from: normalizeTime(slot?.from),
            to: normalizeTime(slot?.to),
            overnight: Boolean(slot?.overnight),
        }))
        .filter((slot) => slot.from && slot.to)

    for (const slot of normalized) {
        if (! slotIsOvernight(slot) && timeToMinutes(slot.from) >= timeToMinutes(slot.to)) {
            return 'from_before_to'
        }
    }

    if (requireSlots && normalized.length < minSlots) {
        return 'min_slots'
    }

    if (normalized.length > maxSlots) {
        return 'max_slots'
    }

    if (slotsOverlap(normalized)) {
        return 'overlap'
    }

    return null
}

/**
 * @param {Array<{from: string, to: string, overnight?: boolean}>} slots
 */
export function slotOverlapsAtIndex(slots, slotIndex) {
    const normalized = slots
        .map((slot) => ({
            from: normalizeTime(slot?.from),
            to: normalizeTime(slot?.to),
            overnight: Boolean(slot?.overnight),
        }))
        .filter((slot) => slot.from && slot.to)

    const target = normalized[slotIndex]

    if (! target) {
        return false
    }

    const targetSegments = expandIntervals([target])

    for (let index = 0; index < normalized.length; index += 1) {
        if (index === slotIndex) {
            continue
        }

        if (segmentsOverlap(targetSegments, expandIntervals([normalized[index]]))) {
            return true
        }
    }

    return false
}

export function slotHasInvalidRange(slot) {
    const from = normalizeTime(slot?.from)
    const to = normalizeTime(slot?.to)

    if (! from || ! to) {
        return true
    }

    if (slotIsOvernight(slot)) {
        return false
    }

    return timeToMinutes(from) >= timeToMinutes(to)
}

/**
 * @param {{
 *   slotCount: number,
 *   minSlots?: number,
 *   isEnabled?: boolean,
 *   isInteractive?: boolean,
 * }} options
 */
export function canRemoveDaySlot({
    slotCount,
    minSlots = 1,
    isEnabled = true,
    isInteractive = true,
}) {
    if (! isInteractive) {
        return false
    }

    if (isEnabled && slotCount <= minSlots) {
        return false
    }

    return true
}

export function resolveSlotType(slot) {
    return slot?.type === SLOT_TYPE_BREAK ? SLOT_TYPE_BREAK : SLOT_TYPE_SLOT
}

export function isBreakSlot(slot) {
    return resolveSlotType(slot) === SLOT_TYPE_BREAK
}

export function createEmptyDay() {
    return {
        enabled: false,
        slots: [],
    }
}

export function createDefaultSlot(from = '09:00', to = '17:00') {
    return {
        from,
        to,
        type: SLOT_TYPE_SLOT,
    }
}

export function createBreakSlot(previousEnd = '12:00') {
    const startMinutes = timeToMinutes(previousEnd)
    const fromMinutes = Number.isFinite(startMinutes) ? startMinutes : 12 * 60
    const toMinutes = Math.min(fromMinutes + 60, (23 * 60) + 59)

    const from = `${String(Math.floor(fromMinutes / 60)).padStart(2, '0')}:${String(fromMinutes % 60).padStart(2, '0')}`
    const to = `${String(Math.floor(toMinutes / 60)).padStart(2, '0')}:${String(toMinutes % 60).padStart(2, '0')}`

    return {
        from,
        to,
        type: SLOT_TYPE_BREAK,
    }
}

export function createWorkSlotAfter(previousEnd = '09:00') {
    const startMinutes = timeToMinutes(normalizeTime(previousEnd) ?? '09:00')
    const fromMinutes = Number.isFinite(startMinutes) ? startMinutes : 9 * 60
    const toMinutes = Math.min(fromMinutes + (4 * 60), (23 * 60) + 59)

    const from = `${String(Math.floor(fromMinutes / 60)).padStart(2, '0')}:${String(fromMinutes % 60).padStart(2, '0')}`
    const to = `${String(Math.floor(toMinutes / 60)).padStart(2, '0')}:${String(toMinutes % 60).padStart(2, '0')}`

    return createDefaultSlot(from, to)
}

export function normalizeScheduleState(state, days = ALL_DAYS, defaultTimezone = null) {
    const normalized = {
        days: {},
    }

    if (defaultTimezone) {
        normalized.timezone = defaultTimezone
    }

    if (state && typeof state === 'object') {
        if (typeof state.timezone === 'string' && state.timezone.trim() !== '') {
            normalized.timezone = state.timezone.trim()
        }
    }

    const dayStates = state?.days && typeof state.days === 'object' ? state.days : {}

    for (const day of days) {
        const dayState = dayStates[day] && typeof dayStates[day] === 'object' ? dayStates[day] : {}
        const enabled = Boolean(dayState.enabled)
        const slots = Array.isArray(dayState.slots)
            ? dayState.slots
                .map((slot) => ({
                    from: normalizeTime(slot?.from) ?? '',
                    to: normalizeTime(slot?.to) ?? '',
                    type: resolveSlotType(slot),
                    ...(Boolean(slot?.overnight) ? { overnight: true } : {}),
                }))
                .filter((slot) => slot.from !== '' && slot.to !== '')
            : []

        normalized.days[day] = {
            enabled,
            slots,
        }
    }

    return normalized
}
