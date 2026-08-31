import {
    applyTypeDigit,
    applyTypeDigitAtCursor,
    buildDisplayItems,
    buildDisplaySegments,
    cursorPosFromClientX,
    deleteBeforeCursor,
    editStateFromMinor,
    getLocaleSeparators,
    getLogicalLength,
    getNavigableCursorPositions,
    groupWholeDigits,
    minorFromEditState,
    moveCursorByDelta,
    normalizeEditForBlur,
    snapCursor,
} from '../../resources/js/components/currency-field.js'
import { describe, it } from 'node:test'
import assert from 'node:assert/strict'

function emptyEdit() {
    return { wholeDigits: '', fracDigits: '', inDecimal: false, negative: false }
}

function typeSequence(keys, { decimals = 2, locale = 'pl_PL', start = emptyEdit() } = {}) {
    let edit = { ...start }
    let cursorPos = getLogicalLength(edit, decimals)
    const caretKeys = []

    for (const key of keys) {
        if (key === ',' || key === '.' ) {
            edit = { ...edit, inDecimal: true }
            cursorPos = snapCursor(edit.wholeDigits.length + 1, edit, decimals)
        } else if (key === 'Backspace') {
            const result = deleteBeforeCursor(edit, cursorPos, decimals)
            edit = result.edit
            cursorPos = result.cursorPos
        } else {
            const result = applyTypeDigitAtCursor(edit, key, cursorPos, decimals)
            edit = result.edit
            cursorPos = result.cursorPos
        }

        const segments = buildDisplaySegments(edit, { decimals, locale }, { showGhost: false })
        const items = buildDisplayItems(segments, cursorPos, decimals, edit)
        const carets = items.filter((item) => item.type === 'caret')

        assert.equal(carets.length, 1, `expected exactly one caret after key ${key}`)
        caretKeys.push(carets[0].key)
        assert.equal(
            snapCursor(cursorPos, edit, decimals),
            cursorPos,
            `cursor must stay on a navigable slot after ${key}`,
        )
    }

    return { edit, cursorPos, caretKeys }
}

