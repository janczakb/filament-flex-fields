import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import flexTimeSegmentsComponent from '../../resources/js/components/flex-time-segments.js'

describe('flex-time-segments', () => {
    it('commits snapped HH:MM values through setValue when selecting minute', () => {
        let value = '09:07'
        const component = flexTimeSegmentsComponent({
            getValue: () => value,
            setValue: (next) => {
                value = next
            },
            minuteStep: 15,
        })

        component.selectMinute('30')

        assert.equal(value, '09:30')
    })

    it('snaps minute selection to configured step', () => {
        let value = '09:00'
        const component = flexTimeSegmentsComponent({
            getValue: () => value,
            setValue: (next) => {
                value = next
            },
            minuteStep: 15,
        })

        component.selectMinute('07')

        assert.equal(value, '09:00')
    })

    it('selects hour with default minute when minute is empty', () => {
        let value = ''
        const component = flexTimeSegmentsComponent({
            getValue: () => value,
            setValue: (next) => {
                value = next
            },
            minuteStep: 15,
        })

        component.selectHour('14')

        assert.equal(value, '14:00')
    })

    it('shows placeholder label when value is empty', () => {
        const component = flexTimeSegmentsComponent({
            getValue: () => '',
            setValue: () => {},
            minuteStep: 15,
            hourPlaceholder: 'hh',
            minutePlaceholder: 'mm',
        })

        assert.equal(component.displayLabel, 'hh : mm')
        assert.equal(component.hasValue, false)
    })

    it('rejects values outside configured min and max bounds', () => {
        let value = '09:00'
        const component = flexTimeSegmentsComponent({
            getValue: () => value,
            setValue: (next) => {
                value = next
            },
            minuteStep: 15,
            minValue: '09:00',
            maxValue: '18:00',
        })

        component.selectHour('20')

        assert.equal(value, '09:00')
    })

    it('updates displayLabel after selecting a new minute', () => {
        let value = '09:00'
        const component = flexTimeSegmentsComponent({
            getValue: () => value,
            setValue: (next) => {
                value = next
            },
            minuteStep: 5,
        })

        component.selectMinute('15')

        assert.equal(value, '09:15')
        assert.equal(component.displayLabel, '09 : 15')
    })

    it('displays 12-hour values with day period', () => {
        const component = flexTimeSegmentsComponent({
            getValue: () => '14:30',
            setValue: () => {},
            minuteStep: 15,
            hourCycle: 12,
        })

        assert.equal(component.displayLabel, '02 : 30 PM')
    })
})
