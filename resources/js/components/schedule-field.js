import {
    canRemoveDaySlot,
    createBreakSlot,
    createDefaultSlot,
    createEmptyDay,
    createWorkSlotAfter,
    isBreakSlot as slotIsBreak,
    normalizeScheduleState,
    normalizeTime,
    slotHasInvalidRange,
    slotOverlapsAtIndex,
    slotsOverlap,
    validateDaySlots,
    WEEKDAYS,
} from '../support/schedule-validation.js'
import { mergeAlpineComponentData } from '../support/merge-alpine-component-data.js'
import flexTimeSegmentsComponent from './flex-time-segments.js'
import {
    createTimezonePickerMixin,
    FFF_TIMEZONE_VIRTUAL_THRESHOLD,
} from '../support/timezone-picker-mixin.js'

export function registerFlexTimeSegmentsComponentGlobally(factory = flexTimeSegmentsComponent) {
    if (typeof factory !== 'function') {
        return false
    }

    globalThis.flexTimeSegmentsComponent = factory

    return true
}

registerFlexTimeSegmentsComponentGlobally()

const timezonePicker = createTimezonePickerMixin({
    triggerRef: 'timezoneTrigger',
    menuRef: 'timezoneMenu',
    ownerIdPrefix: 'fff-schedule-field-timezone',
})

