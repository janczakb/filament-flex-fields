import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import { motionDuration, prefersReducedMotion } from '../../resources/js/core/motion.js'

describe('motion', () => {
    it('re-exports prefersReducedMotion from theme-utils', () => {
        assert.equal(typeof prefersReducedMotion, 'function')
    })

    it('returns fallback durations when document is unavailable', () => {
        assert.equal(motionDuration('fast'), 150)
        assert.equal(motionDuration('base'), 250)
        assert.equal(motionDuration('slow'), 400)
        assert.equal(motionDuration('unknown'), 250)
    })
})
