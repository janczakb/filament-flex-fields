import { getCalculatorKeyIconSvg, prepareCalculatorKeyIconSvg } from '../../resources/js/core/calculator-keypad-icons.js'
import { describe, it } from 'node:test'
import assert from 'node:assert/strict'

describe('calculator-keypad-icons', () => {
    it('returns gravity svg markup for supported keypad icons', () => {
        const svg = getCalculatorKeyIconSvg('plus')

        assert.match(svg, /class="fff-gravity-icon fff-calculator-panel__gravity-icon"/)
        assert.match(svg, /<svg\b/)
    })

    it('uses gravity xmark svg for multiply operator icon', () => {
        const multiply = getCalculatorKeyIconSvg('multiply')
        const xmark = getCalculatorKeyIconSvg('xmark')

        assert.equal(multiply, xmark)
    })

    it('returns null for unknown icons', () => {
        assert.equal(getCalculatorKeyIconSvg('unknown'), null)
    })

    it('strips fixed dimensions from svg markup', () => {
        const svg = prepareCalculatorKeyIconSvg('<svg width="16" height="16" viewBox="0 0 16 16"></svg>')

        assert.doesNotMatch(svg, /width="16"/)
        assert.doesNotMatch(svg, /height="16"/)
    })
})
