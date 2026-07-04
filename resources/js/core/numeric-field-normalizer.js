const ROUNDING_MODES = new Set(['round', 'ceil', 'floor', 'truncate'])

function clampNumeric(value, min, max) {
    let numeric = value

    if (min !== null && min !== undefined && numeric < min) {
        numeric = min
    }

    if (max !== null && max !== undefined && numeric > max) {
        numeric = max
    }

    return numeric
}

function applyRoundingMode(value, roundingMode, decimalPlaces) {
    if (decimalPlaces === null || decimalPlaces === undefined) {
        switch (roundingMode) {
            case 'ceil':
                return Math.ceil(value)
            case 'floor':
                return Math.floor(value)
            case 'truncate':
                return Math.trunc(value)
            default:
                return Math.round(value)
        }
    }

    const factor = 10 ** decimalPlaces

    switch (roundingMode) {
        case 'ceil':
            return Math.ceil(value * factor) / factor
        case 'floor':
            return Math.floor(value * factor) / factor
        case 'truncate':
            return Math.trunc(value * factor) / factor
        default:
            return Math.round(value * factor) / factor
    }
}

function formatNumericString(value, { integer, decimalPlaces }) {
    if (integer) {
        return String(Math.trunc(value))
    }

    if (decimalPlaces === null || decimalPlaces === undefined) {
        return String(value)
    }

    return value.toFixed(decimalPlaces).replace(/(\.\d*?)0+$/, '$1').replace(/\.$/, '')
}

function applyStep(value, step) {
    if (step === null || step === undefined || step === 0 || step === 1) {
        return value
    }

    return Math.round(value / step) * step
}

export function normalizeNumericFieldValue(raw, options = {}) {
    const {
        integer = false,
        decimalPlaces = null,
        roundingMode = 'truncate',
        min = null,
        max = null,
        step = null,
    } = options

    const trimmed = String(raw ?? '').trim()

    if (trimmed === '') {
        return { value: null, display: '' }
    }

    const parsed = integer ? parseInt(trimmed, 10) : parseFloat(trimmed)

    if (Number.isNaN(parsed)) {
        return null
    }

    let numeric = clampNumeric(parsed, min, max)
    numeric = applyStep(numeric, step)
    numeric = applyRoundingMode(numeric, ROUNDING_MODES.has(roundingMode) ? roundingMode : 'truncate', integer ? 0 : decimalPlaces)

    if (integer) {
        numeric = Math.trunc(numeric)
    }

    return {
        value: numeric,
        display: formatNumericString(numeric, { integer, decimalPlaces: integer ? 0 : decimalPlaces }),
    }
}

export function sanitizeNumericInput(raw, { maxLength = null } = {}) {
    let value = String(raw ?? '').replace(/[^\d.-]/g, '')

    const firstMinus = value.indexOf('-')

    if (firstMinus > 0) {
        value = value.replace(/-/g, '')
    } else if (firstMinus === 0) {
        value = `-${value.slice(1).replace(/-/g, '')}`
    }

    const parts = value.split('.')
    value = parts.length <= 1 ? value : `${parts.shift()}.${parts.join('')}`

    if (maxLength !== null && maxLength !== undefined && value.length > maxLength) {
        value = value.slice(0, maxLength)
    }

    return value
}
