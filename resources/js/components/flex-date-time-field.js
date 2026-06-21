import {
    CalendarDate,
    CalendarDateTime,
    Time,
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
    extractDateValue,
    extractTimeValue,
} from '../core/date-time/calendar-grid.js'
import {
    buildSegmentsFromValue,
    getSegmentMaxLength,
} from '../core/date-time/segmented-input.js'
import { createExclusiveDropdownMixin } from '../core/flex-dropdown-coordinator.js'
import { createCalendarInteractionsBehavior } from '../core/date-time/calendar-interactions.js'
import { loadCalendarPanelModule } from '../core/date-time/calendar-panel-loader.js'
import { createSegmentEditingBehavior } from '../core/date-time/segment-editing.js'

const exclusiveDropdown = createExclusiveDropdownMixin({
    openKey: 'calendarOpen',
    closeMethod: 'closeCalendar',
    ownerIdPrefix: 'fff-flex-date-time',
})

let timePanelModule = null
let timePanelLoading = null

function loadTimePanelModule() {
    if (! timePanelLoading) {
        timePanelLoading = import('../core/date-time/time-panel.js')
    }

    return timePanelLoading
}

const timePanelStubs = {
    onTimeSegmentFocus() {},
    onTimeSegmentBlur() {},
    onTimeSegmentInput() {},
    onTimeSegmentKeydown() {},
    finalizeTimeSegment() {},
    focusTimeSegment() {},
    commitRangeTime() {},
    mergeRangeDateTime(target, dateValue) {
        return dateValue
    },
}

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
        ...createSegmentEditingBehavior(),
        ...createCalendarInteractionsBehavior(),
        ...timePanelStubs,
        state,
        statePath,
        disabled,
        readOnly,
        initialState,
        initialDisplay,
        config,
        calendarOpen: false,
        calendarReady: false,
        calendarPanel: null,
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
        timePanelReady: false,

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
            return this.calendarPanel?.getWeekdayLabels(this.config.firstDayOfWeek, this.config.locale) ?? []
        },

        get monthLabel() {
            return this.visibleMonth && this.calendarPanel
                ? this.calendarPanel.getMonthLabel(this.visibleMonth, this.config.locale)
                : ''
        },

        get calendarHeaderLabel() {
            return this.visibleMonth && this.calendarPanel
                ? this.calendarPanel.getCalendarHeaderLabel(this.calendarViewMode, this.visibleMonth, this.config.locale)
                : ''
        },

        get monthOptions() {
            if (! this.calendarPanel) {
                return []
            }

            return this.calendarPanel.getShortMonthLabels(this.config.locale).map((label, index) => ({
                value: index + 1,
                label,
            }))
        },

        get yearOptions() {
            if (! this.visibleMonth || ! this.calendarPanel) {
                return []
            }

            return this.calendarPanel.buildYearRange(this.visibleMonth.year)
        },

        get calendarWeeks() {
            if (! this.visibleMonth || ! this.calendarPanel) {
                return []
            }

            return this.calendarPanel.buildCalendarWeeks(this.visibleMonth, this.config.firstDayOfWeek)
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

        async ensureTimePanel() {
            if (this.timePanelReady) {
                return
            }

            const module = await loadTimePanelModule()

            if (! timePanelModule) {
                timePanelModule = module
            }

            Object.assign(this, module.createTimePanelBehavior())
            this.timePanelReady = true
        },

        init() {
            this.wireExclusiveFlexDropdown()

            if (this.hasHydratedState()) {
                this.bootstrapFromState()
            }

            this.visibleMonth = this.resolveVisibleMonth()

            if (this.showTimeUnderCalendar) {
                loadTimePanelModule().then(() => this.ensureTimePanel())
            }

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
                const hasState = Boolean(this.state)

                if (! hasState && this.segmentsHaveValues(this.segments)) {
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
    }
}

export { loadCalendarPanelModule, loadTimePanelModule }
