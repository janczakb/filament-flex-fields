import assert from 'node:assert/strict'
import { describe, it, beforeEach } from 'node:test'

import {
    overlayOpenLatencyP95,
    overlayOpenLatencySummary,
    recordOverlayOpenLatency,
    resetOverlayOpenLatencySamples,
} from '../../resources/js/core/overlay-telemetry-p95.js'

describe('overlay-telemetry-p95', () => {
    beforeEach(() => {
        resetOverlayOpenLatencySamples()
    })

    it('records samples and computes p95', () => {
        for (let index = 1; index <= 20; index++) {
            recordOverlayOpenLatency(index)
        }

        assert.equal(overlayOpenLatencyP95(), 19)
        assert.equal(overlayOpenLatencySummary().count, 20)
    })
})
