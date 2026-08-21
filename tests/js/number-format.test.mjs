import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import { formatDecimal, normalizeLocale } from '../../resources/js/core/number-format.js'

describe('normalizeLocale', () => {
    it('converts Laravel-style tags to BCP 47', () => {
        assert.equal(normalizeLocale('pt_BR'), 'pt-BR')
        assert.equal(normalizeLocale('  en_US  '), 'en-US')
    })

    it('falls back to en-US for empty input', () => {
        assert.equal(normalizeLocale(''), 'en-US')
        assert.equal(normalizeLocale(null), 'en-US')
    })
})

describe('formatDecimal without a locale', () => {
    it('keeps the historical plain output', () => {
        assert.equal(formatDecimal(1234.5, {}), '1234.5')
        assert.equal(formatDecimal(1234.5, { decimalPlaces: 2 }), '1234.50')
        assert.equal(formatDecimal(1000000, { decimalPlaces: 0 }), '1000000')
        assert.equal(formatDecimal(2, {}), '2')
    })

    it('treats a blank locale as no locale', () => {
        assert.equal(formatDecimal(1234.5, { locale: '   ', decimalPlaces: 2 }), '1234.50')
    })
})

describe('formatDecimal with a locale', () => {
    it('applies grouping and the locale decimal separator', () => {
        assert.equal(formatDecimal(1234.5, { locale: 'pt_BR', decimalPlaces: 2 }), '1.234,50')
        assert.equal(formatDecimal(1234.5, { locale: 'pt-BR' }), '1.234,5')
        assert.equal(formatDecimal(1000000, { locale: 'pt_BR', decimalPlaces: 0 }), '1.000.000')
    })

    it('matches the plain output for locales that use a dot', () => {
        assert.equal(formatDecimal(1234.5, { locale: 'en_US', decimalPlaces: 2 }), '1,234.50')
    })

    it('falls back to the plain output for an invalid locale tag', () => {
        assert.equal(formatDecimal(1234.5, { locale: '!!!', decimalPlaces: 2 }), '1234.50')
    })

    it('returns an empty string for non-finite input', () => {
        assert.equal(formatDecimal(Number.NaN, { locale: 'pt_BR' }), '')
        assert.equal(formatDecimal(Number.POSITIVE_INFINITY, {}), '')
    })
})
