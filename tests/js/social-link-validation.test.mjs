import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    availablePlatforms,
    normalizeSocialLinksState,
    validateSocialLink,
} from '../../resources/js/support/social-link-validation.js'

describe('social-link-validation', () => {
    it('validates platform hostnames', () => {
        assert.equal(validateSocialLink('instagram', 'https://instagram.com/user'), null)
        assert.equal(validateSocialLink('instagram', 'https://example.com/user'), 'platform_mismatch')
        assert.equal(validateSocialLink('website', 'https://example.com'), null)
        assert.equal(validateSocialLink('x', 'https://twitter.com/user'), null)
    })

    it('normalizes and deduplicates links while keeping empty urls', () => {
        assert.deepEqual(
            normalizeSocialLinksState([
                { platform: 'instagram', url: 'https://instagram.com/a' },
                { platform: 'instagram', url: 'https://instagram.com/b' },
                { platform: 'github', url: '' },
            ]),
            [
                { platform: 'instagram', url: 'https://instagram.com/a' },
                { platform: 'github', url: '' },
            ],
        )
    })

    it('lists only unused platforms respecting max links', () => {
        const all = [
            { value: 'instagram', label: 'Instagram' },
            { value: 'x', label: 'X' },
            { value: 'github', label: 'GitHub' },
        ]

        assert.deepEqual(
            availablePlatforms(all, [{ platform: 'instagram', url: 'https://instagram.com/a' }], null),
            [
                { value: 'x', label: 'X' },
                { value: 'github', label: 'GitHub' },
            ],
        )

        assert.deepEqual(
            availablePlatforms(all, [{ platform: 'instagram', url: 'https://instagram.com/a' }], 1),
            [],
        )
    })
})
