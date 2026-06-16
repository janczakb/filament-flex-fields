/**
 * Client-side social link validation — mirrors SocialLinkValidator.php host rules.
 */

const DEFAULT_PLATFORM_HOSTS = {
    instagram: ['instagram.com', 'instagr.am'],
    x: ['x.com', 'twitter.com'],
    linkedin: ['linkedin.com'],
    youtube: ['youtube.com', 'youtu.be', 'youtube-nocookie.com'],
    facebook: ['facebook.com', 'fb.com', 'fb.me'],
    tiktok: ['tiktok.com'],
    github: ['github.com'],
    telegram: ['t.me', 'telegram.me', 'telegram.org'],
    whatsapp: ['wa.me', 'api.whatsapp.com', 'whatsapp.com'],
    pinterest: ['pinterest.com', 'pin.it'],
    threads: ['threads.net'],
    discord: ['discord.com', 'discord.gg'],
    messenger: ['m.me', 'messenger.com', 'm.facebook.com'],
    reddit: ['reddit.com'],
    twitch: ['twitch.tv'],
    vimeo: ['vimeo.com'],
    vk: ['vk.com', 'vk.ru'],
    website: [],
}

function normalizeHost(url) {
    try {
        const host = new URL(url).hostname.toLowerCase()

        return host.startsWith('www.') ? host.slice(4) : host
    } catch {
        return null
    }
}

export function isValidHttpUrl(url) {
    try {
        const parsed = new URL(url)

        return parsed.protocol === 'http:' || parsed.protocol === 'https:'
    } catch {
        return false
    }
}

export function resolvePlatformHosts(platform, platformDefinitions = []) {
    const definition = platformDefinitions.find((entry) => entry.value === platform)

    if (definition && Array.isArray(definition.hosts)) {
        return definition.hosts
    }

    return DEFAULT_PLATFORM_HOSTS[platform] ?? null
}

export function validateSocialLink(platform, url, platformDefinitions = []) {
    const trimmedPlatform = String(platform ?? '').trim()
    const trimmedUrl = String(url ?? '').trim()

    if (trimmedPlatform === '' || trimmedUrl === '') {
        return 'required'
    }

    const hosts = resolvePlatformHosts(trimmedPlatform, platformDefinitions)

    if (hosts === null) {
        return 'unknown_platform'
    }

    if (! isValidHttpUrl(trimmedUrl)) {
        return 'invalid_url'
    }

    if (hosts.length === 0) {
        return null
    }

    const host = normalizeHost(trimmedUrl)

    if (! host) {
        return 'invalid_url'
    }

    for (const pattern of hosts) {
        if (host === pattern || host.endsWith(`.${pattern}`)) {
            return null
        }
    }

    return 'platform_mismatch'
}

export function normalizeSocialLinksState(state) {
    if (! Array.isArray(state)) {
        return []
    }

    const seen = new Set()
    const links = []

    for (const entry of state) {
        if (! entry || typeof entry !== 'object') {
            continue
        }

        const platform = String(entry.platform ?? '').trim()
        const url = String(entry.url ?? '').trim()

        if (platform === '' || seen.has(platform)) {
            continue
        }

        seen.add(platform)
        links.push({ platform, url })
    }

    return links
}

export function dehydrateSocialLinksState(links) {
    return links
        .map((link) => ({
            platform: link.platform,
            url: String(link.url ?? '').trim(),
        }))
        .filter((link) => link.platform !== '' && link.url !== '')
}

export function collectSocialLinkRowErrors(links, platformDefinitions = [], messageResolver = null) {
    const errors = {}

    links.forEach((link, index) => {
        const code = validateSocialLink(link.platform, link.url, platformDefinitions)

        if (code !== null) {
            errors[index] = typeof messageResolver === 'function'
                ? messageResolver(code, link.platform)
                : code
        }
    })

    return errors
}

export function hasSocialLinkValidationErrors(errors) {
    return Object.keys(errors).length > 0
}

export function firstSocialLinkValidationError(errors) {
    const keys = Object.keys(errors).map(Number).sort((left, right) => left - right)

    if (keys.length === 0) {
        return null
    }

    return errors[keys[0]] ?? null
}

export function formatSocialLinkUrl(url) {
    const trimmed = String(url ?? '').trim()

    if (trimmed === '') {
        return ''
    }

    if (/^[a-z][a-z0-9+.-]*:\/\//i.test(trimmed)) {
        return trimmed
    }

    return `https://${trimmed}`
}

export function availablePlatforms(allPlatforms, links, maxLinks = null) {
    const used = new Set(links.map((link) => link.platform))
    const available = allPlatforms.filter((platform) => ! used.has(platform.value))

    if (maxLinks !== null && links.length >= maxLinks) {
        return []
    }

    return available
}
