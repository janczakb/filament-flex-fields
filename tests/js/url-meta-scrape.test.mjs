import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    computeMinSkeletonRemaining,
    hasPreviewData,
    isBlockedScrapeHost,
    isScrapeCandidate,
    isValidHttpUrl,
    readUrlMetaCache,
    resolveUrlWithPrefix,
    shouldShowPreviewCard,
    shouldShowPreviewSkeleton,
    shouldShowPreviewThumb,
    stripUrlPrefix,
    writeUrlMetaCache,
} from '../../resources/js/support/url-meta-scrape.js'

describe('url-meta-scrape', () => {
    it('validates http(s) urls', () => {
        assert.equal(isValidHttpUrl('https://example.com'), true)
        assert.equal(isValidHttpUrl('ftp://example.com'), false)
        assert.equal(isValidHttpUrl('not-a-url'), false)
    })

    it('requires plausible host before scraping', () => {
        assert.equal(isScrapeCandidate('https://a', 10), false)
        assert.equal(isScrapeCandidate('https://example.com', 10), true)
        assert.equal(isScrapeCandidate('http://localhost/page', 10), false)
        assert.equal(isScrapeCandidate('http://127.0.0.1/page', 10), false)
        assert.equal(isBlockedScrapeHost('metadata.google.internal'), true)
    })

    it('caches preview payloads per url', () => {
        writeUrlMetaCache('https://example.com', {
            title: 'Example',
            description: 'Desc',
            image: null,
        })

        assert.deepEqual(readUrlMetaCache('https://example.com'), {
            title: 'Example',
            description: 'Desc',
            image: null,
        })
    })

    it('hides preview card when metadata is empty', () => {
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

    it('shows preview card while fetching metadata or preloading image', () => {
        assert.equal(
            shouldShowPreviewCard({
                previewEnabled: true,
                isFetching: true,
                isImagePending: false,
                preview: { title: null, description: null, image: null },
            }),
            true,
        )

        assert.equal(
            shouldShowPreviewCard({
                previewEnabled: true,
                isFetching: false,
                isImagePending: true,
                preview: { title: 'Example', description: null, image: 'https://example.com/a.jpg' },
            }),
            true,
        )

        assert.equal(
            shouldShowPreviewCard({
                previewEnabled: true,
                isFetching: false,
                isImagePending: false,
                preview: { title: 'Example', description: null, image: null },
            }),
            true,
        )
    })

    it('shows skeleton while fetching or preloading image', () => {
        assert.equal(
            shouldShowPreviewSkeleton({ isFetching: true, isImagePending: false }),
            true,
        )

        assert.equal(
            shouldShowPreviewSkeleton({ isFetching: false, isImagePending: true }),
            true,
        )

        assert.equal(
            shouldShowPreviewSkeleton({ isFetching: false, isImagePending: false }),
            false,
        )

        assert.equal(
            shouldShowPreviewSkeleton({ isFetching: false, isImagePending: false, isMinRevealPending: true }),
            true,
        )
    })

    it('shows thumb skeleton until image is ready', () => {
        assert.equal(
            shouldShowPreviewThumb({ isFetching: false, isImagePending: true, image: 'https://example.com/a.jpg' }),
            true,
        )

        assert.equal(
            shouldShowPreviewThumb({ isFetching: false, isImagePending: false, image: null }),
            false,
        )

        assert.equal(
            shouldShowPreviewThumb({ isFetching: false, isImagePending: false, image: 'https://example.com/a.jpg' }),
            true,
        )
    })

    it('treats blank strings as empty preview values', () => {
        assert.equal(
            hasPreviewData({ title: '   ', description: '', image: null }),
            false,
        )
    })

    it('strips configured prefix from display values', () => {
        assert.equal(
            stripUrlPrefix('https://example.com/article', 'https://'),
            'example.com/article',
        )
    })

    it('resolves full urls from prefixed input values', () => {
        assert.equal(
            resolveUrlWithPrefix('example.com/article', 'https://'),
            'https://example.com/article',
        )

        assert.equal(
            resolveUrlWithPrefix('https://laravel.com', 'https://'),
            'https://laravel.com',
        )
    })

    it('computes remaining min skeleton duration from start time', () => {
        assert.equal(computeMinSkeletonRemaining(1_000, 500, 1_200), 300)
        assert.equal(computeMinSkeletonRemaining(1_000, 500, 1_600), 0)
        assert.equal(computeMinSkeletonRemaining(1_000, 0, 1_100), 0)
    })

    it('normalizes pasted full urls on blur via strip and resolve helpers', () => {
        const prefix = 'https://'
        const pasted = 'https://example.com/article'
        const resolved = resolveUrlWithPrefix(pasted, prefix)
        const display = stripUrlPrefix(resolved, prefix)

        assert.equal(resolved, 'https://example.com/article')
        assert.equal(display, 'example.com/article')
    })

    it('strips prefix from display value after blur normalization', () => {
        const prefix = 'https://'
        const resolved = resolveUrlWithPrefix('https://laravel.com/docs', prefix)

        assert.equal(stripUrlPrefix(resolved, prefix), 'laravel.com/docs')
    })
})
