export const SETTINGS_MENU_TRANSITION_MS = 250

export const SETTINGS_MENU_POPOVER_TRANSITION_MS = 220

export const SETTINGS_MENU_MIN_WIDTH_PX = 192

export const SETTINGS_MENU_MAX_WIDTH_PX = 320

export const SETTINGS_MENU_CONTENT_MAX_HEIGHT_PX = 224

const PANEL_MEASURE_STYLE_PROPERTIES = [
    'position',
    'top',
    'right',
    'bottom',
    'left',
    'width',
    'height',
    'min-width',
    'max-width',
]

const MENU_SIZE_STYLE_PROPERTIES = [
    '--fff-video-field-menu-width',
    '--fff-video-field-menu-height',
    'width',
    'height',
]

const viewportTransitionStates = new WeakMap()

const ROOT_VIEW_STATE_ATTR = 'data-menu-view-state'

function setSettingsRootViewState(rootPanel, state) {
    if (! rootPanel) {
        return
    }

    rootPanel.setAttribute(ROOT_VIEW_STATE_ATTR, state)

    if (state === 'active') {
        rootPanel.setAttribute('data-open', '')
    } else {
        rootPanel.removeAttribute('data-open')
    }
}

function snapshotInlineStyle(element, properties) {
    return properties.map((property) => ({
        property,
        value: element.style.getPropertyValue(property),
        priority: element.style.getPropertyPriority(property),
    }))
}

function restoreInlineStyle(element, snapshot) {
    for (const { property, value, priority } of snapshot) {
        if (value) {
            element.style.setProperty(property, value, priority)
        } else {
            element.style.removeProperty(property)
        }
    }
}

function forceLayout(element) {
    if (! element) {
        return
    }

    void element.offsetHeight
}

function resolveMenuAvailableWidth(menu) {
    const raw = menu?.style?.getPropertyValue('--fff-settings-menu-available-width')?.trim()
        || menu?.style?.getPropertyValue('--fff-video-field-menu-available-width')?.trim()

    if (! raw) {
        return null
    }

    const parsed = Number.parseFloat(raw)

    if (! Number.isFinite(parsed) || parsed <= 0) {
        return null
    }

    return Math.floor(parsed)
}

function isolateMenuViewForMeasure(panel, callback) {
    const viewport = panel?.closest('[data-menu-viewport]')
    const mutedSiblings = []

    if (viewport) {
        for (const sibling of viewport.querySelectorAll('[data-menu-view]')) {
            if (sibling === panel || ! (sibling instanceof HTMLElement)) {
                continue
            }

            mutedSiblings.push({
                element: sibling,
                display: sibling.style.getPropertyValue('display'),
                displayPriority: sibling.style.getPropertyPriority('display'),
            })
            sibling.style.setProperty('display', 'none', 'important')
        }
    }

    try {
        return callback()
    } finally {
        for (const { element, display, displayPriority } of mutedSiblings) {
            if (display) {
                element.style.setProperty('display', display, displayPriority)
            } else {
                element.style.removeProperty('display')
            }
        }
    }
}

export function measureSettingsPanel(panel, minWidth = SETTINGS_MENU_MIN_WIDTH_PX, maxHeight = null) {
    if (! panel) {
        return { width: minWidth, height: 0, naturalHeight: 0 }
    }

    const menu = panel.closest('[data-fff-settings-menu]') ?? panel.closest('.fff-video-field__settings-menu')
    const availableWidth = resolveMenuAvailableWidth(menu)
    const snapshot = snapshotInlineStyle(panel, PANEL_MEASURE_STYLE_PROPERTIES)

    return isolateMenuViewForMeasure(panel, () => {
        try {
            if (panel.hasAttribute('hidden')) {
                panel.removeAttribute('hidden')
            }

            panel.style.setProperty('position', 'absolute', 'important')
            panel.style.setProperty('top', '0px', 'important')
            panel.style.setProperty('right', 'auto', 'important')
            panel.style.setProperty('bottom', 'auto', 'important')
            panel.style.setProperty('left', '0px', 'important')
            panel.style.setProperty('width', 'max-content', 'important')
            panel.style.setProperty('height', 'auto', 'important')
            panel.style.setProperty('min-width', `${minWidth}px`, 'important')
            panel.style.setProperty('max-width', 'none', 'important')

            forceLayout(panel)

            let rect = panel.getBoundingClientRect()
            const measuredWidth = Math.ceil(Math.max(minWidth, rect.width, panel.scrollWidth))
            const cappedWidth = Math.min(
                SETTINGS_MENU_MAX_WIDTH_PX,
                availableWidth ? Math.max(minWidth, Math.min(measuredWidth, availableWidth)) : measuredWidth,
            )

            if (cappedWidth !== measuredWidth) {
                panel.style.setProperty('width', `${cappedWidth}px`, 'important')
                panel.style.setProperty('max-width', `${cappedWidth}px`, 'important')
                forceLayout(panel)
                rect = panel.getBoundingClientRect()
            }

            const naturalHeight = Math.ceil(Math.max(rect.height, panel.scrollHeight, 1))
            const resolvedMaxHeight = maxHeight ?? SETTINGS_MENU_CONTENT_MAX_HEIGHT_PX

            return {
                width: cappedWidth,
                height: naturalHeight,
                naturalHeight,
                cappedHeight: Math.min(resolvedMaxHeight, naturalHeight),
            }
        } finally {
            restoreInlineStyle(panel, snapshot)
            forceLayout(panel)
        }
    })
}