export default function scheduleFieldFormComponent({
    state,
    statePath,
    disabled = false,
    readOnly = false,
    days = [],
    dayLabels = {},
    weekdays = WEEKDAYS,
    timeStep = 15,
    minSlots = 1,
    maxSlots = 10,
    lockedDays = [],
    requireSlotsForEnabledDays = true,
    validationMessages = {},
    allowCopyToWeekdays = true,
    copySourceDay = 'mon',
    showTimezone = true,
    timezones = [],
    defaultTimezone = 'UTC',
    searchable = true,
    showOffset = true,
    searchPlaceholder = '',
    timezonePlaceholder = '',
    virtualScrollThreshold = FFF_TIMEZONE_VIRTUAL_THRESHOLD,
    labels = {},
    initialState = null,
    flexTimeSegmentsSrc = null,
}) {
    return mergeAlpineComponentData({
        state,
        statePath,
        disabled,
        readOnly,
        days,
        dayLabels,
        weekdays,
        timeStep,
        minSlots,
        maxSlots,
        lockedDays,
        requireSlotsForEnabledDays,
        validationMessages,
        allowCopyToWeekdays,
        copySourceDay,
        showTimezone,
        timezones,
        defaultTimezone,
        searchable,
        showOffset,
        searchPlaceholder,
        timezonePlaceholder,
        virtualScrollThreshold,
        labels,
        initialState,
        flexTimeSegmentsSrc,

        displayReady: false,
        dayAnimationsEnabled: false,
        flexTimeSegmentsReady: false,

        async init() {
            const normalized = normalizeScheduleState(
                this.state ?? this.initialState,
                this.days,
                this.showTimezone ? (this.state?.timezone ?? this.defaultTimezone) : null,
            )

            if (JSON.stringify(normalized) !== JSON.stringify(this.state ?? {})) {
                this.state = normalized
            }

            await this.ensureFlexTimeSegmentsLoaded()

            if (this.showTimezone) {
                this.bindSelectMenuLifecycle()
            }

            this.markDisplayReady()
            this.bindFormSubmitGuard()
        },

        bindFormSubmitGuard() {
            const form = this.$el.closest('form')

            if (! form || this._submitGuardBound) {
                return
            }

            this._submitGuardBound = true

            form.addEventListener('submit', (event) => {
                if (! this.hasClientValidationErrors()) {
                    return
                }

                event.preventDefault()
                event.stopImmediatePropagation()
                this.syncClientValidationErrorsToLivewire()
            }, true)
        },

        hasClientValidationErrors() {
            return this.days.some((day) => {
                if (! this.isDayEnabled(day)) {
                    return false
                }

                if (this.dayValidationError(day)) {
                    return true
                }

                return this.daySlots(day).some((_, slotIndex) => this.slotIsInvalid(day, slotIndex))
            })
        },

        firstClientValidationErrorMessage() {
            for (const day of this.days) {
                if (! this.isDayEnabled(day)) {
                    continue
                }

                const message = this.dayValidationErrorMessage(day)

                if (message) {
                    return message
                }

                for (let slotIndex = 0; slotIndex < this.daySlots(day).length; slotIndex += 1) {
                    if (this.slotIsInvalid(day, slotIndex)) {
                        return this.validationMessages?.from_before_to
                            ?? this.validationMessages?.overlap
                            ?? 'Schedule validation failed.'
                    }
                }
            }

            return null
        },

        syncClientValidationErrorsToLivewire() {
            const message = this.firstClientValidationErrorMessage()

            if (! message || ! this.$wire || ! this.statePath) {
                return
            }

            this.$wire.addError(this.statePath, message)
        },

        markDisplayReady() {
            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    this.displayReady = true

                    requestAnimationFrame(() => {
                        this.dayAnimationsEnabled = true
                    })
                })
            })
        },

        async ensureFlexTimeSegmentsLoaded() {
            if (this.flexTimeSegmentsReady) {
                return
            }

            if (this.isFlexTimeSegmentsAvailable()) {
                this.flexTimeSegmentsReady = true

                return
            }

            const source = this.flexTimeSegmentsSrc

            if (source) {
                try {
                    const module = await import(/* @vite-ignore */ source)
                    const factory = module.default ?? module.flexTimeSegmentsComponent

                    registerFlexTimeSegmentsComponentGlobally(factory)
                } catch {
                    // Fall back to per-field x-load below.
                }
            }

            this.flexTimeSegmentsReady = this.isFlexTimeSegmentsAvailable()
        },

        isFlexTimeSegmentsAvailable() {
            return typeof globalThis.flexTimeSegmentsComponent === 'function'
        },

        get isInteractive() {
            return ! this.disabled && ! this.readOnly
        },

        isTimezonePickerLocked() {
            return ! this.isInteractive
        },

        getTimezoneValue() {
            return this.state?.timezone ?? this.defaultTimezone
        },

        setTimezoneValue(timezone) {
            this.updateTimezone(timezone)
        },

        toggleMenu() {
            this.toggleTimezoneMenu()
        },

        closeMenu() {
            this.closeTimezoneMenu()
        },

        dayState(day) {
            if (! this.state?.days?.[day]) {
                return createEmptyDay()
            }

            return this.state.days[day]
        },

        isDayEnabled(day) {
            if (! this.displayReady && this.initialState?.days?.[day]) {
                return Boolean(this.initialState.days[day].enabled)
            }

            return Boolean(this.dayState(day).enabled)
        },

        isDayLocked(day) {
            return Array.isArray(this.lockedDays) && this.lockedDays.includes(day)
        },

        daySlots(day) {
            return Array.isArray(this.dayState(day).slots) ? this.dayState(day).slots : []
        },

        isBreakSlot(slot) {
            return slotIsBreak(slot)
        },

        slotEntryLabel(day, slotIndex) {
            const slots = this.daySlots(day)
            const slot = slots[slotIndex]

            if (! slot) {
                return ''
            }

            if (this.isBreakSlot(slot)) {
                return this.labels.break ?? 'Break'
            }

            let workSlotNumber = 0

            for (let index = 0; index <= slotIndex; index += 1) {
                if (! this.isBreakSlot(slots[index])) {
                    workSlotNumber += 1
                }
            }

            return `${this.labels.slot ?? 'Slot'} ${workSlotNumber}`
        },

        toggleDay(day) {
            if (! this.isInteractive || this.isDayLocked(day)) {
                return
            }

            const current = this.dayState(day)
            const enabled = ! current.enabled

            this.state = {
                ...this.state,
                days: {
                    ...this.state.days,
                    [day]: {
                        enabled,
                        slots: enabled && current.slots.length === 0
                            ? [createDefaultSlot()]
                            : current.slots,
                    },
                },
            }
        },

        addSlot(day) {
            if (! this.isInteractive) {
                return
            }

            const current = this.dayState(day)
            const slots = [...current.slots]

            if (slots.length >= this.maxSlots) {
                return
            }

            const lastSlot = slots[slots.length - 1]
            const nextSlot = lastSlot?.to
                ? createWorkSlotAfter(lastSlot.to)
                : createDefaultSlot()

            slots.push(nextSlot)

            this.updateDay(day, {
                ...current,
                enabled: true,
                slots,
            })
        },

        addBreak(day) {
            if (! this.isInteractive) {
                return
            }

            const current = this.dayState(day)
            const slots = [...current.slots]

            if (slots.length >= this.maxSlots) {
                return
            }

            const lastSlot = slots[slots.length - 1]
            slots.push(createBreakSlot(lastSlot?.to ?? '12:00'))

            this.updateDay(day, {
                ...current,
                enabled: true,
                slots,
            })
        },

        removeSlot(day, slotIndex) {
            if (! this.canRemoveSlot(day)) {
                return
            }

            const current = this.dayState(day)
            const slots = current.slots.filter((_, index) => index !== slotIndex)

            this.updateDay(day, {
                ...current,
                slots,
            })
        },

        updateSlotTime(day, slotIndex, field, value) {
            if (! this.isInteractive) {
                return
            }

            const current = this.dayState(day)
            const slots = current.slots.map((slot, index) => {
                if (index !== slotIndex) {
                    return slot
                }

                return {
                    ...slot,
                    [field]: value,
                }
            })

            this.updateDay(day, {
                ...current,
                slots,
            })
        },

        normalizeSlotTime(day, slotIndex, field) {
            const current = this.dayState(day)
            const slot = current.slots[slotIndex]

            if (! slot) {
                return
            }

            const normalized = normalizeTime(slot[field])

            if (normalized === slot[field]) {
                return
            }

            this.updateSlotTime(day, slotIndex, field, normalized ?? slot[field])
        },

        updateDay(day, dayState) {
            this.state = {
                ...this.state,
                days: {
                    ...this.state.days,
                    [day]: dayState,
                },
            }
        },

        updateTimezone(timezone) {
            if (! this.isInteractive || ! this.showTimezone) {
                return
            }

            this.state = {
                ...this.state,
                timezone,
            }
        },

        copySourceToWeekdays() {
            if (! this.isInteractive || ! this.allowCopyToWeekdays) {
                return
            }

            const confirmMessage = this.labels.copyConfirm
                ?? 'Copy this day\'s schedule to all weekdays? Existing weekday schedules will be replaced.'

            if (! window.confirm(confirmMessage)) {
                return
            }

            const source = this.dayState(this.copySourceDay)
            const nextDays = { ...this.state.days }

            for (const day of this.weekdays) {
                if (day === this.copySourceDay) {
                    continue
                }

                if (! this.days.includes(day)) {
                    continue
                }

                nextDays[day] = {
                    enabled: source.enabled,
                    slots: source.slots.map((slot) => ({ ...slot })),
                }
            }

            this.state = {
                ...this.state,
                days: nextDays,
            }
        },

        shouldShowCopyButton(day) {
            return this.allowCopyToWeekdays
                && day === this.copySourceDay
                && this.weekdays.some((weekday) => weekday !== this.copySourceDay && this.days.includes(weekday))
        },

        dayHasOverlap(day) {
            return slotsOverlap(this.daySlots(day))
        },

        slotHasOverlap(day, slotIndex) {
            return slotOverlapsAtIndex(this.daySlots(day), slotIndex)
        },

        slotIsInvalid(day, slotIndex) {
            const slot = this.daySlots(day)[slotIndex]

            if (! slot) {
                return false
            }

            return slotHasInvalidRange(slot) || this.slotHasOverlap(day, slotIndex)
        },

        dayValidationError(day) {
            if (! this.isDayEnabled(day)) {
                return null
            }

            return validateDaySlots(this.daySlots(day), {
                minSlots: this.minSlots,
                maxSlots: this.maxSlots,
                requireSlots: this.requireSlotsForEnabledDays,
            })
        },

        dayValidationErrorMessage(day) {
            const code = this.dayValidationError(day)

            if (! code) {
                return null
            }

            const templates = this.validationMessages ?? {}
            const template = templates[code]

            if (! template) {
                return code
            }

            const count = code === 'max_slots' ? this.maxSlots : this.minSlots

            return String(template).replace(':count', String(count))
        },

        canRemoveSlot(day) {
            return canRemoveDaySlot({
                slotCount: this.daySlots(day).length,
                minSlots: this.minSlots,
                isEnabled: this.isDayEnabled(day),
                isInteractive: this.isInteractive,
            })
        },

        canAddSlot(day) {
            return this.isInteractive
                && this.isDayEnabled(day)
                && this.daySlots(day).length < this.maxSlots
        },
    }, timezonePicker)
}
