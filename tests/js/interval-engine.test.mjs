import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    copyDay,
    expandIntervals,
    slotIsOvernight,
    validateNoOverlap,
} from '../../resources/js/core/date-time/interval-engine.js'

describe('interval-engine', () => {
    it('expands overnight intervals across midnight', () => {
        assert.deepEqual(expandIntervals([
            { from: '22:00', to: '06:00', overnight: true },
        ]), [
            { start: 22 * 60, end: 1440 },
            { start: 0, end: 6 * 60 },
        ])
    })

    it('detects overlap inside overnight windows', () => {
        assert.equal(validateNoOverlap([
            { from: '22:00', to: '06:00', overnight: true },
            { from: '23:00', to: '01:00', overnight: true },
        ]), false)
    })

    it('allows adjacent morning slot after overnight window', () => {
        assert.equal(validateNoOverlap([
            { from: '22:00', to: '06:00', overnight: true },
            { from: '06:00', to: '09:00' },
        ]), true)
    })

    it('copies a schedule day using ScheduleV2 parity', () => {
        const schedule = {
            days: {
                mon: {
                    enabled: true,
                    slots: [{ from: '09:00', to: '17:00', type: 'slot' }],
                },
                tue: {
                    enabled: false,
                    slots: [],
                },
            },
        }

        const copied = copyDay(schedule, 'mon', 'tue')

        assert.deepEqual(copied.days.tue, {
            enabled: true,
            slots: [{ from: '09:00', to: '17:00', type: 'slot' }],
        })
    })

    it('treats explicit overnight flag as valid range', () => {
        assert.equal(slotIsOvernight({ from: '22:00', to: '06:00', overnight: true }), true)
        assert.equal(slotIsOvernight({ from: '09:00', to: '17:00' }), false)
    })
})
