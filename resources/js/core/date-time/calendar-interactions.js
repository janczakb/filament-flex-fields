import {
    CalendarDate,
    Time,
    compareDateTimeValues,
    getToday,
    parseRangeStoredValue,
    parseStoredValue,
    toCalendarDate,
    toStoredValue,
} from './format-parse.js'
import { extractDateValue, extractTimeValue, mergeDateAndTime } from './calendar-grid.js'
import { segmentsToTime } from './segmented-input.js'
import { getCachedCalendarPanelModule, loadCalendarPanelModule } from './calendar-panel-loader.js'

export function createCalendarInteractionsBehavior() {
    return {
        async ensureCalendarPanel() {
            if (this.calendarPanel) {
                return this.calendarPanel
            }

            this.calendarPanel = await loadCalendarPanelModule()

            if (! getCachedCalendarPanelModule()) {
                // cache is populated by loader
            }

            return this.calendarPanel
        },

        async toggleCalendar() {
            if (this.isLocked || ! this.config.showCalendar) {
                return
            }

            const opening = ! this.calendarOpen

            if (opening) {
                await this.ensureCalendarPanel()
            }

            this.calendarOpen = opening
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
            if (! date || ! this.config.highlightToday || ! this.calendarPanel) {
                return false
            }

            return this.calendarPanel.sameCalendarDate(date, getToday(this.config.timeZone))
        },

        getDayCellClass(date) {
            if (! date) {
                return 'is-outside'
            }

            const selected = this.isRange && this.calendarPanel
                ? this.calendarPanel.getRangeCellState(date, extractDateValue(this.rangeValue.start), extractDateValue(this.rangeValue.end), this.hoveredDate)
                : {
                    'is-selected': this.parsedValue && this.calendarPanel?.sameCalendarDate(toCalendarDate(this.parsedValue), date),
                }

            return {
                ...selected,
                'is-today': this.isToday(date),
                'is-disabled': this.isDateDisabled(date),
            }
        },

        previousMonth() {
            const panel = this.calendarPanel

            if (! this.visibleMonth || ! panel) {
                return
            }

            if (this.calendarViewMode === 'days') {
                this.visibleMonth = panel.addMonths(this.visibleMonth, -1)

                return
            }

            if (this.calendarViewMode === 'months') {
                this.visibleMonth = panel.shiftCalendarYear(this.visibleMonth, -1)

                return
            }

            this.visibleMonth = panel.shiftCalendarYear(this.visibleMonth, -panel.YEARS_PER_PAGE)
        },

        nextMonth() {
            const panel = this.calendarPanel

            if (! this.visibleMonth || ! panel) {
                return
            }

            if (this.calendarViewMode === 'days') {
                this.visibleMonth = panel.addMonths(this.visibleMonth, 1)

                return
            }

            if (this.calendarViewMode === 'months') {
                this.visibleMonth = panel.shiftCalendarYear(this.visibleMonth, 1)

                return
            }

            this.visibleMonth = panel.shiftCalendarYear(this.visibleMonth, panel.YEARS_PER_PAGE)
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
            const panel = this.calendarPanel

            if (! this.visibleMonth || ! panel) {
                return
            }

            this.visibleMonth = panel.setCalendarMonth(this.visibleMonth, month)

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
            const panel = this.calendarPanel

            if (! this.visibleMonth || ! panel) {
                return
            }

            this.visibleMonth = panel.setCalendarYear(this.visibleMonth, year)

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
