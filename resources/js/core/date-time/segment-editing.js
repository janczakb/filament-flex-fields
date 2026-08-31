import {
    CalendarDate,
    Time,
    compareDateTimeValues,
    getToday,
    parseRangeStoredValue,
} from './format-parse.js'
import {
    buildSegmentsFromValue,
    finalizeSegmentValue,
    getSegmentMaxLength,
    processSegmentInputValue,
    resolveAdjacentSegmentIndex,
    segmentsToCalendarDate,
    segmentsToTime,
} from './segmented-input.js'
import { extractDateValue, extractTimeValue, mergeDateAndTime } from './calendar-grid.js'
import { toGregorianDateString } from './calendar-system.js'

export function createSegmentEditingBehavior() {
    return {
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

            if (this.config.isInvalid === true) {
                this.segmentValidationCode = 'invalid'
                this.segmentInvalid = true

                return
            }

            if (this.isRange) {
                const stored = this.resolveRangeStoredForValidation()
                const hasAny = Boolean(stored.start || stored.end)
                    || this.segmentsHaveValues(this.rangeSegments.start)
                    || this.segmentsHaveValues(this.rangeSegments.end)

                if (! hasAny) {
                    this.segmentValidationCode = null
                    this.segmentInvalid = false

                    return
                }

                this.segmentValidationCode = this.resolveValidationCode(stored)
                this.segmentInvalid = this.segmentValidationCode !== null

                return
            }

            const stored = this.resolveStoredFromCurrentSegments()

            if (stored === false) {
                this.segmentValidationCode = this.segmentsHaveValues(this.segments)
                    ? 'invalid'
                    : null
                this.segmentInvalid = this.segmentValidationCode !== null

                return
            }

            this.segmentValidationCode = this.resolveValidationCode(stored)
            this.segmentInvalid = this.segmentValidationCode !== null
        },

        segmentValidationMessage() {
            if ((this.segmentInvalid || this.config.isInvalid) && this.config.segmentInvalidMessage) {
                return this.config.segmentInvalidMessage
            }

            const code = this.segmentValidationCode
            const messages = this.config.validationMessages ?? {}

            if (code && messages[code]) {
                return messages[code]
            }

            return messages.invalid ?? 'Please enter a valid date or time.'
        },

        resolveValidationCode(stored) {
            if (stored === null) {
                return null
            }

            if (typeof stored === 'object') {
                const start = stored.start
                const end = stored.end
                const hasStart = Boolean(start)
                const hasEnd = Boolean(end)

                if ((hasStart && ! hasEnd) || (! hasStart && hasEnd)) {
                    const partialSegments = [
                        ...(this.segmentsHaveValues(this.rangeSegments?.start ?? {}) ? ['start'] : []),
                        ...(this.segmentsHaveValues(this.rangeSegments?.end ?? {}) ? ['end'] : []),
                    ]

                    if (partialSegments.length > 0 && (! hasStart || ! hasEnd)) {
                        return 'incomplete_range'
                    }
                }

                if (! hasStart || ! hasEnd) {
                    return null
                }

                const startCode = this.resolveValidationCode(start)

                if (startCode) {
                    return startCode
                }

                const endCode = this.resolveValidationCode(end)

                if (endCode) {
                    return endCode
                }

                const parseMode = this.isTimeRange ? 'time' : 'dateTime'
                const startParsed = this.parseConfigStoredValue(start, parseMode)
                const endParsed = this.parseConfigStoredValue(end, parseMode)

                if (startParsed && endParsed && compareDateTimeValues(endParsed, startParsed) < 0) {
                    return 'range_order'
                }

                if (
                    this.mode === 'dateRange'
                    && ! this.config.allowSameDay
                    && start === end
                ) {
                    return 'same_day_not_allowed'
                }

                return null
            }

            const parseMode = this.isTimeRange
                ? 'time'
                : (this.mode === 'dateRange' || this.mode === 'timeRange'
                    ? (this.config.granularity === 'day' ? 'date' : 'dateTime')
                    : (this.mode === 'duration' ? 'duration' : this.mode))

            const parsed = this.parseConfigStoredValue(stored, parseMode)

            if (! parsed && stored) {
                return 'invalid'
            }

            if (this.config.minValue) {
                const min = this.parseConfigStoredValue(this.config.minValue, parseMode)

                if (min && parsed && compareDateTimeValues(parsed, min) < 0) {
                    return 'before_min'
                }
            }

            if (this.config.maxValue) {
                const max = this.parseConfigStoredValue(this.config.maxValue, parseMode)

                if (max && parsed && compareDateTimeValues(parsed, max) > 0) {
                    return 'after_max'
                }
            }

            if (this.config.unavailableDates?.length && parsed instanceof CalendarDate) {
                const iso = toGregorianDateString(parsed)

                if (iso && this.config.unavailableDates.includes(iso)) {
                    return 'unavailable'
                }
            }

            return null
        },

        isStoredWithinConstraints(stored) {
            return this.resolveValidationCode(stored) === null
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
                    ? this.toConfigStoredValue(value, mode)
                    : null
            }

            const value = this.buildValueFromSegments(this.segments)

            if (! value && this.segmentsHaveValues(this.segments)) {
                return false
            }

            return value
                ? this.toConfigStoredValue(value, this.mode)
                : null
        },

        resolveRangeStoredForValidation() {
            const startValue = this.buildValueFromSegments(this.rangeSegments.start)
            const endValue = this.buildValueFromSegments(this.rangeSegments.end)
            const parseMode = this.isTimeRange ? 'time' : 'dateTime'

            return {
                start: startValue
                    ? this.toConfigStoredValue(startValue, parseMode)
                    : null,
                end: endValue
                    ? this.toConfigStoredValue(endValue, parseMode)
                    : null,
            }
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

            this.setStateValue(this.toConfigStoredValue(value, this.mode))
        },

        commitRangeSegments(target) {
            const dateValue = this.buildValueFromSegments(this.rangeSegments[target])
            const raw = parseRangeStoredValue(this.state)

            if (this.isTimeRange) {
                const payload = {
                    start: target === 'start'
                        ? this.toConfigStoredValue(dateValue, 'time')
                        : raw.start,
                    end: target === 'end'
                        ? this.toConfigStoredValue(dateValue, 'time')
                        : raw.end,
                }

                this.setStateValue(payload)
                this.validateSegmentConstraints()

                return
            }

            const merged = this.mergeRangeDateTime(target, dateValue)

            const payload = {
                start: target === 'start'
                    ? this.toConfigStoredValue(merged, 'dateTime')
                    : raw.start,
                end: target === 'end'
                    ? this.toConfigStoredValue(merged, 'dateTime')
                    : raw.end,
            }

            this.setStateValue(payload)
        },

        commitRangeTime(target) {
            if (this.mode === 'dateTime' && target === 'single') {
                const parsed = this.parseConfigStoredValue(this.state, 'dateTime')
                const date = extractDateValue(parsed) || getToday(this.config.timeZone, this.config.calendarIdentifier ?? null)
                const time = segmentsToTime(this.timeSegments.single, this.config.hourCycle, this.config.showSeconds)
                const merged = mergeDateAndTime(date, time, this.config.granularity, this.config.showSeconds)

                this.setStateValue(this.toConfigStoredValue(merged, 'dateTime'))
                this.bootstrapFromState(true)

                return
            }

            const raw = parseRangeStoredValue(this.state)
            const existing = this.parseConfigStoredValue(raw[target], 'dateTime')
            const date = extractDateValue(existing) || getToday(this.config.timeZone, this.config.calendarIdentifier ?? null)
            const time = segmentsToTime(this.timeSegments[target], this.config.hourCycle, this.config.showSeconds)
            const merged = mergeDateAndTime(date, time, this.config.granularity, this.config.showSeconds)

            const payload = {
                start: raw.start,
                end: raw.end,
            }

            payload[target] = this.toConfigStoredValue(merged, 'dateTime')
            this.setStateValue(payload)
            this.bootstrapFromState(true)
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
    }
}
