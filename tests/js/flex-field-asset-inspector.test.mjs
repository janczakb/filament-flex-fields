import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import { createAssetInspector, evaluateInvariants, INVARIANT_IDS } from '../../resources/js/core/flex-field-asset-inspector.js'

describe('flex field asset inspector', () => {
    it('lists loaded urls from getLoadedUrls', () => {
        const inspector = createAssetInspector({
            getLoadedUrls: () => [
                'https://example.test/css/teleported-menu.css',
                'https://example.test/css/phone-field.css',
            ],
        })

        assert.deepEqual(inspector.listUrls(), [
            'https://example.test/css/teleported-menu.css',
            'https://example.test/css/phone-field.css',
        ])
    })

    it('detects duplicate hrefs (I1)', () => {
        const duplicate = 'https://example.test/css/teleported-menu.css'
        const inspector = createAssetInspector({
            getLoadedUrls: () => [duplicate, duplicate, 'https://example.test/css/phone-field.css'],
        })

        assert.deepEqual(inspector.duplicateHrefs(), [duplicate])

        const report = inspector.inspect()
        assert.equal(report.invariants.I1, 'fail')
        assert.equal(report.invariants.I34, 'pass')
        assert.ok(report.failingInvariants.includes('I1'))
    })

    it('evaluateInvariants flags legacy retain APIs (I34)', () => {
        const invariants = evaluateInvariants({
            duplicates: [],
            refCounts: { 'https://example.test/a.css': 1 },
            consumers: [],
            modalStack: [],
            inflight: [],
            legacySymbols: ['pageRetainedUrls'],
            urls: ['https://example.test/a.css'],
            document: null,
            injector: { getInflightUrls: () => [], scheduleResyncFromDom: () => {}, getConsumerGraph: () => ({}) },
        })

        assert.equal(invariants.I34, 'fail')
        assert.equal(invariants.I31, 'pass')
    })

    it('evaluateInvariants exposes all I1-I38 contract keys', () => {
        const invariants = evaluateInvariants({
            duplicates: [],
            refCounts: {},
            consumers: [],
            modalStack: [],
            inflight: [],
            legacySymbols: [],
            urls: [],
            document: null,
            injector: {
                getInflightUrls: () => [],
                scheduleResyncFromDom: () => {},
                getConsumerGraph: () => ({}),
            },
        })

        for (const id of INVARIANT_IDS) {
            assert.ok(Object.hasOwn(invariants, id), `missing ${id}`)
        }
    })
})
