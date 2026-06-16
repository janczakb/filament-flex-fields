import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    dayPeriodFromHour24,
    formatHourFor12HourCycle,
    isTimeWithinRange,
    normalizeTime,
    timeToMinutes,
    toHour24,
} from '../../resources/js/core/time-utils.js'

describe('time-utils', () => {
    it('normalizes valid times to HH:MM', () => {
        assert.equal(normalizeTime('9:05'), '09:05')
        assert.equal(normalizeTime('23:59'), '23:59')
        assert.equal(normalizeTime('24:00'), null)
    })

    it('converts times to minutes', () => {
        assert.equal(timeToMinutes('09:00'), 540)
        assert.equal(timeToMinutes('17:30'), 1050)
        assert.equal(timeToMinutes('invalid'), null)
    })

    it('checks min and max bounds', () => {
        assert.equal(isTimeWithinRange('09:30', '09:00', '18:00'), true)
        assert.equal(isTimeWithinRange('08:59', '09:00', '18:00'), false)
        assert.equal(isTimeWithinRange('18:01', '09:00', '18:00'), false)
    })

    it('formats and parses 12-hour values', () => {
        assert.equal(formatHourFor12HourCycle('14'), '02')
        assert.equal(dayPeriodFromHour24('14'), 'PM')
        assert.equal(toHour24('02', 'PM'), 14)
        assert.equal(toHour24('12', 'AM'), 0)
    })
})
