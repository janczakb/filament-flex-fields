import { resolveIsDark } from './theme-utils.js'

export function applyCalculatorPanelTheme(panel) {
    if (! panel) {
        return
    }

    panel.classList.remove('fff-teleported-menu')

    panel.style.removeProperty('background-color')
    panel.style.removeProperty('backdrop-filter')
    panel.style.removeProperty('-webkit-backdrop-filter')
    panel.style.removeProperty('color')

    const isDark = resolveIsDark()

    panel.classList.toggle('is-dark', isDark)
    panel.classList.toggle('is-light', ! isDark)
}

export function watchCalculatorPanelTheme(panel, callback) {
    if (! panel || typeof callback !== 'function') {
        return () => {}
    }

    const observer = new MutationObserver(() => {
        applyCalculatorPanelTheme(panel)
        callback()
    })

    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    })

    return () => observer.disconnect()
}
