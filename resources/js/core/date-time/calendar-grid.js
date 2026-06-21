import {
    CalendarDate,
    CalendarDateTime,
    Time,
    toCalendarDate,
} from './format-parse.js'

export function mergeDateAndTime(date, time, granularity, showSeconds) {
    if (! date) {
        return null
    }

    if (! time || granularity === 'day') {
        return date
    }

    return new CalendarDateTime(
        date.year,
        date.month,
        date.day,
        time.hour ?? 0,
        time.minute ?? 0,
        showSeconds ? (time.second ?? 0) : 0,
    )
}

export function extractTimeValue(dateTime) {
    if (! dateTime) {
        return null
    }

    if (dateTime instanceof Time) {
        return dateTime
    }

    if (dateTime instanceof CalendarDateTime) {
        return new Time(dateTime.hour, dateTime.minute, dateTime.second)
    }

    return new Time(0, 0, 0)
}

export function extractDateValue(dateTime) {
    if (! dateTime) {
        return null
    }

    if (dateTime instanceof CalendarDate) {
        return dateTime
    }

    return toCalendarDate(dateTime)
}
