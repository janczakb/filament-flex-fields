import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    buildRichEditorFooterMetrics,
    countCharacters,
    countTextMetrics,
    countWords,
    createDebouncedScheduler,
    createRafScheduler,
    isEmptyRichEditorState,
    resolveLimitState,
    shouldEnableRichEditorChromeSync,
    shouldTrackRichEditorFooterStats,
} from '../../resources/js/core/rich-editor-chrome.js'

describe('rich-editor chrome helpers', () => {
    it('counts unicode characters without allocating code point arrays', () => {
        assert.equal(countCharacters('café'), 4)
        assert.equal(countCharacters('🙂✨'), 2)
    })

    it('counts words from trimmed text', () => {
        assert.equal(countWords('  one   two  '), 2)
        assert.equal(countWords(''), 0)
    })

    it('detects empty rich editor state shapes', () => {
        assert.equal(isEmptyRichEditorState(null), true)
        assert.equal(isEmptyRichEditorState({ content: [] }), true)
        assert.equal(isEmptyRichEditorState({ content: [{ type: 'paragraph' }] }), false)
    })

    it('resolves limit status for max character thresholds', () => {
        assert.deepEqual(resolveLimitState({
            characters: 95,
            words: 10,
            minCharacters: null,
            maxCharacters: 100,
            maxWords: null,
        }), { ratio: 0.95, status: 'warning' })
    })

    it('counts text metrics in a single pass', () => {
        assert.deepEqual(countTextMetrics('  one two  '), {
            characters: 11,
            words: 2,
        })
        assert.deepEqual(countTextMetrics('café 🙂'), {
            characters: 6,
            words: 2,
        })
    })

    it('builds footer metrics from plain text', () => {
        const metrics = buildRichEditorFooterMetrics('one two', {
            showWordCount: true,
            readingTime: false,
            minCharacters: null,
            maxCharacters: 100,
            maxWords: null,
            wordsPerMinute: 200,
            labels: {
                line: '__CHARACTERS__ / __WORDS__',
            },
        })

        assert.equal(metrics.characters, 7)
        assert.equal(metrics.words, 2)
        assert.equal(metrics.footerStats, '7 / 2')
        assert.equal(metrics.footerLimitStatus, 'ok')
    })

    it('enables chrome sync only when footer metrics or autosave are active', () => {
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

        assert.equal(shouldEnableRichEditorChromeSync({
            autosave: true,
            showWordCount: false,
            readingTime: false,
            minCharacters: null,
            maxCharacters: null,
            maxWords: null,
            altTextRequired: false,
            limitBehavior: 'soft',
        }), true)
    })

    it('debounces callbacks into a single delayed execution', () => {
        let calls = 0
        const scheduler = createDebouncedScheduler(() => {
            calls += 1
        }, 50)

        scheduler.schedule()
        scheduler.schedule()
        scheduler.schedule()

        assert.equal(calls, 0)

        return new Promise((resolve) => {
            setTimeout(() => {
                assert.equal(calls, 1)
                scheduler.cancel()
                resolve()
            }, 80)
        })
    })

    it('coalesces RAF scheduler callbacks into a single frame', () => {
        let calls = 0
        const frames = []
        const originalRaf = globalThis.requestAnimationFrame
        const originalCancel = globalThis.cancelAnimationFrame

        globalThis.requestAnimationFrame = (callback) => {
            const id = frames.length + 1
            frames.push(callback)

            return id
        }

        globalThis.cancelAnimationFrame = () => {}

        try {
            const scheduler = createRafScheduler(() => {
                calls += 1
            })

            scheduler.schedule()
            scheduler.schedule()
            scheduler.schedule()

            assert.equal(calls, 0)
            assert.equal(frames.length, 1)

            frames[0]()

            assert.equal(calls, 1)
        } finally {
            globalThis.requestAnimationFrame = originalRaf
            globalThis.cancelAnimationFrame = originalCancel
        }
    })
})
