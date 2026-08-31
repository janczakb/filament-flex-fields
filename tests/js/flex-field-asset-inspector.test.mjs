import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import { createAssetInspector } from '../../resources/js/core/flex-field-asset-inspector.js'

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

    it('detects duplicate hrefs', () => {
        const duplicate = 'https://example.test/css/teleported-menu.css'
        const inspector = createAssetInspector({
            getLoadedUrls: () => [duplicate, duplicate, 'https://example.test/css/phone-field.css'],
        })

        assert.deepEqual(inspector.duplicateHrefs(), [duplicate])
        assert.deepEqual(inspector.inspect(), {
            urls: [duplicate, duplicate, 'https://example.test/css/phone-field.css'],
            duplicates: [duplicate],
        })
    })
})
