const BACKDROP_CLASS = 'fff-overlay-backdrop'

/**
 * @param {Document} document
 * @param {string} overlayId
 * @param {{ onDismiss?: () => void, zIndex?: number }} [options]
 * @returns {HTMLElement}
 */
export function createOverlayBackdrop(document, overlayId, options = {}) {
    const existing = document.querySelector(`[data-fff-overlay-backdrop="${overlayId}"]`)

    if (existing instanceof HTMLElement) {
        return existing
    }

    const backdrop = document.createElement('div')
    backdrop.className = BACKDROP_CLASS
    backdrop.dataset.fffOverlayBackdrop = overlayId
    backdrop.setAttribute('aria-hidden', 'true')

    if (options.zIndex != null && backdrop.style) {
        backdrop.style.zIndex = String(options.zIndex)
    }

    backdrop.addEventListener?.('click', () => {
        options.onDismiss?.()
    })

    document.body?.appendChild?.(backdrop)

    requestAnimationFrame(() => {
        backdrop.classList.add('is-visible')
    })

    return backdrop
}

/**
 * @param {Document} document
 * @param {string} overlayId
 */
export function removeOverlayBackdrop(document, overlayId) {
    const backdrop = document.querySelector?.(`[data-fff-overlay-backdrop="${overlayId}"]`)

    if (! backdrop || typeof backdrop.remove !== 'function') {
        return
    }

    backdrop.classList.remove('is-visible')
    backdrop.classList.add('is-leaving')

    const remove = () => {
        backdrop.remove()
    }

    backdrop.addEventListener('transitionend', remove, { once: true })
    window.setTimeout(remove, 200)
}
