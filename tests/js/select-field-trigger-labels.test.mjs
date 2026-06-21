import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    findTriggerLabelInOptions,
    populateRepositoryWithTriggerLabels,
    resolveTriggerLabel,
} from '../../resources/js/components/select-field/select-field-trigger-labels.js'

describe('select-field trigger labels', () => {
    it('resolves triggerLabel before label', () => {
        assert.equal(resolveTriggerLabel({ triggerLabel: 'Trigger', label: 'Label' }), 'Trigger')
        assert.equal(resolveTriggerLabel({ label: 'Label only' }), 'Label only')
    })

    it('finds nested trigger labels', () => {
        const options = [
            {
                label: 'Group',
                options: [
                    { value: 'a', triggerLabel: 'Alpha trigger', label: 'Alpha' },
                ],
            },
        ]

        assert.equal(findTriggerLabelInOptions('a', options), 'Alpha trigger')
        assert.equal(findTriggerLabelInOptions('missing', options), null)
    })

    it('populates the select label repository', () => {
        const select = { labelRepository: {} }

        populateRepositoryWithTriggerLabels(select, [
            { value: 1, label: 'One' },
            { value: 2, triggerLabel: 'Two trigger', label: 'Two' },
        ])

        assert.deepEqual(select.labelRepository, {
            1: 'One',
            2: 'Two trigger',
        })
    })
})
