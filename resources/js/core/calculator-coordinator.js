export const CALCULATOR_MOBILE_QUERY = '(max-width: 767px)'

const panelState = {
    isOpen: false,
    activeFieldId: null,
    panelPosition: null,
    listeners: new Set(),
}

/** @type {Map<string, { expression: string, result: string | null, label: string }>} */
const fieldSessions = new Map()

/** @type {Map<string, object>} */
const fieldRegistry = new Map()

function notify() {
    for (const listener of panelState.listeners) {
        listener()
    }
}

export function subscribeCalculatorPanel(listener) {
    panelState.listeners.add(listener)

    return () => panelState.listeners.delete(listener)
}

export function getCalculatorPanelState() {
    return {
        isOpen: panelState.isOpen,
        activeFieldId: panelState.activeFieldId,
        panelPosition: panelState.panelPosition,
    }
}

export function isCalculatorMobileViewport() {
    return window.matchMedia(CALCULATOR_MOBILE_QUERY).matches
}

export function registerCalculatorField(fieldId, api) {
    fieldRegistry.set(fieldId, api)

    if (! fieldSessions.has(fieldId)) {
        fieldSessions.set(fieldId, {
            expression: '',
            result: null,
            label: api.getLabel?.() ?? fieldId,
        })
    } else {
        const session = fieldSessions.get(fieldId)
        session.label = api.getLabel?.() ?? session.label
    }

    return () => {
        fieldRegistry.delete(fieldId)

        if (panelState.activeFieldId === fieldId && panelState.isOpen) {
            closeCalculatorPanel()
        }
    }
}

export function getFieldSession(fieldId) {
    if (! fieldSessions.has(fieldId)) {
        fieldSessions.set(fieldId, { expression: '', result: null, label: fieldId })
    }

    return fieldSessions.get(fieldId)
}

export function updateFieldSession(fieldId, patch, { notifyListeners = false } = {}) {
    const session = getFieldSession(fieldId)
    const next = { ...session, ...patch }

    if (
        session.expression === next.expression
        && session.result === next.result
        && session.label === next.label
    ) {
        return
    }

    fieldSessions.set(fieldId, next)

    if (notifyListeners) {
        notify()
    }
}

export function getActiveFieldApi() {
    if (! panelState.activeFieldId) {
        return null
    }

    return fieldRegistry.get(panelState.activeFieldId) ?? null
}

export function openCalculatorPanel(fieldId, anchorElement) {
    const api = fieldRegistry.get(fieldId)

    if (! api) {
        return
    }

    const wasOpen = panelState.isOpen
    const previousFieldId = panelState.activeFieldId
    const switchedField = wasOpen && previousFieldId !== fieldId

    if (switchedField && previousFieldId) {
        fieldRegistry.get(previousFieldId)?.onPanelClose?.()
    }

    panelState.activeFieldId = fieldId
    panelState.isOpen = true

    if (switchedField) {
        panelState.panelPosition = null
    }

    const session = getFieldSession(fieldId)

    if (! session.expression && api.getInputValue?.()) {
        session.expression = String(api.getInputValue())
    }

    notify()

    api.onPanelOpen?.({
        anchorElement,
        wasOpen,
        switchedField,
    })
}

export function closeCalculatorPanel() {
    if (! panelState.isOpen) {
        return
    }

    panelState.isOpen = false
    getActiveFieldApi()?.onPanelClose?.()
    notify()
}

export function setCalculatorPanelPosition(position, { notifyListeners = false } = {}) {
    panelState.panelPosition = position

    if (notifyListeners) {
        notify()
    }
}

export function applyCalculatorResult(fieldId = panelState.activeFieldId) {
    const api = fieldRegistry.get(fieldId)
    const session = getFieldSession(fieldId)

    if (! api) {
        return
    }

    const value = session.result ?? session.expression

    if (value === null || value === '') {
        return
    }

    api.applyValue?.(value)
    closeCalculatorPanel()
}

export function seedExpressionFromField(fieldId) {
    const api = fieldRegistry.get(fieldId)
    const session = getFieldSession(fieldId)

    if (! api || session.expression) {
        return
    }

    const current = api.getInputValue?.()

    if (current !== null && current !== undefined && String(current) !== '') {
        updateFieldSession(fieldId, { expression: String(current) })
    }
}
