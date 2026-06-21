import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    getVisibleToolbarTools,
    setupToolbarKeyboardNavigation,
} from '../../resources/js/core/rich-editor-toolbar-a11y.js'
import {
    countCharacters,
    countWords,
    shouldEnableRichEditorChromeSync,
    shouldTrackRichEditorFooterStats,
} from '../../resources/js/core/rich-editor-chrome.js'

describe('rich-editor toolbar a11y', () => {
    it('returns no toolbar tools when root is missing', () => {
        assert.deepEqual(getVisibleToolbarTools(null), [])
    })

    it('ignores hidden toolbar tools', () => {
        const toolbar = {
            querySelectorAll: () => [
                { className: 'fi-fo-rich-editor-tool', offsetParent: null },
                { className: 'fi-fo-rich-editor-tool', offsetParent: {} },
            ],
        }

        assert.equal(getVisibleToolbarTools(toolbar).length, 1)
    })

    it('initializes roving tabindex without focusing the first tool on mount', () => {
        let focusCount = 0

        const first = {
            className: 'fi-fo-rich-editor-tool',
            offsetParent: {},
            tabIndex: -1,
            focus() {
                focusCount += 1
            },
        }

        const second = {
            className: 'fi-fo-rich-editor-tool',
            offsetParent: {},
            tabIndex: -1,
            focus() {
                focusCount += 1
            },
        }

        const toolbar = {
            querySelectorAll: () => [first, second],
            addEventListener: () => {},
            removeEventListener: () => {},
        }

        const teardown = setupToolbarKeyboardNavigation(toolbar)

        assert.equal(first.tabIndex, 0)
        assert.equal(second.tabIndex, -1)
        assert.equal(focusCount, 0)

        teardown()
    })
})

describe('rich-editor chrome performance guards', () => {
    it('counts twenty thousand words within a tight budget', () => {
        const words = Array.from({ length: 20_000 }, (_, index) => `word${index}`).join(' ')
        const started = performance.now()

        assert.equal(countWords(words), 20_000)
        assert.equal(countCharacters(words), words.length)

        const elapsed = performance.now() - started

        assert.ok(elapsed < 250, `expected <250ms, got ${elapsed.toFixed(2)}ms`)
    })

    it('skips footer stats tracking when no footer metrics are enabled', () => {
        assert.equal(shouldTrackRichEditorFooterStats({
            showWordCount: false,
            readingTime: false,
            minCharacters: null,
            maxCharacters: null,
            maxWords: null,
            altTextRequired: false,
            limitBehavior: 'soft',
        }), false)

        assert.equal(shouldTrackRichEditorFooterStats({
            showWordCount: true,
            readingTime: false,
            minCharacters: null,
            maxCharacters: null,
            maxWords: null,
            altTextRequired: false,
            limitBehavior: 'soft',
        }), true)

        assert.equal(shouldEnableRichEditorChromeSync({
            autosave: false,
            showWordCount: false,
            readingTime: false,
            minCharacters: null,
            maxCharacters: null,
            maxWords: null,
            altTextRequired: false,
            limitBehavior: 'soft',
        }), false)
    })
})
