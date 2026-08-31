import assert from 'node:assert/strict'
import { describe, it } from 'node:test'
import {
    firstDayOfWeekForLocale,
    isDateUnavailable,
    isDateWithinBounds,
    normalizeTime,
    slotIsOvernight,
    validateNoOverlap,
} from '../../resources/js/core/date-time/datetime-os.js'

describe('datetime-os', () => {
    it('normalizes time strings', () => {
        assert.equal(normalizeTime('09:05'), '09:05')
        assert.equal(normalizeTime('invalid'), null)
    })

    it('resolves first day of week from locale', () => {
        assert.equal(firstDayOfWeekForLocale('de-DE'), 1)
        assert.equal(firstDayOfWeekForLocale('en-US'), 0)
    })

    it('checks unavailable dates', () => {
        assert.equal(isDateUnavailable('2026-06-06', ['2026-06-06', '2026-06-07']), true)
        assert.equal(isDateUnavailable('2026-06-08', ['2026-06-06', '2026-06-07']), false)
    })

    it('checks date bounds', () => {
        assert.equal(isDateWithinBounds('2026-06-10', '2026-06-01', '2026-06-30'), true)
        assert.equal(isDateWithinBounds('2026-07-01', '2026-06-01', '2026-06-30'), false)
    })

    it('mirrors overnight overlap contract', () => {
        assert.equal(slotIsOvernight({ from: '22:00', to: '06:00', overnight: true }), true)
        assert.equal(
            validateNoOverlap([
                { from: '22:00', to: '06:00', overnight: true },
                { from: '07:00', to: '08:00' },
            ]),
            true,
        )
    })
})
