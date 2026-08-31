import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import { emitObservabilityEvent } from '../../resources/js/core/observability.js'

describe('observability', () => {
    it('dispatches fff:observability CustomEvent with event name and payload', () => {
        const events = []

        class CustomEvent {
            constructor(type, init = {}) {
                this.type = type
                this.detail = init.detail ?? null
            }
        }

        const targetWindow = {
            CustomEvent,
            dispatchEvent(event) {
                events.push(event)
            },
        }

        emitObservabilityEvent('overlay.open', { id: 'fff-country-menu' }, { window: targetWindow })

        assert.equal(events.length, 1)
        assert.equal(events[0].type, 'fff:observability')
        assert.deepEqual(events[0].detail, {
            event: 'overlay.open',
            id: 'fff-country-menu',
        })
    })

    it('no-ops when CustomEvent is unavailable', () => {
        emitObservabilityEvent('select.search', { query: 'x' }, { window: {} })
    })
})
