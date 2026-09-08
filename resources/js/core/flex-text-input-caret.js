export function canSetInputSelection(input) {
    if (! input || typeof input.setSelectionRange !== 'function') {
        return false
    }

    // These input types throw InvalidStateError for selection APIs in Chromium/WebKit.
    const type = String(input.type || 'text').toLowerCase()

    return ! [
        'email',
        'number',
        'date',
        'datetime-local',
        'month',
        'time',
        'week',
        'color',
        'range',
        'checkbox',
        'radio',
        'file',
        'hidden',
        'button',
        'submit',
        'reset',
        'image',
    ].includes(type)
}

export function setInputCaretToEnd(input) {
    if (! canSetInputSelection(input)) {
        return false
    }

    const length = input.value.length

    if (length === 0) {
        return true
    }

    try {
        input.setSelectionRange(length, length)
    } catch {
        return false
    }

    return true
}

/**
 * Browsers and Livewire entangle can focus or rewrite the input after Alpine init.
 * Schedule a few safe repaints so the caret lands at the end of prefilled values.
 */
export function scheduleInputCaretToEnd(input) {
    if (! input || ! canSetInputSelection(input) || input.value.length === 0) {
        return () => {}
    }

    let cancelled = false

    const cancel = () => {
        cancelled = true
    }

    const run = () => {
        if (cancelled || document.activeElement !== input) {
            return
        }

        setInputCaretToEnd(input)
    }

    run()

    if (typeof requestAnimationFrame === 'function') {
        requestAnimationFrame(() => {
            run()

            requestAnimationFrame(run)
        })
    }

    setTimeout(run, 0)
    setTimeout(run, 50)

    return cancel
}
