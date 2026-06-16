import { createSearchableSelectMenuMixin } from '../core/searchable-select-menu.js'
import {
    dayPeriodFromHour24,
    formatHourFor12HourCycle,
    isTimeWithinRange,
    normalizeTime,
    timeToMinutes,
    toHour24,
} from '../core/time-utils.js'
import { mergeAlpineComponentData } from '../support/merge-alpine-component-data.js'

const timePickerMenu = createSearchableSelectMenuMixin({
    openKey: 'menuOpen',
    readyKey: 'menuReady',
    triggerRef: 'timeTrigger',
    menuRef: 'timeMenu',
    minMenuWidth: 224,
    matchTriggerWidth: false,
    menuGap: 6,
    closeMethod: 'closeMenu',
    ownerIdPrefix: 'fff-flex-time-segments',
})

export default function flexTimeSegmentsComponent({
    getValue = null,
    setValue = null,
    minuteStep = 15,
    hourCycle = 24,
    minValue = null,
    maxValue = null,
    disabled = false,
    initialValue = '',
    hourPlaceholder = 'hh',
    minutePlaceholder = 'mm',
    immediateDisplayReady = false,
}) {
    return mergeAlpineComponentData({
        getValue,
        setValue,
        minuteStep,
        hourCycle,
        minValue,
        maxValue,
        disabled,
        initialValue,
        hourPlaceholder,
        minutePlaceholder,
        immediateDisplayReady,
        menuOpen: false,
        menuReady: false,
        isSyncing: false,
        componentReady: false,
        externalValue: null,

        get isLocked() {
            return Boolean(this.disabled)
        },

        get uses12HourCycle() {
            return Number(this.hourCycle) === 12
        },

        init() {
            this.bindSelectMenuLifecycle()

            if (typeof this.getValue === 'function') {
                this.syncExternalValue()

                this.$watch(() => this.readExternalValue(), () => {
                    if (this.isSyncing) {
                        return
                    }

                    this.syncExternalValue()

                    if (this.menuOpen) {
                        this.$nextTick(() => this.scrollActiveOptionsIntoView())
                    }
                })
            }

            this.$nextTick(() => {
                if (this.immediateDisplayReady) {
                    this.componentReady = true

                    return
                }

                requestAnimationFrame(() => {
                    this.componentReady = true
                })
            })
        },

        readExternalValue() {
            if (typeof this.getValue !== 'function') {
                return this.getValue
            }

            return this.getValue()
        },

        syncExternalValue() {
            this.externalValue = normalizeTime(this.readExternalValue()) ?? ''
        },

        resolvedValue() {
            if (typeof this.getValue === 'function') {
                if (this.externalValue !== null) {
                    return this.externalValue
                }

                const external = normalizeTime(this.readExternalValue())
                const fallback = normalizeTime(this.initialValue)

                return external ?? fallback ?? ''
            }

            const external = normalizeTime(this.readExternalValue())
            const fallback = normalizeTime(this.initialValue)

            return external ?? fallback ?? ''
        },

        selectedHour24() {
            const value = this.resolvedValue()

            if (! value) {
                return ''
            }

            return value.split(':')[0] ?? ''
        },

        selectedMinute() {
            const value = this.resolvedValue()

            if (! value) {
                return ''
            }

            return value.split(':')[1] ?? ''
        },

        selectedHour() {
            if (this.uses12HourCycle) {
                return formatHourFor12HourCycle(this.selectedHour24())
            }

            return this.selectedHour24()
        },

        selectedDayPeriod() {
            return dayPeriodFromHour24(this.selectedHour24())
        },

        get hasValue() {
            return this.resolvedValue() !== ''
        },

        get showPlaceholderStyle() {
            return ! this.hasValue
        },

        get displayLabel() {
            if (! this.hasValue) {
                return `${this.hourPlaceholder} : ${this.minutePlaceholder}`
            }

            if (this.uses12HourCycle) {
                const period = this.selectedDayPeriod()

                return `${this.selectedHour()} : ${this.selectedMinute()}${period ? ` ${period}` : ''}`
            }

            return `${this.selectedHour24()} : ${this.selectedMinute()}`
        },

        hourOptions() {
            if (this.uses12HourCycle) {
                return ['12', ...Array.from({ length: 11 }, (_, index) => String(index + 1).padStart(2, '0'))]
            }

            return Array.from({ length: 24 }, (_, hour) => String(hour).padStart(2, '0'))
        },

        dayPeriodOptions() {
            return ['AM', 'PM']
        },

        minuteOptions() {
            const step = Math.max(1, Math.min(60, Number(this.minuteStep) || 15))
            const options = []

            for (let minute = 0; minute < 60; minute += step) {
                options.push(String(minute).padStart(2, '0'))
            }

            return options.filter((minute) => this.isMinuteAllowed(minute))
        },

        snapMinute(minute) {
            const step = Math.max(1, Math.min(60, Number(this.minuteStep) || 15))
            const options = []

            for (let candidate = 0; candidate < 60; candidate += step) {
                options.push(String(candidate).padStart(2, '0'))
            }

            if (options.includes(minute)) {
                return minute
            }

            const numericMinute = Number.parseInt(minute, 10)

            if (Number.isNaN(numericMinute)) {
                return options[0] ?? '00'
            }

            const snapped = Math.round(numericMinute / step) * step
            const clamped = Math.min(59, Math.max(0, snapped))
            const normalized = String(clamped).padStart(2, '0')

            if (options.includes(normalized)) {
                return normalized
            }

            return options.reduce((closest, option) => {
                const optionValue = Number.parseInt(option, 10)
                const closestValue = Number.parseInt(closest, 10)

                return Math.abs(optionValue - clamped) < Math.abs(closestValue - clamped)
                    ? option
                    : closest
            }, options[0] ?? '00')
        },

        isHourAllowed(hourLabel) {
            const minute = this.selectedMinute() || this.minuteOptions()[0] || '00'
            const hour24 = this.uses12HourCycle
                ? toHour24(hourLabel, this.selectedDayPeriod() || 'AM')
                : Number.parseInt(hourLabel, 10)

            if (hour24 === null || Number.isNaN(hour24)) {
                return false
            }

            const candidate = normalizeTime(`${String(hour24).padStart(2, '0')}:${this.snapMinute(minute)}`)

            return candidate !== null && isTimeWithinRange(candidate, this.minValue, this.maxValue)
        },

        isMinuteAllowed(minute) {
            const hourLabel = this.selectedHour() || (this.uses12HourCycle ? '12' : '00')
            const hour24 = this.uses12HourCycle
                ? toHour24(hourLabel, this.selectedDayPeriod() || 'AM')
                : Number.parseInt(hourLabel, 10)

            if (hour24 === null || Number.isNaN(hour24)) {
                return this.minValue === null && this.maxValue === null
            }

            const candidate = normalizeTime(`${String(hour24).padStart(2, '0')}:${this.snapMinute(minute)}`)

            return candidate !== null && isTimeWithinRange(candidate, this.minValue, this.maxValue)
        },

        toggleMenu() {
            if (this.isLocked) {
                return
            }

            this.menuOpen = ! this.menuOpen

            if (this.menuOpen) {
                this.$nextTick(() => this.scrollActiveOptionsIntoView())
            }
        },

        closeMenu() {
            this.closeTeleportedMenu()
        },

        selectHour(hour) {
            if (this.isLocked || ! this.isHourAllowed(hour)) {
                return
            }

            const minute = this.selectedMinute() || this.minuteOptions()[0] || '00'
            const hour24 = this.uses12HourCycle
                ? toHour24(hour, this.selectedDayPeriod() || 'AM')
                : Number.parseInt(hour, 10)

            if (hour24 === null || Number.isNaN(hour24)) {
                return
            }

            this.pushValue(normalizeTime(`${String(hour24).padStart(2, '0')}:${this.snapMinute(minute)}`))
            this.scheduleActiveOptionsScroll()
        },

        selectMinute(minute) {
            if (this.isLocked || ! this.isMinuteAllowed(minute)) {
                return
            }

            const hourLabel = this.selectedHour() || (this.uses12HourCycle ? '12' : '00')
            const hour24 = this.uses12HourCycle
                ? toHour24(hourLabel, this.selectedDayPeriod() || 'AM')
                : Number.parseInt(hourLabel, 10)

            if (hour24 === null || Number.isNaN(hour24)) {
                return
            }

            this.pushValue(normalizeTime(`${String(hour24).padStart(2, '0')}:${this.snapMinute(minute)}`))
        },

        selectDayPeriod(dayPeriod) {
            if (this.isLocked || ! this.uses12HourCycle) {
                return
            }

            const hourLabel = this.selectedHour() || '12'
            const hour24 = toHour24(hourLabel, dayPeriod)

            if (hour24 === null) {
                return
            }

            const minute = this.selectedMinute() || this.minuteOptions()[0] || '00'
            const candidate = normalizeTime(`${String(hour24).padStart(2, '0')}:${this.snapMinute(minute)}`)

            if (candidate === null || ! isTimeWithinRange(candidate, this.minValue, this.maxValue)) {
                return
            }

            this.pushValue(candidate)
            this.scheduleActiveOptionsScroll()
        },

        scheduleActiveOptionsScroll() {
            if (typeof this.$nextTick !== 'function') {
                return
            }

            this.$nextTick(() => this.scrollActiveOptionsIntoView())
        },

        pushValue(value) {
            if (typeof this.setValue !== 'function') {
                return
            }

            const normalized = normalizeTime(value)

            if (normalized === null || ! isTimeWithinRange(normalized, this.minValue, this.maxValue)) {
                return
            }

            const external = normalizeTime(this.readExternalValue())

            if (external === normalized) {
                return
            }

            this.isSyncing = true

            try {
                this.setValue(normalized)
                this.externalValue = normalized
            } finally {
                this.isSyncing = false
            }
        },

        scrollActiveOptionsIntoView() {
            this.$nextTick(() => {
                const menu = this.$refs.timeMenu

                if (! menu) {
                    return
                }

                menu.querySelectorAll('[data-selected="true"]').forEach((option) => {
                    option.scrollIntoView({ block: 'nearest' })
                })
            })
        },
    }, timePickerMenu)
}