describe('currency-field edit engine', () => {
    it('maps minor units to edit state and back for pl_PL money', () => {
        const edit = editStateFromMinor(6_666_660, 2)

        assert.deepEqual(edit, {
            wholeDigits: '66666',
            fracDigits: '6',
            inDecimal: false,
            negative: false,
        })
        assert.equal(minorFromEditState({ ...edit, decimals: 2 }), 6_666_660)
    })

    it('uses locale decimal and group separators', () => {
        const pl = getLocaleSeparators('pl_PL')
        const us = getLocaleSeparators('en_US')

        assert.equal(pl.decimal, ',')
        assert.equal(us.decimal, '.')
        assert.equal(groupWholeDigits('66666', pl.group), `66${pl.group}666`)
    })

    it('types whole digits then comma fraction without caret slot thrashing', () => {
        const { edit, cursorPos, caretKeys } = typeSequence(['1', '2', '3', ',', '4', '5'], {
            decimals: 2,
            locale: 'pl_PL',
        })

        assert.deepEqual(edit, {
            wholeDigits: '123',
            fracDigits: '45',
            inDecimal: true,
            negative: false,
        })
        assert.equal(cursorPos, 6)
        assert.equal(caretKeys.at(-1), 'caret-after-f-2')
        assert.equal(minorFromEditState({ ...edit, decimals: 2 }), 12345)
    })

    it('accepts both comma and period as decimal starters', () => {
        for (const sep of [',', '.']) {
            const { edit } = typeSequence(['9', '9', sep, '5'], { decimals: 2, locale: 'pl_PL' })

            assert.equal(edit.wholeDigits, '99')
            assert.equal(edit.fracDigits, '5')
            assert.equal(edit.inDecimal, true)
        }
    })

    it('keeps a single caret while crossing the thousand-separator boundary', () => {
        const { edit, caretKeys } = typeSequence(['9', '9', '9', '9'], {
            decimals: 2,
            locale: 'pl_PL',
        })

        assert.equal(edit.wholeDigits, '9999')
        assert.equal(caretKeys.length, 4)
        assert.ok(caretKeys.every((key) => typeof key === 'string' && key.startsWith('caret-')))

        const segments = buildDisplaySegments(edit, { decimals: 2, locale: 'pl_PL' })
        const groupSep = getLocaleSeparators('pl_PL').group

        assert.ok(segments.some((segment) => segment.type === 'separator' && segment.char === groupSep))
        assert.equal(
            segments.filter((segment) => segment.type === 'digit' && ! segment.ghost).length,
            4,
        )
    })

    it('does not put navigable cursor on group separators', () => {
        const edit = { wholeDigits: '1234', fracDigits: '', inDecimal: false, negative: false }
        const positions = getNavigableCursorPositions(edit, 2)

        assert.deepEqual(positions, [0, 1, 2, 3, 4])
    })

    it('moves caret left/right across decimal without landing on invalid slots', () => {
        const edit = { wholeDigits: '12', fracDigits: '34', inDecimal: true, negative: false }
        let cursor = getLogicalLength(edit, 2)

        cursor = moveCursorByDelta(cursor, -1, edit, 2)
        assert.equal(cursor, 4)
        cursor = moveCursorByDelta(cursor, -1, edit, 2)
        assert.equal(cursor, 3)
        cursor = moveCursorByDelta(cursor, -1, edit, 2)
        assert.equal(cursor, 2)
        cursor = moveCursorByDelta(cursor, -1, edit, 2)
        assert.equal(cursor, 1)
    })

    it('backspaces fraction then decimal then whole predictably', () => {
        let edit = { wholeDigits: '12', fracDigits: '3', inDecimal: true, negative: false }
        let cursor = getLogicalLength(edit, 2)

        ;({ edit, cursorPos: cursor } = deleteBeforeCursor(edit, cursor, 2))
        assert.deepEqual(edit.fracDigits, '')
        assert.equal(edit.inDecimal, false)

        ;({ edit, cursorPos: cursor } = deleteBeforeCursor(edit, cursor, 2))
        assert.equal(edit.wholeDigits, '1')
    })

    it('replaces leading zero when typing the first whole digit', () => {
        const result = applyTypeDigitAtCursor(
            { wholeDigits: '0', fracDigits: '', inDecimal: false, negative: false },
            '7',
            1,
            2,
        )

        assert.equal(result.edit.wholeDigits, '7')
        assert.equal(result.cursorPos, 1)
    })

    it('clamps cursor after leading-zero strip mid-insert', () => {
        const result = applyTypeDigitAtCursor(
            { wholeDigits: '00', fracDigits: '', inDecimal: false, negative: false },
            '5',
            1,
            2,
        )

        assert.equal(result.edit.wholeDigits, '50')
        assert.ok(result.cursorPos <= result.edit.wholeDigits.length)
        assert.equal(snapCursor(result.cursorPos, result.edit, 2), result.cursorPos)
    })

    it('caps fraction digits at currency decimals', () => {
        const first = applyTypeDigitAtCursor(
            { wholeDigits: '1', fracDigits: '', inDecimal: true, negative: false },
            '2',
            2,
            2,
        )
        const second = applyTypeDigitAtCursor(first.edit, '3', first.cursorPos, 2)
        const third = applyTypeDigitAtCursor(second.edit, '4', second.cursorPos, 2)

        assert.equal(second.edit.fracDigits, '23')
        assert.equal(third.edit.fracDigits, '23')
        assert.equal(third.cursorPos, second.cursorPos)
    })

    it('append-type helper matches end-of-field typing', () => {
        let edit = emptyEdit()

        edit = applyTypeDigit(edit, '1', 2)
        edit = applyTypeDigit(edit, '0', 2)
        edit = applyTypeDigit(edit, '0', 2)

        assert.equal(edit.wholeDigits, '100')
        assert.equal(minorFromEditState({ ...edit, decimals: 2 }), 10000)
    })

    it('builds empty caret when focused-empty equivalent state is empty', () => {
        const edit = emptyEdit()
        const items = buildDisplayItems([], 0, 2, edit)

        assert.deepEqual(items, [{ type: 'caret', key: 'caret-empty', cursorPos: 0 }])
    })

    it('keeps exactly one caret for mid-number edits', () => {
        const edit = { wholeDigits: '1234', fracDigits: '', inDecimal: false, negative: false }
        const segments = buildDisplaySegments(edit, { decimals: 2, locale: 'pl_PL' })

        for (const cursorPos of getNavigableCursorPositions(edit, 2)) {
            const carets = buildDisplayItems(segments, cursorPos, 2, edit)
                .filter((item) => item.type === 'caret')

            assert.equal(carets.length, 1, `cursor ${cursorPos}`)
        }
    })

    it('places caret from click x near digit edges and ignores zero-size traps', () => {
        const edit = { wholeDigits: '123', fracDigits: '', inDecimal: false, negative: false }
        const live = {
            querySelectorAll() {
                return [
                    {
                        hasAttribute: (name) => name === 'data-fff-cursor-before' || name === 'data-fff-cursor-after',
                        getAttribute: (name) => (name === 'data-fff-cursor-before' ? '0' : '1'),
                        getBoundingClientRect: () => ({ left: 10, right: 20, width: 10, height: 12 }),
                    },
                    {
                        hasAttribute: (name) => name === 'data-fff-cursor-before' || name === 'data-fff-cursor-after',
                        getAttribute: (name) => (name === 'data-fff-cursor-before' ? '1' : '2'),
                        getBoundingClientRect: () => ({ left: 20, right: 30, width: 10, height: 12 }),
                    },
                    {
                        hasAttribute: (name) => name === 'data-fff-cursor-before' || name === 'data-fff-cursor-after',
                        getAttribute: (name) => (name === 'data-fff-cursor-before' ? '2' : '3'),
                        getBoundingClientRect: () => ({ left: 30, right: 40, width: 10, height: 12 }),
                    },
                    {
                        hasAttribute: () => true,
                        getAttribute: () => 'null',
                        getBoundingClientRect: () => ({ left: 0, right: 0, width: 0, height: 0 }),
                    },
                ]
            },
        }

        assert.equal(cursorPosFromClientX(11, edit, 2, live), 0)
        assert.equal(cursorPosFromClientX(21, edit, 2, live), 1)
        assert.equal(cursorPosFromClientX(39, edit, 2, live), 3)
    })

    it('does not visually pad fraction on blur while storage still pads minors', () => {
        const edit = { wholeDigits: '12', fracDigits: '5', inDecimal: true, negative: false }
        const blurred = normalizeEditForBlur(edit)

        assert.deepEqual(blurred, {
            wholeDigits: '12',
            fracDigits: '5',
            inDecimal: false,
            negative: false,
        })
        assert.equal(minorFromEditState({ ...blurred, decimals: 2 }), 1250)

        const paddedLook = normalizeEditForBlur({
            wholeDigits: '12',
            fracDigits: '50',
            inDecimal: true,
            negative: false,
        })

        assert.equal(paddedLook.fracDigits, '5')
        assert.equal(minorFromEditState({ ...paddedLook, decimals: 2 }), 1250)
    })
})
