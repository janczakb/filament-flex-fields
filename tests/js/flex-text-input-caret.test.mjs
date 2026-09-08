import assert from 'node:assert/strict'
import { describe, it } from 'node:test'

import {
    canSetInputSelection,
    scheduleInputCaretToEnd,
    setInputCaretToEnd,
} from '../../resources/js/core/flex-text-input-caret.js'

describe('flex-text-input-caret', () => {
    it('allows selection only on types that support setSelectionRange', () => {
        assert.equal(canSetInputSelection({ type: 'text', setSelectionRange() {} }), true)
        assert.equal(canSetInputSelection({ type: 'tel', setSelectionRange() {} }), true)
        assert.equal(canSetInputSelection({ type: 'email', setSelectionRange() {} }), false)
        assert.equal(canSetInputSelection({ type: 'number', setSelectionRange() {} }), false)
        assert.equal(canSetInputSelection(null), false)
    })

    it('moves the caret to the end of the current value', () => {
        let selectionStart = null
        let selectionEnd = null

        setInputCaretToEnd({
            type: 'text',
            value: 'hi@example.com',
            setSelectionRange(start, end) {
                selectionStart = start
                selectionEnd = end
            },
        })

        assert.equal(selectionStart, 14)
        assert.equal(selectionEnd, 14)
    })

    it('skips caret moves on email inputs that throw InvalidStateError', () => {
        assert.equal(
            setInputCaretToEnd({
                type: 'email',
                value: 'hi@example.com',
                setSelectionRange() {
                    throw new Error('InvalidStateError')
                },
            }),
            false,
        )
    })

    it('does not reposition the caret after focus is lost', async () => {
        const body = {}
        let selectionStart = null
        let selectionEnd = null
        let active = true

        const input = {
            type: 'text',
            value: 'hi@example.com',
            setSelectionRange(start, end) {
                selectionStart = start
                selectionEnd = end
            },
        }

        globalThis.document = {
            body,
            get activeElement() {
                return active ? input : body
            },
        }

        scheduleInputCaretToEnd(input)

        assert.equal(selectionStart, 14)
        assert.equal(selectionEnd, 14)

        active = false
        selectionStart = 0
        selectionEnd = 0

        await new Promise((resolve) => setTimeout(resolve, 60))

        assert.equal(selectionStart, 0)
        assert.equal(selectionEnd, 0)

        delete globalThis.document
    })
})
