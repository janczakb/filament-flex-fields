export function normalizeYearGridWheelDelta(event, container) {
    if (event.deltaMode === 1) {
        return event.deltaY * 32
    }

    if (event.deltaMode === 2) {
        return event.deltaY * (container?.clientHeight ?? 0)
    }

    return event.deltaY
}

export function applyYearGridWheelScroll(container, deltaY) {
    if (! container || container.scrollHeight <= container.clientHeight + 1) {
        return false
    }

    const maxScroll = Math.max(0, container.scrollHeight - container.clientHeight)
    const next = Math.max(0, Math.min(container.scrollTop + deltaY, maxScroll))

    if (next === container.scrollTop) {
        return false
    }

    container.scrollTop = next

    return true
}

export function handleYearGridWheelEvent(event) {
    const container = event.currentTarget

    if (! container || container.scrollHeight <= container.clientHeight + 1) {
        return
    }

    const deltaY = normalizeYearGridWheelDelta(event, container)
    const maxScroll = Math.max(0, container.scrollHeight - container.clientHeight)
    const scrolled = applyYearGridWheelScroll(container, deltaY)

    if (scrolled) {
        event.preventDefault()
        event.stopPropagation()

        return
    }

    const atTop = container.scrollTop <= 0
    const atBottom = container.scrollTop >= maxScroll - 1

    if ((deltaY < 0 && atTop) || (deltaY > 0 && atBottom)) {
        event.preventDefault()
        event.stopPropagation()
    }
}

export function createYearGridWheelHandler() {
    return handleYearGridWheelEvent
}

export function isYearGridScrollTarget(target) {
    if (! target || typeof target.closest !== 'function') {
        return false
    }

    return target.closest('.fff-date-time-field__year-scroll, .fff-date-time-field__year-overlay') !== null
}

export function bindYearGridWheel(element, handler = handleYearGridWheelEvent) {
    if (! element || ! handler) {
        return () => {}
    }

    element.addEventListener('wheel', handler, { passive: false, capture: true })

    return () => {
        element.removeEventListener('wheel', handler, { capture: true })
    }
}
