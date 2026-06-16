import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    canRemoveDaySlot,
    createBreakSlot,
    createWorkSlotAfter,
    isValidSlot,
    isValidTimeFormat,
    normalizeTime,
    slotOverlapsAtIndex,
    slotsOverlap,
    timeToMinutes,
    validateDaySlots,
} from '../../resources/js/support/schedule-validation.js'

describe('schedule-validation', () => {
    it('normalizes and validates HH:MM times', () => {
        assert.equal(normalizeTime('9:05'), '09:05')
        assert.equal(normalizeTime('23:59'), '23:59')
        assert.equal(normalizeTime('24:00'), null)
        assert.equal(isValidTimeFormat('10:30'), true)
        assert.equal(isValidTimeFormat('1030'), false)
    })

    it('converts times to minutes', () => {
        assert.equal(timeToMinutes('09:00'), 540)
        assert.equal(timeToMinutes('17:30'), 1050)
    })

    it('validates individual slots', () => {
        assert.equal(isValidSlot({ from: '09:00', to: '17:00' }), true)
        assert.equal(isValidSlot({ from: '17:00', to: '09:00' }), false)
        assert.equal(isValidSlot({ from: 'bad', to: '17:00' }), false)
    })

    it('detects overlapping slots', () => {
        assert.equal(slotsOverlap([
            { from: '09:00', to: '12:00' },
            { from: '11:30', to: '13:00' },
        ]), true)

        assert.equal(slotsOverlap([
            { from: '09:00', to: '12:00' },
            { from: '12:00', to: '13:00' },
        ]), false)
    })

    it('validates day slot counts and ordering', () => {
        assert.equal(validateDaySlots([
            { from: '09:00', to: '12:00' },
            { from: '13:00', to: '17:00' },
        ]), null)

        assert.equal(validateDaySlots([], { minSlots: 1 }), 'min_slots')
        assert.equal(validateDaySlots([
            { from: '09:00', to: '12:00' },
            { from: '11:00', to: '13:00' },
        ]), 'overlap')
        assert.equal(validateDaySlots([
            { from: '12:00', to: '09:00' },
        ]), 'from_before_to')
    })

    it('creates break slots after previous end time', () => {
        const slot = createBreakSlot('12:00')

        assert.equal(slot.from, '12:00')
        assert.equal(slot.to, '13:00')
        assert.equal(slot.type, 'break')
    })

    it('creates work slots after previous end time', () => {
        const slot = createWorkSlotAfter('12:00')

        assert.equal(slot.from, '12:00')
        assert.equal(slot.to, '16:00')
        assert.equal(slot.type, 'slot')
    })

    it('detects overlap for a specific slot index', () => {
        const slots = [
            { from: '09:00', to: '12:00' },
            { from: '11:00', to: '13:00' },
            { from: '14:00', to: '16:00' },
        ]

        assert.equal(slotOverlapsAtIndex(slots, 0), true)
        assert.equal(slotOverlapsAtIndex(slots, 1), true)
        assert.equal(slotOverlapsAtIndex(slots, 2), false)
    })

    it('prevents removing the last required slot on an open day', () => {
        assert.equal(canRemoveDaySlot({
            slotCount: 1,
            minSlots: 1,
            isEnabled: true,
            isInteractive: true,
        }), false)

        assert.equal(canRemoveDaySlot({
            slotCount: 2,
            minSlots: 1,
            isEnabled: true,
            isInteractive: true,
        }), true)

        assert.equal(canRemoveDaySlot({
            slotCount: 1,
            minSlots: 1,
            isEnabled: false,
            isInteractive: true,
        }), true)
    })

    it('enforces max slot count during validation', () => {
        const slots = Array.from({ length: 7 }, (_, index) => ({
            from: `${String(8 + index).padStart(2, '0')}:00`,
            to: `${String(8 + index).padStart(2, '0')}:30`,
        }))

        assert.equal(validateDaySlots(slots, { minSlots: 1, maxSlots: 6 }), 'max_slots')
    })
})
