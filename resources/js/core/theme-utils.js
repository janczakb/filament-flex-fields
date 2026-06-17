export function resolveTeleportedMenuZIndex() {
    if (document.querySelector('.fi-modal.fi-modal-open') !== null) {
        return 'var(--fff-z-dropdown-modal, 60)'
    }

    return 'var(--fff-z-dropdown, 20)'
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

    return window.matchMedia('(prefers-color-scheme: dark)').matches
}
