import {
    CalendarDate,
    Time,
    compareDateTimeValues,
    parseRangeStoredValue,
    parseStoredValue,
    toStoredValue,
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
import { getToday } from './format-parse.js'

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
    }
}
