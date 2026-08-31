import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    positionInlineSearchCaretAtInlineStart,
    resolveInlineSearchInputAfterClose,
    resolveInlineSearchInputPlaceholder,
    resolveInlineSearchInputValue,
    shouldInlineSearchInputBeEditable,
    stripHtmlToPlainText,
} from '../../resources/js/components/select-field/headless-inline-search.js'

describe('headless-inline-search', () => {
    it('mirrors the combobox query in the trigger input', () => {
        assert.equal(
            resolveInlineSearchInputValue({ comboboxQuery: 'Tailwind CSS' }),
            'Tailwind CSS',
        )
    })

    it('keeps the selected label in the input when the menu opens', () => {
        assert.equal(
            resolveInlineSearchInputValue({ comboboxQuery: 'Tailwind CSS' }),
            'Tailwind CSS',
        )

        assert.equal(
            resolveInlineSearchInputPlaceholder({
                comboboxQuery: 'Tailwind CSS',
                searchPrompt: 'Start typing to search..',
            }),
            '',
        )
    })

    it('shows the search prompt only when the input is empty', () => {
        assert.equal(
            resolveInlineSearchInputPlaceholder({
                comboboxQuery: '',
                searchPrompt: 'Search animals...',
            }),
            'Search animals...',
        )
    })

    it('restores the selected label after close', () => {
        assert.equal(
            resolveInlineSearchInputAfterClose(true, 'Tailwind CSS'),
            'Tailwind CSS',
        )
        assert.equal(resolveInlineSearchInputAfterClose(false, 'Tailwind CSS'), '')
    })

    it('treats focus as an editable search state', () => {
        assert.equal(shouldInlineSearchInputBeEditable(false, true), true)
    })

    it('strips html labels for closed trigger text', () => {
        assert.equal(stripHtmlToPlainText('<span>Tailwind <strong>CSS</strong></span>'), 'Tailwind CSS')
    })

    it('positions the caret at inline-start', () => {
        let selectionStart = null
        let selectionEnd = null

        positionInlineSearchCaretAtInlineStart({
            setSelectionRange(start, end) {
                selectionStart = start
                selectionEnd = end
            },
        })

        assert.equal(selectionStart, 0)
        assert.equal(selectionEnd, 0)
    })
})
