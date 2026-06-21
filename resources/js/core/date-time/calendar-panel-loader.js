let calendarPanelModule = null
let calendarPanelLoading = null

export function loadCalendarPanelModule() {
    if (! calendarPanelLoading) {
        calendarPanelLoading = import('./calendar-panel.js').then((module) => {
            calendarPanelModule = module

            return module
        })
    }

    return calendarPanelLoading
}

export function getCachedCalendarPanelModule() {
    return calendarPanelModule
}
