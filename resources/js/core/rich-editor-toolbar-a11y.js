const TOOL_SELECTOR = '.fi-fo-rich-editor-tool:not([disabled]), .fi-fo-rich-editor-dropdown-tool-trigger:not([disabled])'

export function getVisibleToolbarTools(toolbarRoot) {
    if (! toolbarRoot) {
        return []
    }

    return Array.from(toolbarRoot.querySelectorAll(TOOL_SELECTOR))
        .filter((element) => element != null && element.offsetParent !== null)
}

export function prepareToolbarTabOrder(toolbarRoot, index = 0) {
    const tools = getVisibleToolbarTools(toolbarRoot)

    if (tools.length === 0) {
        return
    }

    const targetIndex = Math.max(0, Math.min(index, tools.length - 1))

    tools.forEach((tool, toolIndex) => {
        tool.tabIndex = toolIndex === targetIndex ? 0 : -1
    })
}

export function focusToolbarToolAt(toolbarRoot, index) {
    const tools = getVisibleToolbarTools(toolbarRoot)

    if (tools.length === 0) {
        return
    }

    const targetIndex = Math.max(0, Math.min(index, tools.length - 1))

    prepareToolbarTabOrder(toolbarRoot, targetIndex)
    tools[targetIndex].focus()
}

export function setupToolbarKeyboardNavigation(toolbarRoot) {
    if (! toolbarRoot) {
        return () => {}
    }

    const onKeyDown = (event) => {
        const tools = getVisibleToolbarTools(toolbarRoot)
        const currentIndex = tools.indexOf(document.activeElement)

        if (currentIndex === -1) {
            return
        }

        let nextIndex = null

        if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
            nextIndex = currentIndex + 1
        }

        if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
            nextIndex = currentIndex - 1
        }

        if (event.key === 'Home') {
            nextIndex = 0
        }

        if (event.key === 'End') {
            nextIndex = tools.length - 1
        }

        if (nextIndex === null) {
            return
        }

        event.preventDefault()
        focusToolbarToolAt(toolbarRoot, nextIndex)
    }

    toolbarRoot.addEventListener('keydown', onKeyDown)
    prepareToolbarTabOrder(toolbarRoot, 0)

    return () => {
        toolbarRoot.removeEventListener('keydown', onKeyDown)
    }
}
