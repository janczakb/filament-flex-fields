import assert from 'node:assert/strict'
import test from 'node:test'

import {
    advanceMatrixCellTab,
    clampMatrixCell,
    createMatrixKeyboardController,
    matrixCellToIndex,
    matrixIndexToCell,
    moveMatrixCell,
    resolveMatrixKeyAction,
} from '../../resources/js/core/matrix-keyboard.js'

test('clampMatrixCell keeps focus inside grid bounds', () => {
    assert.deepEqual(clampMatrixCell(-1, 9, 3, 4), { row: 0, col: 3 })
    assert.deepEqual(clampMatrixCell(9, -2, 3, 4), { row: 2, col: 0 })
})

test('matrix index helpers round-trip row and column coordinates', () => {
    assert.equal(matrixCellToIndex(1, 2, 4), 6)
    assert.deepEqual(matrixIndexToCell(6, 4), { row: 1, col: 2 })
})

test('moveMatrixCell navigates with arrow keys', () => {
    assert.deepEqual(moveMatrixCell(1, 1, 'ArrowUp', 3, 3), { row: 0, col: 1 })
    assert.deepEqual(moveMatrixCell(1, 1, 'ArrowRight', 3, 3), { row: 1, col: 2 })
    assert.deepEqual(moveMatrixCell(1, 1, 'Home', 3, 3), { row: 1, col: 0 })
    assert.deepEqual(moveMatrixCell(1, 1, 'End', 3, 3), { row: 1, col: 2 })
})

test('advanceMatrixCellTab wraps across rows', () => {
    assert.deepEqual(advanceMatrixCellTab(0, 1, 2, 2, false), { row: 1, col: 0 })
    assert.deepEqual(advanceMatrixCellTab(0, 0, 2, 2, true), { row: 1, col: 1 })
})

test('resolveMatrixKeyAction maps tab and shift-space actions', () => {
    assert.deepEqual(resolveMatrixKeyAction({ key: 'Tab' }, { row: 0, col: 0 }, 2, 2), {
        type: 'navigate',
        focus: { row: 0, col: 1 },
    })

    assert.deepEqual(resolveMatrixKeyAction({ key: ' ', shiftKey: true }, { row: 1, col: 0 }, 2, 2), {
        type: 'toggle',
        focus: { row: 1, col: 0 },
    })
})

test('createMatrixKeyboardController navigates and toggles via callbacks', () => {
    const navigated = []
    const toggled = []

    const controller = createMatrixKeyboardController({
        rows: 2,
        cols: 2,
        onNavigate: (focus) => navigated.push({ ...focus }),
        onToggle: (focus) => toggled.push({ ...focus }),
        initialFocus: { row: 0, col: 0 },
    })

    controller.handleKeydown({ key: 'ArrowRight', preventDefault() {} })
    controller.handleKeydown({ key: ' ', shiftKey: true, preventDefault() {} })

    assert.deepEqual(navigated, [{ row: 0, col: 1 }])
    assert.deepEqual(toggled, [{ row: 0, col: 1 }])
    assert.deepEqual(controller.getFocus(), { row: 0, col: 1 })
})
