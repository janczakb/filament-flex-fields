import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'
import { describe, it } from 'node:test'

import {
    slotsOverlap,
    validateDaySlots,
} from '../../resources/js/support/schedule-validation.js'

const fixturePath = join(dirname(fileURLToPath(import.meta.url)), '../fixtures/schedule-validation-contract.json')
const contract = JSON.parse(readFileSync(fixturePath, 'utf8'))

describe('schedule-validation contract', () => {
    for (const testCase of contract.overlap_cases) {
        it(`js overlap contract: ${testCase.name}`, () => {
            assert.equal(slotsOverlap(testCase.slots), testCase.expects_overlap)

            const validationCode = validateDaySlots(testCase.slots, {
                minSlots: 1,
                maxSlots: 10,
                requireSlots: true,
            })

            if (testCase.expects_validation_code === null) {
                assert.equal(validationCode, null)
            } else {
                assert.equal(validationCode, testCase.expects_validation_code)
            }
        })
    }

    for (const testCase of contract.min_max_cases) {
        it(`js min/max contract: ${testCase.name}`, () => {
            const validationCode = validateDaySlots(testCase.slots, {
                minSlots: testCase.min_slots,
                maxSlots: testCase.max_slots,
                requireSlots: true,
            })

            assert.equal(validationCode, testCase.expects_validation_code)
        })
    }
})