export function applySettingsMenuViewportSize(menu, size) {
    if (! menu || ! size) {
        return
    }

    const width = Math.min(
        SETTINGS_MENU_MAX_WIDTH_PX,
        Math.max(size.width, SETTINGS_MENU_MIN_WIDTH_PX),
    )
    const naturalHeight = Math.max(size.naturalHeight ?? size.height, 1)
    const maxHeight = resolveMenuMaxHeight(menu)
    const isScrollable = naturalHeight > maxHeight

    menu.style.setProperty('--fff-video-field-menu-width', `${width}px`)
    menu.style.setProperty('--fff-video-field-menu-height', `${naturalHeight}px`)
    menu.style.width = `${width}px`
    menu.style.height = `${naturalHeight}px`

    if (isScrollable) {
        menu.setAttribute('data-scrollable', '')
    } else {
        menu.removeAttribute('data-scrollable')
    }
}

export function clearSettingsMenuViewportSize(menu) {
    if (! menu) {
        return
    }

    for (const property of MENU_SIZE_STYLE_PROPERTIES) {
        menu.style.removeProperty(property)
    }

    menu.style.removeProperty('--fff-video-field-menu-max-height')
    menu.removeAttribute('data-scrollable')
}

export function getSettingsSubmenuTransitionAttrs(phase, direction) {
    return {
        'data-open': phase !== 'hidden' ? '' : null,
        hidden: phase === 'hidden' ? '' : null,
        'data-direction': direction,
        'data-starting-style': phase === 'entering' ? '' : null,
        'data-ending-style': phase === 'exiting' ? '' : null,
    }
}

export function isSettingsRootInactive(activeSubmenu, phase) {
    if (! activeSubmenu || phase === 'hidden' || phase === 'entering' || phase === 'exiting') {
        return false
    }

    return phase === 'active'
}

function getViewportTransitionState(menu) {
    let state = viewportTransitionStates.get(menu)

    if (! state) {
        state = {
            pending: null,
            phaseKeys: new WeakMap(),
        }
        viewportTransitionStates.set(menu, state)
    }

    return state
}

function resolveMenuMaxHeight(menu) {
    const raw = menu?.style?.getPropertyValue('--fff-settings-menu-max-height')?.trim()
        || menu?.style?.getPropertyValue('--fff-video-field-menu-max-height')?.trim()

    if (! raw) {
        return SETTINGS_MENU_CONTENT_MAX_HEIGHT_PX
    }

    const parsed = Number.parseFloat(raw)

    if (! Number.isFinite(parsed) || parsed <= 0) {
        return SETTINGS_MENU_CONTENT_MAX_HEIGHT_PX
    }

    return Math.min(SETTINGS_MENU_CONTENT_MAX_HEIGHT_PX, Math.floor(parsed))
}

function resolveMenuAvailableWidthForPending(menu) {
    return resolveMenuAvailableWidth(menu)
}

function prepareEnteringSettingsView(menu, rootPanel, enteringPanel, state, minWidth) {
    const maxHeight = resolveMenuMaxHeight(menu)
    const availableWidth = resolveMenuAvailableWidthForPending(menu)
    const fromSize = measureSettingsPanel(rootPanel, minWidth, maxHeight)

    state.pending = {
        entering: enteringPanel,
        availableWidth,
        fromSize,
        toSize: measureSettingsPanel(enteringPanel, minWidth, maxHeight),
    }

    setSettingsRootViewState(rootPanel, 'active')
    applySettingsMenuViewportSize(menu, fromSize)
    forceLayout(menu)
}

