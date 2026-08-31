/**
 * Inline combobox trigger input: the input value mirrors the selected label when closed
 * or when the menu opens; typing replaces it for filtering. An empty input clears selection.
 */

export function shouldInlineSearchInputBeEditable(comboboxOpen, inlineSearchFocused) {
    return Boolean(comboboxOpen || inlineSearchFocused)
}

/**
 * @param {{ comboboxQuery: string }} state
 */
export function resolveInlineSearchInputValue(state) {
    return state.comboboxQuery ?? ''
}

/**
 * @param {{ comboboxQuery: string, searchPrompt: string }} state
 */
export function resolveInlineSearchInputPlaceholder(state) {
    if (String(state.comboboxQuery ?? '').length > 0) {
        return ''
    }

    return state.searchPrompt ?? ''
}

export function stripHtmlToPlainText(html) {
    const value = String(html ?? '')

    if (! value.includes('<')) {
        return value
    }

    return value.replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim()
}

export function resolveInlineSearchInputAfterClose(hasSelection, closedLabel) {
    return hasSelection ? (closedLabel ?? '') : ''
}

/**
 * Place the text caret at inline-start (right in RTL, left in LTR).
 */
export function positionInlineSearchCaretAtInlineStart(input) {
    if (! input || typeof input.setSelectionRange !== 'function') {
        return
    }

    try {
        input.setSelectionRange(0, 0)
    } catch {
        // Some browsers reject selection changes while the input is readonly.
    }
}
