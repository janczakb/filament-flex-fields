import { describe, it } from 'node:test'
import assert from 'node:assert/strict'
import {
    openCalculatorPanel,
    registerCalculatorField,
    subscribeCalculatorPanel,
    updateFieldSession,
} from '../../resources/js/core/calculator-coordinator.js'

describe('calculator-coordinator', () => {
    it('does not recurse when persisting session updates during panel sync', () => {
        let syncCount = 0

        const unregisterField = registerCalculatorField('weight', {
            getLabel: () => 'Weight',
            getInputValue: () => '10',
            getDecimalPlaces: () => 2,
            getAnchorElement: () => null,
            applyValue: () => {},
        })

        const unsubscribe = subscribeCalculatorPanel(() => {
            syncCount += 1

            if (syncCount > 20) {
                throw new Error('Maximum call stack size exceeded')
            }

            updateFieldSession('weight', {
                expression: '10+2',
                result: '12',
            })
        })

        try {
            openCalculatorPanel('weight')
            assert.ok(syncCount >= 1)
            assert.ok(syncCount < 10)
        } finally {
            unsubscribe()
            unregisterField()
        }
    })

    it('switches active field while keeping per-field sessions', () => {
        let weightTarget = false
        let quantityTarget = false

        const unregisterWeight = registerCalculatorField('weight', {
            getLabel: () => 'Weight',
            getInputValue: () => '10',
            getDecimalPlaces: () => 2,
            getAnchorElement: () => null,
            applyValue: () => {},
            onPanelOpen: () => {
                weightTarget = true
            },
            onPanelClose: () => {
                weightTarget = false
            },
        })

        const unregisterQuantity = registerCalculatorField('quantity', {
            getLabel: () => 'Quantity',
            getInputValue: () => '4',
            getDecimalPlaces: () => null,
            getAnchorElement: () => null,
            applyValue: () => {},
            onPanelOpen: () => {
                quantityTarget = true
            },
            onPanelClose: () => {
                quantityTarget = false
            },
        })

        updateFieldSession('weight', { expression: '10+2', result: '12' })
        updateFieldSession('quantity', { expression: '4*3', result: '12' })

        openCalculatorPanel('weight')
        assert.equal(weightTarget, true)
        assert.equal(quantityTarget, false)

        openCalculatorPanel('quantity')
        assert.equal(weightTarget, false)
        assert.equal(quantityTarget, true)

        unregisterWeight()
        unregisterQuantity()
    })
})
