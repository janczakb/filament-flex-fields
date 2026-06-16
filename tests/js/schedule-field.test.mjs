import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    canRemoveDaySlot,
    createWorkSlotAfter,
    slotHasInvalidRange,
    validateDaySlots,
} from '../../resources/js/support/schedule-validation.js'

describe('schedule-field helpers', () => {
    it('detects invalid slot ranges for inline validation', () => {
        assert.equal(slotHasInvalidRange({ from: '09:00', to: '17:00' }), false)
        assert.equal(slotHasInvalidRange({ from: '17:00', to: '09:00' }), true)
    })

    it('reports overlap validation codes for enabled days', () => {
        assert.equal(validateDaySlots([
            { from: '09:00', to: '12:00' },
            { from: '11:00', to: '13:00' },
        ]), 'overlap')
    })

    it('creates sequential work slots after previous end time', () => {
        const slot = createWorkSlotAfter('12:00')

        assert.equal(slot.from, '12:00')
        assert.equal(slot.to, '16:00')
    })

    it('prevents removing the last required slot on an open day', () => {
        assert.equal(canRemoveDaySlot({
            slotCount: 1,
            minSlots: 1,
            isEnabled: true,
            isInteractive: true,
        }), false)
    })
})
