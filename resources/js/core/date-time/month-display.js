import { normalizeIntlLocale } from './format-parse.js'

const FALLBACK_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
const FALLBACK_LONG = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December',
]

export function isMonthDisplayTextual(monthDisplay) {
    return monthDisplay === 'short' || monthDisplay === 'long'
}

export function getMonthLabels(locale, monthDisplay) {
    if (! isMonthDisplayTextual(monthDisplay)) {
        return Array.from({ length: 12 }, (_, index) => String(index + 1).padStart(2, '0'))
    }

    try {
        const formatter = new Intl.DateTimeFormat(normalizeIntlLocale(locale), {
            month: monthDisplay === 'long' ? 'long' : 'short',
        })

        return Array.from({ length: 12 }, (_, index) => formatter.format(new Date(2024, index, 1)))
    } catch {
        return monthDisplay === 'long' ? [...FALLBACK_LONG] : [...FALLBACK_SHORT]
    }
}

export function formatMonthSegmentValue(monthNumber, locale, monthDisplay, forceLeadingZeros = true) {
    if (! monthNumber || monthNumber < 1 || monthNumber > 12) {
        return ''
    }

    if (! isMonthDisplayTextual(monthDisplay)) {
        return String(monthNumber).padStart(2, forceLeadingZeros ? '0' : '')
    }

    return getMonthLabels(locale, monthDisplay)[monthNumber - 1] ?? ''
}

export function parseMonthSegmentValue(input, locale, monthDisplay) {
    const trimmed = String(input ?? '').trim()

    if (trimmed === '') {
        return null
    }

    if (! isMonthDisplayTextual(monthDisplay) || /^\d+$/.test(trimmed)) {
        const month = Number.parseInt(trimmed, 10)

        return Number.isNaN(month) || month < 1 || month > 12 ? null : month
    }

    const normalizedInput = normalizeMonthLabel(trimmed)
    const labels = getMonthLabels(locale, monthDisplay)
    const matches = []

    for (let index = 0; index < labels.length; index++) {
        const normalizedLabel = normalizeMonthLabel(labels[index])

        if (normalizedLabel === normalizedInput) {
            return index + 1
        }

        if (normalizedLabel.startsWith(normalizedInput)) {
            matches.push(index + 1)
        }
    }

    return matches.length === 1 ? matches[0] : null
}

export function getMonthSegmentMaxLength(locale, monthDisplay) {
    if (! isMonthDisplayTextual(monthDisplay)) {
        return 2
    }

    return Math.max(...getMonthLabels(locale, monthDisplay).map((label) => label.length), monthDisplay === 'long' ? 9 : 3)
}

export function getMonthSegmentPlaceholder(locale, monthDisplay) {
    if (! isMonthDisplayTextual(monthDisplay)) {
        return 'mm'
    }

    return getMonthLabels(locale, monthDisplay)[0] ?? (monthDisplay === 'long' ? 'month' : 'mmm')
}

export function processMonthSegmentInput(previousValue, newRawValue, locale, monthDisplay) {
    if (! isMonthDisplayTextual(monthDisplay)) {
        return String(newRawValue ?? '').replace(/\D/g, '').slice(0, 2)
    }

    const maxLength = getMonthSegmentMaxLength(locale, monthDisplay)
    let value = String(newRawValue ?? '')

    if (value.length > maxLength) {
        value = value.slice(0, maxLength)
    }

    return value
}

export function finalizeMonthSegmentValue(value, locale, monthDisplay) {
    const trimmed = String(value ?? '').trim()

    if (trimmed === '') {
        return ''
    }

    const month = parseMonthSegmentValue(trimmed, locale, monthDisplay)

    if (month === null) {
        return ''
    }

    return formatMonthSegmentValue(month, locale, monthDisplay)
}

function normalizeMonthLabel(value) {
    return String(value)
        .trim()
        .toLocaleLowerCase()
        .normalize('NFD')
        .replace(/\p{M}/gu, '')
}
