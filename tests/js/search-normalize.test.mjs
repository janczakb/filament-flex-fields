import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import { normalizeSearchQuery } from '../../resources/js/core/search-normalize.js'
import { createCountrySearchMixin } from '../../resources/js/core/country-search.js'
import { createTimezonePickerMixin } from '../../resources/js/support/timezone-picker-mixin.js'

describe('normalizeSearchQuery', () => {
    it('lowercases and trims', () => {
        assert.equal(normalizeSearchQuery('  SaO  '), 'sao')
    })

    it('strips diacritics', () => {
        assert.equal(normalizeSearchQuery('S\u00e3o Paulo'), 'sao paulo')
        assert.equal(normalizeSearchQuery('Bel\u00e9m'), 'belem')
        assert.equal(normalizeSearchQuery('D\u00f3lar'), 'dolar')
        assert.equal(normalizeSearchQuery('Iene japon\u00eas'), 'iene japones')
        assert.equal(normalizeSearchQuery('\u00c1ustria'), 'austria')
    })

    it('handles nullish input', () => {
        assert.equal(normalizeSearchQuery(null), '')
        assert.equal(normalizeSearchQuery(undefined), '')
    })

    it('does not strip standalone letters that have no NFD decomposition', () => {
        // U+0141 LATIN CAPITAL LETTER L WITH STROKE is a distinct letter, not
        // a base + combining mark, so it survives normalization by design.
        assert.equal(normalizeSearchQuery('\u0141\u00f3d\u017a'), '\u0142odz')
    })
})

function timezonePicker(timezones, search) {
    const mixin = createTimezonePickerMixin()

    mixin.timezones = timezones
    mixin.timezoneSearch = search

    return mixin.filteredTimezones
}

describe('timezone picker search', () => {
    const timezones = [
        { id: 'America/Sao_Paulo', label: 'Hor\u00e1rio S\u00e3o Paulo', region: 'America', offset: 'UTC-03:00' },
        { id: 'America/Belem', label: 'Hor\u00e1rio Bel\u00e9m', region: 'America', offset: 'UTC-03:00' },
        { id: 'Europe/Warsaw', label: 'Hor\u00e1rio Pol\u00f4nia', region: 'Europe', offset: 'UTC+01:00' },
    ]

    it('matches an accented label from an unaccented query', () => {
        const results = timezonePicker(timezones, 'sao paulo')

        assert.equal(results.length, 1)
        assert.equal(results[0].id, 'America/Sao_Paulo')
    })

    it('still matches when the query carries the accent', () => {
        const results = timezonePicker(timezones, 'S\u00e3o Paulo')

        assert.equal(results.length, 1)
        assert.equal(results[0].id, 'America/Sao_Paulo')
    })

    it('matches accented labels mid-string', () => {
        assert.equal(timezonePicker(timezones, 'belem')[0].id, 'America/Belem')
        assert.equal(timezonePicker(timezones, 'polonia')[0].id, 'Europe/Warsaw')
    })

    it('keeps matching identifiers, regions and offsets', () => {
        assert.equal(timezonePicker(timezones, 'europe').length, 1)
        assert.equal(timezonePicker(timezones, 'utc-03').length, 2)
        assert.equal(timezonePicker(timezones, 'america/belem').length, 1)
    })

    it('returns every timezone for an empty query', () => {
        assert.equal(timezonePicker(timezones, '   ').length, 3)
    })
})

describe('country search', () => {
    const countries = [
        { code: 'AT', name: '\u00c1ustria', dial_code: '+43' },
        { code: 'BR', name: 'Brasil', dial_code: '+55' },
    ]

    it('still matches accented names from unaccented queries', () => {
        const mixin = createCountrySearchMixin()

        mixin.countries = countries
        mixin.countrySearchDebounced = 'austria'

        const results = mixin.filteredCountries()

        assert.equal(results.length, 1)
        assert.equal(results[0].code, 'AT')
    })
})
