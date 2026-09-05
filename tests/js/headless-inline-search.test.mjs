import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    positionInlineSearchCaretAtEnd,
    resolveInlineSearchCaretEndIndex,
    resolveInlineSearchInputAfterClose,
    resolveInlineSearchInputPlaceholder,
    resolveInlineSearchInputValue,
    resolveSelectSearchFocusTarget,
    shouldInlineSearchInputBeEditable,
    shouldShowSelectMenuSearch,
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

    it('positions the caret at the end of the value', () => {
        let selectionStart = null
        let selectionEnd = null

        positionInlineSearchCaretAtEnd({
            type: 'text',
            value: 'Published',
            setSelectionRange(start, end) {
                selectionStart = start
                selectionEnd = end
            },
        })

        assert.equal(selectionStart, 9)
        assert.equal(selectionEnd, 9)
    })

    it('places the caret at the logical end for Hebrew RTL values', () => {
        const hebrew = 'תל אביב'
        assert.equal(resolveInlineSearchCaretEndIndex(hebrew), hebrew.length)

        let selectionStart = null
        let selectionEnd = null

        positionInlineSearchCaretAtEnd({
            type: 'search',
            value: hebrew,
            setSelectionRange(start, end) {
                selectionStart = start
                selectionEnd = end
            },
        })

        assert.equal(selectionStart, hebrew.length)
        assert.equal(selectionEnd, hebrew.length)
    })

    it('shows menu search for inlineSearch only in sheet presentation', () => {
        assert.equal(
            shouldShowSelectMenuSearch({ searchable: true, inlineSearch: true, sheetPresentation: false }),
            false,
        )
        assert.equal(
            shouldShowSelectMenuSearch({ searchable: true, inlineSearch: true, sheetPresentation: true }),
            true,
        )
        assert.equal(
            shouldShowSelectMenuSearch({ searchable: true, inlineSearch: false, sheetPresentation: false }),
            true,
        )
    })

    it('focuses the sheet search input for inlineSearch on mobile', () => {
        assert.equal(
            resolveSelectSearchFocusTarget({ inlineSearch: true, sheetPresentation: true }),
            'menu',
        )
        assert.equal(
            resolveSelectSearchFocusTarget({ inlineSearch: true, sheetPresentation: false }),
            'inline',
        )
        assert.equal(
            resolveSelectSearchFocusTarget({ inlineSearch: false, sheetPresentation: false }),
            'menu',
        )
    })
})
