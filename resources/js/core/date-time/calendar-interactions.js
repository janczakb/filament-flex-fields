import {
    Time,
    compareDateTimeValues,
    getToday,
    parseRangeStoredValue,
    toCalendarDate,
} from './format-parse.js'
import { isSameCalendarMonth, toGregorianDateString } from './calendar-system.js'
import { extractDateValue, extractTimeValue, mergeDateAndTime } from './calendar-grid.js'
import { segmentsToTime } from './segmented-input.js'
import { getCachedCalendarPanelModule, loadCalendarPanelModule } from './calendar-panel-loader.js'
import { scrollYearCellIntoView, YEAR_GRID_VIEWPORT_HEIGHT_PX } from './calendar-panel.js'
import { bindYearGridWheel, isYearGridScrollTarget } from './year-grid-scroll.js'

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

            if (opening) {
                this.scheduleScrollActiveYearIntoView()
            }
        },

        closeCalendar() {
            this.calendarOpen = false
            this.hoveredDate = null
            this.yearPickerOpen = false
            this.calendarViewMode = this.resolveCalendarViewMode()
            this.unbindYearGridWheelListeners()
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
            const resolvedDate = date?.date ?? date

            if (this.isLocked || ! resolvedDate || this.isDateDisabled(resolvedDate)) {
                return
            }

            if (date?.isOutsideMonth) {
                this.visibleMonth = resolvedDate.set({ day: 1 })
            }

            if (this.isRange) {
                this.selectRangeDate(resolvedDate)

                return
            }

            const merged = this.config.granularity === 'day'
                ? resolvedDate
                : mergeDateAndTime(
                    resolvedDate,
                    this.showTimeUnderCalendar
                        ? (segmentsToTime(this.segments, this.config.hourCycle, this.config.showSeconds) || new Time(0, 0, 0))
                        : (extractTimeValue(this.parsedValue) || new Time(0, 0, 0)),
                    this.config.granularity,
                    this.config.showSeconds,
                )

            this.setStateValue(this.toConfigStoredValue(merged, this.mode))
            this.bootstrapFromState()
            this.visibleMonth = toCalendarDate(merged)

            if (this.config.closeOnSelect) {
                this.closeCalendar()
            }
        },

        isOutsideVisibleMonth(date) {
            if (! date || ! this.visibleMonth) {
                return false
            }

            return ! isSameCalendarMonth(date, this.visibleMonth)
        },

        selectRangeDate(date) {
            const raw = parseRangeStoredValue(this.state)
            let start = raw.start ? this.parseConfigStoredValue(raw.start, 'dateTime') : null
            let end = raw.end ? this.parseConfigStoredValue(raw.end, 'dateTime') : null

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
                start: this.toConfigStoredValue(start, 'dateTime'),
                end: end
                    ? this.toConfigStoredValue(end, 'dateTime')
                    : null,
            })

            this.bootstrapFromState()
        },

        isDateDisabled(date) {
            if (! date) {
                return true
            }

            const min = this.config.minValue
                ? this.parseConfigStoredValue(this.config.minValue, 'date', 'day')
                : null
            const max = this.config.maxValue
                ? this.parseConfigStoredValue(this.config.maxValue, 'date', 'day')
                : null

            if (min && compareDateTimeValues(toCalendarDate(date), min) < 0) {
                return true
            }

            if (max && compareDateTimeValues(toCalendarDate(date), max) > 0) {
                return true
            }

            const iso = toGregorianDateString(date)

            if (iso && Array.isArray(this.config.unavailableDates) && this.config.unavailableDates.includes(iso)) {
                return true
            }

            return false
        },

        isToday(date) {
            if (! date || ! this.config.highlightToday || ! this.calendarPanel) {
                return false
            }

            return this.calendarPanel.sameCalendarDate(date, getToday(this.config.timeZone, this.config.calendarIdentifier ?? null))
        },

        resolveCalendarCell(cell) {
            return cell?.date ?? cell
        },

        getDayCellClass(cell) {
            const date = this.resolveCalendarCell(cell)
            const isOutsideMonth = typeof cell?.isOutsideMonth === 'boolean'
                ? cell.isOutsideMonth
                : this.isOutsideVisibleMonth(date)

            if (! date) {
                return {}
            }

            const selected = this.isRange && this.calendarPanel
                ? this.calendarPanel.getRangeCellState(date, extractDateValue(this.rangeValue.start), extractDateValue(this.rangeValue.end), this.hoveredDate)
                : {
                    'is-selected': this.parsedValue && this.calendarPanel?.sameCalendarDate(toCalendarDate(this.parsedValue), date),
                }

            return {
                ...selected,
                'is-outside-month': isOutsideMonth,
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
            if (this.usesYearPickerOverlay) {
                this.yearPickerOpen = ! this.yearPickerOpen

                this.$nextTick(() => {
                    requestAnimationFrame(() => {
                        this.updateYearPickerOverlay()
                        this.bindYearGridWheelListeners()
                        this.scheduleScrollActiveYearIntoView()
                    })
                })

                return
            }

            if (this.calendarViewMode === 'years' && this.mode !== 'year') {
                this.calendarViewMode = 'months'

                return
            }

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
                this.setStateValue(this.toConfigStoredValue(this.visibleMonth, 'month'))
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
                this.setStateValue(this.toConfigStoredValue(this.visibleMonth, 'year'))
                this.bootstrapFromState()

                if (this.config.closeOnSelect) {
                    this.closeCalendar()
                }

                return
            }

            if (this.usesYearPickerOverlay) {
                this.yearPickerOpen = false

                return
            }

            if (this.mode === 'month') {
                this.calendarViewMode = 'months'

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

        isYearGridScrollEvent(event) {
            return isYearGridScrollTarget(event?.target)
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
            const isRtl = this.calendarDirection === 'rtl'

            let top = rect.bottom + gap
            let left = isRtl ? rect.right - menuWidth : rect.left

            menu.style.position = 'fixed'
            menu.style.width = `${Math.round(menuWidth)}px`
            menu.style.zIndex = '200'
            menu.style.top = `${Math.round(top)}px`
            menu.style.left = `${Math.round(left)}px`
            menu.style.right = 'auto'

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
            this.updateYearPickerOverlay()
            this.syncStandaloneYearGridHeight()
            this.calendarReady = true
        },

        scheduleScrollActiveYearIntoView() {
            if (! this.calendarOpen) {
                return
            }

            const shouldScroll = this.usesScrollableYearGrid
                || (this.yearPickerOpen && this.usesYearPickerOverlay)

            if (! shouldScroll) {
                return
            }

            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    this.scrollActiveYearIntoView()
                })
            })
        },

        scrollActiveYearIntoView() {
            const targets = []

            const standalone = this.$refs.calendarYearGrid

            if (standalone?.offsetParent) {
                targets.push(standalone)
            }

            const overlay = this.$refs.calendarYearOverlay

            if (overlay?.getAttribute('data-open') === 'true') {
                targets.push(overlay)
            }

            for (const container of targets) {
                const selectedYear = this.parsedValue
                    ? (extractDateValue(this.parsedValue)?.year ?? this.visibleMonth?.year)
                    : this.visibleMonth?.year

                if (selectedYear != null) {
                    scrollYearCellIntoView(container, selectedYear)

                    return
                }
            }
        },

        updateYearPickerOverlay() {
            const dayGrid = this.$refs.calendarDayGrid
            const yearOverlay = this.$refs.calendarYearOverlay

            if (! dayGrid || ! yearOverlay) {
                this.yearPickerOverlayStyle = ''
                this.syncStandaloneYearGridHeight()

                return
            }

            const top = dayGrid.offsetTop
            const height = Math.max(dayGrid.offsetHeight, YEAR_GRID_VIEWPORT_HEIGHT_PX)

            this.yearPickerOverlayStyle = `top: ${top}px; height: ${height}px;`
            this.syncStandaloneYearGridHeight()
        },

        syncStandaloneYearGridHeight() {
            const yearGrid = this.$refs.calendarYearGrid

            if (! yearGrid) {
                return
            }

            const usesStandaloneYearGrid = this.calendarViewMode === 'years' && ! this.usesYearPickerOverlay

            if (! usesStandaloneYearGrid) {
                return
            }

            const dayGrid = this.$refs.calendarDayGrid
            const height = dayGrid?.offsetHeight > 0 ? dayGrid.offsetHeight : YEAR_GRID_VIEWPORT_HEIGHT_PX

            yearGrid.style.setProperty('--fff-date-time-year-grid-height', `${height}px`)
            yearGrid.style.height = `${height}px`
            yearGrid.style.maxHeight = `${height}px`
        },

        isYearGridWheelContainerVisible(element) {
            if (! element) {
                return false
            }

            const style = window.getComputedStyle(element)

            return style.display !== 'none'
                && style.visibility !== 'hidden'
                && element.clientHeight > 0
        },

        bindYearGridWheelListeners() {
            this.unbindYearGridWheelListeners()

            const elements = []

            const standalone = this.$refs.calendarYearGrid

            if (
                standalone
                && this.calendarViewMode === 'years'
                && ! this.usesYearPickerOverlay
                && this.isYearGridWheelContainerVisible(standalone)
            ) {
                elements.push(standalone)
            }

            const overlay = this.$refs.calendarYearOverlay

            if (
                overlay?.getAttribute('data-open') === 'true'
                && this.isYearGridWheelContainerVisible(overlay)
            ) {
                elements.push(overlay)
            }

            this.yearGridWheelCleanups = elements.map((element) => bindYearGridWheel(element))
        },

        unbindYearGridWheelListeners() {
            if (! Array.isArray(this.yearGridWheelCleanups)) {
                this.yearGridWheelCleanups = []

                return
            }

            for (const cleanup of this.yearGridWheelCleanups) {
                cleanup()
            }

            this.yearGridWheelCleanups = []
        },

        applyCalendarTheme(menu) {
            const isDark = document.documentElement.classList.contains('dark')

            if (isDark) {
                menu.style.setProperty('--fff-date-time-menu-bg', 'rgb(39 39 42)')
                menu.style.setProperty('--fff-date-time-menu-border', 'rgb(255 255 255 / 0.12)')
                menu.style.setProperty('--fff-date-time-menu-shadow', '0 4px 6px -1px rgb(0 0 0 / 0.28), 0 12px 28px -6px rgb(0 0 0 / 0.5)')
                menu.style.setProperty('--fff-date-time-time-track-bg', 'rgb(63 63 70 / 0.5)')
                menu.style.setProperty('--fff-date-time-time-text', 'rgb(244 244 245)')
                menu.style.setProperty('--fff-date-time-muted', 'rgb(161 161 170)')
            } else {
                menu.style.setProperty('--fff-date-time-menu-bg', 'rgb(255 255 255)')
                menu.style.setProperty('--fff-date-time-menu-border', 'rgb(228 228 231 / 0.65)')
                menu.style.setProperty('--fff-date-time-menu-shadow', '0 4px 6px -1px rgb(0 0 0 / 0.06), 0 12px 28px -6px rgb(0 0 0 / 0.12)')
                menu.style.setProperty('--fff-date-time-time-track-bg', 'rgb(244 244 245 / 0.8)')
                menu.style.setProperty('--fff-date-time-time-text', 'rgb(24 24 27)')
                menu.style.setProperty('--fff-date-time-muted', 'rgb(113 113 122)')
            }

            menu.style.backgroundColor = isDark ? 'rgb(39 39 42)' : 'rgb(255 255 255)'
        },

        bindCalendarListeners() {
            if (this.menuScrollHandler) {
                return
            }

            this.menuScrollHandler = (event) => {
                if (this.isYearGridScrollEvent(event)) {
                    return
                }

                this.updateCalendarPosition()
            }
            this.menuResizeHandler = () => this.updateCalendarPosition()

            window.addEventListener('scroll', this.menuScrollHandler, true)
            window.addEventListener('resize', this.menuResizeHandler)
        },

        unbindCalendarListeners() {
            this.unbindYearGridWheelListeners()

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
