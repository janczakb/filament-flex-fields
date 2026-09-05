import { scheduleInputCaretToEnd, setInputCaretToEnd } from '../../core/flex-text-input-caret.js'

/**
 * Inline combobox trigger input: the input value mirrors the selected label when closed
 * or when the menu opens; typing replaces it for filtering. An empty input clears selection.
 */

export function shouldInlineSearchInputBeEditable(comboboxOpen, inlineSearchFocused) {
    return Boolean(comboboxOpen || inlineSearchFocused)
}

/**
 * Mobile bottom-sheet hosts the search field even when `inlineSearch()` is enabled —
 * the trigger sits behind the drawer and cannot accept keyboard input reliably.
 *
 * @param {{ searchable?: boolean, inlineSearch?: boolean, sheetPresentation?: boolean }} state
 */
export function shouldShowSelectMenuSearch(state = {}) {
    if (! state.searchable) {
        return false
    }

    if (! state.inlineSearch) {
        return true
    }

    return Boolean(state.sheetPresentation)
}

/**
 * Which input should receive focus after open.
 *
 * @param {{ inlineSearch?: boolean, sheetPresentation?: boolean }} state
 * @returns {'menu' | 'inline'}
 */
export function resolveSelectSearchFocusTarget(state = {}) {
    if (state.sheetPresentation || ! state.inlineSearch) {
        return 'menu'
    }

    return 'inline'
}

/**
 * Logical end caret (`selectionStart = value.length`) is correct for LTR and RTL
 * (Hebrew/Arabic): typing continues after the last character in string order.
 *
 * @param {string | null | undefined} value
 * @returns {number}
 */
export function resolveInlineSearchCaretEndIndex(value) {
    return String(value ?? '').length
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
 * Place the text caret after the last character (typing continues at the end).
 */
export function positionInlineSearchCaretAtEnd(input) {
    if (! input) {
        return
    }

    // Explicit end index documents RTL-safe behavior (logical end, not visual).
    const end = resolveInlineSearchCaretEndIndex(input.value)

    if (typeof input.setSelectionRange === 'function' && input.type !== 'number') {
        if (end === 0) {
            return
        }

        input.setSelectionRange(end, end)

        return
    }

    setInputCaretToEnd(input)
}

/**
 * Focus / Alpine value rebind can reset the caret — schedule until it sticks at end.
 */
export function scheduleInlineSearchCaretAtEnd(input) {
    return scheduleInputCaretToEnd(input)
}
