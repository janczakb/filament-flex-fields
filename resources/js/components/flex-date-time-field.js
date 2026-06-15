import {
    CalendarDate,
    CalendarDateTime,
    Time,
    compareDateTimeValues,
    getSegmentParts,
    getSegmentPlaceholder,
    getSegmentSeparatorAfter,
    getToday,
    parseRangeStoredValue,
    parseStoredValue,
    toCalendarDate,
    toStoredValue,
} from '../core/date-time/format-parse.js'
import {
    addMonths,
    buildCalendarWeeks,
    buildYearRange,
    extractDateValue,
    extractTimeValue,
    getCalendarHeaderLabel,
    getMonthLabel,
    getRangeCellState,
    getShortMonthLabels,
    getWeekdayLabels,
    mergeDateAndTime,
    sameCalendarDate,
    setCalendarMonth,
    setCalendarYear,
    shiftCalendarYear,
    YEARS_PER_PAGE,
} from '../core/date-time/calendar-grid.js'
import {
    buildSegmentsFromValue,
    finalizeSegmentValue,
    getNextSegmentIndex,
    getSegmentMaxLength,
    processSegmentInputValue,
    resolveAdjacentSegmentIndex,
    segmentsToCalendarDate,
    segmentsToTime,
} from '../core/date-time/segmented-input.js'
import { createExclusiveDropdownMixin } from '../core/flex-dropdown-coordinator.js'

const exclusiveDropdown = createExclusiveDropdownMixin({
    openKey: 'calendarOpen',
    closeMethod: 'closeCalendar',
    ownerIdPrefix: 'fff-flex-date-time',
})

