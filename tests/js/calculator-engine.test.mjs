import {
    appendCalculatorToken,
    applyPercentToExpression,
    evaluateExpression,
    formatCalculatorResult,
    sanitizeExpressionInput,
    toggleSignOnExpression,
} from '../../resources/js/core/calculator-engine.js'
import { describe, it } from 'node:test'
import assert from 'node:assert/strict'

describe('calculator-engine', () => {
    it('evaluates basic arithmetic', () => {
        assert.equal(evaluateExpression('12+3*2'), 18)
        assert.equal(evaluateExpression('(10+2)/3'), 4)
    })

    it('supports unary minus', () => {
        assert.equal(evaluateExpression('-5+2'), -3)
    })

    it('sanitizes unsupported characters', () => {
        assert.equal(sanitizeExpressionInput('12abc+3'), '12+3')
    })

    it('formats decimal places', () => {
        assert.equal(formatCalculatorResult(12.3456, 2), '12.35')
        assert.equal(formatCalculatorResult(12509, 2), '12509')
        assert.equal(formatCalculatorResult(1256.5, 2), '1256.5')
    })

    it('throws on division by zero', () => {
        assert.throws(() => evaluateExpression('10/0'))
    })

    it('treats trailing operators as incomplete without error', async () => {
        const { computeCalculatorDisplay } = await import('../../resources/js/core/calculator-engine.js')

        const display = computeCalculatorDisplay('1250.5+6+', 2)

        assert.equal(display.error, null)
        assert.equal(display.incomplete, true)
        assert.equal(display.preview, '1256.5')
    })

    it('returns null error for unbalanced parentheses while typing', async () => {
        const { computeCalculatorDisplay } = await import('../../resources/js/core/calculator-engine.js')

        const display = computeCalculatorDisplay('(12+3', 2)

        assert.equal(display.error, null)
        assert.equal(display.incomplete, true)
    })

    it('toggles sign on the last number using apple-style parentheses', () => {
        assert.equal(toggleSignOnExpression('5'), '-5')
        assert.equal(toggleSignOnExpression('-5'), '5')
        assert.equal(toggleSignOnExpression('12+5'), '12+(-5)')
        assert.equal(toggleSignOnExpression('12+(-5)'), '12+5')
        assert.equal(toggleSignOnExpression('12+'), '12+(-')
        assert.equal(toggleSignOnExpression('12+(-'), '12+')
    })

    it('builds parenthesized negative operands after toggle then digit entry', () => {
        assert.equal(appendCalculatorToken('9+(-', '6'), '9+(-6)')
        assert.equal(evaluateExpression('9+(-6)'), 3)
        assert.equal(evaluateExpression('5+(-6)'), -1)
    })

    it('prevents stacked leading zeros in the current operand', () => {
        assert.equal(appendCalculatorToken('', '0'), '0')
        assert.equal(appendCalculatorToken('0', '0'), '0')
        assert.equal(appendCalculatorToken('0', '5'), '5')
        assert.equal(appendCalculatorToken('12+0', '0'), '12+0')
        assert.equal(appendCalculatorToken('12+0', '5'), '12+5')
        assert.equal(appendCalculatorToken('9+(-', '0'), '9+(-0)')
        assert.equal(appendCalculatorToken('9+(-0', '0'), '9+(-0')
    })

    it('returns zero result after clear state', async () => {
        const { computeCalculatorDisplay } = await import('../../resources/js/core/calculator-engine.js')

        const display = computeCalculatorDisplay('', 2)

        assert.equal(display.result, '0')
        assert.equal(display.incomplete, false)
    })

    it('does not round typed literals while calculating in the panel', async () => {
        const { computeCalculatorDisplay } = await import('../../resources/js/core/calculator-engine.js')

        const display = computeCalculatorDisplay('9.9999')

        assert.equal(display.result, '9.9999')
    })

    it('can still format preview with decimal places when explicitly requested', async () => {
        const { computeCalculatorDisplay } = await import('../../resources/js/core/calculator-engine.js')

        const display = computeCalculatorDisplay('1250.5+6+', 2)

        assert.equal(display.preview, '1256.5')
    })

    it('applies percent to the last number like ios calculator', () => {
        assert.equal(applyPercentToExpression('9'), '0.09')
        assert.equal(applyPercentToExpression('50'), '0.5')
        assert.equal(applyPercentToExpression('200+10'), '200+0.1')
    })

    it('rejects duplicate decimal points in the current operand', () => {
        assert.equal(appendCalculatorToken('1250.5', '.'), '1250.5')
        assert.equal(appendCalculatorToken('1250.', '.'), '1250.')
        assert.equal(appendCalculatorToken('1250', '.'), '1250.')
        assert.equal(appendCalculatorToken('', '.'), '0.')
        assert.equal(appendCalculatorToken('12+', '.'), '12+0.')
    })

    it('replaces trailing operators instead of stacking them', () => {
        assert.equal(appendCalculatorToken('1250.5+', '+'), '1250.5+')
        assert.equal(appendCalculatorToken('1250.5+', '-'), '1250.5-')
        assert.equal(appendCalculatorToken('1250.5-', '*'), '1250.5*')
        assert.equal(appendCalculatorToken('1250.5*', '/'), '1250.5/')
    })

    it('ignores leading operators except unary minus', () => {
        assert.equal(appendCalculatorToken('', '+'), '')
        assert.equal(appendCalculatorToken('', '*'), '')
        assert.equal(appendCalculatorToken('', '-'), '-')
    })
})
