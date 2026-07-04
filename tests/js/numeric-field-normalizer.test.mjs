import {
    normalizeNumericFieldValue,
    sanitizeNumericInput,
} from '../../resources/js/core/numeric-field-normalizer.js'
import { describe, it } from 'node:test'
import assert from 'node:assert/strict'

describe('numeric-field-normalizer', () => {
    it('applies decimal precision with truncate mode by default', () => {
        const result = normalizeNumericFieldValue('12.3456', {
            decimalPlaces: 2,
        })

        assert.equal(result.value, 12.34)
        assert.equal(result.display, '12.34')
    })

    it('supports ceil floor and truncate rounding modes', () => {
        assert.equal(normalizeNumericFieldValue('12.341', { decimalPlaces: 2, roundingMode: 'round' }).value, 12.34)
        assert.equal(normalizeNumericFieldValue('12.349', { decimalPlaces: 2, roundingMode: 'round' }).value, 12.35)
        assert.equal(normalizeNumericFieldValue('12.341', { decimalPlaces: 2, roundingMode: 'ceil' }).value, 12.35)
        assert.equal(normalizeNumericFieldValue('12.349', { decimalPlaces: 2, roundingMode: 'floor' }).value, 12.34)
        assert.equal(normalizeNumericFieldValue('-12.349', { decimalPlaces: 2, roundingMode: 'truncate' }).value, -12.34)
    })

    it('clamps values to min and max and applies step', () => {
        const result = normalizeNumericFieldValue('17', {
            min: 0,
            max: 20,
            step: 5,
            integer: true,
        })

        assert.equal(result.value, 15)
    })

    it('limits raw input length', () => {
        assert.equal(sanitizeNumericInput('123456789', { maxLength: 5 }), '12345')
    })
})
