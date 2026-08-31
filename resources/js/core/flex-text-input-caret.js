export function canSetInputSelection(input) {
    if (! input || typeof input.setSelectionRange !== 'function') {
        return false
    }

    // Native number inputs throw InvalidStateError for selection APIs in most browsers.
    return input.type !== 'number'
}

export function setInputCaretToEnd(input) {
    if (! canSetInputSelection(input)) {
        return false
    }

    const length = input.value.length

    if (length === 0) {
        return true
    }

    input.setSelectionRange(length, length)

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
