import { prefersReducedMotion } from './theme-utils.js'

export { prefersReducedMotion }

/** @typedef {'fast' | 'base' | 'slow'} MotionDurationToken */

/** @type {Record<MotionDurationToken, string>} */
const DURATION_CSS_VARS = {
    fast: '--fff-motion-duration-fast',
    base: '--fff-motion-duration-base',
    slow: '--fff-motion-duration-slow',
}

/** @type {Record<MotionDurationToken, number>} */
const DURATION_FALLBACK_MS = {
    fast: 150,
    base: 250,
    slow: 400,
}

/**
 * Parse a CSS time value (`150ms`, `0.25s`) into milliseconds.
 *
 * @param {string} raw
 * @returns {number|null}
 */
function parseDurationMs(raw) {
    const value = raw.trim()

    if (value === '') {
        return null
    }

    if (value.endsWith('ms')) {
        const parsed = Number.parseFloat(value)

        return Number.isFinite(parsed) ? parsed : null
    }

    if (value.endsWith('s')) {
        const parsed = Number.parseFloat(value)

        return Number.isFinite(parsed) ? parsed * 1000 : null
    }

    const parsed = Number.parseFloat(value)

    return Number.isFinite(parsed) ? parsed : null
}

/**
 * Resolve a motion duration token to milliseconds, honoring reduced motion and CSS vars.
 *
 * @param {MotionDurationToken} [token='base']
 * @returns {number}
 */
export function motionDuration(token = 'base') {
    const resolvedToken = DURATION_CSS_VARS[token] ? token : 'base'

    if (typeof document === 'undefined' || typeof window === 'undefined') {
        return DURATION_FALLBACK_MS[resolvedToken]
    }

    if (prefersReducedMotion()) {
        return 0
    }

    const raw = getComputedStyle(document.documentElement)
        .getPropertyValue(DURATION_CSS_VARS[resolvedToken])
        .trim()

    return parseDurationMs(raw) ?? DURATION_FALLBACK_MS[resolvedToken]
}