export default function flexDateTimeFieldFormComponent({
    state,
    statePath,
    disabled,
    readOnly,
    initialState = null,
    initialDisplay = null,
    initialSegments = null,
    ...config
}) {
    return {
        ...exclusiveDropdown,
        state,
        statePath,
        disabled,
        readOnly,
        initialState,
        initialDisplay,
        config,
        calendarOpen: false,
        calendarReady: false,
        calendarViewMode: 'days',
        activeSegment: null,
        activeRangeTarget: 'start',
        hoveredDate: null,
        visibleMonth: null,
        segments: initialSegments?.single ?? {},
        rangeSegments: initialSegments?.range ?? { start: {}, end: {} },
        timeSegments: { start: {}, end: {} },
        menuScrollHandler: null,
        menuResizeHandler: null,
        isSyncingState: false,
        segmentInvalid: false,

        get isLocked() {
            return this.disabled || this.readOnly
        },

        get mode() {
            return this.config.mode
        },

        get isRange() {
            return this.mode === 'dateRange' || this.mode === 'timeRange'
        },

        get isTimeRange() {
            return this.mode === 'timeRange'
        },

        get segmentParts() {
            return getSegmentParts(this.mode, this.config.granularity, this.config.hourCycle, this.config.showSeconds, this.config.locale, this.config.showYearSegment)
        },

        get hasYearSegment() {
            return this.segmentParts.includes('year')
        },

        get hasTextualMonthSegment() {
            return this.config.monthDisplay === 'short' || this.config.monthDisplay === 'long'
        },

        get segmentContext() {
            return {
                locale: this.config.locale,
                monthDisplay: this.config.monthDisplay ?? 'numeric',
                hourCycle: this.config.hourCycle,
                mode: this.mode,
                forceLeadingZeros: this.config.forceLeadingZeros,
            }
        },

        get isCalendarHeaderDisabled() {
            if (this.calendarViewMode === 'years') {
                return true
            }

            if (this.mode === 'month' && ! this.hasYearSegment) {
                return true
            }

            return false
        },

        get timeSegmentParts() {
            return getSegmentParts('time', this.config.granularity, this.config.hourCycle, this.config.showSeconds, this.config.locale)
        },

        get showTimeUnderCalendar() {
            if (this.config.hideTimeSection || this.config.granularity === 'day') {
                return false
            }

            return this.isRange || this.mode === 'dateTime'
        },

        get weekdayLabels() {
            return getWeekdayLabels(this.config.firstDayOfWeek, this.config.locale)
        },

        get monthLabel() {
            return this.visibleMonth
                ? getMonthLabel(this.visibleMonth, this.config.locale)
                : ''
        },

        get calendarHeaderLabel() {
            return this.visibleMonth
                ? getCalendarHeaderLabel(this.calendarViewMode, this.visibleMonth, this.config.locale)
                : ''
        },

        get monthOptions() {
            return getShortMonthLabels(this.config.locale).map((label, index) => ({
                value: index + 1,
                label,
            }))
        },

        get yearOptions() {
            if (! this.visibleMonth) {
                return []
            }

            return buildYearRange(this.visibleMonth.year)
        },

        get calendarWeeks() {
            if (! this.visibleMonth) {
                return []
            }

            return buildCalendarWeeks(this.visibleMonth, this.config.firstDayOfWeek)
        },

        get displayText() {
            if (this.isRange) {
                const start = this.formatDisplayValue(this.rangeValue.start)
                const end = this.formatDisplayValue(this.rangeValue.end)

                if (! start && ! end) {
                    return ''
                }

                if (start && end) {
                    return `${start}${this.config.rangeSeparator}${end}`
                }

                return start || end
            }

            return this.formatDisplayValue(this.parsedValue)
        },

        get isEmpty() {
            if (this.isRange) {
                return ! this.rangeValue.start && ! this.rangeValue.end
            }

            return ! this.state
        },

        get parsedValue() {
            if (this.isRange) {
                return null
            }

            return parseStoredValue(this.state, this.mode, this.config.granularity, this.config.timeZone)
        },

        get rangeValue() {
            const raw = parseRangeStoredValue(this.state)

            if (this.isTimeRange) {
                return {
                    start: raw.start ? parseStoredValue(raw.start, 'time', this.config.granularity, this.config.timeZone) : null,
                    end: raw.end ? parseStoredValue(raw.end, 'time', this.config.granularity, this.config.timeZone) : null,
                }
            }

            return {
                start: raw.start ? parseStoredValue(raw.start, 'dateTime', this.config.granularity, this.config.timeZone) : null,
                end: raw.end ? parseStoredValue(raw.end, 'dateTime', this.config.granularity, this.config.timeZone) : null,
            }
        },

        segmentsHaveValues(segments) {
            return Object.values(segments ?? {}).some((value) => String(value ?? '').trim() !== '')
        },

        init() {
            this.wireExclusiveFlexDropdown()

            if (this.hasHydratedState()) {
                this.bootstrapFromState()
            }

            this.visibleMonth = this.resolveVisibleMonth()

            this.$watch('state', () => {
                if (this.isSyncingState) {
                    return
                }

                this.bootstrapFromState()
            })

            this.$watch('calendarOpen', (open) => {
                if (open) {
                    this.scheduleCalendarPosition()
                    this.bindCalendarListeners()

                    return
                }

                this.calendarReady = false
                this.unbindCalendarListeners()
            })
        },

        hasHydratedState() {
            if (this.isRange) {
                const raw = parseRangeStoredValue(this.state)

                return Boolean(raw.start || raw.end)
            }

            return Boolean(this.state)
        },

        bootstrapFromState(resetSegments = true) {
            if (this.isRange) {
                const raw = parseRangeStoredValue(this.state)
                const hasState = Boolean(raw.start || raw.end)
                const parseMode = this.isTimeRange ? 'time' : 'dateTime'

                if (resetSegments) {
                    const startSegments = buildSegmentsFromValue(
                        raw.start ? parseStoredValue(raw.start, parseMode, this.config.granularity, this.config.timeZone) : null,
                        this.segmentParts,
                        this.config.locale,
                        this.config.hourCycle,
                        this.config.forceLeadingZeros,
                        this.config.monthDisplay,
                    )
                    const endSegments = buildSegmentsFromValue(
                        raw.end ? parseStoredValue(raw.end, parseMode, this.config.granularity, this.config.timeZone) : null,
                        this.segmentParts,
                        this.config.locale,
                        this.config.hourCycle,
                        this.config.forceLeadingZeros,
                        this.config.monthDisplay,
                    )

                    if (hasState || ! this.segmentsHaveValues(this.rangeSegments.start)) {
                        this.rangeSegments.start = startSegments
                    }

                    if (hasState || ! this.segmentsHaveValues(this.rangeSegments.end)) {
                        this.rangeSegments.end = endSegments
                    }

                    if (this.showTimeUnderCalendar) {
                        if (hasState || ! this.segmentsHaveValues(this.timeSegments.start)) {
                            this.timeSegments.start = buildSegmentsFromValue(
                                raw.start ? extractTimeValue(parseStoredValue(raw.start, 'dateTime', this.config.granularity, this.config.timeZone)) : null,
                                this.timeSegmentParts,
                                this.config.locale,
                                this.config.hourCycle,
                                this.config.forceLeadingZeros,
                                this.config.monthDisplay,
                            )
                        }

                        if (hasState || ! this.segmentsHaveValues(this.timeSegments.end)) {
                            this.timeSegments.end = buildSegmentsFromValue(
                                raw.end ? extractTimeValue(parseStoredValue(raw.end, 'dateTime', this.config.granularity, this.config.timeZone)) : null,
                                this.timeSegmentParts,
                                this.config.locale,
                                this.config.hourCycle,
                                this.config.forceLeadingZeros,
                                this.config.monthDisplay,
                            )
                        }
                    }
                }

                return
            }

            const parsed = parseStoredValue(this.state, this.mode, this.config.granularity, this.config.timeZone)

            if (resetSegments) {
                if (! parsed && this.segmentsHaveValues(this.segments) && ! this.state) {
                    return
                }

                this.segments = buildSegmentsFromValue(
                    parsed,
                    this.segmentParts,
                    this.config.locale,
                    this.config.hourCycle,
                    this.config.forceLeadingZeros,
                    this.config.monthDisplay,
                )
            }
        },

        resolveVisibleMonth() {
            const source = this.isRange
                ? (this.rangeValue.start || this.rangeValue.end)
                : this.parsedValue

            if (source) {
                return toCalendarDate(source)
            }

            return getToday(this.config.timeZone)
        },

        formatDisplayValue(value) {
            if (! value) {
                return ''
            }

            if (value instanceof Time) {
                return this.segmentParts
                    .map((part) => buildSegmentsFromValue(value, [part], this.config.locale, this.config.hourCycle, this.config.forceLeadingZeros, this.config.monthDisplay)[part])
                    .filter(Boolean)
                    .join(':')
                    .replace(/:AM|:PM/g, (match) => ` ${match.slice(1)}`)
            }

            const segments = buildSegmentsFromValue(value, this.segmentParts, this.config.locale, this.config.hourCycle, this.config.forceLeadingZeros, this.config.monthDisplay)

            if (this.mode === 'time') {
                return this.segmentParts
                    .map((part) => segments[part])
                    .filter(Boolean)
                    .join(':')
                    .replace(/:AM|:PM/g, (match) => ` ${match.slice(1)}`)
            }

            const dateParts = ['month', 'day', 'year']
                .map((part) => segments[part])
                .filter(Boolean)

            if (dateParts.length < 3) {
                return ''
            }

            let display = `${dateParts[0]}/${dateParts[1]}/${dateParts[2]}`

            if ('hour' in value) {
                const timeParts = ['hour', 'minute', 'second', 'dayPeriod']
                    .filter((part) => this.segmentParts.includes(part))
                    .map((part) => segments[part])
                    .filter(Boolean)

                if (timeParts.length) {
                    display += ` ${timeParts.join(':').replace(/:AM|:PM/g, (match) => ` ${match.slice(1)}`)}`
                }
            }

            return display
        },

        placeSegmentCaret(input, position = 'end') {
            if (! input?.setSelectionRange) {
                return
            }

            const length = String(input.value ?? '').length
            const caret = position === 'start' ? 0 : length

            input.setSelectionRange(caret, caret)
        },

        finishSegmentEditing(input = null) {
            this.activeSegment = null

            const target = input ?? this.$root.querySelector(':focus')

            target?.blur()
        },

        focusSegment(index, rangeTarget = null) {
            if (rangeTarget) {
                this.activeRangeTarget = rangeTarget
            }

            this.activeSegment = index

            this.$nextTick(() => {
                const part = this.segmentParts[index]
                const selector = this.isRange
                    ? `[data-range-target="${this.activeRangeTarget}"] [data-segment-part="${part}"]`
                    : `[data-segment-part="${part}"]`

                const input = this.$root.querySelector(selector)

                input?.focus()
                this.placeSegmentCaret(input)
            })
        },

        resolveRangeTargetFromInput(input) {
            return input?.closest('[data-range-target]')?.dataset?.rangeTarget ?? null
        },

        onSegmentFocus(index, rangeTarget = null, event = null) {
            if (rangeTarget) {
                this.activeRangeTarget = rangeTarget
            } else if (event?.target) {
                const resolvedTarget = this.resolveRangeTargetFromInput(event.target)

                if (resolvedTarget) {
                    this.activeRangeTarget = resolvedTarget
                }
            }

            this.activeSegment = index

            const input = event?.target

            if (input) {
                this.$nextTick(() => this.placeSegmentCaret(input))
            }
        },

        onSegmentBlur(event = null) {
            this.finalizeSegmentFromEvent(event, () => {
                this.commitSegments()
                this.validateSegmentConstraints()
            })

            this.$nextTick(() => {
                if (! this.$root.contains(document.activeElement)) {
                    this.activeSegment = null
                }
            })
        },

        validateSegmentConstraints() {
            if (this.isLocked) {
                return
            }

            const stored = this.resolveStoredFromCurrentSegments()

            if (stored === false) {
                this.segmentInvalid = this.segmentsHaveValues(this.isRange ? this.rangeSegments[this.activeRangeTarget] : this.segments)

                return
            }

            this.segmentInvalid = stored !== null && ! this.isStoredWithinConstraints(stored)
        },

        resolveStoredFromCurrentSegments() {
            if (this.isRange) {
                const segments = this.rangeSegments[this.activeRangeTarget]
                const value = this.buildValueFromSegments(segments)

                if (! value && this.segmentsHaveValues(segments)) {
                    return false
                }

                const mode = this.isTimeRange ? 'time' : 'dateTime'

                return value
                    ? toStoredValue(value, mode, this.config.granularity, this.config.showSeconds, this.config.storageFormat)
                    : null
            }

            const value = this.buildValueFromSegments(this.segments)

            if (! value && this.segmentsHaveValues(this.segments)) {
                return false
            }

            return value
                ? toStoredValue(value, this.mode, this.config.granularity, this.config.showSeconds, this.config.storageFormat)
                : null
        },

        isStoredWithinConstraints(stored) {
            if (stored === null) {
                return true
            }

            if (typeof stored === 'object') {
                const start = stored.start
                const end = stored.end

                return (! start || this.isStoredWithinConstraints(start))
                    && (! end || this.isStoredWithinConstraints(end))
            }

            const parsed = parseStoredValue(stored, this.mode === 'duration' ? 'duration' : this.mode, this.config.granularity, this.config.timeZone)

            if (! parsed && stored) {
                return false
            }

            if (this.config.minValue) {
                const min = parseStoredValue(this.config.minValue, this.mode, this.config.granularity, this.config.timeZone)

                if (min && parsed && compareDateTimeValues(parsed, min) < 0) {
                    return false
                }
            }

            if (this.config.maxValue) {
                const max = parseStoredValue(this.config.maxValue, this.mode, this.config.granularity, this.config.timeZone)

                if (max && parsed && compareDateTimeValues(parsed, max) > 0) {
                    return false
                }
            }

            if (this.config.unavailableDates?.length && parsed instanceof CalendarDate) {
                const iso = `${String(parsed.year).padStart(4, '0')}-${String(parsed.month).padStart(2, '0')}-${String(parsed.day).padStart(2, '0')}`

                if (this.config.unavailableDates.includes(iso)) {
                    return false
                }
            }

            return true
        },

        onSegmentInput(part, event) {
            if (this.isLocked) {
                return
            }

            const rangeTarget = this.resolveRangeTargetFromInput(event.target)

            if (rangeTarget) {
                this.activeRangeTarget = rangeTarget
            }

            const container = this.isRange
                ? this.rangeSegments[this.activeRangeTarget]
                : this.segments
            const previousValue = container[part] ?? ''
            const value = processSegmentInputValue(part, previousValue, event.target.value, this.config.hourCycle, this.mode, this.segmentContext)

            if (event.target.value !== value) {
                const caret = Math.min(event.target.selectionStart ?? value.length, value.length)
                event.target.value = value
                event.target.setSelectionRange(caret, caret)
            }

            container[part] = value
            this.commitSegments()

            if (value.length >= getSegmentMaxLength(part, this.mode, this.segmentContext) && part !== 'dayPeriod') {
                const index = this.segmentParts.indexOf(part)
                const nextIndex = resolveAdjacentSegmentIndex(this.segmentParts, index)

                this.$nextTick(() => {
                    if (nextIndex === null) {
                        this.finishSegmentEditing(event.target)
                    } else {
                        this.focusSegment(nextIndex, rangeTarget)
                    }
                })
            }
        },

        onSegmentKeydown(part, event) {
            const index = this.segmentParts.indexOf(part)
            const rangeTarget = this.resolveRangeTargetFromInput(event.target)

            this.handleSegmentKeydown(event, this.segmentParts, index, (nextIndex) => {
                this.focusSegment(nextIndex, rangeTarget)
            }, (input) => {
                this.finishSegmentEditing(input)
            })

            if (event.key === 'Enter') {
                event.preventDefault()
                this.commitSegments()
            }
        },

        onTimeSegmentFocus(target, part, event) {
            this.activeRangeTarget = target

            const input = event?.target

            if (input) {
                this.$nextTick(() => this.placeSegmentCaret(input))
            }
        },

        onTimeSegmentBlur(target, part, event) {
            this.finalizeTimeSegment(target, part, () => {
                this.commitRangeTime(target)
            })
        },

        onTimeSegmentInput(target, part, event) {
            if (this.isLocked) {
                return
            }

            const previousValue = this.timeSegments[target][part] ?? ''
            const value = processSegmentInputValue(part, previousValue, event.target.value, this.config.hourCycle, 'time')

            this.timeSegments[target][part] = value
            this.commitRangeTime(target)

            if (value.length >= getSegmentMaxLength(part)) {
                const index = this.timeSegmentParts.indexOf(part)
                const nextIndex = resolveAdjacentSegmentIndex(this.timeSegmentParts, index)

                this.$nextTick(() => {
                    if (nextIndex === null) {
                        this.finishSegmentEditing(event.target)
                    } else {
                        this.focusTimeSegment(target, nextIndex)
                    }
                })
            }
        },

        onTimeSegmentKeydown(target, part, event) {
            const index = this.timeSegmentParts.indexOf(part)

            this.handleSegmentKeydown(event, this.timeSegmentParts, index, (nextIndex) => {
                this.focusTimeSegment(target, nextIndex)
            }, (input) => {
                this.finishSegmentEditing(input)
            })
        },

        finalizeSegmentFromEvent(event, commit) {
            if (this.isLocked) {
                return
            }

            const part = event?.target?.dataset?.segmentPart

            if (! part) {
                return
            }

            const rangeTarget = this.resolveRangeTargetFromInput(event.target)

            if (rangeTarget) {
                this.activeRangeTarget = rangeTarget
            }

            const container = this.isRange
                ? this.rangeSegments[this.activeRangeTarget]
                : this.segments
            const current = container[part] ?? ''

            if (current === '') {
                return
            }

            const finalized = finalizeSegmentValue(part, current, this.config.hourCycle, this.mode, this.segmentContext)

            if (finalized !== current) {
                container[part] = finalized
                commit()
            }
        },

        finalizeTimeSegment(target, part, commit) {
            if (this.isLocked) {
                return
            }

            const current = this.timeSegments[target][part] ?? ''

            if (current === '') {
                return
            }

            const finalized = finalizeSegmentValue(part, current, this.config.hourCycle, 'time')

            if (finalized !== current) {
                this.timeSegments[target][part] = finalized
                commit()
            }
        },

        handleSegmentKeydown(event, parts, index, focusSegmentAtIndex, finishEditing = null) {
            const input = event.target
            const value = input.value ?? ''
            const start = input.selectionStart ?? 0
            const end = input.selectionEnd ?? 0

            if (event.key === 'ArrowRight' || event.key === '/') {
                if (event.key === 'ArrowRight' && end < value.length) {
                    return
                }

                if (event.key === '/' && end < value.length) {
                    return
                }

                event.preventDefault()

                const nextIndex = resolveAdjacentSegmentIndex(parts, index)

                if (nextIndex === null) {
                    finishEditing?.(input)

                    return
                }

                focusSegmentAtIndex(nextIndex)

                return
            }

            if (event.key === 'ArrowLeft') {
                if (start > 0) {
                    return
                }

                event.preventDefault()

                const previousIndex = resolveAdjacentSegmentIndex(parts, index, true)

                if (previousIndex === null) {
                    return
                }

                focusSegmentAtIndex(previousIndex)
            }
        },

        focusTimeSegment(target, index) {
            const part = this.timeSegmentParts[index]

            this.$nextTick(() => {
                const input = this.$root.querySelector(`[data-time-target="${target}"] [data-segment-part="${part}"]`)

                input?.focus()
                this.placeSegmentCaret(input)
            })
        },

        setStateValue(value) {
            this.isSyncingState = true
            this.state = value

            this.$nextTick(() => {
                this.isSyncingState = false
            })
        },

        commitSegments() {
            if (this.isRange) {
                this.commitRangeSegments(this.activeRangeTarget)

                return
            }

            const value = this.buildValueFromSegments(this.segments)

            this.setStateValue(toStoredValue(value, this.mode, this.config.granularity, this.config.showSeconds, this.config.storageFormat))
        },

        commitRangeSegments(target) {
            const dateValue = this.buildValueFromSegments(this.rangeSegments[target])
            const raw = parseRangeStoredValue(this.state)

            if (this.isTimeRange) {
                const payload = {
                    start: target === 'start'
                        ? toStoredValue(dateValue, 'time', this.config.granularity, this.config.showSeconds, this.config.storageFormat)
                        : raw.start,
                    end: target === 'end'
                        ? toStoredValue(dateValue, 'time', this.config.granularity, this.config.showSeconds, this.config.storageFormat)
                        : raw.end,
                }

                this.setStateValue(payload)
                this.validateSegmentConstraints()

                return
            }

            const merged = this.mergeRangeDateTime(target, dateValue)

            const payload = {
                start: target === 'start'
                    ? toStoredValue(merged, 'dateTime', this.config.granularity, this.config.showSeconds, this.config.storageFormat)
                    : raw.start,
                end: target === 'end'
                    ? toStoredValue(merged, 'dateTime', this.config.granularity, this.config.showSeconds, this.config.storageFormat)
                    : raw.end,
            }

            this.setStateValue(payload)
        },

        commitRangeTime(target) {
            const raw = parseRangeStoredValue(this.state)
            const existing = parseStoredValue(raw[target], 'dateTime', this.config.granularity, this.config.timeZone)
            const date = extractDateValue(existing) || getToday(this.config.timeZone)
            const time = segmentsToTime(this.timeSegments[target], this.config.hourCycle, this.config.showSeconds)
            const merged = mergeDateAndTime(date, time, this.config.granularity, this.config.showSeconds)

            const payload = {
                start: raw.start,
                end: raw.end,
            }

            payload[target] = toStoredValue(merged, 'dateTime', this.config.granularity, this.config.showSeconds, this.config.storageFormat)
            this.setStateValue(payload)
        },

        mergeRangeDateTime(target, dateValue) {
            if (! this.showTimeUnderCalendar) {
                return dateValue
            }

            const time = segmentsToTime(this.timeSegments[target], this.config.hourCycle, this.config.showSeconds)

            return mergeDateAndTime(dateValue, time, this.config.granularity, this.config.showSeconds)
        },

        buildValueFromSegments(segments) {
            if (this.mode === 'time' || this.mode === 'duration' || this.isTimeRange) {
                const hourCycle = this.isTimeRange || this.mode === 'duration' ? 24 : this.config.hourCycle
                const time = segmentsToTime(segments, hourCycle, this.config.showSeconds)

                return time ? new Time(time.hour, time.minute, time.second) : null
            }

            const calendarMode = this.mode === 'month' ? 'month' : (this.mode === 'year' ? 'year' : 'date')
            const dateParts = segmentsToCalendarDate(segments, calendarMode, this.config.locale, this.config.monthDisplay)

            if (! dateParts) {
                return null
            }

            const date = new CalendarDate(dateParts.year, dateParts.month, dateParts.day)

            if (this.mode === 'date' || this.mode === 'month' || this.mode === 'year' || this.config.granularity === 'day') {
                return date
            }

            const time = segmentsToTime(segments, this.config.hourCycle, this.config.showSeconds)

            return mergeDateAndTime(date, time, this.config.granularity, this.config.showSeconds)
        },

        toggleCalendar() {
            if (this.isLocked || ! this.config.showCalendar) {
                return
            }

            this.calendarOpen = ! this.calendarOpen
            this.visibleMonth = this.resolveVisibleMonth()
            this.calendarViewMode = this.resolveCalendarViewMode()
        },

        closeCalendar() {
            this.calendarOpen = false
            this.hoveredDate = null
            this.calendarViewMode = this.resolveCalendarViewMode()
        },

        resolveCalendarViewMode() {
            if (this.mode === 'month') {
                return this.hasYearSegment ? 'years' : 'months'
            }

            if (this.mode === 'year') {
                return 'years'
            }

            return 'days'
        },

        selectDate(date) {
            if (this.isLocked || ! date || this.isDateDisabled(date)) {
                return
            }

            if (this.isRange) {
                this.selectRangeDate(date)

                return
            }

            const merged = this.config.granularity === 'day'
                ? date
                : mergeDateAndTime(
                    date,
                    this.showTimeUnderCalendar
                        ? (segmentsToTime(this.segments, this.config.hourCycle, this.config.showSeconds) || new Time(0, 0, 0))
                        : (extractTimeValue(this.parsedValue) || new Time(0, 0, 0)),
                    this.config.granularity,
                    this.config.showSeconds,
                )

            this.setStateValue(toStoredValue(merged, this.mode, this.config.granularity, this.config.showSeconds, this.config.storageFormat))
            this.bootstrapFromState()
            this.visibleMonth = toCalendarDate(merged)

            if (this.config.closeOnSelect) {
                this.closeCalendar()
            }
        },

        selectRangeDate(date) {
            const raw = parseRangeStoredValue(this.state)
            let start = raw.start ? parseStoredValue(raw.start, 'dateTime', this.config.granularity, this.config.timeZone) : null
            let end = raw.end ? parseStoredValue(raw.end, 'dateTime', this.config.granularity, this.config.timeZone) : null

            if (! start || (start && end)) {
                start = mergeDateAndTime(date, extractTimeValue(start) || new Time(0, 0, 0), this.config.granularity, this.config.showSeconds)
                end = null
                this.activeRangeTarget = 'end'
            } else {
                const candidate = mergeDateAndTime(date, extractTimeValue(end) || new Time(23, 59, this.config.showSeconds ? 59 : 0), this.config.granularity, this.config.showSeconds)

                if (compareDateTimeValues(candidate, start) < 0) {
                    end = start
                    start = candidate
                } else {
                    end = candidate
                }

                if (this.config.closeOnSelect) {
                    this.closeCalendar()
                }
            }

            this.setStateValue({
                start: toStoredValue(start, 'dateTime', this.config.granularity, this.config.showSeconds, this.config.storageFormat),
                end: end
                    ? toStoredValue(end, 'dateTime', this.config.granularity, this.config.showSeconds, this.config.storageFormat)
                    : null,
            })

            this.bootstrapFromState()
        },

        isDateDisabled(date) {
            if (! date) {
                return true
            }

            const min = this.config.minValue
                ? parseStoredValue(this.config.minValue, 'date', 'day', this.config.timeZone)
                : null
            const max = this.config.maxValue
                ? parseStoredValue(this.config.maxValue, 'date', 'day', this.config.timeZone)
                : null

            if (min && compareDateTimeValues(toCalendarDate(date), min) < 0) {
                return true
            }

            if (max && compareDateTimeValues(toCalendarDate(date), max) > 0) {
                return true
            }

            const iso = `${String(date.year).padStart(4, '0')}-${String(date.month).padStart(2, '0')}-${String(date.day).padStart(2, '0')}`

            if (Array.isArray(this.config.unavailableDates) && this.config.unavailableDates.includes(iso)) {
                return true
            }

            return false
        },

        isToday(date) {
            if (! date || ! this.config.highlightToday) {
                return false
            }

            return sameCalendarDate(date, getToday(this.config.timeZone))
        },

        getDayCellClass(date) {
            if (! date) {
                return 'is-outside'
            }

            const selected = this.isRange
                ? getRangeCellState(date, extractDateValue(this.rangeValue.start), extractDateValue(this.rangeValue.end), this.hoveredDate)
                : {
                    'is-selected': this.parsedValue && sameCalendarDate(toCalendarDate(this.parsedValue), date),
                }

            return {
                ...selected,
                'is-today': this.isToday(date),
                'is-disabled': this.isDateDisabled(date),
            }
        },

        previousMonth() {
            if (! this.visibleMonth) {
                return
            }

            if (this.calendarViewMode === 'days') {
                this.visibleMonth = addMonths(this.visibleMonth, -1)

                return
            }

            if (this.calendarViewMode === 'months') {
                this.visibleMonth = shiftCalendarYear(this.visibleMonth, -1)

                return
            }

            this.visibleMonth = shiftCalendarYear(this.visibleMonth, -YEARS_PER_PAGE)
        },

        nextMonth() {
            if (! this.visibleMonth) {
                return
            }

            if (this.calendarViewMode === 'days') {
                this.visibleMonth = addMonths(this.visibleMonth, 1)

                return
            }

            if (this.calendarViewMode === 'months') {
                this.visibleMonth = shiftCalendarYear(this.visibleMonth, 1)

                return
            }

            this.visibleMonth = shiftCalendarYear(this.visibleMonth, YEARS_PER_PAGE)
        },

        onCalendarHeaderClick() {
            if (this.mode === 'month') {
                if (! this.hasYearSegment || this.calendarViewMode !== 'months') {
                    return
                }

                this.calendarViewMode = 'years'

                return
            }

            if (this.calendarViewMode === 'days') {
                this.calendarViewMode = 'months'

                return
            }

            if (this.calendarViewMode === 'months') {
                this.calendarViewMode = 'years'
            }
        },

        selectCalendarMonth(month) {
            if (! this.visibleMonth) {
                return
            }

            this.visibleMonth = setCalendarMonth(this.visibleMonth, month)

            if (this.mode === 'month') {
                this.setStateValue(toStoredValue(this.visibleMonth, 'month', this.config.granularity, false, this.config.storageFormat))
                this.bootstrapFromState()

                if (this.config.closeOnSelect) {
                    this.closeCalendar()
                }

                return
            }

            this.calendarViewMode = 'days'
        },

        selectCalendarYear(year) {
            if (! this.visibleMonth) {
                return
            }

            this.visibleMonth = setCalendarYear(this.visibleMonth, year)

            if (this.mode === 'year') {
                this.setStateValue(toStoredValue(this.visibleMonth, 'year', this.config.granularity, false, this.config.storageFormat))
                this.bootstrapFromState()

                if (this.config.closeOnSelect) {
                    this.closeCalendar()
                }

                return
            }

            this.calendarViewMode = 'months'
        },

        isSelectedCalendarMonth(month) {
            const parsed = this.parsedValue

            if (parsed) {
                const date = extractDateValue(parsed)

                return date?.month === month && date?.year === this.visibleMonth?.year
            }

            return this.visibleMonth?.month === month
        },

        isSelectedCalendarYear(year) {
            const parsed = this.parsedValue

            if (parsed) {
                return extractDateValue(parsed)?.year === year
            }

            return this.visibleMonth?.year === year
        },

        segmentPlaceholder(part) {
            return getSegmentPlaceholder(part, this.config.locale, this.config.monthDisplay)
        },

        segmentMaxLength(part) {
            return getSegmentMaxLength(part, this.mode, this.segmentContext)
        },

        segmentInputMode(part) {
            if (part === 'dayPeriod' || (part === 'month' && this.hasTextualMonthSegment)) {
                return 'text'
            }

            return 'numeric'
        },

        segmentSeparatorAfter(part, parts) {
            return getSegmentSeparatorAfter(part, parts)
        },

        scheduleCalendarPosition() {
            this.calendarReady = false

            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    this.updateCalendarPosition()

                    requestAnimationFrame(() => {
                        this.updateCalendarPosition()
                    })
                })
            })
        },

        updateCalendarPosition() {
            const trigger = this.$refs.calendarTrigger || this.$refs.fieldShell
            const menu = this.$refs.calendarMenu

            if (! trigger || ! menu) {
                return
            }

            this.applyCalendarTheme(menu)

            const rect = trigger.getBoundingClientRect()
            const gap = 6
            const viewportPadding = 16
            const menuWidth = Math.min(Math.max(rect.width, 320), window.innerWidth - (viewportPadding * 2))

            let top = rect.bottom + gap
            let left = rect.left

            menu.style.position = 'fixed'
            menu.style.width = `${Math.round(menuWidth)}px`
            menu.style.zIndex = '80'
            menu.style.top = `${Math.round(top)}px`
            menu.style.left = `${Math.round(left)}px`

            const menuRect = menu.getBoundingClientRect()

            if (menuRect.bottom > window.innerHeight - viewportPadding) {
                const aboveTop = rect.top - menuRect.height - gap

                if (aboveTop >= viewportPadding) {
                    top = aboveTop
                }
            }

            if (left + menuRect.width > window.innerWidth - viewportPadding) {
                left = window.innerWidth - menuRect.width - viewportPadding
            }

            if (left < viewportPadding) {
                left = viewportPadding
            }

            menu.style.top = `${Math.round(top)}px`
            menu.style.left = `${Math.round(left)}px`
            this.calendarReady = true
        },

        applyCalendarTheme(menu) {
            const isDark = document.documentElement.classList.contains('dark')
            const blur = 'blur(16px) saturate(180%)'

            if (isDark) {
                menu.style.setProperty('--fff-date-time-menu-bg', '#27272a3d')
                menu.style.setProperty('--fff-date-time-menu-border', 'rgb(255 255 255 / 0.12)')
                menu.style.setProperty('--fff-date-time-menu-shadow', '0 4px 6px -1px rgb(0 0 0 / 0.28), 0 12px 28px -6px rgb(0 0 0 / 0.5)')
                menu.style.setProperty('--fff-date-time-time-track-bg', 'rgb(63 63 70 / 0.5)')
                menu.style.setProperty('--fff-date-time-time-text', 'rgb(244 244 245)')
                menu.style.setProperty('--fff-date-time-muted', 'rgb(161 161 170)')
            } else {
                menu.style.setProperty('--fff-date-time-menu-bg', '#ffffffa3')
                menu.style.setProperty('--fff-date-time-menu-border', 'rgb(228 228 231 / 0.65)')
                menu.style.setProperty('--fff-date-time-menu-shadow', '0 4px 6px -1px rgb(0 0 0 / 0.06), 0 12px 28px -6px rgb(0 0 0 / 0.12)')
                menu.style.setProperty('--fff-date-time-time-track-bg', 'rgb(244 244 245 / 0.8)')
                menu.style.setProperty('--fff-date-time-time-text', 'rgb(24 24 27)')
                menu.style.setProperty('--fff-date-time-muted', 'rgb(113 113 122)')
            }

            menu.style.backgroundColor = isDark ? '#27272a3d' : '#ffffffa3'
            menu.style.setProperty('backdrop-filter', blur)
            menu.style.setProperty('-webkit-backdrop-filter', blur)
        },

        bindCalendarListeners() {
            if (this.menuScrollHandler) {
                return
            }

            this.menuScrollHandler = () => this.updateCalendarPosition()
            this.menuResizeHandler = () => this.updateCalendarPosition()

            window.addEventListener('scroll', this.menuScrollHandler, true)
            window.addEventListener('resize', this.menuResizeHandler)
        },

        unbindCalendarListeners() {
            if (! this.menuScrollHandler) {
                return
            }

            window.removeEventListener('scroll', this.menuScrollHandler, true)
            window.removeEventListener('resize', this.menuResizeHandler)

            this.menuScrollHandler = null
            this.menuResizeHandler = null
        },
    }
}