function startEnteringSettingsView(menu, rootPanel, enteringPanel, state, minWidth) {
    const maxHeight = resolveMenuMaxHeight(menu)
    const availableWidth = resolveMenuAvailableWidthForPending(menu)
    const pending = state.pending?.entering === enteringPanel && state.pending?.availableWidth === availableWidth
        ? state.pending
        : {
            fromSize: measureSettingsPanel(rootPanel, minWidth, maxHeight),
            toSize: measureSettingsPanel(enteringPanel, minWidth, maxHeight),
        }

    state.pending = null

    applySettingsMenuViewportSize(menu, pending.fromSize)
    forceLayout(rootPanel)
    setSettingsRootViewState(rootPanel, 'inactive')
    forceLayout(rootPanel)
    applySettingsMenuViewportSize(menu, pending.toSize)
}

function startExitingSettingsView(menu, rootPanel, exitingPanel, state, minWidth) {
    state.pending = null

    const maxHeight = resolveMenuMaxHeight(menu)
    const fromSize = measureSettingsPanel(exitingPanel, minWidth, maxHeight)
    const toSize = measureSettingsPanel(rootPanel, minWidth, maxHeight)

    applySettingsMenuViewportSize(menu, fromSize)
    setSettingsRootViewState(rootPanel, 'inactive')
    forceLayout(rootPanel)
    setSettingsRootViewState(rootPanel, 'active')
    forceLayout(rootPanel)
    applySettingsMenuViewportSize(menu, toSize)
}

export function syncSettingsMenuViewportFromPanel(menu, panel, minWidth = SETTINGS_MENU_MIN_WIDTH_PX) {
    if (! menu || ! panel) {
        return
    }

    applySettingsMenuViewportSize(menu, measureSettingsPanel(panel, minWidth, resolveMenuMaxHeight(menu)))
}

/** @deprecated Use syncSettingsMenuViewportFromPanel */
export function syncSettingsMenuViewRoot(menu, rootPanel, hasActiveChildView, minWidth = SETTINGS_MENU_MIN_WIDTH_PX) {
    if (! menu || ! rootPanel || hasActiveChildView) {
        return
    }

    syncSettingsMenuViewportFromPanel(menu, rootPanel, minWidth)
}

export function syncSettingsMenuViewTransition(menu, viewPanel, viewState, rootPanel, minWidth = SETTINGS_MENU_MIN_WIDTH_PX) {
    if (! menu || ! viewPanel || ! rootPanel) {
        return
    }

    const state = getViewportTransitionState(menu)
    const phaseKey = `${viewState.phase}:${viewState.direction}`
    const shouldResyncActiveView = viewState.phase === 'active'
        && rootPanel.getAttribute(ROOT_VIEW_STATE_ATTR) !== 'inactive'

    if (state.phaseKeys.get(viewPanel) === phaseKey && ! shouldResyncActiveView) {
        return
    }

    state.phaseKeys.set(viewPanel, phaseKey)

    if (viewState.phase === 'hidden') {
        state.phaseKeys.delete(viewPanel)

        return
    }

    if (viewState.phase === 'entering') {
        prepareEnteringSettingsView(menu, rootPanel, viewPanel, state, minWidth)

        return
    }

    if (viewState.phase === 'active') {
        startEnteringSettingsView(menu, rootPanel, viewPanel, state, minWidth)

        return
    }

    startExitingSettingsView(menu, rootPanel, viewPanel, state, minWidth)
}

export function runSettingsMenuPopoverFrame(callback) {
    requestAnimationFrame(() => {
        requestAnimationFrame(callback)
    })
}

export async function waitForSettingsMenuAnimations(element, fallbackMs = SETTINGS_MENU_TRANSITION_MS) {
    const animations = element?.getAnimations?.({ subtree: true }) ?? []

    if (! animations.length) {
        await new Promise((resolve) => {
            setTimeout(resolve, fallbackMs)
        })

        return
    }

    await Promise.all(animations.map((animation) => animation.finished)).catch(() => {})
}

export async function waitForSettingsMenuPopoverAnimation(element, fallbackMs = SETTINGS_MENU_POPOVER_TRANSITION_MS) {
    const animations = element?.getAnimations?.() ?? []

    if (! animations.length) {
        await new Promise((resolve) => {
            setTimeout(resolve, fallbackMs)
        })

        return
    }

    await Promise.all(animations.map((animation) => animation.finished)).catch(() => {})
}
