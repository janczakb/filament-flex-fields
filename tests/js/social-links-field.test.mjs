import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    collectSocialLinkRowErrors,
    dehydrateSocialLinksState,
    firstSocialLinkValidationError,
    formatSocialLinkUrl,
    hasSocialLinkValidationErrors,
    normalizeSocialLinksState,
    validateSocialLink,
} from '../../resources/js/support/social-link-validation.js'

const platformDefinitions = [
    {
        value: 'instagram',
        label: 'Instagram',
        placeholder: 'https://instagram.com/username',
        hosts: ['instagram.com', 'instagr.am'],
    },
    {
        value: 'mastodon',
        label: 'Mastodon',
        placeholder: 'https://mastodon.social/@username',
        hosts: ['mastodon.social'],
    },
    {
        value: 'website',
        label: 'Website',
        placeholder: 'https://example.com',
        hosts: [],
    },
]

describe('social-link-validation', () => {
    it('validates platform hostnames from dynamic definitions', () => {
        assert.equal(validateSocialLink('instagram', 'https://instagram.com/user', platformDefinitions), null)
        assert.equal(validateSocialLink('instagram', 'https://example.com/user', platformDefinitions), 'platform_mismatch')
        assert.equal(validateSocialLink('website', 'https://example.com', platformDefinitions), null)
        assert.equal(validateSocialLink('mastodon', 'https://mastodon.social/@user', platformDefinitions), null)
        assert.equal(validateSocialLink('mastodon', 'https://example.com/@user', platformDefinitions), 'platform_mismatch')
    })

    it('keeps empty-url rows during normalization and strips them on dehydrate', () => {
        assert.deepEqual(
            normalizeSocialLinksState([
                { platform: 'instagram', url: '' },
                { platform: 'github', url: 'https://github.com/laravel' },
            ]),
            [
                { platform: 'instagram', url: '' },
                { platform: 'github', url: 'https://github.com/laravel' },
            ],
        )

        assert.deepEqual(
            dehydrateSocialLinksState([
                { platform: 'instagram', url: '' },
                { platform: 'github', url: 'https://github.com/laravel' },
            ]),
            [
                { platform: 'github', url: 'https://github.com/laravel' },
            ],
        )
    })

    it('collects row errors and exposes submit-guard helpers', () => {
        const links = [
            { platform: 'instagram', url: '' },
            { platform: 'mastodon', url: 'https://example.com/@user' },
        ]

        const errors = collectSocialLinkRowErrors(links, platformDefinitions)

        assert.equal(hasSocialLinkValidationErrors(errors), true)
        assert.equal(firstSocialLinkValidationError(errors), 'required')
    })

    it('auto-formats urls without an existing scheme', () => {
        assert.equal(formatSocialLinkUrl(' example.com '), 'https://example.com')
        assert.equal(formatSocialLinkUrl('https://instagram.com/user'), 'https://instagram.com/user')
        assert.equal(formatSocialLinkUrl(''), '')
    })
})
