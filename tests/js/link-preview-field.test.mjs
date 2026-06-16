import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    DEFAULT_IMAGE_PRELOAD_TIMEOUT_MS,
    hasPreviewData,
    isBlockedScrapeHost,
    isScrapeCandidate,
    resolveUrlWithPrefix,
    shouldShowPreviewCard,
    stripUrlPrefix,
} from '../../resources/js/support/url-meta-scrape.js'

describe('link-preview-field helpers', () => {
    it('uses a five second image preload timeout constant', () => {
        assert.equal(DEFAULT_IMAGE_PRELOAD_TIMEOUT_MS, 5_000)
    })

    it('blocks localhost scrape candidates client-side', () => {
        assert.equal(isScrapeCandidate('http://localhost/page', 10), false)
        assert.equal(isScrapeCandidate('http://127.0.0.1/page', 10), false)
        assert.equal(isScrapeCandidate('https://example.com/page', 10), true)
    })

    it('blocks private and metadata hosts', () => {
        assert.equal(isBlockedScrapeHost('10.0.0.1'), true)
        assert.equal(isBlockedScrapeHost('169.254.169.254'), true)
        assert.equal(isBlockedScrapeHost('metadata.google.internal'), true)
        assert.equal(isBlockedScrapeHost('example.com'), false)
    })

    it('shows preview card when metadata exists or fetch is pending', () => {
        assert.equal(
            shouldShowPreviewCard({
                previewEnabled: true,
                isFetching: false,
                isImagePending: false,
                preview: { title: 'Example', description: null, image: null },
            }),
            true,
        )

        assert.equal(
            shouldShowPreviewCard({
                previewEnabled: true,
                isFetching: false,
                isImagePending: false,
                preview: { title: null, description: null, image: null },
            }),
            false,
        )
    })

    it('normalizes prefixed display and resolved urls together', () => {
        const prefix = 'https://'
        const resolved = resolveUrlWithPrefix('example.com/article', prefix)

        assert.equal(resolved, 'https://example.com/article')
        assert.equal(stripUrlPrefix(resolved, prefix), 'example.com/article')
        assert.equal(hasPreviewData({ title: 'Example', description: null, image: null }), true)
    })
})
