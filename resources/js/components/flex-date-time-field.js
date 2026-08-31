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
import { buildScrollableYearRange } from '../core/date-time/calendar-panel.js'
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
        timeSegments: { start: {}, end: {}, single: {} },
        menuScrollHandler: null,
        menuResizeHandler: null,
        isSyncingState: false,
        segmentInvalid: false,
        segmentValidationCode: null,
        timePanelReady: false,
        displayReady: false,
        yearPickerOpen: false,
        yearPickerOverlayStyle: '',
        yearGridWheelCleanups: [],

        parseConfigStoredValue(value, mode, granularity = this.config.granularity) {
            return parseStoredValue(
                value,
                mode,
                granularity,
                this.config.timeZone,
                this.config.calendarIdentifier ?? null,
            )
        },

        toConfigStoredValue(value, mode, granularity = this.config.granularity) {
            return toStoredValue(
                value,
                mode,
                granularity,
                this.config.showSeconds,
                this.config.storageFormat,
                this.config.calendarIdentifier ?? null,
            )
        },

        get usesYearPickerOverlay() {
            return ['date', 'dateTime', 'dateRange'].includes(this.mode)
        },

        get showsDayCalendarGrid() {
            if (this.mode === 'month' || this.mode === 'year') {
                return false
            }

            return this.calendarViewMode === 'days'
        },

        get usesScrollableYearGrid() {
            if (this.mode === 'year') {
                return true
            }

            return this.mode === 'month'
                && this.hasYearSegment
                && this.calendarViewMode === 'years'
        },

        get showsCalendarNavigation() {
            if (this.mode === 'year') {
                return false
            }

            if (this.calendarViewMode === 'years') {
                return false
            }

            return true
        },

        resolveConstraintYear(value) {
            if (! value) {
                return null
            }

            const parseMode = this.mode === 'year' ? 'year' : 'date'
            const parsed = this.parseConfigStoredValue(value, parseMode, 'day')

            if (! parsed) {
                return null
            }

            return toCalendarDate(parsed)?.year ?? null
        },

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
            if (this.mode === 'month' && ! this.hasYearSegment) {
                return true
            }

            if (this.mode === 'year') {
                return true
            }

            return false
        },

        get timeSegmentParts() {
            return getSegmentParts('time', this.config.granularity, this.config.hourCycle, this.config.showSeconds, this.config.locale)
        },

        get calendarRangeSummary() {
            if (! this.isRange || this.mode !== 'dateRange') {
                return ''
            }

            const raw = parseRangeStoredValue(this.state)

            if (! raw.start || ! raw.end) {
                return ''
            }

            const start = this.parseConfigStoredValue(raw.start, 'dateTime')
            const end = this.parseConfigStoredValue(raw.end, 'dateTime')

            if (! start || ! end) {
                return ''
            }

            const locale = this.config.locale?.replace(/_/g, '-') ?? undefined
            const formatter = new Intl.DateTimeFormat(locale, {
                day: 'numeric',
                month: 'short',
                year: 'numeric',
            })

            const startDate = toCalendarDate(start).toDate(this.config.timeZone || undefined)
            const endDate = toCalendarDate(end).toDate(this.config.timeZone || undefined)

            return `${this.config.labels.selected_range} ${formatter.formatRange(startDate, endDate)}`
        },

        get showTimeUnderCalendar() {
            if (this.config.hideTimeSection || this.config.granularity === 'day') {
                return false
            }

            return this.isRange || this.mode === 'dateTime'
        },

        get calendarDirection() {
            return this.config.direction === 'rtl' ? 'rtl' : 'ltr'
        },

        get weekdayLabels() {
            return this.calendarPanel?.getWeekdayLabels(
                this.config.firstDayOfWeek,
                this.config.locale,
                this.calendarDirection,
            ) ?? []
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
            if (! this.visibleMonth) {
                return []
            }

            return buildScrollableYearRange({
                centerYear: this.visibleMonth.year,
                minYear: this.resolveConstraintYear(this.config.minValue),
                maxYear: this.resolveConstraintYear(this.config.maxValue),
            })
        },

        get calendarWeeks() {
            if (! this.visibleMonth || ! this.calendarPanel) {
                return []
            }

            return this.calendarPanel.buildCalendarWeeks(
                this.visibleMonth,
                this.config.firstDayOfWeek,
                this.calendarDirection,
            )
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

            return parseStoredValue(this.state, this.mode, this.config.granularity, this.config.timeZone, this.config.calendarIdentifier ?? null)
        },

        get rangeValue() {
            const raw = parseRangeStoredValue(this.state)

            if (this.isTimeRange) {
                return {
                    start: raw.start ? this.parseConfigStoredValue(raw.start, 'time') : null,
                    end: raw.end ? this.parseConfigStoredValue(raw.end, 'time') : null,
                }
            }

            return {
                start: raw.start ? this.parseConfigStoredValue(raw.start, 'dateTime') : null,
                end: raw.end ? this.parseConfigStoredValue(raw.end, 'dateTime') : null,
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

            const hasInitialSegments = this.isRange
                ? this.segmentsHaveValues(this.rangeSegments?.start)
                    || this.segmentsHaveValues(this.rangeSegments?.end)
                : this.segmentsHaveValues(this.segments)

            if (this.hasHydratedState() && ! hasInitialSegments) {
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

                    this.$nextTick(() => {
                        requestAnimationFrame(() => {
                            this.bindYearGridWheelListeners()
                            this.scheduleScrollActiveYearIntoView()
                        })
                    })

                    return
                }

                this.calendarReady = false
                this.yearPickerOpen = false
                this.unbindCalendarListeners()
            })

            this.$watch('yearPickerOpen', () => {
                if (! this.calendarOpen) {
                    return
                }

                this.$nextTick(() => {
                    requestAnimationFrame(() => {
                        this.updateYearPickerOverlay()
                        this.bindYearGridWheelListeners()
                        this.scheduleScrollActiveYearIntoView()
                    })
                })
            })

            this.$watch('calendarViewMode', () => {
                if (! this.calendarOpen) {
                    return
                }

                this.$nextTick(() => {
                    requestAnimationFrame(() => {
                        this.syncStandaloneYearGridHeight()
                        this.bindYearGridWheelListeners()
                        this.scheduleScrollActiveYearIntoView()
                    })
                })
            })

            this.$watch('config.isInvalid', () => {
                this.validateSegmentConstraints()
            })

            this.markDisplayReady()
        },

        markDisplayReady() {
            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    this.displayReady = true
                })
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
                        raw.start ? this.parseConfigStoredValue(raw.start, parseMode) : null,
                        this.segmentParts,
                        this.config.locale,
                        this.config.hourCycle,
                        this.config.forceLeadingZeros,
                        this.config.monthDisplay,
                    )
                    const endSegments = buildSegmentsFromValue(
                        raw.end ? this.parseConfigStoredValue(raw.end, parseMode) : null,
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
                                raw.start ? extractTimeValue(this.parseConfigStoredValue(raw.start, 'dateTime')) : null,
                                this.timeSegmentParts,
                                this.config.locale,
                                this.config.hourCycle,
                                this.config.forceLeadingZeros,
                                this.config.monthDisplay,
                            )
                        }

                        if (hasState || ! this.segmentsHaveValues(this.timeSegments.end)) {
                            this.timeSegments.end = buildSegmentsFromValue(
                                raw.end ? extractTimeValue(this.parseConfigStoredValue(raw.end, 'dateTime')) : null,
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

            const parsed = this.parseConfigStoredValue(this.state, this.mode)

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

                if (this.showTimeUnderCalendar && this.mode === 'dateTime') {
                    this.timeSegments.single = buildSegmentsFromValue(
                        parsed ? extractTimeValue(parsed) : null,
                        this.timeSegmentParts,
                        this.config.locale,
                        this.config.hourCycle,
                        this.config.forceLeadingZeros,
                        this.config.monthDisplay,
                    )
                }
            }
        },

        resolveVisibleMonth() {
            const source = this.isRange
                ? (this.rangeValue.start || this.rangeValue.end)
                : this.parsedValue

            if (source) {
                return toCalendarDate(source)
            }

            return getToday(this.config.timeZone, this.config.calendarIdentifier ?? null)
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
