export function clampMatrixCell(row, col, rows, cols) {
    const safeRows = Math.max(1, Number(rows) || 1)
    const safeCols = Math.max(1, Number(cols) || 1)

    return {
        row: Math.max(0, Math.min(Number(row) || 0, safeRows - 1)),
        col: Math.max(0, Math.min(Number(col) || 0, safeCols - 1)),
    }
}

export function matrixCellToIndex(row, col, cols) {
    const safeCols = Math.max(1, Number(cols) || 1)

    return (Number(row) || 0) * safeCols + (Number(col) || 0)
}

export function matrixIndexToCell(index, cols) {
    const safeCols = Math.max(1, Number(cols) || 1)
    const safeIndex = Math.max(0, Number(index) || 0)

    return {
        row: Math.floor(safeIndex / safeCols),
        col: safeIndex % safeCols,
    }
}

export function moveMatrixCell(row, col, key, rows, cols) {
    const focus = clampMatrixCell(row, col, rows, cols)

    switch (key) {
        case 'ArrowUp':
            return clampMatrixCell(focus.row - 1, focus.col, rows, cols)
        case 'ArrowDown':
            return clampMatrixCell(focus.row + 1, focus.col, rows, cols)
        case 'ArrowLeft':
            return clampMatrixCell(focus.row, focus.col - 1, rows, cols)
        case 'ArrowRight':
            return clampMatrixCell(focus.row, focus.col + 1, rows, cols)
        case 'Home':
            return clampMatrixCell(focus.row, 0, rows, cols)
        case 'End':
            return clampMatrixCell(focus.row, Math.max(0, (Number(cols) || 1) - 1), rows, cols)
        default:
            return focus
    }
}

export function advanceMatrixCellTab(row, col, rows, cols, reverse = false) {
    const safeRows = Math.max(1, Number(rows) || 1)
    const safeCols = Math.max(1, Number(cols) || 1)
    const totalCells = safeRows * safeCols
    const currentIndex = matrixCellToIndex(row, col, safeCols)
    const nextIndex = reverse
        ? (currentIndex - 1 + totalCells) % totalCells
        : (currentIndex + 1) % totalCells

    const nextCell = matrixIndexToCell(nextIndex, safeCols)

    return clampMatrixCell(nextCell.row, nextCell.col, safeRows, safeCols)
}

export function resolveMatrixKeyAction(event, focus, rows, cols) {
    const key = event?.key

    if (! key) {
        return null
    }

    if (key === 'Tab') {
        return {
            type: 'navigate',
            focus: advanceMatrixCellTab(focus.row, focus.col, rows, cols, Boolean(event.shiftKey)),
        }
    }

    if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(key)) {
        return {
            type: 'navigate',
            focus: moveMatrixCell(focus.row, focus.col, key, rows, cols),
        }
    }

    if (key === ' ' && event.shiftKey) {
        return {
            type: 'toggle',
            focus,
        }
    }

    return null
}

export function createMatrixKeyboardController({
    rows,
    cols,
    onNavigate = null,
    onToggle = null,
    initialFocus = { row: 0, col: 0 },
} = {}) {
    let focus = clampMatrixCell(initialFocus.row, initialFocus.col, rows, cols)

    const notifyNavigate = (nextFocus) => {
        focus = nextFocus

        if (typeof onNavigate === 'function') {
            onNavigate({ ...focus })
        }
    }

    return {
        getFocus() {
            return { ...focus }
        },

        setFocus(nextFocus) {
            notifyNavigate(clampMatrixCell(nextFocus.row, nextFocus.col, rows, cols))
        },

        handleKeydown(event) {
            const action = resolveMatrixKeyAction(event, focus, rows, cols)

            if (! action) {
                return false
            }

            if (action.type === 'navigate') {
                event.preventDefault?.()
                notifyNavigate(action.focus)

                return true
            }

            if (action.type === 'toggle') {
                event.preventDefault?.()

                if (typeof onToggle === 'function') {
                    onToggle({ ...focus })
                }

                return true
            }

            return false
        },
    }
}
