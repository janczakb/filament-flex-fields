/** @type {MediaQueryList | null} */
let reducedMotionQuery = null

/** @type {MediaQueryList | null} */
let colorSchemeQuery = null

export function resolveTeleportedMenuZIndex() {
    if (document.querySelector('.fi-modal.fi-modal-open') !== null) {
        return 'var(--fff-z-dropdown-modal, 60)'
    }

    return 'var(--fff-z-dropdown, 20)'
}

export function resolveCalculatorPanelZIndex() {
    if (document.querySelector('.fi-modal.fi-modal-open') !== null) {
        return 120
    }

    return 100
}

export function prefersReducedMotion() {
    reducedMotionQuery ??= window.matchMedia('(prefers-reduced-motion: reduce)')

    return reducedMotionQuery.matches
}

export function resolveIsDark() {
    if (document.documentElement.classList.contains('dark')) {
        return true
    }

    if (document.body.classList.contains('dark')) {
        return true
    }

    try {
        const alpineTheme = window.Alpine?.store?.('theme')

        if (alpineTheme === 'dark') {
            return true
        }

        if (alpineTheme === 'light') {
            return false
        }
    } catch {
        // Alpine may not be ready yet.
    }

    colorSchemeQuery ??= window.matchMedia('(prefers-color-scheme: dark)')

    return colorSchemeQuery.matches
}

/** Stable fingerprint for theme-sensitive caches (token copies, glass chrome). */
export function resolveThemeFingerprint() {
    return resolveIsDark() ? 'dark' : 'light'
}
