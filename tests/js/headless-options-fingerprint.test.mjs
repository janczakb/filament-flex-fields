import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import { createHeadlessComboboxEngineSyncMixin } from '../../resources/js/components/select-field/headless-combobox-engine-sync.js'

describe('headless options fingerprint', () => {
    it('stable for identical option trees and changes when labels/values change', () => {
        const mixin = createHeadlessComboboxEngineSyncMixin()
        const host = { ...mixin }

        const a = [
            { value: '1', label: 'One', triggerLabel: 'One' },
            { value: '2', label: 'Two', triggerLabel: 'Two' },
        ]
        const b = [
            { value: '1', label: 'One', triggerLabel: 'One' },
            { value: '2', label: 'Two', triggerLabel: 'Two' },
        ]
        const c = [
            { value: '1', label: 'One', triggerLabel: 'One' },
            { value: '2', label: 'Deux', triggerLabel: 'Deux' },
        ]

        assert.equal(host.fingerprintHeadlessOptions(a), host.fingerprintHeadlessOptions(b))
        assert.notEqual(host.fingerprintHeadlessOptions(a), host.fingerprintHeadlessOptions(c))
        assert.equal(host.fingerprintHeadlessOptions([]), '0')
    })

    it('skips Alpine flatOptions rewrite when fingerprint matches', () => {
        const mixin = createHeadlessComboboxEngineSyncMixin()
        const options = [
            { value: 'a', label: 'A', triggerLabel: 'A' },
            { value: 'b', label: 'B', triggerLabel: 'B' },
        ]
        let setOptionsCalls = 0
        let syncCalls = 0
        const host = {
            ...mixin,
            options,
            flatOptions: options.slice(),
            _engineOptionsFingerprint: null,
            _engine: {
                setOptions() {
                    setOptionsCalls += 1
                },
            },
            _syncFromEngine() {
                syncCalls += 1
            },
        }

        host.syncEngineOptions()
        const firstFingerprint = host._engineOptionsFingerprint
        assert.ok(firstFingerprint)
        assert.equal(setOptionsCalls, 1)
        assert.equal(syncCalls, 1)

        host.syncEngineOptions()
        assert.equal(host._engineOptionsFingerprint, firstFingerprint)
        assert.equal(setOptionsCalls, 2)
        assert.equal(syncCalls, 1)
    })
})
